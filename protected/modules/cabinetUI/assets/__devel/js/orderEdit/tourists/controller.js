(function(global,factory) {

    KT.crates.OrderEdit.tourists.controller = factory(KT.crates.OrderEdit.tourists);

}(this,function(crate) {
  /**
  * Редактирование заявки: туристы
  * submodule
  * @constructor
  * @param {Object} module - хранилище модуля (родительского)
  * @param {Object} orderId - ID заявки
  */
  var oetController = function(module, orderId) {
    this.mds = module;
    this.orderId = orderId;
    this.mds.tourists.view = new crate.view(this.mds);
  };

  oetController.prototype.init = function() {
    var _instance = this;
    var modView = this.mds.tourists.view;

    /** ID сервиса, из которого вызвано добавление туриста; при перезагрузке неактуально */
    window.sessionStorage.removeItem('serviceToAddTourist');

    /**
    * Запрос на добавление туриста к заявке
    * @todo rethink mechanism
    */
    KT.on('OrderEdit.createAddTouristForm', function() {
      modView.addTouristForm(_instance.mds.OrderStorage);
    });

    /*==========Обработчики событий модели============================*/
    /** Рендер пустого списка если в заявке нет услуг */
    KT.on('OrderStorage.initialized', function(e, OrderStorage) {
      if (OrderStorage.orderId === 'new') {
        modView.$touristList.empty();
        modView.addTouristForm(_instance.mds.OrderStorage);
      }
    });

    /** Обработка обновления информации по туристам в заявке */
    KT.on('OrderStorage.setTourists', function(e, OrderStorage) {
      modView.renderTouristList(OrderStorage);
    });

    /** Добавление/обновление туриста */
    KT.on('OrderStorage.savedTourist', function(e, data) {
      var touristId = data.touristId;
      var Tourist = _instance.mds.OrderStorage.tourists[touristId];
      var isOffline = _instance.mds.OrderStorage.isOffline;
      modView.refreshTouristForm(Tourist, isOffline);

      if (_instance.mds.OrderStorage.touleader !== null) {
        modView.removeTLSetter().rearrange();
      }

      KT.dispatch('OrderEdit.reloadHeader');
    });

    /*==========Обработчики событий представления============================*/

    /** Обработка выбора сотрудника в саджесте */
    modView.$touristList.on('change', '.js-ore-tourist--suggest', function() {
      var documentId = $(this).val();
      if (documentId !== '') {
        var userId = $(this)[0].selectize.options[+documentId].userId;
        var tempTouristId = $(this).closest('.js-ore-tourist').attr('data-touristid');
        var $touristForm = modView.touristForms[tempTouristId];

        modView.lockClientSelect($touristForm, function() {
          KT.apiClient.getClientUser(documentId)
            .done(function(response) {
              if (response.status === 0) {
                var documentId = response.body.document.docId;
                var newTouristId = 'doc' + documentId;
                var Tourist = new KT.storage.TouristStorage(newTouristId);
        
                $touristForm.attr('data-touristid', newTouristId);
                $touristForm.data('userid', userId);

                modView.touristForms[newTouristId] = modView.touristForms[tempTouristId];
                delete modView.touristForms[tempTouristId];

                Tourist.initializeFromUser(response.body);
                modView.refreshTouristForm(Tourist, false);
              }
            });
        });
      }
    });

    /** Скрыть/показать подробную информацию по туристу */
    modView.$touristList.on('click','.js-ore-tourist--header',function(e) {
      var $touristForm = $(this).closest('.js-ore-tourist');

      if (!$touristForm.hasClass('is-locked')) {
        var $dataForm = $touristForm.find('.js-ore-tourist--form-wrapper');

        if ($(e.target).closest('.js-ore-tourist--suggest-field', $touristForm).length === 0) {
          if ($touristForm.hasClass('active')) {
            $touristForm.removeClass('active');
            $dataForm.css({'display':'block'}).slideUp(500);
          } else {
            $touristForm.addClass('active');
            $dataForm.css({'display':'none'}).slideDown(500);
          }
        }
      }
    });

    /** Обработка нажатия на кнопку "Удалить туриста" */
    modView.$touristList.on('click','.js-ore-tourist--remove',function(e) {
      e.stopPropagation();

      var $touristForm = $(this).closest('.js-ore-tourist');
      var touristId = $touristForm.data('touristid');

      if (String(touristId).indexOf('tmp') === 0 || String(touristId).indexOf('doc') === 0) {
        // отмена добавления туриста
        modView.showRevertAddTouristModal(function() {
          KT.Modal.closeModal();
          $touristForm.remove();
          window.sessionStorage.removeItem('serviceToAddTourist');
        });

      } else {
        // удаление туриста
        modView.showRemoveTouristModal(function() {
          KT.Modal.showLoader();
          modView.renderPendingProcess($touristForm);

          touristId = +touristId;

          KT.apiClient.removeOrderTourist(_instance.orderId, touristId)
            .done(function(response) {
              KT.Modal.closeModal();
              if (response.status === 0) {
                var tourist = _instance.mds.OrderStorage.tourists[touristId];

                _instance.mds.OrderStorage.removeTourist(touristId);
                modView.removeTouristForm(touristId);

                KT.dispatch('OrderEdit.reloadHeader');
                KT.notify('touristRemoved',{name: tourist.firstname + ' ' + tourist.lastname});
              } else {
                modView.refreshTouristFormActions($touristForm,  _instance.mds.OrderStorage.isOffline);
                KT.notify('removingTouristFailed', response.errors);
              }
            });
        });
      }
    });

    /** Обработка нажатия кнопки отмены ввода данных формы туриста
    * @todo для свежесозданного туриста надо полностью очищать форму
    */
    modView.$touristList.on('click','.js-ore-tourist--reset',function() {
      var $touristForm = $(this).closest('.js-ore-tourist');
      var touristId = $touristForm.data('touristid');

      if (String(touristId).indexOf('tmp') !== -1) {
          modView.removeTouristForm(touristId);

      } else if (String(touristId).indexOf('doc') !== -1) {
        var documentId = +touristId.substr(3);
        modView.renderPendingProcess($touristForm);

        KT.apiClient.getClientUser(documentId)
          .done(function(response) {
            if (response.status === 0) {
              var Tourist = new KT.storage.TouristStorage(touristId);
              Tourist.initializeFromUser(response.body);
              modView.refreshTouristForm(Tourist, false);
            }
          });

      } else {
        touristId = +touristId;
        var Tourist = _instance.mds.OrderStorage.tourists[touristId];
        var isOffline = _instance.mds.OrderStorage.isOffline;
        modView.refreshTouristForm(Tourist, isOffline);
      }
    });

    /** Обработка подтверждения формы данных туриста */
    modView.$touristList.on('submit','.js-ore-tourist',function(e) {
      e.preventDefault();
      var $touristForm = $(this);
      var formdata = modView.getTouristFormData($touristForm);

      if (formdata !== false && typeof formdata === 'object') {
        modView.renderPendingProcess($touristForm);

        var currentTouristId = formdata.touristId;
        var newTouristId;

        KT.apiClient.setOrderTourist(_instance.mds.OrderStorage, formdata)
          .then(function(response) {
            if (response.status === 0) {
              /* если заявка создана через туриста */
              if (_instance.mds.OrderStorage.orderId === 'new') {
                window.sessionStorage.removeItem('clientId');
                window.sessionStorage.removeItem('contractId');
                window.location.assign('/cabinetUI/orders/order/' + response.body.orderId);
              } else {
                newTouristId = response.body.touristId;
                _instance.updateTouristForm(currentTouristId, newTouristId);
              }

            } else {
              if (_instance.mds.OrderStorage.tourleader === null) {
                $touristForm.removeAttr('data-leader')
                  .find('.js-ore-tourist--tourleader-toggler')
                    .prop('checked',false).change();
              }
              modView.refreshTouristFormActions($touristForm, _instance.mds.OrderStorage.isOffline);
              KT.notify('saveTouristFailed', response.errors);
            }
          });
      } else {
        KT.notify('incorrectTouristData');
      }
    });

    /** Обработка нажатия на кнопку "Сохранить как сотрудника" */
    modView.$touristList.on('click', '.js-ore-tourist--save-user', function() {
      var $touristForm = $(this).closest('.js-ore-tourist');
      var tempTouristId = $touristForm.data('touristid');
      var touristData = modView.getTouristFormData($touristForm);
      var userData = modView.getUserFormData($touristForm);

      if (touristData !== false && userData !== false) {
        userData.user.clientId = _instance.mds.OrderStorage.client.id;
        var currentTouristId = touristData.touristId;
        var newTouristId;

        modView.renderPendingProcess($touristForm);

        var savingTourist = KT.apiClient.setOrderTourist(_instance.mds.OrderStorage, touristData);
        var savingUser = KT.apiClient.setUser(userData, tempTouristId);

        $.when(savingTourist, savingUser)
          .then(function(touristResponse, userResponse) {
            if (touristResponse.status === 0) {
              /* если заявка создана через туриста */
              if (_instance.mds.OrderStorage.orderId === 'new') {
                window.sessionStorage.removeItem('clientId');
                window.sessionStorage.removeItem('contractId');
                window.location.assign('/cabinetUI/orders/order/' + touristResponse.body.orderId);
              } else {
                newTouristId = touristResponse.body.touristId;
                _instance.updateTouristForm(currentTouristId, newTouristId);

                if (userResponse.status === 0) {
                  KT.notify('userSaved');
                } else {
                  KT.notify('savingUserFailed', userResponse.errors);
                }
              }
            } else {
              // обработка ошибкуи сохранения туриста
              if (_instance.mds.OrderStorage.tourleader === null) {
                $touristForm.removeAttr('data-leader')
                  .find('.js-ore-tourist--tourleader-toggler')
                    .prop('checked',false).change();
              }
              modView.refreshTouristFormActions($touristForm, _instance.mds.OrderStorage.isOffline);
              KT.notify('saveTouristFailed', touristResponse.errors);

              // обработка результата сохранения сотрудника
              if (userResponse.status === 0) {
                // несохраненные туристы имеют Id со строковым префиксом
                var isExistingTourist = !isNaN(parseInt(tempTouristId));

                if (!isExistingTourist) {
                  var documentId = userResponse.body.userDocId;
                  var userId = userResponse.body.userId;
                  newTouristId = 'doc' + documentId;

                  if (newTouristId !== tempTouristId) {
                    $touristForm.attr('data-touristid', newTouristId);
                    $touristForm.attr('data-userid', userId);
                    $touristForm.data('userid', userId);

                    modView.touristForms[newTouristId] = modView.touristForms[tempTouristId];
                    delete modView.touristForms[tempTouristId];
                  }

                  modView.lockClientSelect($touristForm, function() {
                    KT.apiClient.getClientUser(documentId)
                      .done(function(response) {
                        if (response.status === 0) {
                          var Tourist = new KT.storage.TouristStorage(newTouristId);
                          Tourist.initializeFromUser(response.body);
                          modView.refreshTouristForm(Tourist, false);
                        }
                      });
                  });
                }
                KT.notify('userSaved');
              } else {
                KT.notify('savingUserFailed', userResponse.errors);
              }
            }
          });
      } else {
        console.error('Ошибка ввода данных туриста');
      }
    });

    /** Обработка изменения переключателя "Заказчик" */
    modView.$touristList.on('change','.js-ore-tourist--tourleader-toggler',function() {
      var $leaderToggler = $(this);
      var $touristForm = $leaderToggler.closest('.js-ore-tourist');

      var declineAction = function() {
        $leaderToggler.prop('checked',false).change();
        KT.Modal.closeModal();
      };

      var submitAction = function() {
        KT.Modal.closeModal();
        $touristForm.attr('data-leader','true');
        $touristForm.submit();
      };

      if ($leaderToggler.prop('checked')) {
        KT.Modal.notify({
          type:'info',
          title:'Определить заказчика',
          msg:'<p>Вы действительно хотите сделать этого туриста заказчиком?</p>',
          buttons:[{
              type:'common',
              title:'нет',
              callback:declineAction
            },
            {
              type:'success',
              title:'да',
              callback:submitAction
          }]
        });
      } else {
        $leaderToggler.closest('.simpletoggler').removeClass('active');
        $touristForm.removeAttr('data-leader');
      }
    });

    /** Обработка изменения типа документа */
    modView.$touristList.on('change','select[name="doctype"]',function() {
      modView.livecheckDocument($(this).closest('.js-ore-tourist'));
    });

    /** Добавление новой формы ввода мильной карты */
    modView.$touristList.on('click', '.js-ore-tourist--bonus-card-add', function() {
      modView.addBonusCardForm($(this).closest('.js-ore-tourist'));
    });

    /** Удаление формы ввода мильной карты */
    modView.$touristList.on('click', '.js-ore-tourist--bonus-card-delete', function() {
      modView.removeBonusCardForm($(this).closest('.js-ore-tourist--bonus-card'));
    });

    /** Изменение тогглера
    * @todo move to library
    */
    modView.$touristList.on('change','.simpletoggler input',function() {
      if ($(this).prop('checked')) {
        $(this).closest('.simpletoggler').addClass('active');
      } else {
        $(this).closest('.simpletoggler').removeClass('active');
      }
    });

    /** Обработка нажатия на кнопку "скопировать из данных ФИО туриста" */
    modView.$touristList.on('click','.js-ore-tourist--copy-name',function() {
      modView.copyNamesToDoc($(this));
    });

    /** @deprecated
    * Обработка нажатия на переключатель "виза"
    */
    modView.$touristList.on('change','input[name="getvisa"]',function() {
      var text = $(this).prop('checked') ? 'Требуется' : 'Не требуется';
      $(this).closest('.js-ore-tourist').find('.js-ore-tourist--require-visa').text(text);
    });

    /** @deprecated
    * Обработка нажатия на переключатель "страховка"
    */
    modView.$touristList.on('change','input[name="getinsurance"]',function() {
      var text = $(this).prop('checked') ? 'Требуется' : 'Не требуется';
      $(this).closest('.js-ore-tourist').find('.js-ore-tourist--require-insurance').text(text);
    });

    /** Обработка нажатия на кнопку "Добавить туриста" */
    modView.$touristListControls.on('click','.js-ore-tourists--add-new',function() {
      modView.addTouristForm(_instance.mds.OrderStorage);
    });

    
  };

  /** 
  * Обновление данных формы туриста 
  */
  oetController.prototype.updateTouristForm = function(currentTouristId, newTouristId) {
    var _instance = this;
    var modView = _instance.mds.tourists.view;
    var $touristForm = modView.touristForms[currentTouristId];

    KT.apiClient.getOrderTourists(_instance.orderId)
      .then(function(response) {
        if (response.status !== 0) {
          window.location.assign('/cabinetUI/orders/order/' + _instance.orderId);
        } else {
          var touristData = response.body.tourists.filter(function(item) {
            return newTouristId === item.touristId;
          })[0];

          var Tourist = new KT.storage.TouristStorage(touristData.touristId);
          Tourist.initialize(touristData);

          if (newTouristId !== currentTouristId) {
            // изменение данных формы туриста и сброс структур отображения
            $touristForm
              .attr('data-touristid', newTouristId)
              .data('touristid', newTouristId);
            modView.touristForms[newTouristId] = $touristForm;
            delete modView.touristForms[currentTouristId];
            delete modView.customFields[currentTouristId];

            /*
            * Проверка наличия у туриста доп. полей: 
            * если есть, не совершать переход к услуге
            */
            if (Tourist.customFields !== null) {
              window.sessionStorage.removeItem('serviceToAddTourist');
              KT.notify('customFieldsRevealed');
            }
          }

          _instance.mds.OrderStorage.saveTourist(Tourist);

          if (newTouristId === currentTouristId) {
            KT.notify('touristUpdated', {name: [Tourist.firstname, Tourist.lastname].join(' ')});
          } else {
            KT.notify('touristAdded', {name: [Tourist.firstname, Tourist.lastname].join(' ')});
          }
        }
      });
  };

  return oetController;
}));
