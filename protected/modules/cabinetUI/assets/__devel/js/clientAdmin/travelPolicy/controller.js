(function(global,factory) {
  
      KT.crates.ClientAdmin.travelPolicy.controller = factory(KT.crates.ClientAdmin);
  
  }(this, function(crate) {
    'use strict';
    
    /**
    * Администрирование клиента: корпоративные политики
    * @param {Object} module - родительский модуль
    */
    var modController = function(module) {
      this.mds = module;
      this.mds.travelPolicy.view = new crate.travelPolicy.view(this.mds);
    };
  
    /** Инициализация событий модуля */
    modController.prototype.init = function() {
      var _instance = this;
      var modView = this.mds.travelPolicy.view;

      /*==========Обработчики событий представления============================*/

      /** Обработка инициализации хранилища данных компании */
      KT.on('CompanyStorage.initialized', function(e, CompanyStorage) {
        modView.render();

        KT.apiClient.getCompanyTravelPolicyRules(CompanyStorage.companyId)
          .then(function(response) {
            console.log('travel policy rules loaded');
            if (response.status !== 0) {
              console.error('Ошибка получения данных корпоративных политик!');
              return;
            }

            _instance.mds.TravelPolicyStorage = new KT.storage.TravelPolicyStorage(_instance.mds.CompanyStorage);
            _instance.mds.TravelPolicyStorage.initialize(response.body);
          });
      });

      /** Обработка инициализации хранилища данных корпоративных политик */
      KT.on('TravelPolicyStorage.initialized', function(e, TravelPolicyStorage) {
        modView.TravelPolicyRules.render(TravelPolicyStorage);
      });

      /*==========Обработчики событий представления============================*/

      /** Обработка запроса на сохранение правила корпоративной политики */
      modView.$travelPolicyRules.on('click', '.js-travel-policy-form--action-save', function() {
        var TravelPolicyStorage = _instance.mds.TravelPolicyStorage;
        var ruleConfig = modView.TravelPolicyRules.getFormData();

        if (ruleConfig !== null) {
          var serviceType = ruleConfig.serviceType;
          
          KT.apiClient.setTPRuleForCompany(ruleConfig)
            .then(function(response) {
              if (response.status === 0) {
                ruleConfig.id = response.body.id;
                modView.TravelPolicyRules.renderForm(ruleConfig);
                modView.TravelPolicyRules.showRulesListLoading(serviceType);
                TravelPolicyStorage.setRule(ruleConfig);
                modView.TravelPolicyRules.renderRulesList(serviceType);

                if (ruleConfig.id !== undefined) {
                  KT.notify('travelPolicyRuleUpdated', {'ruleName': ruleConfig.comment});
                } else {
                  KT.notify('travelPolicyRuleCreated', {'ruleName': ruleConfig.comment});
                }
              } else {
                KT.notify('travelPolicyRuleSavingFailed', response.errors);
              }
            });
        }
      });
      
      /** Обработка запроса на включение/отключение правила политики */
      modView.$travelPolicyRules.on('click', 
        '.js-travel-policy-rule--action-disable, .js-travel-policy-rule--action-enable',  
        function() {
          var activeStatus = $(this).hasClass('js-travel-policy-rule--action-enable');

          var TravelPolicyStorage = _instance.mds.TravelPolicyStorage;
          var $rule = $(this).closest('.js-travel-policy-rule');
          var ruleId = +$rule.data('ruleid');

          var policyRule = TravelPolicyStorage.getRule(ruleId);
          var serviceType = policyRule.serviceType;
          policyRule.active = activeStatus;

          KT.apiClient.setTPRuleForCompany(policyRule)
            .then(function(response) {
              // если данные поля приняты, запрашиваем данные компании с доп. полями для обновления
              if (response.status === 0) {
                modView.TravelPolicyRules.showRulesListLoading(serviceType);
                TravelPolicyStorage.setRule(policyRule);
                modView.TravelPolicyRules.renderRulesList(serviceType);

                KT.notify('travelPolicyRuleUpdated', {'fieldName': policyRule.comment});
              } else {
                KT.notify('travelPolicyRuleSavingFailed', response.errors);
              }
            });
      });

    };
  
    return modController;
  
  }));
  