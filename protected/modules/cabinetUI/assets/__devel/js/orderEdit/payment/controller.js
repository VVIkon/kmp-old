(function(global,factory) {

    KT.crates.OrderEdit.payment.controller = factory(KT.crates.OrderEdit.payment);

}(this, function(crate) {
  /**
  * Редактирование заявки: оформление
  * @constructor
  * @param {Object} module - хранилище родительского модуля
  * @param {Object} orderId - ID заявки
  */
  var oepController = function(module, orderId) {
    this.mds = module;
    this.orderId = orderId;
    this.mds.payment.view = new crate.view(this.mds);
  };

  oepController.prototype.init = function() {
    var _instance = this;
    var modView = this.mds.payment.view;

    /** Рендер пустого списка если в заявке нет услуг */
    KT.on('OrderStorage.initialized', function(e, OrderStorage) {
      if (OrderStorage.getServices().length === 0) {
        modView.renderServiceList([]);
      }
    });

    /** Рендер списка услуг */
    KT.on('OrderStorage.setServices', function(e, OrderStorage) {
      modView.renderServiceList(OrderStorage.getServices());
    });

    /** Обработка загрузки данных по счетам */
    KT.on('OrderStorage.setInvoices', function(e, OrderStorage) {
      modView.renderInvoiceList(OrderStorage.getInvoices());
    });

    /**
    * При загрузке доступных переходов разблокировать выбор услуг для совершения действий
    */
    KT.on('OrderStorage.setAllowedTransitions', function() {
      modView.renderServiceActions(false);
    });

    /** Обработка получения всех запрашиваемых валидаций действий над услугами */
    KT.on('OrderStorage.setValidatedActions', function(e, OrderStorage) {
      console.warn('service validations:');
      console.log(OrderStorage.validatedActions);

      var actions = $.extend(true, {}, OrderStorage.validatedActions);

      // хак для разного стиля кнопок, переделать?
      if (KT.profile.userType !== 'op' && actions['Manual']) {
        actions['ToManager'] = actions['Manual'];
        delete actions['Manual'];
      }

      modView.renderServiceActions(actions);
    });

    /*===============================================
    * Обработчики событий представления
    **===============================================*/
    
    /** Обработка нажатия кнопки "Сохранить" на вкладке оформления заявки */
    KT.on('PaymentView.setDiscount', function(e, data) {
      KT.apiClient.setDiscount(_instance.orderId, data.discount)
        .done(function(response) {
          if (response.status === 0) {
            KT.notify('discountSet',{'discount': response.discount});
            modView.clearSetInvoiceForm();
            modView.clearServiceList();
          } else {
            $(modView.$serviceList).find('input[name="discount"]')
              .prop('disabled',false)
              .val(modView.discountSum);
            KT.notify('settingDicountFailed', response.errors);
          }
        });
    });

    /** Раскрыть/свернуть информацию по счету */
    modView.$invoiceList.on('click','.js-ore-invoice--header', function() {
      var $invoice = $(this).closest('.js-ore-invoice');
      if ($invoice.hasClass('active')) { $invoice.removeClass('active'); }
      else { $invoice.addClass('active'); }
      $invoice.find('.js-ore-invoice--description').toggle(500);
    });

    /**
    * Обработка выбора услуги на вкладке "Оформление": 
    * вычисление доступных действий с услугами
    */
    modView.$serviceList.on('change','.js-ore-payment-services--check-service', function() {
      modView.clearServiceActions();
      _instance.mds.OrderStorage.validatedActions = {};
      
      var checkedServices = modView.getSelectedServices();
      modView.$serviceList
        .find('.js-ore-payment-services--check-service')
          .prop('disabled', true);
      
      if (checkedServices.length === 0) {
        modView.renderServiceActions(false);
      } else {
        var servicesTransitions = [];
        // для каждой услуги получим набор доступных переходов по checkTransitions ...
        checkedServices.forEach(function(serviceId) {
          servicesTransitions.push(
            $.extend(true, [], _instance.mds.OrderStorage.services[serviceId].allowedTransitions)
          );
        });
        // ... и отфильтруем переходы, общие для всех выбранных услуг
        var commonTransitions;
        if (servicesTransitions.length === 1) {
          commonTransitions = servicesTransitions[0];
        } else {
          commonTransitions = servicesTransitions.shift().filter(function(v) {
            return servicesTransitions.every(function(a) {
              return a.indexOf(v) !== -1;
            });
          });
        }

        var validationParams = {};
        var transitionsAmount = 0;

        // для каждого перехода получим масив парвметров по всем услугам для передачи на validate
        commonTransitions.forEach(function(transition) {
          validationParams[transition] = [];
          var paramsMergeError = false;

          checkedServices.forEach(function(serviceId) {
            var serviceValidations = _instance.mds.OrderStorage.getServiceCommandParams(transition, serviceId);
            if (serviceValidations === false) {
              paramsMergeError = true;
            } else {
              /** @todo хак для галочки bookStart */
              if (transition === 'BookStart') {
                serviceValidations.agreementSet = true;
              }

              validationParams[transition].push(serviceValidations);
            }
          });

          if (paramsMergeError) {
            delete validationParams[transition];
          } else {
            transitionsAmount++;
          }
        });
        
        if (transitionsAmount === 0) {
          modView.renderServiceActions(false);
        } else {
          _instance.mds.OrderStorage.pendingValidations = transitionsAmount;
          for (var action in validationParams) {
            if (validationParams.hasOwnProperty(action)) {
              KT.apiClient.validateAction(_instance.orderId, action, validationParams[action])
                .done(function(response) {
                  if (response.status === 0) {
                    _instance.mds.OrderStorage.setValidatedAction(response.action, response.body);
                  } else {
                    _instance.mds.OrderStorage.setValidatedAction(response.action, false);
                  }  
                });
            }
          }
        }
      }
    });

    /** Обработка нажатия на кнопку "Бронировать" */
    modView.$serviceList.on('click','.js-ore-payment-services--book', function() {
      var serviceIds = modView.getSelectedServices();
      var tosData = {
        'services': [],
        'hasNonRefundableServices': false
      };

      serviceIds.forEach(function(serviceId) {
        var Service = _instance.mds.OrderStorage.services[serviceId];

        if (Service.isNonRefundable) {
          tosData.hasNonRefundableServices = true;
        }

        tosData.services.push({
          'icon': KT.getCatalogInfo('servicetypes', Service.typeCode, 'icon'),
          'type': KT.getCatalogInfo('servicetypes', Service.typeCode, 'title'),
          'name': Service.name,
          'tosdoc': Service.tosDocumentName
        });
      });

      var chainBooking = function() {
        KT.Modal.showLoader();

        var bookProcesses = [];
        
        serviceIds.forEach(function(serviceId) {
          var commandParams = _instance.mds.OrderStorage.getServiceCommandParams('BookStart', serviceId);
          commandParams['agreementSet'] = true;
          bookProcesses.push(KT.apiClient.startBooking(_instance.orderId, commandParams));
        });

        $.when.apply($, bookProcesses).always(function() {
          KT.Modal.closeModal();
          KT.dispatch('OrderEdit.reloadInfo');
        });
      };

      modView.showChainBookingModal(tosData, chainBooking);
    });

    /** Обработка нажатия на кнопку "Отменить бронь" */
    modView.$serviceList.on('click','.js-ore-payment-services--book-cancel', function() {
      var serviceIds = modView.getSelectedServices();
      var services = [];

      serviceIds.forEach(function(serviceId) {
        services.push(_instance.mds.OrderStorage.services[serviceId]);
      });

      var chainBookCancel = function($modal) {
        var $isSetInvoiceSelected = $modal.find('.js-modal-book-cancel--set-invoice');
        KT.Modal.showLoader();

        var bookCancelProcesses = [];

        serviceIds.forEach(function(serviceId) {
          var commandParams = _instance.mds.OrderStorage.getServiceCommandParams('BookCancel', serviceId);
          var $serviceInvoiceCheckbox = $isSetInvoiceSelected.filter('[data-serviceid="' + serviceId + '"]');
          if ($serviceInvoiceCheckbox.length !== 0) {
            commandParams['createPenaltyInvoice'] = $serviceInvoiceCheckbox.prop('checked');
          } else {
            commandParams['createPenaltyInvoice'] = true;
          }

          bookCancelProcesses.push(KT.apiClient.bookCancel(_instance.orderId, commandParams));
        });

        $.when.apply($, bookCancelProcesses).always(function() {
          KT.Modal.closeModal();
          KT.dispatch('OrderEdit.reloadInfo');
        });
      };

      modView.showChainBookCancelModal(services, chainBookCancel);
    });

    /** Обработка нажатия на кнопку "Выставить счет" */
    modView.$serviceList.on('click','.js-ore-payment-services--set-invoice', function() {
      KT.dispatch('OrderEdit.openSetInvoiceForm');
    });

    /** Обработка нажатия кнопки "Аннулировать услугу" */
    /** @todo template, не реализовано */
    modView.$serviceList.on('click','.js-ore-payment-services--cancel', function() {
      var serviceIds = modView.getSelectedServices();

      KT.Modal.notify({
        type:'info',
        title:'Аннулирование услуг',
        msg:'<p>Вы действительно хотите аннулировать выбранные услуги: ' +
          serviceIds.join(',') + ' ?</p>',
        buttons:[
          {title:'Да'},
          {title:'Нет'}
        ]
      });
    });

    /** Обработка нажатия кнопки "Выписать ваучер" */
    modView.$serviceList.on('click','.js-ore-payment-services--issue', function() {
      var serviceIds = modView.getSelectedServices();
      var services = [];

      serviceIds.forEach(function(serviceId) {
        services.push(_instance.mds.OrderStorage.services[serviceId]);
      });

      var chainIssueTickets = function() {
        KT.Modal.showLoader();

        var issueProcesses = [];

        serviceIds.forEach(function(serviceId) {
          var commandParams = _instance.mds.OrderStorage.getServiceCommandParams('IssueTickets', serviceId);
          issueProcesses.push(KT.apiClient.issueTickets(_instance.orderId, commandParams));
        });

        $.when.apply($, issueProcesses).always(function() {
          KT.Modal.closeModal();
          KT.dispatch('OrderEdit.reloadInfo');
        });
      };

      modView.showChainIssueModal(chainIssueTickets);
    });

    /** Обработка нажатия кнопки "Перевести в ручной режим" */
    modView.$serviceList.on('click','.js-ore-payment-services--to-manual', function() {
      var serviceIds = modView.getSelectedServices();
      var services = [];

      serviceIds.forEach(function(serviceId) {
        services.push(_instance.mds.OrderStorage.services[serviceId]);
      });

      var chainSetServiceToManual = function() {
        KT.Modal.showLoader();

        var chainProcesses = [];

        serviceIds.forEach(function(serviceId) {
          var commandParams = _instance.mds.OrderStorage.getServiceCommandParams('Manual', serviceId);
          chainProcesses.push(KT.apiClient.setServiceToManual(_instance.orderId, commandParams));
        });

        $.when.apply($, chainProcesses).always(function() {
          KT.Modal.closeModal();
          KT.dispatch('OrderEdit.reloadInfo');
        });
      };

      modView.showChainSetToManualModal(chainSetServiceToManual);
    });

    /** Обработка нажатия на ссылку на условия бронирования */
    modView.$serviceList.on('click','.js-ore-payment-services--tos', function() {
      var serviceId = +$(this).data('serviceid');
      var service = _instance.mds.OrderStorage.services[serviceId];

      if (service.cancellationDocumentName !== false) {
        if (_instance.mds.servicesTermsDocuments[service.cancellationDocumentName] !== undefined) {
          _instance.mds.servicesTermsDocuments[service.cancellationDocumentName].open();
        } else {
          KT.notify('waitTOSLoading');
        }
      }
    });

    /** Обработка нажатия на кнопку отмены счета */
    /** @todo добавить обновление модели счета? */
    modView.$invoiceList.on('click', '.js-ore-invoice-actions--cancel', function() {
      var invoiceId = +$(this).closest('.js-ore-invoice').data('invoiceid');
      var InvoiceStorage = _instance.mds.OrderStorage.invoices[invoiceId];

      var cancelInvoce = function() {
        KT.Modal.showLoader();

        KT.apiClient.cancelInvoice(invoiceId)
          .then(function(response) {
            if (response.status === 0) {
              //InvoiceStorage.status = InvoiceStorage.statuses.CANCELLED;
              modView.clearInvoiceList();

              var getServices = KT.apiClient.getOrderOffers(_instance.orderId, _instance.mds.OrderStorage.getServiceIds());
              var getInvoices = KT.apiClient.getOrderInvoices(_instance.orderId);

              getServices.done(function(offersData) {
                if (offersData.status !== 0) {
                  console.error('offer load error: ' + offersData.error);
                } else {
                  _instance.mds.OrderStorage.setServices(offersData.body);
                }
              });

              getInvoices.done(function(invoiceData) {
                var invoices = (invoiceData.body.Invoices !== undefined && Array.isArray(invoiceData.body.Invoices)) ?
                    invoiceData.body.Invoices : [];
                _instance.mds.OrderStorage.setInvoices(invoices);
              });

              $.when(getServices, getInvoices).always(function() {
                KT.Modal.closeModal();
              });
            }
          });
      };

      modView.showCancelInvoiceModal(InvoiceStorage, cancelInvoce);
    });
  };

  return oepController;
}));
