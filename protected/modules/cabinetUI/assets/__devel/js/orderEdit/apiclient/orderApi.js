(function(global, extendApiClient) {

    extendApiClient(KT.apiClient);

}(this,function(ApiClient) {
  /**
  * Запрос информации о заявке
  * @param {Integer} orderId - ID заявки
  */
  ApiClient.getOrderInfo = function(orderId) {
    var _instance = this;
    var params = {
      'orderId': orderId,
      'getInCurrency': KT.profile.viewCurrency
    };

    return KT.rest({
        caller: 'orderEdit - getOrderInfo',
        data: params,
        url: _instance.urls.getOrder
      });
  };

  /**
  * Запрос информации о счетах
  * @param {Integer} orderId - ID заявки
  */
  ApiClient.getOrderInvoices = function(orderId) {
    var _instance = this;

    var params = {
      'orderId':orderId
    };

    return KT.rest({
        caller:'orderEdit - getOrderInvoices',
        data: params,
        url: _instance.urls.getOrderInvoices
      });
  };

  /**
  * Получение списка документов
  * @param {Integer} orderId - ID заявки
  */
  ApiClient.getOrderDocuments = function(orderId) {
    var _instance = this;

    var params = {
      'orderId': orderId
    };

    return KT.rest({
        caller: 'orderEdit - getOrderDocuments',
        data: params,
        url: _instance.urls.getOrderDocuments
      });
  };

  /**
  * Получение истории заявки
  * @param {Integer} orderId - ID заявки
  */
  ApiClient.getOrderHistory = function(orderId) {
    var _instance = this;

    var params = {
      'orderId': orderId,
      'lang': 'ru'
    };

    return KT.rest({
        caller:'orderEdit - getOrderHistory',
        data: params,
        url: _instance.urls.getOrderHistory
      });
  };

  /** Установка скидки
  * @param {Integer} orderId - ID заявки
  * @param {Number} discount - сумма скидки в рублях
  */
  ApiClient.setDiscount = function(orderId, discount) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      'orderId': orderId,
      'agentOrderDiscount': discount
    };

    KT.rest({
        caller: 'orderEdit#payment - setDiscount',
        data: params,
        url: _instance.urls.setDiscount
      })
      .done(function (data) {
        data.discount = discount;
        request.resolve(data);
      })
      .fail(function() { request.reject(); });

    return request;
  };

  /**
  * Загрузка документов в заявку
  * @param {Integer} orderId - ID заявки
  * @param {Object} $docform - форма отправки документов
  * @fires Apiclient.documentUploadProgress
  * @fires Apiclient.uploadedDocument
  * @todo убрать проброс формы, заменить события на что-то еще (коллбек на прогресс и промис?)
  */
  ApiClient.uploadDocument = function(orderId, $docform) {
    var _instance = this;

    $docform.ajaxSubmit({
      url: _instance.urls.uploadDocument,
      data: {
        'orderId': orderId,
        'usertoken': KT.profile.userToken
      },
      uploadProgress: function(e, position, total, percent) {
        _instance.dispatch('documentUploadProgress', {'percent': percent});
      },
      success: function(data) {
        try {
          var loadedData = JSON.parse(data);
          _instance.dispatch('uploadedDocument', loadedData);
        } catch(e) {
          _instance.dispatch('uploadedDocument', {'error':'uploadDocument: not json'});
        }
      },
      error: function(xhr,text,err) {
        _instance.dispatch('uploadedDocument', {'error':text + ' ' + err});
      }
    });
  };

  /**
  * Выставление счета
  * @param {OrderStorage} orderStorage - данные заявки
  * @param {Array} invoiceData - массив номеров услуг и счетов по ним
  */
  ApiClient.setInvoice = function(orderStorage, invoiceData) {
    var _instance = this;

    var invoiceServices = [];
    invoiceData.forEach(function(invoice) {
      invoiceServices.push({
        'serviceId': invoice.id,
        'invoicePrice': invoice.sum
      });
    });

    /** @todo грязнейший хак, по сути же одна клиентская валюта? */
    var params = {
      'userId': KT.profile.userId,
      'orderId': orderStorage.orderId,
      'paymentType': '2',
      'currency': orderStorage.getServices()[0].prices.inClient.currencyCode,
      'Services': invoiceServices
    };

    return KT.rest({
        caller: 'orderEdit - setInvoice',
        data: params,
        url: _instance.urls.setInvoice
      });
  };

  /**
  * Отмена счета
  * @param {Integer} invoiceId  - ID отменяемого счета 
  */
  ApiClient.cancelInvoice = function(invoiceId) {
    var _instance = this;

    /** @todo грязнейший хак, по сути же одна клиентская валюта? */
    var params = {
      'invoiceId': invoiceId
    };

    return KT.rest({
      caller: 'orderEdit - setInvoice',
      data: params,
      url: _instance.urls.cancelInvoice
    });
  };

}));