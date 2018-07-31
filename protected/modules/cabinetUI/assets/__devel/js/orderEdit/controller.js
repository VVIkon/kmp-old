(function(global,factory) {

    KT.crates.OrderEdit.controller = factory(KT.crates.OrderEdit);

}(this,function(crate) {
  /**
  * Редактирование заявки
  * @constructor
  * @param {Object} module - ссылка на модуль
  */
  var oeController = function(module) {
    /** Module storage - модуль со всеми его компонентами */
    this.mds = module;

    window.sessionStorage.removeItem('inOrderInfo');

    /** Получение номера запрашиваемой заявки из ссылки */
    try {
      this.orderId = window.location.pathname.match(/[^/]+$/)[0];
      if (this.orderId !== 'new') {
        this.orderId = +this.orderId;
        if (isNaN(this.orderId) || this.orderId <= 0) {
          this.orderId = null;
        }
      }
    } catch (e) {
      this.orderId = null;
    }

    this.mds.view = new crate.view(this.mds, this.orderId);
    this.mds.OrderStorage = new KT.storage.OrderStorage(this.orderId);

    if (this.orderId === 'new') {
      this.mds.view.setActiveTab('tourists');
      this.mds.tourists.controller = new crate.tourists.controller(this.mds, this.orderId);
      this.mds.view.$addServiceBlock.find('.iconed-link[data-srv]').addClass('disabled');
      
    } else if (this.orderId !== null) {
      this.mds.view.enableTab('tourists');
      this.mds.view.enableTab('payment');
      this.mds.view.setActiveTab('services');
      
      // список с регламентом доступа
      this.mds.accessList = {
        'sendReport': false,
        'sendDocument': false
      };

      this.mds.payment.controller = new crate.payment.controller(this.mds, this.orderId);
      this.mds.tourists.controller = new crate.tourists.controller(this.mds, this.orderId);
      this.mds.services.controller = new crate.services.controller(this.mds, this.orderId);
    }
  };

  /** Инициализация событий */
  oeController.prototype.init = function() {
    var _instance = this;
    var modView = _instance.mds.view;

    /** Обработка смены валюты просмотра */
    KT.on('KT.changedViewCurrency', function() {
      if (_instance.orderId !== 'new') {
        modView.clearTopInfo();
        KT.apiClient.getOrderInfo(_instance.orderId)
          .done(function(response) {
            if (response.status !== 0) {
              window.location.assign(KT.appEntries.order + _instance.orderId);
            } else {
              _instance.mds.OrderStorage.initialize(response.body);
            }
          });
      }
    });

    /** Обработка изменения значения переключателя нетто/брутто */
    KT.on('KT.changedViewPrice',function() {
      if (_instance.orderId !== 'new') {
        modView.clearTopInfo();
        KT.apiClient.getOrderInfo(_instance.orderId)
          .done(function(response) {
            if (response.status !== 0) {
              window.location.assign(KT.appEntries.order + _instance.orderId);
            } else {
              _instance.mds.OrderStorage.initialize(response.body);
            }
          });
      }
    });

    /** Обработка запроса на переход по вкладке */
    KT.on('OrderEdit.setActiveTab', function(e, data) {
      if (data.activeTab !== undefined) {
        modView.setActiveTab(data.activeTab);
        if (typeof data.callback === 'function') {
          data.callback();
        }
      }
    });

    /** Обработка запроса на перезагрузку данных */
    KT.on('OrderEdit.reloadInfo', function() {
      modView.clearTopInfo();
      _instance.mds.OrderStorage.loadStates.servicedata = 'pending';
      _instance.mds.OrderStorage.loadStates.transitionsdata = 'pending';
      console.warn('reload catched');

      KT.apiClient.getOrderInfo(_instance.orderId)
        .then(function(orderInfo) {
          if (orderInfo.status === 0) {
            _instance.mds.OrderStorage.initialize(orderInfo.body);
            var serviceIds = _instance.mds.OrderStorage.getServiceIds();
            return KT.apiClient.getOrderOffers(_instance.orderId, serviceIds);
          } else {
            return $.Deferred().reject();
          }
        })
        .then(function(offersData) {
          if (offersData.status === 0) {
            _instance.mds.OrderStorage.setServices(offersData.body);
            return KT.apiClient.getAllowedTransitions(_instance.orderId);
          } else {
            return $.Deferred().reject();
          }
        })
        .then(function(transitionsData) {
          if (transitionsData.status === 0 ) {
            if (Array.isArray(transitionsData.body.services)) {
              _instance.mds.OrderStorage.setAllowedTransitions(transitionsData.body.services);
            } else {
              _instance.mds.OrderStorage.setAllowedTransitions([]);
            }
          } else {
            console.error('не удалось получить доступные действия');
          }
        });

      KT.apiClient.getOrderHistory(_instance.orderId)
        .done(function(response) {
          if (response.status !== 0) {
            if (response.errorCode === 8) {
              console.log('Для данной заявки нет истории');
            } else {
              KT.notify('loadingHistoryFailed', response.errorCode + ': ' + response.errors);
            }
          } else {
            _instance.mds.OrderStorage.setHistory(response.body);
          }
        });
    });

    /** Обработка запроса на обновление информации в шапке
    * @todo normalize event model
    */
    KT.on('OrderEdit.reloadHeader', function() {
      modView.clearTopInfo();
      modView.renderHeaderInfo(_instance.mds.OrderStorage);
    });

    /** Открытие формы выставления счетов */
    KT.on('OrderEdit.openSetInvoiceForm', function() {
      modView.SetInvoiceForm.open();
    });

    /*==========Обработчики событий модели============================*/

    /** Обработка инициализации хранилища данных заявки */
    KT.on('OrderStorage.initialized', function(e, OrderStorage) {
      modView.renderHeaderInfo(OrderStorage);
    });

    /** Обработка обновления документов заявки */
    KT.on('OrderStorage.setDocuments', function(e, documents) {
      modView.Documents.render(documents.items);
    });

    /** Обработка обновления истории заявки */
    KT.on('OrderStorage.setHistory', function(e, history) {
      modView.History.render(history.records);
    });

    /** Обработка обновления данных по услугам */
    KT.on('OrderStorage.setAllowedTransitions', function(e, OrderStorage) {
      modView.SetInvoiceForm.render(OrderStorage.getServices());
    });

    /** Обработка получения информации о прогрессе загрузки документа */
    KT.on('ApiClient.documentUploadProgress', function(e, data) {
      modView.Documents.showUploadProgress(parseInt(data.percent));
    });

    /** Обработка загрузки документа */
    KT.on('ApiClient.uploadedDocument', function(e, data) {
      if (data.error !== undefined) {
        KT.notify('uploadDocumentFailed');
      } else {
        if (data.status !== 0) {
          if (data.status === 2) {
            if (data.errorCode === 2) {
              KT.notify('uploadDocumentNotAllowedByFilesize');
            } else {
              KT.notify('uploadDocumentFailed', data.errors);
            }
          } else {
            KT.notify('uploadDocumentFailed', data.errors);
          }
        } else {
          KT.notify('documentUploaded');
          modView.Documents.clear();
          KT.apiClient.getOrderDocuments(_instance.orderId)
            .done(function(response) {
              if (response.status !== 0) {
                KT.notify('loadingDocumentsFailed', response.errorCode + ': ' + response.errors);
                _instance.mds.OrderStorage.setDocuments(_instance.mds.OrderStorage.documents);
              } else {
                _instance.mds.OrderStorage.setDocuments(response.body);
              }
            });
        }
      }
    });

    /*==========Обработчики событий представления========================*/

    /** Изменение тогглера
    * @todo move to library
    */
    $('body').on('change','.simpletoggler input', function() {
      if ($(this).prop('checked')) {
        $(this).closest('.simpletoggler').addClass('active');
      } else {
        $(this).closest('.simpletoggler').removeClass('active');
      }
    });
    
    /** Открытие формы выставления счетов по клику на кнопку "Счета" в шапке */
    modView.$headerControls.on('click', '.js-ore-show-invoices', function() {
      KT.dispatch('OrderEdit.openSetInvoiceForm');
    });

    /** Обработка изменения значения в поле ввода "К оплате" блока выставления счетов */
    modView.SetInvoiceForm.elem.$content.on('change', '.js-ore-set-invoice--service-sum', function() {
      var $serviceRow = $(this).closest('.js-ore-set-invoice--service');
      var serviceId = +$serviceRow.attr('data-serviceid');
      var Service = _instance.mds.OrderStorage.services[serviceId];
      modView.SetInvoiceForm.checkInvoiceInput($serviceRow, Service.unpaidSum);
    });

    /** Обработка выбора услуги (чекбокс) на форме выставления счетов */
    modView.SetInvoiceForm.elem.$content.on('change', '.js-ore-set-invoice--service-check', function() {
      var $serviceRow = $(this).closest('.js-ore-set-invoice--service');
      var serviceId = +$serviceRow.attr('data-serviceid');
      var Service = _instance.mds.OrderStorage.services[serviceId];
      if ($(this).prop('checked')) {
        modView.SetInvoiceForm.markServiceForInvoice($serviceRow, Service.unpaidSum);
      } else {
        modView.SetInvoiceForm.markServiceForInvoice($serviceRow, 0);
      }
    });

    /** Обработка нажатия кнопки "Выставить счет" в форме выставления счета */
    modView.SetInvoiceForm.elem.$content.on('click', '.js-ore-set-invoice--action-set', function() {
      var invoices = modView.SetInvoiceForm.getInvoiceFormData();

      if (invoices === false) {
        KT.Modal.notify({
          high: true,
          type: 'info',
          title: 'Выставление счета',
          msg: '<p>Нельзя выставить нулевой счет</p>',
          buttons: {title: 'ok'}
        });
      } else if (invoices.length === 0) {
        KT.Modal.notify({
          high: true,
          type: 'info',
          title: 'Выставление счета',
          msg: 'Вы не выбрали ни одной услуги для выставления счета!',
          buttons: {title:'ok'}
        });
      } else {
        KT.Modal.showLoader();
        KT.apiClient.setInvoice(_instance.mds.OrderStorage, invoices)
          .done(function(response) {
            if (response.status === 0) {
              modView.SetInvoiceForm.clear();

              KT.dispatch('OrderEdit.reloadInfo');

              KT.apiClient.getOrderInvoices(_instance.orderId)
                .done(function(invoiceData) {
                  var invoices = (invoiceData.body.Invoices !== undefined && Array.isArray(invoiceData.body.Invoices)) ?
                      invoiceData.body.Invoices : [];
                  _instance.mds.OrderStorage.setInvoices(invoices);
                });

              KT.Modal.notify({
                high: true,
                type: 'success',
                title: 'Выставление счета',
                msg: 'Счет успешно выставлен!',
                buttons: {type: 'success', title: 'ok'}
              });
            } else {
              KT.Modal.notify({
                high: true,
                type: 'error',
                title: 'Выставление счета',
                msg: 'Не удалось выставить счет<br>Код ошибки: ' + response.errors,
                buttons: {type: 'error', title: 'ok'}
              });
            }
          })
          .fail(function(err) {
            if (err.error !== 'denied') {
              KT.Modal.closeModal();
            }
          });
      }
    });

    /** Обработка нажатия кнопки "Отмена" в форме выставления счета */
    modView.SetInvoiceForm.elem.$content.on('click', '.js-ore-set-invoice--action-cancel', function() {
      modView.SetInvoiceForm.close();
    });

    /** Обработка выбора документа для загрузки */
    modView.Documents.elem.$content.on('change','.js-ore-documents--upload-field', function() {
      var $uploadLabel = modView.Documents.elem.$uploadLabel;
      var file;

      if ($(this)[0].files.length > 0) {
        file = $(this)[0].files[0].name;
      }
      if (file !== undefined) {
        $uploadLabel.text(file);
      } else {
        $uploadLabel.text('Добавить документ');
      }
    });

    /** Обработка нажатия кнопки "отмена" формы загрузки документов */
    modView.Documents.elem.$content.on('click', '.js-ore-documents--upload-decline', function() {
      modView.Documents.cancelDocumentUpload();
    });

    /** Блокировка подтверждения формы загрузки файла при сабмите формы */
    modView.Documents.elem.$content.on('submit', '.js-ore-documents--upload', function(e) {
      e.preventDefault();
    });

    /** Обработка подтвержения формы загрузки файла кликом на кнопку  */
    modView.Documents.elem.$content.on('click', '.js-ore-documents--upload-confirm', function() {
      var $uploadForm = modView.Documents.elem.$uploadForm;
      if ($uploadForm.find('.js-ore-documents--upload-field').val() !== '') {
        $uploadForm.find('.js-ore-documents--upload-control').hide();
        $uploadForm
          .find('.js-ore-documents--upload-progress')
            .find('.js-ore-documents--upload-progress-bar')
              .css({'width':'0%'})
              .addClass('active')
            .end()
          .show();

        KT.apiClient.uploadDocument(_instance.orderId, $uploadForm);
      }
    });

    /** Обработка запроса на отправку документов по почте */
    modView.Documents.elem.$content.on('click', '.js-send-via-email--action-send', function() {
      var sendingData = modView.Documents.getSendingDocumentsData();

      if (sendingData !== null) {
        var email = sendingData.email;
        
        modView.Documents.showDocumentsSending();

        var sendRequests = sendingData.documents.map(function(document) {
          var documentName = document.name;

          return KT.apiClient.sendDocumentToUser({
            'email': email,
            'documentId': document.id
          }).then(
              function(response) {
              if (response.status === 0) {
                KT.notify('documentSent', {'email': email, 'document': documentName});
              } else {
                KT.notify('sendingDocumentFailed', {'document': documentName, 'error': response.errors});
              }

              return response;
            }, function() {
              KT.notify('sendingDocumentFailed', '');
              return {status: 1};
            }
          );
        });

        $.when.apply($, sendRequests).then(function() {
          var aggregateStatus = [].slice.call(arguments).reduce(function(sum, response) {
            return sum + response.status;
          });

          if (aggregateStatus === 0) {
            modView.Documents.renderSendForm();
          } else {
            modView.Documents.renderSendForm(email);
          }
        }).fail(function() {
          modView.Documents.renderSendForm(email);
        });
      }
    });

    /** Обработка запроса на отправку истории заявки по почте */
    modView.History.elem.$content.on('click', '.js-send-via-email--action-send', function() {
      var historyReportData = modView.History.getHistoryReportData();
      
      if (historyReportData !== null) {
        var email = historyReportData.email;

        modView.History.showHistoryReportSending();

        KT.apiClient.sendReportFile(historyReportData)
          .then(function(response) {
            if (response.status === 0) {
              modView.History.renderSendForm();
              KT.notify('historyReportSent', {'email': email});
            } else {
              modView.History.renderSendForm(email);
              KT.notify('sendingHistoryReportFailed', response.errors);
            }
          })
          .fail(function() {
            KT.notify('sendingHistoryReportFailed');
            modView.History.renderSendForm(email);
          });
      }
    });

    /** Обработка нажатия на кнопки добавления услуг */
    modView.$addServiceBlock.on('click', '.iconed-link[data-srv]:not(.disabled)', function() {
      if (!_instance.mds.OrderStorage.isOffline) {
        var serviceType = $(this).attr('data-srv');
        window.sessionStorage.setItem('inOrderInfo', JSON.stringify(_instance.mds.OrderStorage));

        switch(serviceType) {
          case 'flight':
            window.location.assign(KT.appEntries.searchavia);
            break;
          case 'accommodation':
            window.location.assign(KT.appEntries.searchhotel);
            break;
          default:
            window.sessionStorage.removeItem('inOrderInfo');
            break;
        }
      }
    });
  };

  /** Инициализация модуля управления заявкой */
  oeController.prototype.load = function() {
    var _instance = this;

    //==== сбор данных по требуемым шаблонам
    $.extend(_instance.mds.view.config.templates, _instance.mds.payment.view.config.templates);
    $.extend(_instance.mds.view.config.templates, _instance.mds.tourists.view.config.templates);
    $.extend(_instance.mds.view.config.templates, _instance.mds.services.view.config.templates);

    //==== инициализация контроллеров субмодулей
    _instance.mds.payment.controller.init();
    _instance.mds.tourists.controller.init();
    _instance.mds.services.controller.init();

    //==== загрузка шаблонов
    var getTemplates = KT.getTemplates(
        _instance.mds.view.config.templateUrl,
        _instance.mds.view.config.templates
      ).then(function(templates) {
        _instance.mds.tpl = templates;
        _instance.mds.view.renderHeaderControls({
          'invoices': (KT.profile.userType !== 'corp'),
          'documents': true,
          'history': true
        });
      });

    //==== проверка прав
    var checkAccessRequests = [];
    var accessList = _instance.mds.accessList;

    // проверка прав на отправку отчетов
    checkAccessRequests.push(KT.apiClient.checkUserAccess({'permissions': [6]})
      .then(function(r) { 
        if (+r.status === 0) {
          accessList['sendReport'] = r.body.hasAccess;
        } 
      }));

    // проверка прав на отправку документов
    checkAccessRequests.push(KT.apiClient.checkUserAccess({'permissions': [7]})
      .then(function(r) { 
        if (+r.status === 0) {
          accessList['sendDocument'] = r.body.hasAccess;
        } 
      }));

    //==== получение данных компании
    var getOrderInfo = KT.apiClient.getOrderInfo(_instance.orderId);

    //=== загрузка справочников ядра
    var dictionaryRequests = [];

    // статусы услуги
    /** @todo придумать механизм получше... */
    dictionaryRequests.push(KT.Dictionary.getAsMap('serviceStatuses', 'key')
      .then(function(serviceStatusesMap) {
        KT.SERVICE_STATUSES = serviceStatusesMap;
      }));

    //==== инициализация

    var dataRequests = [];
    dataRequests.push(getOrderInfo);
    dataRequests.push(getTemplates);
    [].push.apply(dataRequests, checkAccessRequests);
    [].push.apply(dataRequests, dictionaryRequests);

    $.when.apply($, dataRequests)
      .then(function(orderInfo) {
        if (orderInfo.status !== 0 || orderInfo.body.orderId === undefined) {
          console.error('Заявка ' + _instance.orderId + ' не найдена');
          window.location.assign(KT.appEntries.orderlist);
        }

        _instance.mds.OrderStorage.initialize(orderInfo.body);
        
        var serviceIds = _instance.mds.OrderStorage.getServiceIds();
        if (serviceIds.length > 0) {
          KT.apiClient.getOrderOffers(_instance.orderId, serviceIds)
            .then(function(offersData) {
              if (offersData.status === 0) {
                _instance.mds.OrderStorage.setServices(offersData.body);
                return KT.apiClient.getAllowedTransitions(_instance.orderId);
              } else {
                return $.Deferred().reject();
              }
            })
            .then(function(transitionsData) {
              if (transitionsData.status === 0 ) {
                if (Array.isArray(transitionsData.body.services)) {
                  _instance.mds.OrderStorage.setAllowedTransitions(transitionsData.body.services);
                } else {
                  console.warn('список доступных действий пуст');
                  _instance.mds.OrderStorage.setAllowedTransitions([]);
                }
              } else {
                console.error('не удалось получить доступные действия');
              }
            });
        } else {
          _instance.mds.OrderStorage.setServices([]);
        }

        KT.apiClient.getOrderTourists(_instance.orderId)
          .done(function(response) {
            var tourists = (response.body.tourists !== undefined) ?
              response.body.tourists : [];
            _instance.mds.OrderStorage.setTourists(tourists);
          });
        
        KT.apiClient.getOrderInvoices(_instance.orderId)
          .done(function(response) {
            var invoices = (response.body.Invoices !== undefined && Array.isArray(response.body.Invoices)) ?
                response.body.Invoices : [];
            _instance.mds.OrderStorage.setInvoices(invoices);
          });

        KT.apiClient.getOrderHistory(_instance.orderId)
          .done(function(response) {
            if (response.status !== 0) {
              if (response.errorCode === 8) {
                console.log('Для данной заявки нет истории');
              } else {
                KT.notify('loadingHistoryFailed', response.errorCode + ': ' + response.errors);
              }
            } else {
              _instance.mds.OrderStorage.setHistory(response.body);
            }
          });
             
        KT.apiClient.getOrderDocuments(_instance.orderId)
          .done(function(response) {
            if (response.status !== 0) {
              KT.notify('loadingDocumentsFailed', response.errorCode + ': ' + response.errors);
            } else {
              _instance.mds.OrderStorage.setDocuments(response.body);
            }
          });
      });
  };

  /** Загрузка необходимых данных для интерфейса создания новой заявки */
  oeController.prototype.loadBare = function() {
    var _instance = this;

    $.extend(_instance.mds.view.config.templates, _instance.mds.tourists.view.config.templates);

    _instance.mds.tourists.controller.init();

    var clientId = window.sessionStorage.getItem('clientId');
    var contractId = window.sessionStorage.getItem('contractId');

    if (clientId === null) { 
      clientId = KT.getStoredSettings().companyId;
    }

    //==== загрузка шаблонов
    var getTemplates = KT.getTemplates(
        _instance.mds.view.config.templateUrl,
        _instance.mds.view.config.templates
      );
    
    //==== загрузка данных компании
    var companyInfo = KT.Dictionary.getAsList('companies', {
        'companyId': +clientId,
        'fieldsFilter': [],
        'lang': 'ru'
      });

    //==== инициализация
    getTemplates
      .then(function(templates) {
        _instance.mds.tpl = templates;
        _instance.mds.view.renderHeaderControls({ });
      });

    companyInfo
      .fail(function() {
        console.error('Ошибка получения данных клиента');
      });

    $.when(getTemplates, companyInfo)
      .then(function(tpl, companyData) {
        if (companyData.length !== 0) {
          var clientData = companyData[0];

          _instance.mds.OrderStorage.initializeBare({
            'clientId': +clientId,
            'clientType': clientData.companyRoleType,
            'clientName': clientData.name,
            'contractId': contractId,
            'companyMainOffice': clientData.companyMainOffice
          });
        } else {
          console.error('Ошибка получения данных клиента');
        }
      });
  };

  return oeController;
}));

(function() {
  KT.on('KT.initializedCore', function() {
    /** Инициализация модуля */
    KT.mdx.OrderEdit.controller = new KT.crates.OrderEdit.controller(KT.mdx.OrderEdit);
    KT.mdx.OrderEdit.controller.init();

    if (KT.mdx.OrderEdit.controller.orderId === 'new') {
      KT.mdx.OrderEdit.controller.loadBare();
    } else if (KT.mdx.OrderEdit.controller.orderId !== null) {
      KT.mdx.OrderEdit.controller.load();
    } else {
      window.location.assign(KT.appEntries.orderlist);
    }
  });
}());
