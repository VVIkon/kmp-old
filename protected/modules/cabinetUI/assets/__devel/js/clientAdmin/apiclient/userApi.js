(function(global, extendApiClient) {
  
      extendApiClient(KT.apiClient);
  
  }(this,function(ApiClient) {
    'use strict';
    
    /**
    * Получение списка пользователей
    * @param {Object} params - параметры запроса списка
    */
    ApiClient.getUserSuggest = function(params) {
      var _instance = this;
  
      return KT.rest({
          caller:'clientAdmin - getUserSuggest',
          url: _instance.urls.getUserSuggest,
          data: params
        });
    };
    
    /**
    * Получение списка пользователей
    * @param {Object} params - параметры запроса списка
    */
    ApiClient.getClientUserSuggest = function(params) {
      var _instance = this;
  
      return KT.rest({
          caller:'clientAdmin - getClientUserSuggest',
          url: _instance.urls.getClientUserSuggest,
          data: params
        });
    };

    /**
    * Получение данных сотрудника компании
    * @param {Integer} documentId - ID документа пользователя
    */
    ApiClient.getClientUser = function(documentId) {
      var _instance = this;
  
      return KT.rest({
          caller:'clientAdmin - getClientUser',
          url: _instance.urls.getClientUser,
          data: {'docId': documentId}
        });
    };
  
  }));