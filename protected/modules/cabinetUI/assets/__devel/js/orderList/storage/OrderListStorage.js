/* global KT */
/* global ktStorage */

(function(global,factory){

    KT.storage.OrderListStorage = factory();

}(this,function() {
  'use strict';
  
  /**
  * Хранилище списка заявок
  * @module OrderListStorage
  * @constructor
  */
  var OrderListStorage = ktStorage.extend(function() {
    this.namespace = 'OrderListStorage';

    this.orders = [];
    this.totalOrdersAmount = 0;

    // количество заявок для вывода *на одной странице*
    this.listPaging = 20;

    this.setDefaultRequestParams();

    var storedParams = window.sessionStorage.getItem('OrderListRequestParams');
    if (storedParams !== null && storedParams !== '') {
      $.extend(true, this.requestParams, JSON.parse(storedParams));
      this.requestParams.limit = this.listPaging;
    }

    // агрегированные данные заявок для фильтрации
    this.aggregateOrderData = {
      'managers': {},
      'countries': {},
      'cities': {},
      'tourleaders': {}
    };

  });

  KT.addMixin(OrderListStorage, 'Dispatcher');

  /**
  * Установка значений по умолчанию для запроса заявок
  * @return {Object} параметры по умолчанию 
  */
  OrderListStorage.prototype.setDefaultRequestParams = function() {
    this.requestParams = {
      'limit': this.listPaging,
      'archived': false,
      'sortBy': ['lastChangeDate'],
      'sortDir': 'desc'
    };

    return this.getRequestParams();
  };

  /** 
  * Возвращает копию параметров запроса списка заявок 
  * @return {Object} - параметры запроса
  */
  OrderListStorage.prototype.getRequestParams = function() {
    return $.extend(true, {}, this.requestParams);
  };

  /**
  * Установка поля фильтрации для запроса списка
  * @param {String} option - название опции
  * @param {*} value - значение опции
  */
  OrderListStorage.prototype.setFilterOption = function(option, value) {
    this.requestParams[option] = value;
    this.requestParams.limit = this.listPaging;
    window.sessionStorage.setItem('OrderListRequestParams', JSON.stringify(this.requestParams));

    return this;
  };

  /**
  * Установка нескольких полей фильтрации для запроса списка
  * @param {Object} options - список полей со значениями
  */
  OrderListStorage.prototype.setFilterOptions = function(options) {
    $.extend(true, this.requestParams, options);
    this.requestParams.limit = this.listPaging;
    window.sessionStorage.setItem('OrderListRequestParams', JSON.stringify(this.requestParams));
  };

  /**
  * Сохранение количества всех найденных заявок по заданным фильтрам
  * @param {Number} amount - количество заявок
  */
  OrderListStorage.prototype.setTotalOrdersAmount = function(amount) {
    this.totalOrdersAmount = amount;
    this.dispatch('changedTotalOrdersAmount', amount);
  };

  /**
  * Задает список заявок (стирая старые)
  * @param {Array} ordersData - данные заявок
  */
  OrderListStorage.prototype.setOrders = function(ordersData) {
    var self = this;

    self.orders = ordersData.map(function(orderData) {
      var order = self.makeOrder(orderData);
      self.updateAggregateOrderData(order);
      return order;
    });

    self.dispatch('setOrders', {'orders': self.orders});
  };

  /**
  * Обновление сохраненного списка заявок
  * @param {Array} ordersData - данные заявок
  */
  OrderListStorage.prototype.updateOrders = function(ordersData) {
    var self = this;
    
    var newListIDs = [];
    var oldListIDs = [];
    var lastTimeStamp = 0;
    var hasOrderChanged;
    var hasStatusChanged;

    var orders = ordersData.map(function(orderData) {
      var order = self.makeOrder(orderData);
      newListIDs.push(order.orderId);
      return order;
    });

    self.orders.forEach(function(order) {
      oldListIDs.push(order.orderId);
      var od = (order.dolc !== null) ? moment(order.dolc, 'YYYY-MM-DD HH:mm:ss').valueOf() : od;
      lastTimeStamp = (lastTimeStamp < od) ? od : lastTimeStamp;
    });

    var processOrder = function(order, i) {
      if (self.orders[i] !== undefined && self.orders[i].orderId === order.orderId) {
        // тот же ID 
        if (self.orders[i].dolc !== order.dolc) {
          hasStatusChanged = (self.orders[i].status !== order.status);
          self.orders.splice(i, 1, order);
          self.dispatch('orderChanged', [order, hasStatusChanged]);
        }
      } else {
        // другой ID или старый список кончился
        if (self.orders[i] !== undefined && newListIDs.indexOf(self.orders[i].orderId) === -1) {
          // заявки нет в новом списке
          oldListIDs.splice(i,1);
          self.dispatch('orderRemoved', self.orders.splice(i, 1)[0].orderId);
          processOrder(order, i);
        } else {
          var oldItemPlace = oldListIDs.indexOf(order.orderId);

          if (oldItemPlace === -1) {
            // новой заявки нет в старом списке
            self.orders.splice(i, 0, order);
            oldListIDs.splice(i, 0, order.orderId);

            var isNewOrder = (moment(order.dolc,'YYYY-MM-DD HH:mm:ss').valueOf() > lastTimeStamp);

            self.dispatch('orderAdded', [order, i, isNewOrder]);
          } else {
            // новая заявка есть в старом списке
            var movingOrder = self.orders.splice(oldItemPlace, 1)[0];
            oldListIDs.splice(oldItemPlace, 1);
            
            self.orders.splice(i, 0, order);
            oldListIDs.splice(i, 0, order.orderId);

            hasOrderChanged = (movingOrder.dolc !== order.dolc);
            hasStatusChanged = (movingOrder.status !== order.status);

            self.dispatch('orderReordered', [order, i, hasOrderChanged, hasStatusChanged]);
          }
        }
      }
    };

    orders.forEach(processOrder);

    var i = orders.length;
    while (i < self.orders.length) {
      self.dispatch('orderRemoved', self.orders.splice(i, 1)[0].orderId);
    }

    self.dispatch('ordersUpdated');
  };

  /**
  * Добавление новых заявок к списку 
  * @param {Array} ordersData - данные заявок
  */
  OrderListStorage.prototype.appendOrders = function(ordersData) {
    var self = this;

    self.requestParams.limit += self.listPaging;

    var newOrders = ordersData.map(function(orderData) {
      var order = self.makeOrder(orderData);
      self.updateAggregateOrderData(order);
      return order;
    });

    [].push.apply(self.orders, newOrders);

    self.dispatch('setOrders', {'orders': newOrders});
  };

  /**
  * Преобразование данных заявки
  * @param {Object} orderData - данные заявки
  * @return {Object} 
  */
  OrderListStorage.prototype.makeOrder = function(orderData) {
    var order = $.extend(true, {}, orderData);

    order.holdingCompany = (typeof orderData.companyMainOffice === 'string') ?
      orderData.companyMainOffice : 
      null;

    return order;
  };

  /**
  * Сбор данных из заявки
  * @param {Object} order - заявка
  */
  OrderListStorage.prototype.updateAggregateOrderData = function(order) {
    var aggregateData = this.aggregateOrderData;

    if (order.mgrLastName !== undefined && order.mgrLastName !== null) {
      aggregateData.managers[order.mgrLastName] = order.mgrLastName;
    }

    if (order.country !== undefined && order.country !== null) {
      aggregateData.countries[order.country] = order.country;
    }

    if (order.city !== undefined && order.city !== null) {
      aggregateData.cities[order.city] = order.city;
    }

    if (order.touristLastName !== undefined && order.touristLastName !== null) {
      aggregateData.tourleaders[order.touristLastName] = order.touristLastName;
    }
  };

  return OrderListStorage;
}));
