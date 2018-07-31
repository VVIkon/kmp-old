(function(global, extendApiClient) {
  
  extendApiClient(KT.apiClient);

}(this,function(ApiClient) {

  /**
  * Получение информации по туристам заявки
  * @param {Integer} orderId - ID заявки
  */
  ApiClient.getOrderTourists = function(orderId) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      'orderId':orderId
    };

    KT.rest({
        caller:'orderEdit#tourists - getOrderTourists',
        data: params,
        url: _instance.urls.getOrderTourists
      })
      .done(function (response) {
        /** @todo API patch; deprecated with KT-1351 */
        if (response.status === 0 && Array.isArray(response.body.tourists)) {
          response.body.tourists.forEach(function(tourist) {
            if (tourist.surName !== undefined) {
              tourist.lastName = tourist.surName;
              delete tourist.surName;
            }
            if (tourist.document.surName !== undefined) {
              tourist.document.lastName = tourist.document.surName;
              delete tourist.document.surName;
            }
            if (tourist.document.serialNum !== undefined) {
              tourist.document.serialNumber = tourist.document.serialNum;
              delete tourist.document.serialNum;
            }
          });
        }
        /** @todo end API patch */
        request.resolve(response);
      })
      .fail(function() { request.reject(); });

    return request;
  };

  /**
  * Удаление туриста из заявки
  * @param {Integer} orderId - ID заявки
  * @param {Integer} touristId - id туриста для удаления
  */
  ApiClient.removeOrderTourist = function(orderId, touristId) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      'orderId':orderId,
      'touristId':touristId
    };

    KT.rest({
        caller:'orderEdit#tourists - removeOrderTourist',
        data: params,
        url: _instance.urls.removeOrderTourist
      })
      .done(function (response) {
        response.touristId = touristId;
        request.resolve(response);
      })
      .fail(function() { request.reject(); });

    return request;
  };

  /**
  * Добавление/обновление туриста в заявк(у|е)
  * @param {OrderStorage} OrderStorage - хранилище данных заявки
  * @param {Object} touristData - информация по туристу
  * @todo сменить OrderStorage наа отправку только orderId
  */
  ApiClient.setOrderTourist = function(OrderStorage, touristData) {
    var _instance = this;

    if (
      String(touristData.touristId).indexOf('tmp') === 0 ||
      String(touristData.touristId).indexOf('doc') === 0 
    ) {
      // если добавляем в заявку нового туриста
      delete touristData.touristId;
    }

    var params = {
      'orderId': (OrderStorage.orderId !== 'new') ? OrderStorage.orderId : null,
      'action':'AddTourist',
      'actionParams':touristData
    };

    return KT.rest({
        caller:'orderEdit#tourists - setOrderTourist',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

}));