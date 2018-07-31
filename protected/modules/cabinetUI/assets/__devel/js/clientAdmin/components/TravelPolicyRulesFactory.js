/* Фабрика обработчиков правил корпоративных политик */
(function(global,factory) {
  
      KT.crates.ClientAdmin.TravelPolicyRulesFactory = factory(KT.crates.ClientAdmin);
      
  }(this, function(crate) {
    'use strict';

    var ruleTypes = {
      0: 'Поиск',
      1: 'Оформление',
      2: 'Создание услуги'
    };
  
    /** 
    * Класс правила корпоративной политики
    * @param {Object} tpl - набор шаблонов
    * @param {Object} CompanyStorage - хранилище данных комании, для которой создается правило
    */
    var TravelPolicyRule = function(tpl, CompanyStorage) {
      this.tpl = tpl;
      this.CompanyStorage = CompanyStorage;

      this.companyId = CompanyStorage.companyId;

      this.companyInHolding = (this.CompanyStorage.roleType === 'corp');
      this.companyIsMain = (this.CompanyStorage.holdingCompany === null);
      this.disableEdit = false;

      this.serviceType = null;
      this.ruleType = null;
      // набор условий, доступных для этого правила
      this.conditionSet = {};
      // набор действий (эффектов), доступных для этого правила
      this.effectSet = {};
      // условия правила
      this.conditions = [];
      // действия (эффекты) правила
      this.effects = [];
    };

    // наборы условий и действий политик в зависимости от услуги
    TravelPolicyRule.ruleSets = {
      1: { /** @todo настроить проживание */
        name: 'Проживание',
        conditionSet: crate.hotelTPRuleCondSet,
        effectSet: crate.hotelTPRuleEffectSet
      },
      2: {
        name: 'Перелет',
        conditionSet: crate.aviaTPRuleCondSet,
        effectSet: crate.aviaTPRuleEffectSet
      }
    };

    /** 
    * Установка типа услуги
    * @param {Integer:null} serviceType - тип услуги (null для сброса)
    */
    TravelPolicyRule.prototype.setServiceType = function(serviceType) {
      if (serviceType !== null) {
        this.serviceType = +serviceType;
        // доступные типы правил для услуги
        /** @todo если возможно правило с действиями без условий, поменять на effectSet */
        this.availableRuleTypes = Object.keys(TravelPolicyRule.ruleSets[this.serviceType].conditionSet);
      } else {
        this.setRuleType(null);
        this.serviceType = null;
        this.availableRuleTypes = [];
      }
    };

    /** 
    * Установка типа правила
    * @param {Integer|null} ruleType - тип правила (null для сброса)
    */
    TravelPolicyRule.prototype.setRuleType = function(ruleType) {
      if (ruleType !== null) {
        if (this.serviceType === null) { 
          throw new Error('TravelPolicyRule: service type should be set before rule type!');
        }
        this.ruleType = +ruleType;
        this.conditionSet = TravelPolicyRule.ruleSets[this.serviceType].conditionSet[ruleType];
        this.effectSet = TravelPolicyRule.ruleSets[this.serviceType].effectSet[ruleType];
      } else {
        this.ruleType = null;
        this.conditionSet = {};
        this.effectSet = {};
      }
    };

    /** 
    * Установка параметров правила из готового объекта
    * @param {Object} rule - объект правила (из GetTPlistForCompany)
    */
    TravelPolicyRule.prototype.initialize = function(rule) {
      this.setServiceType(rule.serviceType);
      this.setRuleType(rule.ruleType);

      this.id = rule.id;
      this.disableEdit = (rule.forAllCompanyInHolding && this.companyInHolding && !this.companyIsMain);
      this.name = (rule.comment !== '') ? rule.comment : null;
      this.companyId = (rule.companyId !== undefined) ? rule.companyId : null;
      // this.supplierIds = rule.supplierId;
      this.forAllCompanyInHolding = rule.forAllCompanyInHolding;

      var self = this;

      rule.conditions.forEach(function(condConfig) {
        self.addCondition(condConfig);
      });

      rule.actions.forEach(function(effectConfig) {
        self.addEffect(effectConfig);
      });
    };

    /**
    * Рендер интерфейса настройки правила
    * @return {Object} - [jQuery] объект интерфейса
    */
    TravelPolicyRule.prototype.render = function() {

      var $rule = $(Mustache.render(this.tpl.travelPolicyFormRule, {
        'name': this.name,
        'companyInHolding': (this.id !== undefined) ? 
          this.companyInHolding :
          this.companyInHolding && this.companyIsMain, // для создания холдинговых полей нужно, чтобы компания была главной в холдинге
        'companyIsMain': this.companyIsMain,
        'forAllCompanyInHolding': this.forAllCompanyInHolding,
        'disableEdit': this.disableEdit
      }));

      this.$name = $rule.find('.js-travel-policy-form-rule--name');
      this.$forAllHolding = $rule.find('.js-travel-policy-form-rule--for-all-holding');
      this.$serviceTypeSelect = $rule.find('.js-travel-policy-form-rule--service-type');
      this.$ruleTypeSelect = $rule.find('.js-travel-policy-form-rule--type');
      this.$ruleConditions = $rule.find('.js-travel-policy-form-rule--conditions');
      this.$ruleEffects = $rule.find('.js-travel-policy-form-rule--effects');
      this.$addConditionAction = $rule.find('.js-travel-policy-form-rule--action-add-condition');
      this.$addEffectAction = $rule.find('.js-travel-policy-form-rule--action-add-effect');

      this.$rule = $rule;
      return this.$rule;
    };

    /** Инициализация элементов управления  */
    TravelPolicyRule.prototype.initControls = function() {

      var self = this;
      
      this.$ruleTypeSelect.selectize({
        openOnFocus: true,
        allowEmptyOption: false,
        create: false,
        selectOnTab: true,
        valueField: 'value',
        labelField: 'name',
        maxItems: 1,
        options: (this.id === undefined) ? [] :
          self.availableRuleTypes.map(function(type) {
            return {value: type, name: ruleTypes[type]};
          }),
        items: (this.id !== undefined) ? [this.ruleType] : [],
        onItemAdd: function(ruleType) {
          self.resetRule();
          self.setRuleEditState(true);
          self.setRuleType(+ruleType);
        },
        onItemRemove: function() {
          self.setRuleType(null);
          self.resetRule();
          self.setRuleEditState(false);
        }
      });

      this.$serviceTypeSelect.selectize({
        openOnFocus: true,
        allowEmptyOption: false,
        create: false,
        selectOnTab: true,
        valueField: 'value',
        labelField: 'name',
        maxItems: 1,
        options: Object.keys(TravelPolicyRule.ruleSets).map(function(serviceType) {
          return {
            value: serviceType,
            name: TravelPolicyRule.ruleSets[serviceType].name
          };
        }),
        items: (this.id !== undefined) ? [this.serviceType] : [],
        onItemAdd: function(serviceType) {
          self.setServiceType(serviceType);

          self.resetRule();
          self.setRuleEditState(false);

          self.$ruleTypeSelect[0].selectize.clear();
          self.$ruleTypeSelect[0].selectize.enable();
          self.$ruleTypeSelect[0].selectize.addOption(
            self.availableRuleTypes.map(function(type) {
              return {value: type, name: ruleTypes[type]};
            })
          );
        },
        onItemRemove: function() {
          self.$ruleTypeSelect[0].selectize.clear();
          self.setServiceType(null);
          self.resetRule();
          self.setRuleEditState(false);
        }
      });

      if (this.id !== undefined && !this.disableEdit) {
        this.$serviceTypeSelect[0].selectize.disable();
        this.$ruleTypeSelect[0].selectize.disable();
        self.setRuleEditState(true);
      }

      if (this.conditions.length > 0) {
        this.conditions.forEach(function(condition) {
          self.renderCondition(condition);
        });
      }

      if (this.effects.length > 0) {
        this.effects.forEach(function(effect) {
          self.renderEffect(effect);
        });
      }

      this.$addConditionAction.on('click', function() {
        self.renderCondition(self.addCondition());
      });

      this.$addEffectAction.on('click', function() {
        self.renderEffect(self.addEffect());
      });
    };

    /** 
    * Добавляет условие к правилу 
    * @param {Object} [condConfig] - конфигурация условия (so_TP_ServiceCondition)
    * @return {Object} - ссылка на объект
    */
    TravelPolicyRule.prototype.addCondition = function(condConfig) {
      var condition = {
        $container: $(Mustache.render(this.tpl.travelPolicyFormCondition, {
            'disableEdit': this.disableEdit
          })),
        cond: null
      };

      condition.$selector = condition.$container.find('.js-travel-policy-form-condition--cond');
      condition.$body = condition.$container.find('.js-travel-policy-form-condition--body');
      condition.idx = this.conditions.push(condition) - 1;

      if (condConfig !== undefined) {
        if (this.conditionSet[condConfig.serviceField] === undefined) {
          KT.notify('brokenTravelPolicyRule');
          return;
        }

        condConfig.disableEdit = this.disableEdit;

        condition.cond = new this.conditionSet[condConfig.serviceField](this.tpl, this.CompanyStorage, condConfig);
        condition.$body.html(condition.cond.render());
      }

      return condition;
    };

    /**
    * Отрисовка условия корпоративной политики 
    * @param {Object} condition - ссылка на условие
    */
    TravelPolicyRule.prototype.renderCondition = function(condition) {
      var self = this;
      
      this.$ruleConditions.append(condition.$container);

      condition.$selector.selectize({
        openOnFocus: true,
        allowEmptyOption: false,
        create: false,
        selectOnTab: true,
        valueField: 'value',
        labelField: 'name',
        maxItems: 1,
        options: Object.keys(self.conditionSet).map(function(cond) {
          return {
            value: cond,
            name: self.conditionSet[cond].prototype.condName
          };
        }),
        items: (condition.cond !== null) ? [condition.cond.dataField] : [],
        onItemAdd: function(cond) {
          condition.cond = new self.conditionSet[cond](self.tpl, self.CompanyStorage);
          condition.$body.html(condition.cond.render());
          condition.cond.init();
        },
        onItemRemove: function() {
          condition.$body.empty();
        }
      });

      if (condition.cond !== null) {
        condition.cond.init();
      }

      var $removeBtn = condition.$container.find('.js-travel-policy-form-condition--action-remove');
      var $removeConfirm = condition.$container.find('.js-travel-policy-form-condition--remove-confirm');
      var $removeCompletelyBtn = condition.$container.find('.js-travel-policy-form-condition--action-remove-completely');

      $removeBtn.on('click', function(e) {
        $removeConfirm.show();
        e.stopPropagation();
        $('body').one('click.tprulecond', function() {
          $removeConfirm.hide();
        });
      });

      $removeCompletelyBtn.on('click', function() {
        $('body').off('click.tprulecond');
        self.conditions.splice(condition.idx);
        condition.$container.remove();
      });
    };

    /**
    * Добавляет действие к правилу
    * @param {Object} [effectConfig] - конфигурация действия (so_TP_ServiceCondition)
    * @return {Object} - ссылка на объект
    */
    TravelPolicyRule.prototype.addEffect = function(effectConfig) {
      var effect = {
        $container: $(Mustache.render(this.tpl.travelPolicyFormEffect, {
            'disableEdit': this.disableEdit
          })),
        eff: null
      };

      effect.$selector = effect.$container.find('.js-travel-policy-form-effect--eff');
      effect.$body = effect.$container.find('.js-travel-policy-form-effect--body');
      effect.idx = this.effects.push(effect) - 1;

      if (effectConfig !== undefined) {
        if (!this.effectSet.hasOwnProperty(effectConfig.action)) {
          console.error('действие не определено: ' + effectConfig.action);
          console.log(effectConfig);
          console.log(this.effectSet);
          return;
        }
        
        effectConfig.disableEdit = this.disableEdit;
        
        effect.eff = new this.effectSet[effectConfig.action](this.tpl, this.CompanyStorage, effectConfig);
        effect.$body.html(effect.eff.render());
      }

      return effect;
    };
    
    /**
    * Отрисовка действия корпоративной политики 
    * @param {Object} effect - ссылка на действие
    */
    TravelPolicyRule.prototype.renderEffect = function(effect) {
      var self = this;
      
      this.$ruleEffects.append(effect.$container);

      effect.$selector.selectize({
        openOnFocus: true,
        allowEmptyOption: false,
        create: false,
        selectOnTab: true,
        valueField: 'value',
        labelField: 'name',
        maxItems: 1,
        options: Object.keys(self.effectSet).map(function(eff) {
          return {
            value: eff,
            name: self.effectSet[eff].prototype.effectName
          };
        }),
        items: (effect.eff !== null) ? [effect.eff.key] : [],
        onItemAdd: function(eff) {
          effect.eff = new self.effectSet[eff](self.tpl, self.CompanyStorage);
          effect.$body.html(effect.eff.render());
          effect.eff.init();
        },
        onItemRemove: function() {
          effect.$body.empty();
        }
      });

      if (effect.eff !== null) {
        effect.eff.init();
      }

      var $removeBtn = effect.$container.find('.js-travel-policy-form-effect--action-remove');
      var $removeConfirm = effect.$container.find('.js-travel-policy-form-effect--remove-confirm');
      var $removeCompletelyBtn = effect.$container.find('.js-travel-policy-form-effect--action-remove-completely');

      $removeBtn.on('click', function(e) {
        $removeConfirm.show();
        e.stopPropagation();
        $('body').one('click.tpruleeffect', function() {
          $removeConfirm.hide();
        });
      });

      $removeCompletelyBtn.on('click', function() {
        $('body').off('click.tpruleeffect');
        self.effects.splice(effect.idx);
        effect.$container.remove();
      });
    };

    /**
    * Возвращает конфигурацию правила для сохранения 
    * @return {Object|null} - конфигурация правила (sk_travelPolicyEditRule)
    */
    TravelPolicyRule.prototype.getRuleConfig = function() {
      if (this.serviceType === null) {
        this.$serviceTypeSelect[0].selectize.$control
          .addClass('error')
          .one('click',function() { $(this).removeClass('error'); });
        return null;
      }

      if (this.ruleType === null) {
        this.$ruleTypeSelect[0].selectize.$control
          .addClass('error')
          .one('click',function() { $(this).removeClass('error'); });
        return null;
      }

      if (this.conditions.length === 0 && this.effects.length === 0 ) {
        /** @todo вызвать уведомление? или может такие правила можно? */
        console.error('cannot create rule without conditions and actions');
        return null;
      }

      var forAllHolding = (this.$forAllHolding.length !== 0) ?
        this.$forAllHolding.prop('checked') :
        Boolean(this.forAllCompanyInHolding);

      var ruleConfig = {
        comment: this.$name.val(),
        active: true,
        companyId: this.companyId,
        serviceType: this.serviceType,
        ruleType: this.ruleType,
        forAllCompanyInHolding: forAllHolding,
        conditions: [],
        actions: []
      };

      if (this.id !== undefined) {
        ruleConfig.id = this.id;
      }

      ruleConfig.conditions = this.conditions.map(function(condition) {
        return condition.cond.getCondConfig();
      });
      
      ruleConfig.actions = this.effects.map(function(effect) {
        var effectConfig = effect.eff.getEffectConfig();
        effectConfig.actionType = ruleConfig.ruleType;
        return effectConfig;
      });

      return ruleConfig;
    };

    /** 
    * Очистка блока редактирования правила 
    */
    TravelPolicyRule.prototype.resetRule = function() {
      this.conditions = [];
      this.$ruleConditions.empty();
      this.$ruleEffects.empty();
    };

    /**
    * Установка возможности редактирования условий и действий правила
    * @param {Boolean} state - состояние (true - можно редактировать)
    */
    TravelPolicyRule.prototype.setRuleEditState = function(state) {
      var isDisabled = !Boolean(state);
      this.$addConditionAction.prop('disabled', isDisabled);
      this.$addEffectAction.prop('disabled', isDisabled);
    };
  
  
    /*=================================
    *  Фабрика правил корпоратиных политик
    *=================================*/

    /**
    * @param {Object} tpl - список шаблонов 
    * @param {Object} CompanyStorage - хранилище данных компании, для которой будут создаваться правила
    */
    var TravelPolicyRulesFactory = function(tpl, CompanyStorage) {
      this.tpl = tpl;
      this.CompanyStorage = CompanyStorage;
    };
      
    /**
    * Создает объект управления правилом корпоратиной политики
    */
    TravelPolicyRulesFactory.prototype.create = function() {
      return new TravelPolicyRule(this.tpl, this.CompanyStorage);
    };
  
    return TravelPolicyRulesFactory;
  
  }));