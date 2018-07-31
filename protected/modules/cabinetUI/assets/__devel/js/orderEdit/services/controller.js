(function(global,factory) {

    KT.crates.OrderEdit.services.controller = factory(KT.crates.OrderEdit);

}(this,function(crate) {
  /**
  * Редактирование заявки: услуги
  * @constructor
  * @param {Object} module - хранилище родительского модуля
  * @param {Integer} orderId - ID заявки
  */
  var oesController = function(module,orderId) {
    this.mds = module;
    this.orderId = orderId;

    this.mds.services.view = new crate.services.view(this.mds);

    /** @deprecated */
    //this.mds.orderInfo = {};
  };

  /** Инициализация событий модуля */
  oesController.prototype.init = function() {
    var _instance = this;
    var modView = this.mds.services.view;

    /*==========Обработчики событий модели============================*/

    /** @todo временное решение, надо бы переделать с отдельным рендером
    * самой услуги и блока привязки туристов
    * @param {OrderStorage} OrderStorage - хранилище данных заявки
    */
    var callServicesRendering = function(OrderStorage) {
      modView.renderServices(OrderStorage)
        .then(function() {
          var showService = window.sessionStorage.getItem('showService');
          if (showService !== null) {
            window.sessionStorage.removeItem('showService');
            modView.navigateToService(+showService);
          }
          
          // исключительно для авиации: проверка на наличие правил тарифа
          OrderStorage.getServices().forEach(function(Service) {
            if (Service.typeCode === 2 && !Service.isPartial) {
              var fareRules = Service.offerInfo.fareRules;
              if (!Array.isArray(fareRules) || fareRules.length === 0) {

                var fareRulesLoader = function(serviceId, retry) {
                  console.log('load rules');
                  retry--;
                  if (retry === 0) { 
                    modView.updateFareRules(serviceId);
                    return;
                  }

                  KT.apiClient.getOrderOffers(OrderStorage.orderId, [serviceId])
                    .then(function(response) {
                      if (response.status !== 0) {
                        setTimeout(function() { fareRulesLoader(Service.serviceId, retry); }, 5000);
                      } else {
                        var fareRules = response.body[0].offerInfo.fareRules;
                        if (!Array.isArray(fareRules) || fareRules.length === 0) {
                          setTimeout(function() { fareRulesLoader(Service.serviceId, retry); }, 5000);
                        } else {
                          OrderStorage.services[serviceId].offerInfo.fareRules = fareRules;
                          modView.updateFareRules(serviceId, fareRules);
                        }
                      }
                    });
                };

                setTimeout(function() { fareRulesLoader(Service.serviceId, 3); }, 5000);
              }
            }
          });
        });
    };

    /** Обработка смены валюты просмотра */
    KT.on('KT.changedViewCurrency', function() {
      modView.$serviceList.html(Mustache.render(KT.tpl.spinner, {}));
      var OrderStorage = _instance.mds.OrderStorage;
      var serviceIds = _instance.mds.OrderStorage.getServiceIds();

      if (serviceIds.length > 0) {
        KT.apiClient.getOrderOffers(_instance.orderId, serviceIds)
          .then(function(offersData) {
            if (offersData.status === 0) {
              OrderStorage.setServices(offersData.body);
              if (OrderStorage.loadStates.transitionsdata === 'loaded') {
                callServicesRendering(_instance.mds.OrderStorage);
              }
            } else {
              return $.Deferred().reject();
            }
          });
      }
    });

    /** Рендер пустого списка если в заявке нет услуг */
    KT.on('OrderStorage.initialized', function(e, OrderStorage) {
      if (OrderStorage.getServices().length === 0) {
        modView.renderEmptyServiceList();
      }
    });

    /** Обработка инициализации доступных действий с услугами */
    KT.on('OrderStorage.setAllowedTransitions', function(e, OrderStorage) {
      if (OrderStorage.loadStates.touristdata === 'loaded') {
        callServicesRendering(_instance.mds.OrderStorage);
      }
    });

    /** Обработка обновления данных туристов в хранилище */
    KT.on('OrderStorage.setTourists', function(e, OrderStorage) {
      if (OrderStorage.loadStates.transitionsdata === 'loaded') {
        callServicesRendering(OrderStorage);
      }
    });

    /** Обработка привязки туриста к услуге */
    KT.on('OrderStorage.savedServiceLinkage', function(e, data) {
      modView.renderServiceTourists(data.serviceId);
    });

    /** Обработка добавления/создания туриста (перерисовка блока услуг) */
    KT.on('OrderStorage.savedTourist', function(e, data) {
      _instance.mds.OrderStorage.getServiceIds().forEach(function(serviceId) {
          _instance.mds.OrderStorage.updateServiceTourists(serviceId);
          modView.renderServiceTourists(serviceId);
      });

      // проверка на добавление туриста из услуги для привязки
      var linkingServiceId = window.sessionStorage.getItem('serviceToAddTourist');

      if (linkingServiceId !== null) {
        window.sessionStorage.removeItem('serviceToAddTourist');

        var touristId = +data.touristId;
        linkingServiceId = +linkingServiceId;

        KT.dispatch('OrderEdit.setActiveTab', {activeTab:'services', callback: function() {
          modView.navigateToService(linkingServiceId);
        }});
        KT.notify('linkingTourists');

        var touristLinkage = {};
        touristLinkage[touristId] = {
          'state': true,
          'loyalityCardNumber': null,
          'loyalityProviderId': null
        };
        var linkageInfo = _instance.mds.OrderStorage.createLinkageStructure(linkingServiceId, touristLinkage);

        KT.apiClient.setTouristsLinkage(_instance.orderId, linkingServiceId, linkageInfo)
          .done(function(response) {
            if (+response.status === 0) {
              var link = response.body.result[0];

              if (link === undefined) {
                KT.notify('linkingTouristFailed', ['Турист', tourist.lastname, tourist.firstname].join(' '));
                _instance.mds.OrderStorage.updateServiceTourists(response.serviceid);
                modView.renderServiceTourists(response.serviceId);

              } else if (!Boolean(link.success)) {
                var tourist = _instance.mds.OrderStorage.tourists[link.touristId];
                KT.notify('linkingTouristFailed', [
                    'Турист', tourist.lastname, tourist.firstname,
                    ':', link.error
                  ].join(' '));
                _instance.mds.OrderStorage.updateServiceTourists(response.serviceid);
                modView.renderServiceTourists(response.serviceId);

              } else {
                _instance.mds.OrderStorage.saveServiceLinkage(response.serviceId, response.linkageInfo);
                KT.notify('touristsLinked');
                
              }
            } else {
              KT.notify('linkingTouristFailed', response.errors);
            }
          });
      }
    });

    /** Обработка удаления туриста (перерисовка блока услуг) */
    KT.on('OrderStorage.touristRemoved', function() {
      modView.renderServices(_instance.mds.OrderStorage);
    });

    /*==========Обработчики событий представления============================*/

    /** Скрыть/показать подробную информацию по услуге */
    modView.$serviceList.on('click','.js-service-form-header', function() {
      var $sf = $(this).closest('.js-service-form');
      var $dataform = $sf.find('.js-service-form-content');

      if ($sf.hasClass('active')) {
        $sf.removeClass('active');
        $dataform.css({'display':'block'}).slideUp(500);
      } else {
        $sf.addClass('active');
        $dataform.css({'display':'none'}).slideDown(500);
      }
    });

    /** Обработка изменения значения привязки туриста */
    modView.$serviceList.on('change','.js-service-form-tourist--service-bound', function() {
      var touristId = +$(this).attr('data-touristid');
      var tourist = _instance.mds.OrderStorage.tourists[touristId];
      var $serviceForm = $(this).closest('.js-service-form');
      var serviceId = +$serviceForm.attr('data-sid');
      var service = _instance.mds.OrderStorage.services[serviceId];

      var agegroup = service.getAgeGroup(service.getAgeByServiceEnding(tourist.birthdate));

      if ($(this).prop('checked')) {
        if ((service.touristAges[agegroup] + 1) <= service.declaredTouristAges[agegroup]) {
          service.touristAges[agegroup] += 1;
          $serviceForm.data('unsaved',true);
        } else {
          $(this).prop('checked',false).closest('.simpletoggler').removeClass('active');
          KT.notify('touristLinkageNotAllowedByAge');
        }
      } else {
        service.touristAges[agegroup] -= 1;
        $serviceForm.data('unsaved',true);
      }
    });

    /** Обработка изменения данных программы лояльности */
    modView.$serviceList.on('change', [
        '.js-service-avia-loyalty-program--provider',
        '.js-service-avia-loyalty-program--number'
      ].join(','), 
      function() {
        $(this).closest('.js-service-form').data('unsaved', true);
      }
    );

    /** Обработка нажатия на кнопку "Забронировать" услуги */
    modView.$serviceList.on('click','.js-service-form-actions--book', function() {
      var $serviceForm = $(this).closest('.js-service-form');
      var serviceId = +$serviceForm.data('sid');
      var Service = _instance.mds.OrderStorage.services[serviceId];

      var startBooking = function() {
        if ($serviceForm.data('unsaved') === false) {
          _instance.saveCustomFields(serviceId)
            .then(function() {
              return _instance.bookService(serviceId);
            });
        } else {
          _instance.saveServiceLinkage(serviceId)
            .then(function() {
              return _instance.saveCustomFields(serviceId);
            })
            .then(function() {
              return _instance.bookService(serviceId);
            });
        }
      };

      var submitAction = function() {
        KT.Modal.closeModal();
        startBooking();
      };

      var cancelAction = function () {
        KT.Modal.closeModal();
      };

      if (Service.isNonRefundable) {
        modView.showNonrefundableModal(submitAction, cancelAction);
      } else {
        startBooking();
      }
    });

    /** Обработка изменения переключателя согласия с условиями бронирования */
    modView.$serviceList.on('change','.js-service-form-tos-agreement input', function() {
      var serviceId = +$(this).closest('.js-service-form').data('sid');
      var service = _instance.mds.OrderStorage.services[serviceId];
      service.isTOSAgreementSet = $(this).prop('checked');
    });

    /** Обработка нажатия на кнопку сохранения полей услуги */
    modView.$serviceList.on('click','.js-service-form-actions--save', function() {
      var serviceId = +$(this).closest('.js-service-form').data('sid');

      modView.renderPendingServiceProcess(serviceId);

      _instance.saveCustomFields(serviceId)
        .always(function() {
          modView.renderServiceActions(serviceId);
        });
    });

    /** Обработка нажатия на кнопку проверки статуса услуги */
    modView.$serviceList.on('click','.js-service-form-actions--check-status', function() {
      var serviceId = +$(this).closest('.js-service-form').data('sid');

      modView.renderPendingServiceProcess(serviceId);

      KT.apiClient.checkServiceStatus(_instance.orderId, {
        'serviceId': serviceId
      }).then(function(response) {
          if (response.status === 0) {
            KT.notify('serviceStatusRenewed', response.errors);
            if (response.body.statusChanged) {
              KT.dispatch('OrderEdit.reloadInfo');
            } else {
              modView.renderServiceActions(serviceId);
            }
          } else {
            modView.renderServiceActions(serviceId);
            KT.notify('checkingServiceStatusFailed', response.errors);
          }
        });
    });

    /** Обработка нажатия на кнопку "Выписать билеты/ваучер" */
    modView.$serviceList.on('click','.js-service-form-actions--issue', function() {
      var serviceId = +$(this).closest('.js-service-form').attr('data-sid');

      modView.renderPendingServiceProcess(serviceId);

      KT.apiClient.issueTickets(_instance.orderId, serviceId)
        .done(function(response) {
          if (response.status === 0) {
            switch (response.body.serviceStatus) {
              case 9:
                if (KT.profile.userType === 'op') {
                  KT.notify('serviceSwitchedToManual', response.errors);
                } else {
                  KT.notify('serviceInProcess');                      
                }
                break;
              default:
                KT.notify('ticketsIssued');
                break;
            }
          } else {
            if (response.errors !== undefined) {
              KT.notify('issuingTicketsFailed', response.errors);
            }
          }

          KT.apiClient.getOrderDocuments(_instance.orderId)
            .done(function(response) {
              if (response.status !== 0) {
                KT.notify('loadingDocumentsFailed', response.errorCode + ': ' + response.errors);
              } else {
                _instance.mds.OrderStorage.setDocuments(response.body);
              }
            });

          KT.dispatch('OrderEdit.reloadInfo');
        });
    });

    /** Обработка нажатия на кнопку "Изменить бронь" */
    modView.$serviceList.on('click','.js-service-form-actions--book-change', function() {
      var serviceId = +$(this).closest('.js-service-form').data('sid');

      modView.showBookChangeModal(serviceId)
        .then(function(actionParams) {
          switch (actionParams.action) {
            case 'bookChange':
              modView.renderPendingServiceProcess(serviceId);
              KT.Modal.showLoader();
              
              KT.apiClient.bookChange(_instance.orderId, actionParams.params)
                .then(function(response) {      
                  if (response.status === 0) {
                    switch (response.body.serviceStatus) {
                      case 9:
                        KT.Modal.closeModal();
                        if (KT.profile.userType === 'op') {
                          KT.notify('serviceSwitchedToManual', response.errors);
                        } else {
                          KT.notify('serviceInProcess');
                        }
                        break;
                      default:
                        KT.notify('bookingChanged');
                        var Service = _instance.mds.OrderStorage.services[serviceId];
          
                        if (response.body.newSalesTerms !== undefined) {
                          console.warn('data changed:');
                          console.log(Service.compareSalesTerms(response.body.newSalesTerms));
            
                          if (!Service.compareSalesTerms(response.body.newSalesTerms)) {
                            modView.showPricesChangedModal(response.body.newSalesTerms, Service);
                          } else {
                            KT.Modal.closeModal();
                          }
                        } else {
                          KT.Modal.closeModal();
                        }
                        break;
                    }
                  } else {
                    KT.Modal.closeModal();
                    
                    switch(+response.errorCode) {
                      case 165:
                        KT.notify('bookingChangeNotSupported');
                        break;
                      case 170:
                        KT.notify('bookingChangeProhibited');
                        break;
                      default:
                        KT.notify('bookingChangeFailed');
                        break;
                    }
                  }
      
                  KT.dispatch('OrderEdit.reloadInfo');
                })
                .fail(function(err) { 
                  modView.renderServiceActions(serviceId);
                  if (err.error !== 'denied') {
                    KT.Modal.closeModal(); 
                  }
                });
              break;
            case 'sendToManager':      
              modView.renderPendingServiceProcess(serviceId);
              KT.Modal.showLoader();
              
              KT.apiClient.setServiceToManual(_instance.orderId, actionParams.params)
                .then(function(response) {
                  KT.Modal.closeModal();
      
                  if (response.status === 0) {
                    KT.notify('serviceSetToManual');
                  } else {
                    KT.notify('settingServiceToManualFailed');
                  }
                  
                  KT.dispatch('OrderEdit.reloadInfo');
                })
                .fail(function(err) {
                  modView.renderServiceActions(serviceId);
                  if (err.error !== 'denied') {
                    KT.Modal.closeModal(); 
                  }
                });
              break;
            default: 
              KT.Modal.closeModal();
              return;
          }
        })
        .fail(function() {
          KT.Modal.closeModal();
        });
    });

    /** Обработка нажатия на кнопку "Отменить бронь" */
    modView.$serviceList.on('click','.js-service-form-actions--book-cancel', function() {
      var serviceId = +$(this).closest('.js-service-form').data('sid');

      modView.showBookCancelModal(serviceId)
        .then(function(cancelParams) {
          // подтверждение отмены бронирования
          if (cancelParams !== undefined) {
            modView.renderPendingServiceProcess(serviceId);
            KT.Modal.showLoader();
            return KT.apiClient.bookCancel(_instance.orderId, cancelParams);
          } else {
            return $.Deferred().reject(true);
          }
        })
        .then(function(response) {
          // проверка на изменение штрафов за отмену
          if (
            response.status === 0 && 
            response.body.newPenalties !== undefined &&
            response.body.newPenalties !== null
          ) {
            var request = $.Deferred();

            modView.showPenaltiesChangedModal(serviceId, response.body.newPenalties)
              .then(function(cancelParams) {
                if (cancelParams !== undefined) {
                  KT.Modal.showLoader();
                  KT.apiClient.bookCancel(_instance.orderId, cancelParams)
                    .then(function(result) {
                      request.resolve(result);
                    });
                  } else {
                    request.reject();
                  }
              })
              .fail(function() {
                request.reject();
              });

            return request.promise();
          } else {
            return response;
          }
        })
        .then(function(response) {
          // финальная обработка ответа команды отмены брони
          KT.Modal.closeModal();
          
          if (response.status === 0) {
            switch (response.body.serviceStatus) {
              case 7:
                KT.notify('bookingCancelled');
                break;
              case 9:
                if (KT.profile.userType === 'op') {
                  KT.notify('serviceSwitchedToManual', response.errors);
                } else {
                  KT.notify('serviceInProcess');                      
                }
                break;
              default:
                if (KT.profile.userType === 'op') {
                  KT.notify('bookingCancelFailed', response.errors);
                } else {
                  KT.notify('serviceInProcess');                      
                }
                break;
            }
          } else {
            KT.notify('bookingCancelFailed');
          }
          KT.dispatch('OrderEdit.reloadInfo');
        })
        .fail(function(noReload) {
          KT.Modal.closeModal();
          if (noReload !== true) {
            KT.dispatch('OrderEdit.reloadInfo');
          }
        });
    });

    /** Обработка нажатия на кнопку "Выставить счет" */
    modView.$serviceList.on('click','.js-service-form-actions--set-invoice', function() {
      KT.dispatch('OrderEdit.openSetInvoiceForm');
    });

    /** Обработка нажатия на кнопку "Перевести в ручной режим" */
    modView.$serviceList.on('click','.js-service-form-actions--to-manual', function() {
      var serviceId = +$(this).closest('.js-service-form').data('sid');

      modView.renderPendingServiceProcess(serviceId);

      KT.apiClient.setServiceToManual(_instance.orderId, {'serviceId': serviceId})
        .done(function(response) {
          if (response.status === 0) {
            console.log('Услуга переведена в ручной режим');
          } else {
            console.error('Не удалось перевести услугу в ручной режим');
          }
          
          KT.dispatch('OrderEdit.reloadInfo');
        });
    });

    /** Обработка нажатия на кнопку "Добавить туриста" */
    modView.$serviceList.on('click','.js-service-form--add-tourist', function() {
      var $serviceForm = $(this).closest('.js-service-form');
      var serviceId = +$serviceForm.attr('data-sid');

      /** ID сервиса, из которого вызвано добавление туриста */
      window.sessionStorage.setItem('serviceToAddTourist', serviceId);

      KT.dispatch('OrderEdit.setActiveTab', {activeTab: 'tourists'});
      KT.dispatch('OrderEdit.createAddTouristForm');
    });

    /** Обработка нажатия на кнопку добавления доп. услуги */
    modView.$serviceList.on('click','.js-service-form-add-service--action-add', function() {
      var $addService = $(this).closest('.js-service-form-add-service--available-service');
      var $serviceForm = $addService.closest('.js-service-form');

      var OrderStorage = _instance.mds.OrderStorage;
      var serviceId = +$serviceForm.attr('data-sid');
      var innerServiceId = $addService.data('add-service-id');

      var AdditionalServiceOffer = OrderStorage.services[serviceId].getAdditionalServiceOffer(innerServiceId);

      AdditionalServiceOffer.chooseParams()
        .then(function(addServiceParams) {
          $addService.closest('.js-service-form--add-services')
            .html(Mustache.render(KT.tpl.spinner, {}));

          KT.apiClient.addExtraService(OrderStorage.orderId, $.extend({
              'serviceId': serviceId,
              'viewCurrency': KT.profile.viewCurrency
            }, addServiceParams)
          )
          .then(function(response) {
            if (response.status !== 0) {
              KT.notify('AddingAdditionalServiceFailed', response.errors);
              modView.updateAdditionalServices(serviceId);
            } else {
              KT.dispatch('OrderEdit.reloadInfo');
              //OrderStorage.services[serviceId].addAdditionalService(response.body.addService);
            }
          });
        });
    });

    /** Обработка нажатия на кнопку удаления доп. услуги */
    modView.$serviceList.on('click','.js-service-form-add-service--action-remove', function() {
      var $addService = $(this).closest('.js-service-form-add-service--issued-service');
      var $serviceForm = $addService.closest('.js-service-form');

      var OrderStorage = _instance.mds.OrderStorage;
      var serviceId = +$serviceForm.attr('data-sid');
      var addServiceId = +$addService.data('add-service-id');

      $addService.closest('.js-service-form--add-services')
        .html(Mustache.render(KT.tpl.spinner, {}));

      KT.apiClient.removeExtraService(OrderStorage.orderId, {
        'serviceId': serviceId,
        'addServiceId': addServiceId
      }).then(function(response) {
        if (response.status !== 0) {
          KT.notify('RemovingAdditionalServiceFailed', response.errors);
        } else {
          KT.dispatch('OrderEdit.reloadInfo');
          //OrderStorage.services[serviceId].removeAdditionalService(addServiceId);
        }

        modView.updateAdditionalServices(serviceId);
      });
    });

    /** Отобразить полную информацию по отелю  */
    modView.$serviceList.on('click', '.js-service-hotel--hotel-link', function(e) {
      e.stopPropagation();
      var hotelId = +$(this).data('hotelid');
      
      if (modView.HotelInfoPages.createPage(hotelId)) {
        KT.apiClient.getHotelInfo(hotelId)
          .done(function(response) {
            if (response.status !== 0) {
              console.error(response.errors);
            } else {
              modView.HotelInfoPages.render(response.body);
            }
          });
      } else {
        modView.HotelInfoPages.open(hotelId);
      }
    });

    /** Открыть окно редактирования услуги в ручном режиме */
    modView.$serviceList.on('click', '.js-service-form-actions--manual-edit', function() {
      var $serviceForm = $(this).closest('.js-service-form');
      var serviceId = +$serviceForm.attr('data-sid');

      modView.ManualEditForms.open(serviceId);
    });

    /*==========Обработчики ручного режима============================*/
    /** Изменение параметров услуги */
    modView.$manualFormsRoot.on('click', '.js-ore-service-manualedit--save-common-data', function() {
      var orderId = _instance.mds.OrderStorage.orderId;
      var $manualForm = $(this).closest('.js-ore-service-manualedit');
      var serviceId = +$manualForm.data('serviceid');

      var actionParams = modView.ManualEditForms.getSaveServiceDataParams(serviceId);
      if (actionParams === false) {
        console.error('Некорректные параметры для изменения услуги');
        return;
      }

      modView.ManualEditForms.forms[serviceId].close();
      modView.renderPendingServiceProcess(serviceId);

      KT.Modal.showLoader();

      KT.apiClient.setServiceData(orderId, serviceId, actionParams)
        .done(function(response) {
          KT.Modal.closeModal();
          if (response.status === 0) {
            KT.notify('serviceDataChanged');
          } else {
            KT.notify('changingServiceDataFailed', response.errors);
          }
          KT.dispatch('OrderEdit.reloadInfo');
        });
    });

    /** Изменение статуса услуги */
    modView.$manualFormsRoot.on('click', '.js-ore-service-manualedit--save-service-status', function() {
      var orderId = _instance.mds.OrderStorage.orderId;
      var $manualForm = $(this).closest('.js-ore-service-manualedit');
      var serviceId = +$manualForm.data('serviceid');

      var actionParams = modView.ManualEditForms.getSaveServiceStatusParams(serviceId);
      if (actionParams === false) {
        console.error('Некорректные параметры для изменения статуса');
        return;
      }

      modView.ManualEditForms.forms[serviceId].close();
      modView.renderPendingServiceProcess(serviceId);

      KT.Modal.showLoader();

      KT.apiClient.manualSetStatus(orderId, serviceId, actionParams)
        .done(function(response) {
          KT.Modal.closeModal();
          if (response.status === 0) {
            KT.notify('reservationDataChanged');
          } else {
            KT.notify('changingReservationDataFailed');
          }
          KT.dispatch('OrderEdit.reloadInfo');
        });
    });

    /** Изменение параметров брони */
    modView.$manualFormsRoot.on('click', '.js-ore-service-manualedit--save-book-info', function() {
      var orderId = _instance.mds.OrderStorage.orderId;
      var $manualForm = $(this).closest('.js-ore-service-manualedit');
      var serviceId = +$manualForm.data('serviceid');

      var actionParams = modView.ManualEditForms.getChangeBookDataParams(serviceId);
      if (actionParams === false) {
        console.error('Некорректные параметры для изменения брони');
        return;
      }

      modView.ManualEditForms.forms[serviceId].close();
      modView.renderPendingServiceProcess(serviceId);

      KT.Modal.showLoader();

      KT.apiClient.setReservationData(orderId, serviceId, actionParams)
        .done(function(response) {
          KT.Modal.closeModal();
          if (response.status === 0) {
            KT.notify('reservationDataChanged');
          } else {
            KT.notify('changingReservationDataFailed');
          }
          KT.dispatch('OrderEdit.reloadInfo');
        });
    });

    /** Изменение авиабилета */
    modView.$manualFormsRoot.on('click', '.js-ore-service-manualedit--save-changed-avia-ticket', function() {
      var orderId = _instance.mds.OrderStorage.orderId;
      var $manualForm = $(this).closest('.js-ore-service-manualedit');
      var serviceId = +$manualForm.data('serviceid');

      var actionParams = modView.ManualEditForms.getChangeTicketDataParams(serviceId);
      if (actionParams === false) {
        console.error('Некорректные параметры для изменения билета');
        return;
      }

      modView.ManualEditForms.forms[serviceId].close();
      modView.renderPendingServiceProcess(serviceId);

      KT.Modal.showLoader();

      KT.apiClient.setTicketsData(orderId, serviceId, actionParams)
        .done(function(response) {
          KT.Modal.closeModal();
          if (response.status === 0) {
            KT.notify('ticketsDataChanged');
          } else {
            KT.notify('changingTicketsDataFailed');
          }
          KT.dispatch('OrderEdit.reloadInfo');
        });
    });

    /** Добавление авиабилета */
    modView.$manualFormsRoot.on('click', '.js-ore-service-manualedit--save-new-avia-ticket', function() {
      var orderId = _instance.mds.OrderStorage.orderId;
      var $manualForm = $(this).closest('.js-ore-service-manualedit');
      var serviceId = +$manualForm.data('serviceid');

      var actionParams = modView.ManualEditForms.getAddTicketParams(serviceId);
      if (actionParams === false) {
        console.error('Некорректные параметры для изменения билета');
        return;
      }

      modView.ManualEditForms.forms[serviceId].close();
      modView.renderPendingServiceProcess(serviceId);

      KT.Modal.showLoader();

      KT.apiClient.setTicketsData(orderId, serviceId, actionParams)
        .done(function(response) {
          KT.Modal.closeModal();
          if (response.status === 0) {
            KT.notify('ticketsDataChanged');
          } else {
            KT.notify('changingTicketsDataFailed');
          }
          KT.dispatch('OrderEdit.reloadInfo');
        });
    });
  };

  /** 
  * Передача данных призязки туристов к услуге
  * @param {Integer} serviceId - ID услуги
  */
  oesController.prototype.saveServiceLinkage = function(serviceId) {
    var _instance = this;
    var modView = _instance.mds.services.view;
    var request = $.Deferred();

    if (_instance.mds.OrderStorage.getTourists().length > 0) {
      var newTouristLinkage = modView.getServiceTouristsData(serviceId);
      if (newTouristLinkage === false) {
        return request.reject();
      }

      var linkageInfo = _instance.mds.OrderStorage.createLinkageStructure(serviceId, newTouristLinkage);
      if (linkageInfo === false) {
        return request.reject();
      } else if (linkageInfo.length === 0) {
        return request.resolve(serviceId);
      } else {
        KT.apiClient.setTouristsLinkage(_instance.orderId, serviceId, linkageInfo)
          .done(function(response) {
            if (+response.status === 0) {
              var errors = [];
              
              response.body.result.forEach(function(link) {
                if (!Boolean(link.success)) {
                  var tourist = _instance.mds.OrderStorage.tourists[link.touristId];
                  errors.push([
                    'Турист', tourist.lastname, tourist.firstname,
                    ':',link.error
                  ].join(' '));
                }
              });

              /** 
               * @todo по идее, это должно вызываться только после сохранения, 
               * но тогда не сохраняются данные мильных карт. пересмотреть.
               */
              _instance.mds.OrderStorage.saveServiceLinkage(serviceId, linkageInfo);

              if (errors.length !== 0) {
                KT.notify('linkingTouristFailed', errors.join('<br>'));
                _instance.mds.OrderStorage.updateServiceTourists(serviceId);
                modView.renderServiceTourists(serviceId);

                request.reject();
              } else {
                KT.notify('touristsLinked');
                request.resolve(serviceId);
              }
            } else {
              KT.notify('linkingTouristFailed', response.errors);
              request.reject();
            }
          });
      }
    } else {
      KT.notify('saveServiceFailedNoTourists');
      return request.reject();
    }

    return request.promise();
  };

  /** 
  * Передача значений дополнительных полей
  * @param {Integer} serviceId - ID услуги
  */
  oesController.prototype.saveCustomFields = function(serviceId) {
    var _instance = this;
    var modView = _instance.mds.services.view;
    var customFieldsValues = modView.getCustomFieldsValues(serviceId);
    var request = $.Deferred();

    if (customFieldsValues === null) {
      KT.notify('notAllCustomFieldsSet');
      request.reject();
    } else {
      if (customFieldsValues.length === 0) {
        request.resolve(serviceId);
      } else {
        KT.apiClient.setServiceAdditionalData(_instance.orderId, customFieldsValues)
          .done(function(response) {
            if (response.status === 0) {
              KT.notify('customFieldsSaved');
              request.resolve(serviceId);
            } else {
              KT.notify('savingCustomFieldsFailed', response.errors);
              request.reject();
            }
          });
      }
    }

    return request.promise();
  };

  /** 
  * Бронирование услуги
  * @param {Integer} serviceId - ID услуги
  */
  oesController.prototype.bookService = function(serviceId) {
    var _instance = this;
    var modView = _instance.mds.services.view;
    var $serviceForm = modView.serviceForms[serviceId];
    var orderTourists = _instance.mds.OrderStorage.getTourists();
    var request = $.Deferred();

    if (orderTourists.length === 0) {
      KT.notify('bookingFailedNoTourists');
      return request.reject();
    }

    var service = _instance.mds.OrderStorage.services[serviceId];
    var hasAllTouristsLinked = service.checkAllTouristsLinked();
    var isTOSAgreementSet = $serviceForm
      .find('.js-service-form-tos-agreement')
      .find('input[type="checkbox"]')
      .prop('checked');

    if (!hasAllTouristsLinked) {
      KT.notify('notAllTouristsLinked');
      return request.reject();
    } else if (!isTOSAgreementSet) {
      KT.notify('bookingTermsNotAccepted');
      return request.reject();
    }
    
    modView.renderPendingServiceProcess(serviceId);

    var actionParams = _instance.mds.OrderStorage.getServiceCommandParams('BookStart', serviceId);
    if (actionParams === false) {
      return request.reject();
    }
    
    KT.apiClient.startBooking(_instance.orderId, actionParams)
      .then(function(response) {
        //в этом блоке выполняется проверка на изменение цен, обработка результата бронирования в следующием 
        var bookrequest = $.Deferred();

        if (
          response.status === 0 && 
          response.body.newOfferData !== undefined && 
          typeof response.body.newOfferData === 'object'
        ) {
          KT.notify('pricesChanged');
          var Service = _instance.mds.OrderStorage.services[serviceId];

          var submitAction = function() {
            KT.Modal.closeModal();
            KT.apiClient.startBooking(_instance.orderId, actionParams)
              .done(function(rebookResponse) {
                bookrequest.resolve(rebookResponse);
              });
          };

          var cancelAction = function() {
            KT.Modal.closeModal();
            KT.dispatch('OrderEdit.reloadInfo');
            bookrequest.reject();
          };

          modView.showPricesChangedModal(response.body.newOfferData, Service, submitAction, cancelAction);
        } else {
          bookrequest.resolve(response);
        }

        return bookrequest.promise();
      })
      .then(function(response) {
        if (response.status === 0) {
          switch (response.body.serviceStatus) {
            case 1:
              KT.notify('bookingStarted');
              break;
            case 2:
              KT.notify('bookingFinished');
              break;
            case 9:
              if (KT.profile.userType === 'op') {
                KT.notify('serviceSwitchedToManual', response.errors);
              } else {
                KT.notify('serviceInProcess');                      
              }
              break;
            default:
              if (KT.profile.userType === 'op') {
                KT.notify('bookingFailed', response.errors);
              } else {
                KT.notify('serviceInProcess');                      
              }
              break;
          }

          request.resolve(serviceId);
        } else {
          switch (+response.errorCode) {
            case 139:
            case 807:
              KT.notify('offerExpired');
              break;
            case 461:
            case 462:
            case 463:
            case 464:
            case 465:
            case 466:
            case 467:
            case 468:
            case 477:
            case 480:
            case 481:
              KT.notify('bookingFailed', response.errors);
              break;
            case 482:
              KT.notify('bookingFailed', 'ошибка поставщика:' + response.errorMessages);
              break;
            case 440:
            case 441:
            case 442:
            case 443:
            case 444:
            case 445:
            case 446:
            case 447:
              if (KT.profile.userType === 'op') {
                KT.notify('bookingFailed', response.errors);
              } else {
                KT.notify('serviceSwitchedToManual');                      
              }
              break;
            default:
              if (KT.profile.userType === 'op') {
                KT.notify('bookingFailed');
              } else {
                KT.notify('serviceSwitchedToManual');      
              }
          }
          
          request.reject(serviceId);
        }

        KT.dispatch('OrderEdit.reloadInfo');
      })
      .fail(function() {
        // отказ от принятия измененной цены
        request.resolve(serviceId);
      });

    return request.promise();
  };

  return oesController;

}));
