(function(global,factory) {
  
      KT.crates.ClientAdmin.companyInfo.controller = factory(KT.crates.ClientAdmin);
  
  }(this, function(crate) {
    'use strict';
    
    /**
    * Администрирование клиента: дополнительные поля
    * @constructor
    * @param {Object} module - родительский модуль
    */
    var modController = function(module) {
      this.mds = module;
      this.mds.companyInfo.view = new crate.companyInfo.view(this.mds);
    };
  
    /** Инициализация событий модуля */
    modController.prototype.init = function() {
      var _instance = this;
      var modView = this.mds.companyInfo.view;

      /*==========Обработчики событий представления============================*/

      /** Обработка инициализации хранилища данных компании */
      KT.on('CompanyStorage.initialized', function(e, CompanyStorage) {
        modView.render();
        modView.CustomFieldTypes.render(CompanyStorage);

        KT.apiClient.getClientUserSuggest({
          'companyId': CompanyStorage.companyId,
          'substringFIO': null
        }).then(function(usersSuggest) {
          console.log('users loaded');
          if (usersSuggest.status !== 0) {
            console.error('Ошибка получения данных пользователей!');
            return;
          }

          _instance.mds.UsersStorage = new KT.storage.UsersStorage(_instance.mds.CompanyStorage);
          _instance.mds.UsersStorage.initialize(usersSuggest.body);
        });
      });

      /** Обработка инициализации хранилища данных пользователей */
      KT.on('UsersStorage.initialized', function(e, UsersStorage) {
        modView.UserCustomFields.render(UsersStorage);
      });

      /*==========Обработчики событий представления============================*/

      /** Обработка запроса на сохранение дополнительного поля компании */
      modView.$customFieldTypes.on('click', '.js-custom-field-form--action-save', function() {
        var customFieldData = modView.CustomFieldTypes.getFormData();

        if (customFieldData !== null) {
          _instance.saveCustomFieldType(customFieldData);
        }
      });

      /** Обработка запроса на отключение/активацию дополнительного поля */
      modView.$customFieldTypes.on('click', 
        '.js-custom-field-type--action-disable, .js-custom-field-type--action-enable', 
        function() {
          var activeStatus = $(this).hasClass('js-custom-field-type--action-enable');

          var CompanyStorage = _instance.mds.CompanyStorage;
          var $field = $(this).closest('.js-custom-field-type');
          var fieldTypeId = +$field.data('fieldid');

          var customField = CompanyStorage.getCustomField(fieldTypeId);
          customField.active = activeStatus;
          var fieldCategory = customField.getFieldCategory();

          KT.apiClient.setCustomFieldType(customField)
            .then(function(response) {
              // если данные поля приняты, запрашиваем данные компании с доп. полями для обновления
              if (response.status === 0) {
                modView.CustomFieldTypes.showCustomFieldsListLoading(fieldCategory);
                CompanyStorage.setCustomField(customField);
                modView.CustomFieldTypes.renderCustomFieldsList(fieldCategory);

                KT.notify('customFieldTypeUpdated', {'fieldName': customField.fieldTypeName});
              } else {
                KT.notify('customFieldTypeSavingFailed', response.errors);
              }
            });
      });
      
      /** Обработка запроса на создание поля минимальной цены */
      modView.$customFieldTypes.on('click', '.js-custom-field-type--action-add-minimal-price', function() {
        var minimalPriceFieldData = {
          'require': false,
          'typeTemplate': 5,
          'fieldTypeName': 'minimalPrice',
          'fieldCategory': 2,
          'active': true,
          'reasonFailTP': false,
          'availableValueList': null,
          'modifyAvailable': false
        };
        
        _instance.saveCustomFieldType(minimalPriceFieldData);
      });

      /** Обработка выбора пользователя для редактирования его дополнительных полей */
      modView.$userCustomFields.on('click', '.js-user-custom-fields--user', function() {
        var documentId = +$(this).data('documentid');

        KT.apiClient.getClientUser(documentId)
          .then(function(response) {
            if (response.status === 0) {
              var userData = _instance.mds.UsersStorage.prepareUserData(response.body);
              console.log(userData);
              modView.UserCustomFields.renderForm(userData);
            }
            console.log('данные пользователя загружены');
          });
      });

      /** Обработка запроса на отключение/активацию доп. поля пользователя */
      modView.$userCustomFields.on('click', 
        '.js-custom-field--action-disable, .js-custom-field--action-enable', 
        function() {
          var activeStatus = $(this).hasClass('js-custom-field--action-enable');

          var fieldIndex = +$(this).closest('.js-custom-field').data('idx');
          var fieldData = modView.UserCustomFields.changeActivationState(fieldIndex, activeStatus);

          KT.apiClient.setUserAddField(fieldData)
            .then(function(response) {
              if (response.status === 0) {
                modView.UserCustomFields.refreshCustomFields();
              } else {
                modView.UserCustomFields.changeActivationState(fieldIndex, !activeStatus);
              }
            });
      });

      /** Обработка запроса сохранения доп. полей пользователя */
      modView.$userCustomFields.on('click', '.js-custom-field-form--action-save', function() {
        var formData = modView.UserCustomFields.getFormData();
        console.log('доп. поля для сохранения ');

        if (formData !== null) {
          modView.UserCustomFields.showFormSavingProcess();

          var fieldSaveProcesses = formData.customFieldsData.map(function(fieldData) {
            return KT.apiClient.setUserAddField(fieldData);
          });

          $.when.apply($, fieldSaveProcesses)
            .then(function() {
              if (
                Array.prototype.every.call(arguments, function(response) {
                  return response.status === 0;
                })
              ) {
                KT.notify('userCustomFieldsSaved');
              } else {
                var errorMessages = Array.prototype.slice.call(arguments)
                  .filter(function(response) {
                    return response.status !== 0;
                  })
                  .map(function(response) {
                    return response.errors;
                  })
                  .join('<br>');

                KT.notify('userCustomFieldsSavingFailed', errorMessages);
              }
              modView.UserCustomFields.refreshCustomFields();
            })
            .fail(function() {
              KT.notify('userCustomFieldsSavingFailed');
              modView.UserCustomFields.refreshCustomFields();
            });
        } else {
          KT.notify('customFieldsValidationWarning');
        }
      });
    };

    /** 
    * Сохранение типа дополнительного поля 
    * @param {Object} customFieldData - данные поля для сохранения
    */
    modController.prototype.saveCustomFieldType = function(customFieldData) {
      var modView = this.mds.companyInfo.view;
      var CompanyStorage = this.mds.CompanyStorage;

      var customField = CompanyStorage.createCustomField(customFieldData);
      var fieldCategory = customField.getFieldCategory();

      KT.apiClient.setCustomFieldType(customField)
        .then(function(response) {
          // если данные поля приняты, запрашиваем данные компании с доп. полями для обновления
          if (response.status === 0) {
            modView.CustomFieldTypes.showCustomFieldsListLoading(fieldCategory);
            CompanyStorage.setCustomField(response.body.addFieldType);
            modView.CustomFieldTypes.renderCustomFieldsList(fieldCategory);

            if (customFieldData.fieldTypeId !== undefined) {
              KT.notify('customFieldTypeUpdated', {'fieldName': customFieldData.fieldTypeName});
            } else {
              KT.notify('customFieldTypeCreated', {'fieldName': customFieldData.fieldTypeName});
            }
          } else {
            KT.notify('customFieldTypeSavingFailed', response.errors);
          }
        });
    };
  
    return modController;
  
  }));
  