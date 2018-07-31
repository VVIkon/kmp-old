/* Общие действия для правил корпоративных политик */
(function(global,factory) {
  
      KT.crates.ClientAdmin.TPRuleCommonEffects = factory(KT.crates.ClientAdmin);
      
  }(this, function(crate) {
    'use strict';
    
    var TPRuleEffect = crate.TPRuleEffect;

    // Конфигурация типов значений для сложных действий
    var valueTypes = {
      'bool': {
        template: 'TPREboolValue',
        getValue: function() {
          return this.$valueContainer.find('.js-travel-policy-rule-effect--value').prop('checked');
        },
        setValue: function(value) {
          this.$valueContainer.find('.js-travel-policy-rule-effect--value').prop('checked', Boolean(value));
        }
      },
      'string': {
        template: 'TPREstringValue',
        getValue: function() {
          return this.$valueContainer.find('.js-travel-policy-rule-effect--value').val();
        },
        setValue: function(value) {
          this.$valueContainer.find('.js-travel-policy-rule-effect--value').val(value);
        }
      }
    };

    /*==========Классы действий корпоративных политик===========*/

    // Удаление оффера из результатов поиска
    var deleteNonConditionOffersEffect = TPRuleEffect.extend(function() {
      TPRuleEffect.apply(this, arguments);

      this.name = this.effectName;
      this.key = 'deleteNonConditionOffers';
      this.template = null;
    });
    deleteNonConditionOffersEffect.prototype.effectName = 'Удалить из результата поиска предложения, не удовлетворяющие условиям';

    // Установка предложения с минимальной ценой
    var SetMinimalPriceValueEffect = TPRuleEffect.extend(function() {
      TPRuleEffect.apply(this, arguments);

      this.name = this.effectName;
      this.key = 'SetMinimalPriceValue';
      this.template = null;
    });
    SetMinimalPriceValueEffect.prototype.effectName = 'Установить параметры минимальной цены для услуги';

    // Установить параметр заявки
    /** @todo сделать */
    /*
    var SetOrderValueEffect = TPRuleEffect.extend(function() {
      TPRuleEffect.apply(this, arguments);

      this.name = this.effectName;
      this.key = 'SetOrderValue';
      this.template = null;
    });
    SetOrderValueEffect.prototype.effectName = 'Установить параметр заявки';
    */

    // Пометка предложения
    var markOfferEffect = TPRuleEffect.extend(function() {
      TPRuleEffect.apply(this, arguments);

      this.name = this.effectName;
      this.key = 'markOffer';
      this.template = 'markOfferTPRE';
    });
    markOfferEffect.prototype.effectName = 'Пометить предложение';

    markOfferEffect.prototype.fieldsList = { // список доступных для пометки полей
      'travelPolicyFailCode': {
        name: 'Нарушение корпоративной политики',
        placeholder: 'Код нарушения',
        valueType: 'string'
      }
    };
    
    markOfferEffect.prototype.valueTypes = valueTypes;

    markOfferEffect.prototype.init = function() {
      this.$fieldTypeSelect = this.$container.find('.js-travel-policy-rule-effect--field');
      this.$valueContainer = this.$container.find('.js-travel-policy-rule-effect--value-container');
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
          items: (this.params !== undefined) ? [this.params.field] : [],
          onItemAdd: function(field) {
            var fieldDefinition = self.fieldsList[field];
            var valueType = fieldDefinition.valueType;
            if (!self.valueTypes.hasOwnProperty(valueType)) {
              throw new Error('mark offer effect: value type template undefined: ' + valueType);
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
      if (this.params !== undefined) {
        if (!this.fieldsList.hasOwnProperty(this.params.field)) {
          return; // silent failure
        }

        var fieldDefinition = this.fieldsList[this.params.field];
        var valueType = fieldDefinition.valueType;
        if (!this.valueTypes.hasOwnProperty(valueType)) {
          throw new Error('mark offer effect: value type template undefined: ' + valueType);
        }
        var valueTypeDefinition = this.valueTypes[valueType];

        this.$valueContainer.html(Mustache.render(this.tpl[valueTypeDefinition.template], {
          'placeholder': fieldDefinition.placeholder,
          'disableEdit': this.disableEdit
        }));
        this.valueTypes[valueType].setValue.call(this, this.params.value);
      }
    };

    markOfferEffect.prototype.getParams = function() {
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
    };

    /*================Набор действий==================*/

    var TPRuleCommonEffects = {
      'deleteNonConditionOffersEffect' : deleteNonConditionOffersEffect,
      'markOfferEffect': markOfferEffect,
      //'SetOrderValueEffect': SetOrderValueEffect,
      'SetMinimalPriceValueEffect': SetMinimalPriceValueEffect
    };

    return TPRuleCommonEffects;
  }));