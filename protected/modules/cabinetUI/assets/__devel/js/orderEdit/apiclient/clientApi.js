(function(global, extendApiClient) {

    extendApiClient(KT.apiClient);

}(this,function(ApiClient) {

  /**
  * Саджест сотрудников компании клиента
  * @param {Object} options - опции
  * @param {Object} options.data - параметры саджеста
  * @param {Function} options.error - коллбек при ошибке
  * @param {Function} options.success - коллбэк при успехе
  */
  ApiClient.getClientUserSuggest = function(options) {
    var _instance = this;
    
    KT.rest({
        caller:"orderEdit - getClientUserSuggest",
        data: options.data,
        url: _instance.urls.getClientUserSuggest
      })
      .done(options.success)
      .fail(options.error);
  };

  /**
  * Создание/обновление сотрудника компании 
  * @param {Object} userData - данные пользователя 
  * @param {String|Integer} touristId - ID сохраняемого туриста
  */
  ApiClient.setUser = function(userData, touristId) {
    var _instance = this;
    var request = $.Deferred();

    KT.rest({
        caller:'orderEdit#tourists - setUser',
        url: _instance.urls.setUser,
        data: userData
      })
      .done(function(response) {
        response.touristId = touristId;
        request.resolve(response);
      })
      .fail(function() { request.reject(); });

    return request;
  };

  /**
  * Получение данных сотрудника компании
  * @param {Integer} documentId - ID документа пользователя
  */
  ApiClient.getClientUser = function(documentId) {
    var _instance = this;

    return KT.rest({
        caller:'orderEdit#tourists - getClientUser',
        url: _instance.urls.getClientUser,
        data: {'docId': documentId}
      });
  };

}));