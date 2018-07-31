/* Общие условия для правил корпоративных политик */
(function(global,factory) {
  
      KT.crates.ClientAdmin.TPRuleCommonConds = factory(KT.crates.ClientAdmin);
      
  }(this, function(crate) {
    'use strict';
    
    var TPRuleCond = crate.TPRuleCond;

    // Конфигурация типов значений для сложных условий
    var valueTypes = { // Типы значений полей
      'bool': {
        template: 'TPRCboolValue',
        getValue: function() {
          return this.$valueContainer.find('.js-travel-policy-rule-cond--value').prop('checked');
        },
        setValue: function(value) {
          this.$valueContainer.find('.js-travel-policy-rule-cond--value').prop('checked', Boolean(value));
        }
      },
      'string': {
        template: 'TPRCstringValue',
        getValue: function() {
          return this.$valueContainer.find('.js-travel-policy-rule-cond--value').val();
        },
        setValue: function(value) {
          this.$valueContainer.find('.js-travel-policy-rule-cond--value').val(value);
        }
      }
    };

    /*==========Классы условий корпоративных политик===========*/

    // Стоимость оффера
    var offerPriceCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=','>=','<='];
      this.dataField = 'offerPrice';
      this.template = 'PriceTPRC';
    });
    offerPriceCond.prototype.condName = 'Стоимость предложения';

    // Значение параметра предложения
    var offerValueCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=','!=','>=','<='];
      this.dataField = 'offerValue';
      this.template = 'ComplexTPRC';
      this.fieldPlaceholder = 'Параметр предложения';
    });
    offerValueCond.prototype.condName = 'Параметр предложения';

    offerValueCond.prototype.fieldsList = { // список доступных для проверки полей
      'travelPolicyFailCodes': {
        name: 'Код нарушения корпоративной политики',
        valueType: 'string'
      },
      'priorityOffer': {
        name: 'Приоритетное предложение',
        valueType: 'bool'
      }
    };
    
    offerValueCond.prototype.valueTypes = valueTypes;

    // Превышение стоимости по сравнению с минимальной (cумма)
    var comparePriceWithMinimalCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=','>=','<='];
      this.dataField = 'comparePriceWithMinimal';
      this.template = 'PriceTPRC';
    }); 
    comparePriceWithMinimalCond.prototype.condName = 'Превышение стоимости по сравнению с минимальной (сумма)';
    
    // Превышение стоимости по сравнению с минимальной (процент)
    var comparePricePercentWithMinimalCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=','>=','<='];
      this.dataField = 'comparePricePercentWithMinimal';
      this.template = 'NumberTPRC';
      this.placeholder = 'Процент';
    }); 
    comparePricePercentWithMinimalCond.prototype.condName = 'Превышение стоимости по сравнению с минимальной (процент)';

    // Значение доп. поля услуги / любого туриста в услуге 
    var addFieldValueCond = TPRuleCond.extend(function(tpl, CompanyStorage) {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=','!='];
      this.dataField = 'addFieldValue';
      this.template = 'ComplexTPRC';

      /** @todo убрать хардкод? */
      var customFieldValueTypesMap = {
        1: 'string',
        2: 'string',
        3: 'string',
        4: 'string'
      };

      var customFieldCategoriesMap = {
        1: 'Поле пользователя',
        2: 'Поле услуги',
        3: 'Поле заявки'
      };

      var self = this;

      this.fieldsList = {};
      CompanyStorage.customFields
        .filter(function(customField) {
          return customField.active;
        })
        .forEach(function(customField) {
          var fieldCategory = customFieldCategoriesMap[customField.fieldCategory];
          var fieldName = customField.fieldTypeName;

          self.fieldsList[customField.fieldTypeId] = {
            name: fieldName + ' [' + fieldCategory + ']',
            valueType: customFieldValueTypesMap[customField.typeTemplate]
          };
        });
    }); 
    addFieldValueCond.prototype.condName = 'Значение доп. поля услуги / любого туриста в услуге';
    
    addFieldValueCond.prototype.fieldsList = {}; // определяется в момент создания
    
    addFieldValueCond.prototype.valueTypes = valueTypes;
    
    addFieldValueCond.prototype.initControl = function() {
      if (this.value !== undefined) {
        this.value = {
          'field': this.value.fieldTypeId,
          'value': this.value.value
        };
      }
      this.ancestor.initControl.call(this);
    };

    addFieldValueCond.prototype.getValue = function() {
      var value = this.ancestor.getValue.call(this);
      return {
        'fieldTypeId': value.field,
        'value': value.value
      };
    };

    // Минимальная стоимость предложения
    var minimalPriceCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = [];
      this.dataField = 'minimalPrice';
      this.template = 'BooleanTPRC';
      this.placeholder = 'Включать в выборку предложения с нарушением корпоративных политик';
    }); 
    minimalPriceCond.prototype.condName = 'Минимальная стоимость предложения';

    /*================Набор условий==================*/

    var TPRuleCommonConds = {
      'offerPriceCond' : offerPriceCond, // 0
      // 1
      'offerValueCond': offerValueCond,
      'comparePriceWithMinimalCond': comparePriceWithMinimalCond,
      'comparePricePercentWithMinimalCond': comparePricePercentWithMinimalCond,
      'addFieldValueCond': addFieldValueCond,
      //2
      'minimalPriceCond': minimalPriceCond
    };

    return TPRuleCommonConds;
  }));