(function(global, extendApiClient) {
  
      extendApiClient(KT.apiClient);
  
  }(this,function(ApiClient) {
    'use strict';
  
    /**
    * Изменение/создание нового дополнительного поля
    * @param {Object} customField - параметры дополнительного поля
    */
    ApiClient.setCustomFieldType = function(customField) {
      var _instance = this;
      
      return KT.rest({
        caller: "clientAdmin - setAddFieldType",
        data: {
          'addFieldType': customField
        },
        url: _instance.urls.setAddFieldType
      });
    };
    
    /**
    * Сохранение дополнительного поля пользователя
    * @param {Object} customField - параметры дополнительного поля
    */
    ApiClient.setUserAddField = function(customField) {
      var _instance = this;
      
      return KT.rest({
        caller: "clientAdmin - setUserAddField",
        data: {
          'userCorporateField': customField
        },
        url: _instance.urls.setUserAddField
      });
    };
  
  
  }));