(function(global, extendApiClient) {

    extendApiClient(KT.apiClient);

}(this,function(ApiClient) {

  /**
  * Получение информации по услуге перелета
  * @param {Integer} orderId - ID заявки
  * @param {Array} serviceIds - массив ID'шников сервисов
  */
  ApiClient.getOrderOffers = function(orderId, serviceIds) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'servicesIds':serviceIds,
      'lang':'ru',
      'getInCurrency':KT.profile.viewCurrency
    };

    return KT.rest({
        caller:'orderEdit#services - getOrderOffers',
        data: params,
        url: _instance.urls.getOrderOffers
      });
  };

  /**
  * Получение доступных действий с услугами
  * @param {Integer} orderId - ID заявки
  */
  ApiClient.getAllowedTransitions = function(orderId) {
    var _instance = this;

    var params = {
        'operation': 'checkTransition',
        'orderId': orderId
    };

    return KT.rest({
        caller: 'orderEdit - getAllowedTransitions',
        data: params,
        url: _instance.urls.checkWorkflow
      });
  };

  /**
  * Проверка доступности совершения операции с услугами с определенным набором параметров
  * @param {Integer} orderId - ID заявки
  * @param {String} action - валидируемое действие
  * @param {Object[]} actionParams - массив структур actionParams для услуг
  */
  ApiClient.validateAction = function(orderId, action, actionParams) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
        'operation': 'validate',
        'orderId': orderId,
        'action': action,
        'actionParams': actionParams
    };

    KT.rest({
        caller: 'orderEdit - validateAction',
        data: params,
        url: _instance.urls.checkWorkflow
      })
      .done(function(response) {
        response.action = action;
        if (response.status === 0) {
          actionParams.forEach(function(service, i) {
            response.body[i].serviceId = service.serviceId;
          });
        }

        request.resolve(response);
      })
      .fail(function() { request.reject(); });

    return request;
  };

  /**
  * Запрос на старт процесса бронирования
  * @param {Integer} orderId - ID заявки
  * @param {Object} actionParams - параметры actionParams команды
  */
  ApiClient.startBooking = function(orderId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'BookStart',
      'actionParams': actionParams
    };

    return KT.rest({
        caller: 'orderEdit#services - startBooking',
        url: _instance.urls.orderWorkflowManager,
        data: params
      });
  };

  /**
  * Запрос на выписку билетов
  * @param {Integer} orderId - ID заявки
  * @param {Object} serviceId - ID сервиса для выписки
  */
  ApiClient.issueTickets = function(orderId, serviceId) {
    var _instance = this;

    var params = {
      'orderId': orderId,
      'action': 'IssueTickets',
      'actionParams': {
        'serviceId': serviceId
      }
    };

    return KT.rest({
        caller:'orderEdit#services - issueTickets',
        url: _instance.urls.orderWorkflowManager,
        data:params
      });
  };

  /**
  * Запрос на изменение данных брони
  * @param {Integer} orderId - ID заявки
  * @param {Object} actionParams - параметры actionParams команды
  */
  ApiClient.bookChange = function(orderId, actionParams) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      'orderId': orderId,
      'action': 'BookChange',
      'actionParams': actionParams
    };

    KT.rest({
        caller:'orderEdit#services - bookChange',
        url: _instance.urls.orderWorkflowManager,
        data:params
      })
      .done(function(response) {
        response.body.serviceId = actionParams.serviceId;
        request.resolve(response);
      })
      .fail(function() { request.reject(); });
    
    return request;
  };

  /**
  * Запрос на отмену бронирования услуги
  * @param {Integer} orderId - ID заявки
  * @param {Object} actionParams - параметры actionParams команды
  */
  ApiClient.bookCancel = function(orderId, actionParams) {
    var _instance = this;

    var params = {
      'orderId': orderId,
      'action': 'BookCancel',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - bookCancel',
        url: _instance.urls.orderWorkflowManager,
        data:params
      });
  };

  /**
  * Запрос на отмену бронирования услуги
  * @param {Integer} orderId - ID заявки
  * @param {Object} actionParams - параметры actionParams команды
  */
  ApiClient.setServiceToManual = function(orderId, actionParams) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      'orderId': orderId,
      'action': 'Manual',
      'actionParams': actionParams
    };

    KT.rest({
        caller: 'orderEdit#services - Manual',
        url: _instance.urls.orderWorkflowManager,
        data: params
      })
      .done(function(response) {
        response.serviceId = actionParams.serviceId;
        request.resolve(response);
      })
      .fail(function() { request.reject(); });

    return request;
  };

  /**
  * Установка привязки туристов к услуге
  * @param {Integer} orderId - ID заявки
  * @param {Integer} serviceId - ID сохраняемого сервиса
  * @param {Object[]} linkageInfo - данные по туристам
  */
  ApiClient.setTouristsLinkage = function(orderId, serviceId, linkageInfo) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      'orderId': orderId,
      'action':'TouristToService',
      'actionParams':{
        'serviceId': serviceId,
        'touristData': linkageInfo
      }
    };

    KT.rest({
        caller:'orderEdit#services - setTouristsLinkage',
        data: params,
        url: _instance.urls.orderWorkflowManager
      })
      .done(function (response) {
        response.serviceId = serviceId;
        response.linkageInfo = linkageInfo;

        request.resolve(response);
      })
      .fail(function() { request.reject(); });

    return request;
  };

  /**
  * Изменение параметров услуги в ручном режиме (даты, цены)
  * @param {Integer} orderId - ID заявки
  * @param {Integer} serviceId - ID сохраняемого сервиса
  * @param {Object} actionParams - параметры команды 
  */
  ApiClient.setServiceData = function(orderId, serviceId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'SetServiceData',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - setServiceData',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Изменение статуса услуги в ручном режиме
  * @param {Integer} orderId - ID заявки
  * @param {Integer} serviceId - ID сохраняемого сервиса
  * @param {Object} actionParams - параметры команды 
  */
  ApiClient.manualSetStatus = function(orderId, serviceId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'ManualSetStatus',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - manualSetStatus',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Изменение параметров брони услуги в ручном режиме
  * @param {Integer} orderId - ID заявки
  * @param {Integer} serviceId - ID сохраняемого сервиса
  * @param {Object} actionParams - параметры команды 
  */
  ApiClient.setReservationData = function(orderId, serviceId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'SetReservationData',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - SetReservationData',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Добавление/изменение билетов
  * @param {Integer} orderId - ID заявки
  * @param {Integer} serviceId - ID сохраняемого сервиса
  * @param {Object} actionParams - параметры команды 
  */
  ApiClient.setTicketsData = function(orderId, serviceId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'SetTicketsData',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - SetTicketsData',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Сохранение значений дополнительных полей услуги 
  * @param {Integer} orderId - ID заявки
  * @param {Array} customFieldsValues - список значений дополнительных полей
  */
  ApiClient.setServiceAdditionalData = function(orderId, customFieldsValues) {
    var _instance = this;

    var params = {
      'orderId': orderId,
      'action': 'SetAdditionalData',
      'actionParams': {
        'additionalFields': customFieldsValues
      }
    };

    return KT.rest({
        caller:'orderEdit#services - SetAdditionalData',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Добавление дополнительной услуги
  * @param {Integer} orderId - ID заявки
  * @param {Object} axctionParams - параметры команды 
  */
  ApiClient.addExtraService = function(orderId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'AddExtraService',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - AddExtraService',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Добавление дополнительной услуги
  * @param {Integer} orderId - ID заявки
  * @param {Object} avtionParams - параметры команды 
  */
  ApiClient.removeExtraService = function(orderId, actionParams) {
    var _instance = this;

    var params = {
      'orderId': orderId,
      'action': 'RemoveExtraService',
      'actionParams': actionParams
    };

    return KT.rest({
        caller: 'orderEdit#services - RemoveExtraService',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Проверка статуса услуги
  * @param {Integer} orderId - ID заявки
  * @param {Object} actionParams - параметры команды 
  */
  ApiClient.checkServiceStatus = function(orderId, actionParams) {
    var _instance = this;

    var params = {
      'orderId': orderId,
      'action': 'OrderSync',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - CheckServiceStatus',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };
  
  /**
  * Получение полной информации по отелю
  * @param {Integer} hotelId - ID отеля
  */
  ApiClient.getHotelInfo = function(hotelId) {
    var _instance = this;

    var params = {
      'hotelId': hotelId,
      'lang':'ru'
    };

    return KT.rest({
        caller: "orderEdit - getHotelInfo",
        data: params,
        url: _instance.urls.getHotelInfo
      });
  };

}));