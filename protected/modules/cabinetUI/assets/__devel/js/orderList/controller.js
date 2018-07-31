(function(global,factory){

    KT.crates.OrderList.controller = factory(KT.crates.OrderList);

}(this,function(crate) {
  'use strict';
  
  /**
  * Список заявок: контроллер
  * @constructor
  * @param {Object} module - ссылка на модуль
  */
  var olController = function(module) {
    /** Module storage - модуль со всеми его компонентами */
    this.mds = module;

    this.mds.view = new crate.view(this.mds);
    this.mds.OrderListStorage = new KT.storage.OrderListStorage();

    this.sortOptions = {
      'lastChangeDate': ['desc','по дате изменения'],
      'dateStart': ['asc','по дате заезда'],
      'touristName': ['asc','по туристу'],
      'agentCompany': ['asc','по агентству'],
      'countryName': ['asc','по стране'],
      'offline': ['asc','по типу'], //online - offline
      'status': ['asc','по статусу']
    };
  };

  olController.prototype.init = function() {
    var _instance = this;
    var modView = this.mds.view;
    var OrderListStorage = this.mds.OrderListStorage;

    var beaconId = null;

    var regenerateOrdersStorage = function(requestParams) {
      if (beaconId !== null) {
        clearTimeout(beaconId);
        beaconId = null;
      }
      
      KT.apiClient.getOrderList(requestParams)
        .then(function(response) {
          if (response.status === 0) {
            OrderListStorage.setTotalOrdersAmount(response.body.nums);
            OrderListStorage.setOrders(response.body.orders);
          } else {
            KT.notify('getOrderListFailed');
          }
        });
    };

    /**
    * Обработка изменения валюты просмотра
    */
    KT.on('KT.changedViewCurrency', function() {
      modView.clearOrderList();
      var requestParams = OrderListStorage.getRequestParams();
      requestParams.offset = 0;
      regenerateOrdersStorage(requestParams);
    });

    /** Обработка изменения значения переключателя нетто/брутто */
    KT.on('KT.changedViewPrice', function() {
      modView.clearOrderList();
      var requestParams = OrderListStorage.getRequestParams();
      requestParams.offset = 0;
      regenerateOrdersStorage(requestParams);
    });

    /*=============================================
    * Обработчики событий модели
    **=============================================*/
    /** Обработка установки списка заявок */
    KT.on('OrderListStorage.setOrders', function(e, ordersContainer) {
      modView.stopPreLoader();

      modView.updateSearchFormSuggest(OrderListStorage.aggregateOrderData);
      modView.renderOrderList(ordersContainer.orders);

      if (OrderListStorage.totalOrdersAmount > OrderListStorage.orders.length) {
        modView.enableMoar();
      } else {
        modView.disableMoar();
      }
      
      var requestTimeout = 10000;
      var requestParams = OrderListStorage.getRequestParams();
      requestParams.offset = 0;

      var pollOrderList = function() {
        var currentBeacon = beaconId;

        KT.apiClient.getOrderList(requestParams)
          .then(function(response) {
            if (response.status === 0) {
              OrderListStorage.setTotalOrdersAmount(response.body.nums);
              OrderListStorage.updateOrders(response.body.orders);
            }

            if (beaconId === currentBeacon) {
              beaconId = setTimeout(pollOrderList, requestTimeout);
            }
          }, function(err) {
            if (err.error !== 'denied' && beaconId === currentBeacon) {
              beaconId = setTimeout(pollOrderList, requestTimeout);
            }
          });
      };

      beaconId = setTimeout(pollOrderList, 10000);
    });

    /** Обработка обновления числа найденных заявок */
    KT.on('OrderListStorage.changedTotalOrdersAmount', function(e, amount) {
      modView.SearchForm.renderOrdersAmount(amount);
    });

    /** Обработка обновления списка заявок */
    KT.on('OrderListStorage.ordersUpdated', function() {
      if (OrderListStorage.totalOrdersAmount >= OrderListStorage.orders.length) {
        modView.enableMoar();
      } else {
        modView.disableMoar();
      }
    });


    /** Обработка изменения заявки на сервере */
    KT.on('OrderListStorage.orderChanged', function(e, order, hasStatusChanged) {
      modView.showOrderChanges(order, hasStatusChanged);
    });

    /** Обработка удаления заявки на сервере */
    KT.on('OrderListStorage.orderRemoved', function(e, orderId) {
      modView.showOrderRemoval(orderId);
    });

    /** Обработка добавления заявки на сервере */
    KT.on('OrderListStorage.orderAdded', function(e, order, position, isNewOrder) {
      modView.showOrderAdd(order, position, isNewOrder);
    });

    /** Обработка перемещения заявки по списку */
    KT.on('OrderListStorage.orderReordered', function(e, order, position, hasOrderChanged, hasStatusChanged) {
      modView.showOrderMove(order, position, hasOrderChanged, hasStatusChanged);
    });

    /*===============================================
    * Обработчики событий представления
    **===============================================*/
    
    /** Обработка нажатия на кнопку создания заявки */
    modView.$createOrder.on('click', function() {
      console.log('add new order');
      if (KT.profile.userType === 'op') {
        modView.renderCreateOrderModal([{
          type:'common',
          title:'создать заявку',
          callback: function($modal) {
            var $contract = $modal.data('$contract');
            var $company = $modal.data('$company');
            window.sessionStorage.setItem('clientId', $company.val());
            window.sessionStorage.setItem('contractId', $contract.val());
            window.location.assign('/cabinetUI/orders/order/new');
          }
        },
        {
          type:'common',
          title:'отмена',
          callback: function() {
            KT.Modal.closeModal();
          }
        }]);

      } else {
        window.location.assign('/cabinetUI/orders/order/new');
      }
    });

    /** Обработка нажатия на метки в строке фильтра */
    modView.SearchForm.elem.$inputField.on('click', '.js-orl-filter--control-label', function(e) {
      e.stopPropagation();

      var controlKey = $(this).attr('data-key');
      var isApplied = $(this).hasClass('is-applied');
      var control = modView.SearchForm.getControlByKey(controlKey);

      control.clear();

      if (isApplied) {
        OrderListStorage.setFilterOption(control.key, control.getValue());
        modView.clearOrderList();

        var requestParams = OrderListStorage.getRequestParams();
        requestParams.offset = 0;

        regenerateOrdersStorage(requestParams);
      }
    });

    /** Обработка подтверждения формы поиска (нажатие кнопки "Найти") */
    modView.$searchForm.on('click', '.js-orl-filter--action-find', function(e) {
      e.preventDefault();
      e.stopPropagation();

      var filterOptions = modView.SearchForm.getFilterValues();
      OrderListStorage.setFilterOptions(filterOptions);
      
      modView.SearchForm.markLabelsApplied();
      modView.SearchForm.toggleForm('hide');
      modView.clearOrderList();

      var requestParams = OrderListStorage.getRequestParams();
      requestParams.offset = 0;

      regenerateOrdersStorage(requestParams);
    });

    /** Обработка выбора типа сортировки */
    modView.$sortForm.on('change', 'input[name="orderField"]', function() {
      var sortField = $(this).val();
      var $control = $(this).closest('label');
      var sortDir = $(this).closest('label').hasClass('sort-desc') ? 'asc' : 'desc';

      if ($control.hasClass('sort-desc')) { sortDir = 'asc'; }
      else if ($control.hasClass('sort-asc')) { sortDir = 'desc'; }
      else {sortDir = _instance.sortOptions[sortField][0];}

      OrderListStorage
        .setFilterOption('sortDir', sortDir)
        .setFilterOption('sortBy', new Array(sortField));

      var requestParams = OrderListStorage.getRequestParams();
      requestParams.offset = 0;

      modView.clearOrderList();
      modView.SearchForm.setSortOption(sortField, requestParams.sortDir);
            
      regenerateOrdersStorage(requestParams);
    });

    /** Обработка нажатия кнопки очистки фильтра */
    modView.$searchForm.on('click', '.js-orl-filter--action-reset', function(e) {
      e.preventDefault();

      modView.SearchForm.clearControls();
      OrderListStorage.setDefaultRequestParams();

      modView.SearchForm.toggleForm('hide');
      modView.clearOrderList();

      var requestParams = OrderListStorage.getRequestParams();
      requestParams.offset = 0;

      regenerateOrdersStorage(requestParams);
    });

    /** Обработка нажатия кнопки "Еще" (получение следующей порции заявок) */
    modView.$showMore.on('click',function() {
      modView.disableMoar();
      modView.initPreLoader();

      var requestParams = OrderListStorage.getRequestParams();
      requestParams.offset = OrderListStorage.orders.length;
      requestParams.limit = OrderListStorage.listPaging;

      if (beaconId !== null) {
        clearTimeout(beaconId);
        beaconId = null;
      }

      /** @todo внимание! здесь, в отличие от setOrders, вызывается добавление заявок */
      KT.apiClient.getOrderList(requestParams)
        .then(function(response) {
          if (response.status === 0) {
            OrderListStorage.setTotalOrdersAmount(response.body.nums);
            OrderListStorage.appendOrders(response.body.orders);
          } else {
            KT.notify('getOrderListFailed');
          }
        });
    });
  };

  /** @todo move prerequisites load here */
  olController.prototype.load = function() {
    var _instance = this;
    var modView = this.mds.view;
    var OrderListStorage = this.mds.OrderListStorage;

    /** Загрузка шаблонов */
    KT.getTemplates(modView.config.templateUrl, modView.config.templates)
      .done(function(templates) {
        _instance.mds.tpl = templates;
        
        var requestParams = OrderListStorage.getRequestParams();
        requestParams.offset = 0;

        modView.SearchForm.render(requestParams, _instance.sortOptions);

        /*
        modView
          .renderSearchForm(requestParams)
          .renderSortForm(requestParams, _instance.sortOptions);
          */

        KT.apiClient.getOrderList(requestParams)
          .then(function(response) {
            if (response.status === 0) {
              OrderListStorage.setTotalOrdersAmount(response.body.nums);
              OrderListStorage.setOrders(response.body.orders);
            } else {
              KT.notify('getOrderListFailed');
            }
          });
      });
  };

  return olController;
}));

(function() {
  KT.on('KT.initializedCore', function() {
    KT.mdx.OrderList.controller = new KT.crates.OrderList.controller(KT.mdx.OrderList);
    KT.mdx.OrderList.controller.init();
    KT.mdx.OrderList.controller.load();
  });
}());
