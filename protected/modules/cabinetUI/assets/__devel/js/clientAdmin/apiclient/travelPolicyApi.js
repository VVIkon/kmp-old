(function(global, extendApiClient) {
  
      extendApiClient(KT.apiClient);
  
  }(this,function(ApiClient) {
    'use strict';
  
    /**
    * Получение списка правил корпоративных политик
    * @param {Integer} companyId - ID компании, чей список политик нужно получить
    */
    ApiClient.getCompanyTravelPolicyRules = function(companyId) {
      var _instance = this;
      
      return KT.rest({
        caller: "clientAdmin - getCompanyTravelPolicyRules",
        data: {
          'companyId': companyId
        },
        url: _instance.urls.getCompanyTravelPolicyRules
      });
    };
    
    /**
    * Установка правила корпоративной политики
    * @param {Object} policyRule - правило (sk_travelPolicyEditRule)
    */
    ApiClient.setTPRuleForCompany = function(policyRule) {
      var _instance = this;
      
      return KT.rest({
        caller: "clientAdmin - setTPRuleForCompany",
        data: {
          'travelPolicyRules': policyRule
        },
        url: _instance.urls.setTPRuleForCompany
      });
    };
  
  
  }));