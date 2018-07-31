(function(global,factory) {
  
    KT.crates.ClientAdmin.companyInfo.view = factory(KT.crates.ClientAdmin);

}(this, function(crate) {
  'use strict';

  /**
  * Администрирование клиента: корпоративные политики
  * @param {Object} module - родительский модуль
  * @param {Object} [options] - конфигурация
  */
  var modView = function(module, options) {
    this.mds = module;
    if (options === undefined) {options = {};}
    this.config = $.extend(true, {
      'templateUrl': '/cabinetUI/admin/getTemplates',
      'templates': {
        customFieldTypes: 'companyInfo/customFieldTypes',
        customFieldType: 'companyInfo/customFieldType',
        customFieldMinimalPrice: 'companyInfo/customFieldMinimalPrice',
        customFieldForm: 'companyInfo/customFieldForm',
        userCustomFields: 'companyInfo/userCustomFields',
        userListRow: 'companyInfo/userListRow',
        userCustomFieldsForm: 'companyInfo/userCustomFieldsForm',
        // custom fields
        TextCF: 'customFields/textCF',
        TextAreaCF: 'customFields/textAreaCF',
        NumberCF: 'customFields/numberCF',
        DateCF: 'customFields/dateCF'
      }
    },options);

    this.$companyInfo = $('#company-info');

    this.$customFieldTypesToggler = this.$companyInfo.find('#custom-field-types--toggler');
    this.$customFieldTypes = this.$companyInfo.find('#custom-field-types');
    this.$userCustomFieldsToggler = this.$companyInfo.find('#user-custom-fields--toggler');
    this.$userCustomFields = this.$companyInfo.find('#user-custom-fields');

    this.CustomFieldTypes = this.setCustomFieldTypes(this.$customFieldTypes);
    this.UserCustomFields = this.setUserCustomFields(this.$userCustomFields);

    this.init();
  };

  /** Инициализация */
  modView.prototype.init = function() {
    var self = this;

    this.$customFieldTypesToggler.on('click', function() {
      if ($(this).hasClass('is-active')) {
        self.$customFieldTypes.slideUp(300);
        $(this).removeClass('is-active');
      } else {
        self.$customFieldTypes.slideDown(300);
        $(this).addClass('is-active');
      }
    });
    
    this.$userCustomFieldsToggler.on('click', function() {
      if ($(this).hasClass('is-active')) {
        self.$userCustomFields.slideUp(300);
        $(this).removeClass('is-active');
      } else {
        self.$userCustomFields.slideDown(300);
        $(this).addClass('is-active');
      }
    });
    
    this.CustomFieldTypes.init();
    this.UserCustomFields.init();
  };

  modView.prototype.render = function() {
    this.$customFieldTypes.html(Mustache.render(KT.tpl.spinner, {}));
    this.$userCustomFields.html(Mustache.render(KT.tpl.spinner, {}));
  };

  /**
  * Инициализация формы редактирования типов дополнительных полей
  * @param {Object} $container - контейнер для размещения объекта
  * @return {Object} - объект управления дополнительными полями компании
  */
  modView.prototype.setCustomFieldTypes = function($container) {
    var _instance = this;

    var CustomFieldTypes = {
      elem: {
        $container: $container,
        $formContainer: null,
        $serviceCustomFields: null,
        $userCustomFields: null
      },
      // Ссылка на хранилище данных компании
      CompanyStorage: null,
      // флаг: является ли компания частью холдинга    
      companyInHolding: false,
      // флаг: является ли компания главной (все не-филиалы - главные)
      companyIsMain: true,
      /** Инициализация объекта управления */
      init: function() {
        var self = this;

        $container.on('click', '.js-custom-field-types--action-add-field', function() {
          self.renderForm();
        });

        $container.on('click', '.js-custom-field-type--action-edit', function() {
          var fieldTypeId = +$(this).closest('.js-custom-field-type').data('fieldid');
          var CompanyStorage = self.CompanyStorage;
          var customField = CompanyStorage.getCustomField(fieldTypeId);

          if (customField !== null) {
            self.renderForm(customField);
          }
        });

        // загрузка вариантов для списка из файла
        $container.on('change', '.js-custom-field-form--load-options-from-file', function() {
          if ($(this).val() !== '') {
            var selectedFile = $(this)[0].files[0];
            if (selectedFile.size > 1024 * 50) {
              KT.notify('tooLargeFileWarning');
              return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
              self.setOptionValueList(e.target.result);
            };
            reader.onerror = function() {
              KT.notify('fileWasntLoadedWarning');
            };
            reader.readAsText(selectedFile);
          }
        });

        // защита "от дурака" - поле не может быть одновременно немодифицируемым и обязательным для заполнения
        $container
          .on('change', '.js-custom-field-form--required', function() {
            if ($(this).prop('checked')) {
              self.elem.$formContainer.find('.js-custom-field-form--editable').prop('checked', true);
            }
          })
          .on('change', '.js-custom-field-form--editable', function() {
            if (!$(this).prop('checked')) {
              self.elem.$formContainer.find('.js-custom-field-form--required').prop('checked', false);
            }
          });
      },
      /** Рендер объекта управления */
      render: function(CompanyStorage) {
        this.CompanyStorage = CompanyStorage;

        // другого метода определить пока нет, предполагаем, что каждый корпоратов в холдинге
        this.companyInHolding = (CompanyStorage.roleType === 'corp');
        this.companyIsMain = (CompanyStorage.holdingCompany === null);

        var $customFields = $(Mustache.render(_instance.mds.tpl.customFieldTypes, {
          'serviceCustomFields': CompanyStorage.getCustomFields('service'),
          'userCustomFields': CompanyStorage.getCustomFields('user')
        }));

        this.elem.$serviceCustomFields = $customFields.find('.js-custom-field-types--service');
        this.elem.$userCustomFields = $customFields.find('.js-custom-field-types--user');

        this.renderCustomFieldsList('service');
        this.renderCustomFieldsList('user');

        this.elem.$container.html($customFields);

        this.elem.$formContainer = this.elem.$container.find('.js-custom-field-types--form-container');
        this.renderForm();
      },
      /**
      * Отображение процесса загрузки списка доп. полей 
      * @param {String} type - тип списка доп. полей (service|user)
      */
      showCustomFieldsListLoading: function(type) {
        var $listContainer;
        
        switch (type) {
          case 'user':
            $listContainer = this.elem.$userCustomFields;
            break;
          case 'service':
            $listContainer = this.elem.$serviceCustomFields;
            break;
          default: return;
        }

        $listContainer.html('<tr><td>' + Mustache.render(KT.tpl.spinner,{}) + '</td></tr>');
      },
      /**
      * Рендер списка доп. полей
      * @param {String} type - тип списка доп. полей (service|user)
      */
      renderCustomFieldsList: function(type) {
        var CompanyStorage = this.CompanyStorage;
        var $listContainer, customFields;

        switch (type) {
          case 'user':
            $listContainer = this.elem.$userCustomFields;
            customFields = CompanyStorage.getCustomFields('user');
            break;
          case 'service':
            $listContainer = this.elem.$serviceCustomFields;
            customFields = CompanyStorage.getCustomFields('service');
            break;
          default:
            return;
        }

        var companyIsAffiliate = (this.companyInHolding && !this.companyIsMain);

        if (companyIsAffiliate) {
          customFields = customFields.filter(function(customField) {
            return !(customField.forAllCompanyInHolding && !customField.active);
          });
        }

        $listContainer.html(
          customFields
            .map(function(customField) {
              var fieldFromHeavens = customField.forAllCompanyInHolding && companyIsAffiliate;

              customField.actions = {
                'edit': (!fieldFromHeavens) ? customField.active : false,
                'view': fieldFromHeavens,
                'disable': (!fieldFromHeavens) ? customField.active : false,
                'enable': (!fieldFromHeavens) ? !customField.active : false,
              };
              return Mustache.render(_instance.mds.tpl.customFieldType, customField);
            })
            .join('')
        );

        // особая обработка для поля минимальной стоимости >.<
        if (type === 'service') {
          if (CompanyStorage.minimalPriceField === null) {
            $listContainer.append(
              Mustache.render(
                _instance.mds.tpl.customFieldMinimalPrice, 
                $.extend({
                  'actions': {
                    'add': true
                  }
                }, CompanyStorage.minimalPriceField)
              )
            );
          } else {
            var fieldFromHeavens = CompanyStorage.minimalPriceField.forAllCompanyInHolding && companyIsAffiliate;

            $listContainer.append(
              Mustache.render(
                _instance.mds.tpl.customFieldMinimalPrice, 
                $.extend({
                  'actions': {
                    'disable': (!fieldFromHeavens) ? CompanyStorage.minimalPriceField.active : false,
                    'enable': (!fieldFromHeavens) ? !CompanyStorage.minimalPriceField.active : false
                  }
                }, CompanyStorage.minimalPriceField)
              )
            );
          }
        }
      },
      /**
      * Рендер формы добавления/редактирования поля
      * @param {Object} [fieldData] - если редактируется поле, передаются данные этого поля 
      */
      renderForm: function(fieldData) {
        var params = {
          'blockEdit': false,
          'modifyAvailable': true,
          'companyInHolding': ( // для создания холдинговых полей нужно, чтобы компания была главной в холдинге
            this.companyInHolding &&
            this.companyIsMain
          ),
          'companyIsMain': this.companyIsMain,
          'targetType': {
            'user': false,
            'service': true,
            'order': false
          },
          'actions': {
            'create': true,
            'save': false
          },
          'valueType': 1,
          'valueOptionsList': null
        };
        
        if (fieldData !== undefined) {
          $.extend(params, fieldData);

          params.companyInHolding = this.companyInHolding;

          if (params.forAllCompanyInHolding && params.companyInHolding && !params.companyIsMain) {
            params.blockEdit = true;
          }

          params.targetType.user = (+fieldData.fieldCategory === 1);
          params.targetType.service = (+fieldData.fieldCategory === 2);
          params.targetType.order = (+fieldData.fieldCategory === 3);

          params.actions.create = false;
          params.actions.save = (params.blockEdit) ? false : true;

          params.valueType = +params.typeTemplate;

          if (
            Array.isArray(fieldData.availableValueList) && 
            fieldData.availableValueList.length > 0
          ) {
            params.valueType = 'optionlist';
            params.valueOptionsList = fieldData.availableValueList.join('\n');
          }
        }

        this.elem.$formContainer.html(Mustache.render(_instance.mds.tpl.customFieldForm, params));
        this.initFormControls(params);
      },
      /** 
      * Инициализация элементов управления формы редактирования доп. полей 
      * @param {Object} fieldParams - установленные параметры доп. поля
      */
      initFormControls: function(fieldParams) {
        var $valueOptionsBlock = this.elem.$formContainer.find('.js-custom-field-form--value-options-block');
        var $reasonFailTPFlag = this.elem.$formContainer.find('.js-custom-field-form--reason-fail-tp');

        
        if (+fieldParams.valueType === 4) {
          $reasonFailTPFlag.prop('disabled', true);
        }

        this.elem.$formContainer.find('.js-custom-field-form--type')
          .selectize({
            openOnFocus: true,
            allowEmptyOption: false,
            create: false,
            selectOnTab:true,
            valueField: 'value',
            labelField: 'name',
            maxItems: 1,
            options: [
              {value: 1, name: 'Текст'},
              {value: 2, name: 'Длинный текст'},
              {value: 3, name: 'Число'},
              {value: 4, name: 'Дата'},
              {value: 'optionlist', name: 'Список'}
            ],
            items: [fieldParams.valueType],
            onItemAdd: function(val) {
              if (val === 'optionlist') {
                $valueOptionsBlock.removeClass('is-hidden');
              } else {
                $valueOptionsBlock.addClass('is-hidden');
              }

              if (+val === 4) {
                $reasonFailTPFlag.prop('checked', false);
                $reasonFailTPFlag.prop('disabled', true);
              } else {
                $reasonFailTPFlag.prop('disabled', false);
              }
            }
          });
      },
      /**
      * Установка значений для списка по данным из файла
      * @param {String} optionsData - строка с опциями (через перевод строки)
      */
      setOptionValueList: function(optionsData) {
        var $optionValueList = this.elem.$formContainer.find('.js-custom-field-form--value-list');
        var options = optionsData
          .split(/\r?\n/)
          .map(function(v) { return v.trim(); })
          .join('\n');

        $optionValueList.val(options);
      },
      /**
      * Возвращает введенные данные формы добавления/редактирования доп. полей
      * @return {Object} - данные дополнительных полей для сохранения
      */
      getFormData: function() {
        var CompanyStorage = this.CompanyStorage;
        var validation = {errors: false};
        
        var $form = this.elem.$formContainer.find('.js-custom-field-form');
        var $fieldCategory = $form.find('.js-custom-field-form--target-type').filter(':checked');
        var $fieldName = $form.find('.js-custom-field-form--name');
        var $valueType = $form.find('.js-custom-field-form--type');
        var $valueList = $form.find('.js-custom-field-form--value-list');
        var $forAllHolding = $form.find('.js-custom-field--for-all-holding');

        var fieldCategory = (function(category) {
            switch (category) {
              case 'user': return 1;
              case 'service': return 2;
              default: 
                validation.errors = true;
                return null;
            }
          }($fieldCategory.val()));

        var fieldName = validateControl($fieldName, function(v) {
            return v !== '';
          }, validation);

        var availableValueList = null;
        var valueType = $valueType.val();

        if (valueType === 'optionlist') {
          valueType = 1; // Текст
          availableValueList = validateControl($valueList, function(v) {
              return v !== '';
            }, validation);
          if (availableValueList !== null) {
            availableValueList = availableValueList
              .split(/\n/)
              .map(function(v) { return v.trim(); })
              .filter(function(v) { return v !== ''; });
          }
        }

        if (validation.errors) {
          console.error('ошибка ввода данных дополнительного поля');
          return null;
        }

        var customField = {
          'companyId': CompanyStorage.companyId,
          'active': 1,
          'fieldTypeName': fieldName,
          'fieldCategory': fieldCategory,
          'availableValueList': availableValueList,
          'typeTemplate': +valueType,
          'modifyAvailable': $form.find('.js-custom-field-form--editable').prop('checked'),
          'require': $form.find('.js-custom-field-form--required').prop('checked'),
          'reasonFailTP': $form.find('.js-custom-field-form--reason-fail-tp').prop('checked'),
          'forAllCompanyInHolding': ($forAllHolding.length !== 0) ?
            $forAllHolding.prop('checked') : false
        };

        var fieldTypeId = $form.data('fieldid');
        if (fieldTypeId !== undefined) {
          customField.fieldTypeId = +fieldTypeId;
        }

        return customField;
      }
    };

    return CustomFieldTypes;
  };

  /**
  * Инициализация формы редактирования дополнительных полей пользователя
  * @param {Object} $container - контейнер для размещения объекта
  * @return {Object} - объект управления дополнительными полями компании 
  */
  modView.prototype.setUserCustomFields = function($container) {
    var _instance = this;

    var UserCustomFields = {
      elem: {
        $container: $container,
        $userSuggest: null,
        $userList: null,
        $customFieldsFormContainer: null,
        $customFieldsForm: null
      },
      // Ссылка на хранилище данных пользователей
      UsersStorage: null,
      // Фабрика дополнительных полей
      CustomFieldsFactory: null,
      // хранлище объектов управления дополнительными полями
      customFieldControls: null,
      /** Инициализация объекта управления */
      init: function() {

      },
      /** Рендер объекта управления */
      render: function(UsersStorage) {
        this.UsersStorage = UsersStorage;
        this.CustomFieldsFactory = new crate.CustomFieldsFactory(_instance.mds.tpl);

        var $userCustomFields = $(Mustache.render(_instance.mds.tpl.userCustomFields, {}));
        this.elem.$userSuggest = $userCustomFields.find('.js-user-custom-fields--user-suggest');
        this.elem.$userList = $userCustomFields.find('.js-user-custom-fields--user-list');
        this.elem.$customFieldsFormContainer = $userCustomFields.find('.js-user-custom-fields--form-container');

        this.renderUsersList(UsersStorage.getUsers());
        
        this.elem.$container.html($userCustomFields);

        this.initUserListControls();
      },
      /**
      * Рендер списка пользователей
      * @param {Array} userList - список пользователей 
      */
      renderUsersList: function(userList) {
        this.elem.$userList.html(
          userList.map(function(user) {
            return Mustache.render(_instance.mds.tpl.userListRow, user);
          }).join('')
        );
      },
      /**
      *  Инициализация элементов управления списка выбора сотрудника
      */
      initUserListControls: function() {
        var self = this;

        this.elem.$userSuggest.on('change', function() {
          self.elem.$userList.html('<tr><td>' + Mustache.render(KT.tpl.spinner, {}) + '</td></tr>');
  
          var searchString = $(this).val();
          var userList = (searchString === '') ?
            self.UsersStorage.getUsers() :
            self.UsersStorage.getUsers(searchString);

          self.renderUsersList(userList);
        });
      },
      /**
      * Рендер формы редактирования дополнительных полей пользователя
      * @param {Object} userData - данные пользователя
      */
      renderForm: function(userData) {

        userData.customFields.sort(function(a,b) {
          var typeSorting = (a.typeTemplate === 2) ?
            ((b.typeTemplate === 2) ? 0 : 1) :
            ((b.typeTemplate === 2) ? -1 : 0);
          
          if (typeSorting !== 0) {
            return typeSorting;
          } else {
            return (a.require) ?
              (b.require ? 0 : -1) :
              (b.require ? 1 : 0);
          }
        });

        this.customFieldControls = [];
        var $customFields = $();
        var self = this;
    
        userData.customFields.forEach(function(fieldData) {
          var customField = self.CustomFieldsFactory.create(fieldData);
          if (customField !== null) {
            self.customFieldControls.push(customField);
          }
        });
    
        self.customFieldControls.forEach(function(customField, idx) {
          $customFields = $customFields.add(customField.render({'idx': idx}));
        });
        
        var userName = [
          (userData.user.surname !== null) ? userData.user.surname : '',
          (userData.user.name !== null) ? userData.user.name : '',
          (userData.user.secondName !== null) ? userData.user.secondName : '',
        ].join(' ').replace(/s+/,' ');

        this.currentUserId = userData.user.userId;

        this.elem.$customFieldsForm = $(Mustache.render(_instance.mds.tpl.userCustomFieldsForm, {
          'userId': userData.user.userId,
          'documentId': userData.document.docId,
          'userName': userName
        }));
        
        this.elem.$customFieldsForm.find('.js-user-custom-fields-form--list').html($customFields);

        this.elem.$customFieldsFormContainer.html(this.elem.$customFieldsForm);
    
        self.customFieldControls.forEach(function(customField) {
          customField.initialize();
        });
      },
      /** Обновление доп. полей */
      refreshCustomFields: function() {
        var $customFields = $();

        this.customFieldControls.forEach(function(customField, idx) {
          $customFields = $customFields.add(customField.render({'idx': idx}));
        });

        this.elem.$customFieldsForm.find('.js-user-custom-fields-form--list').html($customFields);

        this.customFieldControls.forEach(function(customField) {
          customField.initialize();
        });
      },
      /** Отображение процесса сохранения формы */
      showFormSavingProcess: function() {
        this.elem.$customFieldsForm.find('.js-user-custom-fields-form--list')
          .html(Mustache.render(KT.tpl.spinner, {}));
      },
      /**
      * Возвращает данные формы дополнительных полей для сохранения
      * @return {Object} - данные формы 
      */
      getFormData: function() {
        if (Array.isArray(this.customFieldControls)) {
          var customFieldsData = [];
          var validationErrors = false;

          var userId = +this.elem.$customFieldsForm.data('userid');
          var documentId = +this.elem.$customFieldsForm.data('documentid');

          this.customFieldControls.forEach(function(customField) {
            if (!customField.active) {
              return;
            }

            var fieldValue = customField.getValue();
            var fieldId = customField.$field.data('fieldid');

            if (fieldId !== undefined) {
              fieldId = +fieldId;
            }

            if (customField.modifiable && customField.validate(fieldValue)) {
              customFieldsData.push({
                'fieldId': (fieldId !== undefined) ? +fieldId : null,
                'userId': userId,
                'fieldTypeId': customField.fieldTypeId,
                'active': customField.active,
                'value': (fieldValue !== '') ? fieldValue : null
              });
            } else { validationErrors = true; }
          });

          return (validationErrors) ? null : {
            'userId': userId,
            'documentId': documentId,
            'customFieldsData': customFieldsData
          };
        } else {
          return null;
        }
      },
      /**
      * Меняет статус активации поля и возвращает данные  для запроса активации/деактивации
      * @param {Integer} idx - индекс поля 
      * @param {Boolean} state - новое состояние поля
      * @return {Object} - данные для запроса
      */
      changeActivationState: function(idx, state) {
        var userId = +this.elem.$customFieldsForm.data('userid');
        var customField = this.customFieldControls[idx];
        customField.active = state;

        var fieldId = customField.$field.data('fieldid');

        if (fieldId !== undefined) {
          fieldId = +fieldId;
        }

        return {
          'fieldId': (fieldId !== undefined) ? +fieldId : null,
          'userId': userId,
          'fieldTypeId': customField.fieldTypeId,
          'active': customField.active,
          'value': null
        };
      }
    };

    return UserCustomFields;
  };

  /**
  * Отобразить ошибку значения в поле
  * @param {Object} $el - [jQuery DOM] поле формы
  * @param {String} [msg] - текст для плейсхолдера контрола
  */
  function makeInvalid($el, msg) {
    if (msg === undefined || msg === null) { msg = ''; }
    $el
      .addClass('error')
      .attr('data-pl', $el.attr('placeholder'))
      .attr('placeholder', msg)
      .one('focus', function() {
        $(this)
          .removeClass('error')
          .attr('placeholder', $(this).attr('data-pl'))
          .removeAttr('data-pl');
      });
  }

  /**
  * Проверка значений форм
  * @param {Object} $el - (jQuery DOM) элемент для проверки
  * @param {Function} rule - функция проверки значения, принимает значение и возвращает true/false
  * @param {Object} flag - объект флага для сохранения состояния ошибки
  * @param {String} [msg] - текст для плейсхолдера контрола
  * @return {*|Boolean} - возращает значение элемента или false в случае непрошедшей валидации
  */
  function validateControl($el, rule, flag, msg) {
    var cv = $el.val();
    if (msg === undefined || msg === null) { msg = ''; }

    if (rule(cv)) {
      return cv;
    } else {
      makeInvalid($el, msg);
      flag.errors = true;
      return null;
    }
  }


  return modView;
  
}));
  