(function(global, extendApiClient) {

    extendApiClient(KT.apiClient);

}(this,function(ApiClient) {
  'use strict';

  /**
  * Получение списка заявок
  * @param {Object} params - параметры команды
  */
  ApiClient.getOrderList = function(params) {
    var _instance = this;

    params.detailsType = 'long';
    params.getInCurrency = KT.profile.viewCurrency;
    
    if (params.offset === undefined) {
      params.offset = 0;
    }
    
    return KT.rest({
        caller:"orderList - getOrderList",
        data: params,
        url: _instance.urls.getOrderList
      });
  };


}));