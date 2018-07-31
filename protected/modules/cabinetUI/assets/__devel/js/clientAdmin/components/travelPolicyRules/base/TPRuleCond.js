/* Набор правил корпоратиных полити для авиа */
(function(global,factory) {
  
      KT.crates.ClientAdmin.TPRuleCond = factory();
      
  }(this, function() {
    'use strict';
    
    // список операторов
    var operatorsMap = {
      '=': 'равно',
      '<=': 'меньше или равно',
      '>=': 'больше или равно',
      '<': 'меньше',
      '>': 'больше',
      '<>': 'не равно',
      '!=': 'не равно',
      'IN': 'одно из значений',
      'NOT IN': 'не одно из значений'
    };
    
    // часы 🕑 🕒 🕔
    var hours = [];
    for (var i = 0; i < 24; i++) {
      hours.push({value: i, title: (i + ':00')});
    }

    // Параметры шаблонов
    var templatesMap = {
      'NumberTPRC': {
        getValue: function() {
          return this.$container.find('.js-travel-policy-rule-cond--control').val();
        }
      },
      'StringTPRC': {
        getValue: function() {
          return this.$container.find('.js-travel-policy-rule-cond--control').val();
        }
      },
      'BooleanTPRC': {
        getValue: function() {
          return this.$container.find('.js-travel-policy-rule-cond--control').prop('checked');
        }
      },
      'HourIntervalHitTPRC': {
        initControl: function() {
          this.$intervalFrom = this.$container.find('.js-travel-policy-rule-cond--control-from');
          this.$intervalTo = this.$container.find('.js-travel-policy-rule-cond--control-to');
          this.$checkPresense = this.$container.find('.js-travel-policy-rule-cond--control-switch');

          $()
            .add(this.$intervalFrom)
            .add(this.$intervalTo)
              .selectize({
                openOnFocus: true,
                allowEmptyOption: false,
                create: false,
                selectOnTab: true,
                valueField: 'value',
                labelField: 'title',
                maxItems: 1,
                options: hours
              });
        },
        getValue: function() {
          return {
            'fromHour': this.$intervalFrom.val(),
            'toHour': this.$intervalTo.val(),
            'value': this.$checkPresense.prop('checked')
          };
        }
      },
      'HourIntervalIntersectionTPRC': {
        initControl: function() {
          this.$intervalFrom = this.$container.find('.js-travel-policy-rule-cond--control-from');
          this.$intervalTo = this.$container.find('.js-travel-policy-rule-cond--control-to');
          this.$time = this.$container.find('.js-travel-policy-rule-cond--control-time');
          
          $()
            .add(this.$intervalFrom)
            .add(this.$intervalTo)
              .selectize({
                openOnFocus: true,
                allowEmptyOption: false,
                create: false,
                selectOnTab: true,
                valueField: 'value',
                labelField: 'title',
                maxItems: 1,
                options: hours
              });
        },
        getValue: function() {
          return {
            'fromHour': this.$intervalFrom.val(),
            'toHour': this.$intervalTo.val(),
            'value': this.$time.val()
          };
        }
      },
      'OptionListTPRC': {
        getValue: function() {
          var control = this.$container.find('.js-travel-policy-rule-cond--control')[0].selectize;
          var value = control.getValue();

          if (control.settings.mode === 'multi') {
            if (typeof value === 'string') {
              return (value !== '') ? value.split(',') : [];
            } else { return []; } /** @todo validate empty ruleset */
          } else {
            return value;
          }
        }
      },
      'PriceTPRC': {
        initControl: function() {
          this.$price = this.$container.find('.js-travel-policy-rule-cond--control-price');
          this.$currency = this.$container.find('.js-travel-policy-rule-cond--control-currency');

          /** @todo нормальный справочник валют? */
          var currencyOptions = Object.keys(KT.getCatalogInfo.lcurrency).map(function(currency) {
            return {value: currency};
          });
          if (this.value !== undefined) {
            currencyOptions.push({value: this.value.currency});
          }

          this.$currency.selectize({
            openOnFocus: true,
            allowEmptyOption: false,
            create: true,
            selectOnTab: true,
            valueField: 'value',
            labelField: 'value',
            maxItems: 1,
            options: currencyOptions,
            items: (this.value !== undefined) ? [this.value.currency] : [currencyOptions[0].value],
            render: {
              'option_create': function(data, escape) {
                return '<div class="create">Добавить <strong>' + escape(data.input) + '</strong>&hellip;</div>';
              }
            }
          });
        },
        getValue: function() {
          return {
            'price': this.$price.val(),
            'currency': this.$currency.val()
          };
        }
      },
      'ComplexTPRC': {
        initControl: function() {
          this.$fieldTypeSelect = this.$container.find('.js-travel-policy-rule-cond--field');
          this.$valueContainer = this.$container.find('.js-travel-policy-rule-cond--value-container');
          var self = this;
    
          this.$fieldTypeSelect
            .selectize({
              openOnFocus: true,
              allowEmptyOption: false,
              create: false,
              selectOnTab: true,
              valueField: 'field',
              labelField: 'name',
              maxItems: 1,
              options: Object.keys(self.fieldsList).map(function(field) {
                return {
                  field: field,
                  name: self.fieldsList[field].name
                };
              }),
              items: (this.value !== undefined) ? [this.value.field] : [],
              onItemAdd: function(field) {
                var fieldDefinition = self.fieldsList[field];
                var valueType = fieldDefinition.valueType;
                if (!self.valueTypes.hasOwnProperty(valueType)) {
                  throw new Error(this.dataField + ': value type template undefined: ' + valueType);
                }
                var valueTypeDefinition = self.valueTypes[valueType];
    
                self.$valueContainer.html(Mustache.render(self.tpl[valueTypeDefinition.template], {
                  'placeholder': fieldDefinition.placeholder
                }));
              },
              onItemRemove: function() {
                self.$valueContainer.empty();
              },
              onClear: function() {
                self.$valueContainer.empty();
              }
            });
    
          // отрисовка блока значения, если значение задано
          if (this.value !== undefined) {
            if (!this.fieldsList.hasOwnProperty(this.value.field)) {
              return; // silent failure
            }
    
            var fieldDefinition = this.fieldsList[this.value.field];
            var valueType = fieldDefinition.valueType;
            if (!this.valueTypes.hasOwnProperty(valueType)) {
              throw new Error('mark offer effect: value type template undefined: ' + valueType);
            }
            var valueTypeDefinition = this.valueTypes[valueType];
    
            this.$valueContainer.html(Mustache.render(this.tpl[valueTypeDefinition.template], {
              'placeholder': fieldDefinition.placeholder
            }));
            this.valueTypes[valueType].setValue.call(this, this.value.value);
          }
        },
        getValue: function() {
          var field = this.$fieldTypeSelect[0].selectize.getValue();
          if (field === '') {
            throw new Error('mark offer: field not set');
          }
    
          var fieldOption = this.fieldsList[field];
          var valueGetter = this.valueTypes[fieldOption.valueType].getValue;
    
          return {
            'field': field,
            'value': valueGetter.call(this)
          };
        }
      }
    };

    /** 
    * Базовый класс условия правила корпоративной политики 
    * Только для переопределения!
    * @param {Object} tpl - список шаблонов
    * @param {Object} CompanyStorage - хранилище данных компании
    * @param {Object} [config] - конфигурация условия
    */
    var TPRuleCond = function(tpl, CompanyStorage, config) {
      this.tpl = tpl;
      this.CompanyStorage = CompanyStorage;

      this.name = 'base rule condition';
      this.operators = [];
      this.dataField = null;
      this.template = null;

      if (config !== undefined) {
        // преобразование второго варианта оператора "не равно"
        if (config.condition === '<>') {
          config.condition = '!=';
        }
        this.selectedOperator = config.condition;
        if (this.operators.indexOf(config.condition) === -1) {
          this.operators.push(config.condition);
        }

        this.value = config.value;
        this.disableEdit = config.disableEdit;
      }

      this.$container = null;
      this.$operatorSelect = null;
    };
    
    /** Механизм наследования */
    TPRuleCond.extend = function (cfunc) {
      cfunc.prototype = Object.create(this.prototype);
      cfunc.prototype.ancestor = this.prototype;
      cfunc.extend = this.extend;
      cfunc.constructor = cfunc;
      return cfunc;
    };

    /** 
    * Рендер блока настройки условия
    * @return {Object} - блок настройки условия
    */
    TPRuleCond.prototype.render = function() {
      this.$container = $(Mustache.render(this.tpl[this.template], this));
      this.$operatorSelect = this.$container.find('.js-travel-policy-rule-cond--operator');
      return this.$container;
    };

    /** Инициализация элемента управления */
    TPRuleCond.prototype.init = function() {
      this.initOperatorSelect();
      this.initControl();
    };

    /** Инициализирует блок выбора оператора */
    TPRuleCond.prototype.initOperatorSelect = function() {
      if (this.operators.length === 0) {
        this.selectedOperator = '';
        return;
      }

      var availableOperators = this.operators.map(function(op) {
        return {
          'name': operatorsMap[op],
          'value': op
        };
      });

      this.$operatorSelect
        .selectize({
          openOnFocus: true,
          allowEmptyOption: false,
          create: false,
          selectOnTab: true,
          valueField: 'value',
          labelField: 'name',
          maxItems: 1,
          options: availableOperators,
          items: (this.selectedOperator !== undefined) ? 
            [this.selectedOperator] : 
            [this.operators[0]] 
        });
    };

    /** Инициализирует элемент(-ы) выбора значения */
    TPRuleCond.prototype.initControl = function() {
      if (!templatesMap.hasOwnProperty(this.template)) {
        throw new Error('travel policy condition template params not set: ' + this.template);
      }

      if (templatesMap[this.template].initControl !== undefined) {
        templatesMap[this.template].initControl.call(this);
      }
    };
    
    /** 
    * Валидация введенных данных
    * @return {Boolean} - результат проверки
    */
    TPRuleCond.prototype.validate = function() { return true; };

    /**
    * Возвращает значение условия в структуре, соответствующей шаблону
    * @return {*} - значение
    */
    TPRuleCond.prototype.getValue = function() {
      if (!templatesMap.hasOwnProperty(this.template)) {
        throw new Error('travel policy condition template params not set: ' + this.template);
      }
      return templatesMap[this.template].getValue.call(this);
    };

    /** 
    * Возвращает конфигурацию условия для сохранения
    * @return {Object} - конфигурация условия (so_TP_ServiceCondition)
    */
    TPRuleCond.prototype.getCondConfig = function() {
      if (this.getValue === undefined) {
        throw new Error(this.name + ': data retrieval method not defined (getValue)');
      }

      /** @todo добавить валидацию */

      return {
        'comment': this.name,
        'serviceField': this.dataField,
        'condition': (this.operators.length > 0) ? this.$operatorSelect[0].selectize.getValue() : '',
        'value': this.getValue()
      };
    };

    return TPRuleCond;
  }));