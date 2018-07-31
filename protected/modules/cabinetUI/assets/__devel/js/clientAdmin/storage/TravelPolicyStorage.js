/* global ktStorage */
(function(global,factory){
  
      KT.storage.TravelPolicyStorage = factory();
  
  }(this,function() {
    'use strict';

    /**
    * Хранилище корпоративных политик компании
    * @module TravelPolicyStorage
    * @constructor
    * @param {Object} CompanyStorage - хранилище данных компании
    */
    var TravelPolicyStorage = ktStorage.extend(function(CompanyStorage) {
      this.namespace = 'TravelPolicyStorage';  

      this.CompanyStorage = CompanyStorage;

      this.rules = [];

      // правило для включения минимальной цены
      this.minimalPriceRule = null;
    });
  
    KT.addMixin(TravelPolicyStorage, 'Dispatcher');
  
    /**
    * Инициализация хранилища
    * @param {Object[]} travelPoliciesList - данные пользователей (сотрудников) компании
    */
    TravelPolicyStorage.prototype.initialize = function(travelPolicyRules) {
      this.rulesMap = {};
      var self = this;

      this.rules = travelPolicyRules.map(function(policyRule, idx) {
        if (policyRule.active === undefined) {
          policyRule.active = true;
        } else {
          policyRule.active = Boolean(policyRule.active);
        }

        self.rulesMap[policyRule.id] = idx;
        return policyRule;
      });

      this.dispatch('initialized', this);
    };

    /**
    * Возвращает список правил (id, название, тип услуги)
    * @param {String} [typeFilter] - возвращать правила только для определенных услуг (avia|hotel)
    */
    TravelPolicyStorage.prototype.getRulesList = function(typeFilter) {
      if (typeFilter !== undefined) {
        switch (typeFilter) {
          case 'avia': typeFilter = 2; break;
          case 'hotel': typeFilter = 1; break;
        }
      }

      var rules = (typeFilter === undefined) ? 
        this.rules : 
        this.rules.filter(function(rule) {
          return rule.serviceType === typeFilter;
        });

      return rules.map(function(rule) {
        return {
          'id': rule.id,
          'name': (rule.comment !== '') ? rule.comment : null,
          'serviceType': rule.serviceType,
          'forAllCompanyInHolding': rule.forAllCompanyInHolding,
          'active': rule.active
        };
      });
    };
    
    /**
    * Возвращает конфигурацию правила
    * @param {Integer} ruleId - идентификатор правила
    * @return {Object|null} - конфигурация правила
    */
    TravelPolicyStorage.prototype.getRule = function(ruleId) {
      var filteredRules = this.rules.filter(function(rule) {
        return rule.id === ruleId;
      });

      if (filteredRules.length === 0) {
        return null;
      } else {
        return $.extend(true, {}, filteredRules[0]);
      }
    };

    /**
    * Установка правила корпоративной политики
    * @param {Object} policyRule - конфигурация правила
    */
    TravelPolicyStorage.prototype.setRule = function(policyRule) {
      var idx = this.rulesMap[policyRule.id];
      
      if (idx !== undefined) {
        this.rules[idx] = policyRule;
      } else {
        idx = this.rules.push(policyRule) - 1;
        this.rulesMap[policyRule.id] = idx;
      }
    };

  
    return TravelPolicyStorage;
  }));
  