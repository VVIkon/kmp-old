(function(global, extendApiClient) {
  
      extendApiClient(KT.apiClient);
  
  }(this,function(ApiClient) {
    'use strict';
    
  /**
  * Получение результатов саджеста городов
  * @param {Object} query - строка запроса
  * @param {String} serviceType - тип услуги (avia|hotel)
  */
  ApiClient.getLocationSuggest = function(query, serviceType) {
    var _instance = this;
    var request = $.Deferred();
    var suggestUrl;

    var params = {
      lang: 'ru',
      location: query
    };

    switch (serviceType) {
      case 'avia':
        params.serviceType = 2;
        suggestUrl = _instance.urls.getAviaLocationSuggest;
        break;
      case 'hotel':
        params.serviceType = 1;
        suggestUrl = _instance.urls.getHotelLocationSuggest;
        break;
      default:
        throw new Error('getLocationSuggest: unknown service type: ' + serviceType);
    }

    KT.rest({
        caller: "clientAdmin - getSuggestLocation",
        data: params,
        url: suggestUrl
      })
      .then(function(response) {
        response.query = query;
        request.resolve(response);
      })
      .fail(function() {
        request.reject(query);
      });

      return request.promise();
  };

  /**
  * Возвращает локацию в структуре саджеста по ID города (😠)
  * @param {Integer} cityId - ID города
  * @param {String} serviceType - тип услуги (avia|hotel)
  */
  ApiClient.getLocationById = function(cityId, serviceType) {
    var _instance = this;
    var request = $.Deferred();
    var suggestUrl;

    var params = {
      lang: 'ru',
      location: '',
      cityId: cityId
    };
    
    switch (serviceType) {
      case 'avia':
        params.serviceType = 2;
        suggestUrl = _instance.urls.getAviaLocationSuggest;
        break;
      case 'hotel':
        params.serviceType = 1;
        suggestUrl = _instance.urls.getHotelLocationSuggest;
        break;
      default:
        throw new Error('getLocationSuggest: unknown service type: ' + serviceType);
    }

    KT.rest({
        caller: "clientAdmin - getSuggestLocation",
        data: params,
        url: suggestUrl
      })
      .then(function(response) {
        if (response.status === 0 && response.body.length > 0) {
          response.body[0].cityId = cityId;
          request.resolve(response.body);
        } else {
          request.resolve([]);
        }
      })
      .fail(function() { request.reject(); });

      return request.promise();
  };
    
  /**
  * Получение результатов саджеста отелей
  * @param {Object} query - строка запроса
  */
  ApiClient.getHotelSuggest = function(query) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      lang: 'ru',
      hotelName: query,
      maxCount: 300
    };

    KT.rest({
        caller: "clientAdmin - getHotelSuggest",
        data: params,
        url: _instance.urls.getHotelSuggest
      })
      .then(function(response) {
        response.query = query;
        request.resolve(response);
      })
      .fail(function() {
        request.reject(query);
      });

      return request.promise();
  };

}));