KT.crates.OrderList = {};

KT.mdx.OrderList = {};
KT.mdx.OrderList.tpl = {};

$.extend(KT.notifications,{
  'getOrderListFailed': {
    type:'error',
    title:'Не удалось загрузить список',
    msg:'Попробуйте перезагрузить страницу',
    killtarget: 'body'
  }
});
(function(global, extendApiClient) {

    extendApiClient(KT.apiClient);

}(this,function(ApiClient) {

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
/* global KT */
/* global ktStorage */

(function(global,factory){

    KT.storage.OrderListStorage = factory();

}(this,function() {
  /**
  * Прототип хранилища данных заявки
  * @module OrderStorage
  * @constructor
  * @param {Integer} orderId - ID заявки
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
  * @param {Array} orders - заявки
  */
  OrderListStorage.prototype.setOrders = function(orders) {
    var self = this;

    self.orders.splice(0, self.orders.length);

    orders.forEach(function(order) {
      self.updateAggregateOrderData(order);
      self.orders.push(order);
    });

    self.dispatch('setOrders', {'orders': self.orders});
  };

  /**
  * Обновление сохраненного списка заявок
  * @param {Array} orders - заявки
  */
  OrderListStorage.prototype.updateOrders = function(orders) {
    var self = this;
    
    var newListIDs = [];
    var oldListIDs = [];
    var lastTimeStamp = 0;
    var hasOrderChanged;
    var hasStatusChanged;

    orders.forEach(function(order) { newListIDs.push(order.orderId); });
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
  * @param {Array} orders - заявки
  */
  OrderListStorage.prototype.appendOrders = function(orders) {
    var self = this;

    self.requestParams.limit += self.listPaging;

    orders.forEach(function(order) {
      self.updateAggregateOrderData(order);
      self.orders.push(order);
    });

    self.dispatch('setOrders', {'orders': orders});
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

(function(global,factory) {

    KT.crates.OrderList.view = factory();

}(this, function() {
  /**
  * Список заявок: представление
  * @constructor
  * @param {Object} module - хранилище родительского модуля
  * @param {Object} [options] - Объект конфигурации
  */
  var modView = function(module, options) {
    this.mds = module;    
    if (options === undefined) { options = {}; }
    this.config = $.extend(true,{
      'templateUrl': '/cabinetUI/orders/getTemplates',
      'templates': {
        createOrderModal: 'orderList/modals/createOrder',
        orderListItem: 'orderList/listItem',
        orderListItemServices: 'orderList/itemServices',
        orderListFilters: 'orderList/filterControls',
        filterControlLabel: 'orderList/filterControlLabel',
        sortOption: 'orderList/sortOption',
        clndr: 'clndrTemplate'
      }
    },options);

    this.$createOrder = $('.js-orl--add-new-order');

    this.$searchForm = $('#order-list-filter');
    this.$sortForm = $('#order-list-sorting');

    this.SearchForm = this.setSearchForm(this.$searchForm, this.$sortForm);

    this.$orderListContainer = $('#order-list-items');
    this.$spinner = this.$orderListContainer.find('.spinner');

    this.$actionsPanel = $('#order-list-actions');
    this.$showMore = this.$actionsPanel.find('.js-orl-list-actions--show-more');

    this.initPreLoader();
  };

  /**
  * Отображение списка заявок
  * @param {Array} orderList - Массив заявок
  */
  modView.prototype.renderOrderList = function(orderList) {
    this.stopPreLoader();
    var _instance = this;

    orderList.forEach(function(item) {
      var order = _instance.mapOrderInfo(item);
      $(Mustache.render(_instance.mds.tpl.orderListItem, order))
        .appendTo(_instance.$orderListContainer)
        .show();
    });

    return this;
  };

  /**
  * Метод: Отображение количества заявок
  * @param {Number} orderNum - Число заявок
  */
  modView.prototype.renderOrderCount = function(orderNum) {
    this.searchForm.$orderQuan.text(orderNum);
    this.searchForm.$orderQuanLabel.text(
      declOfNum(orderNum,['найденная заявка','найденные заявки','найденных заявок'])
    );

    return this;
  };

  /**
  * Инициализация формы поиска и фильтрации заявок
  * @param {Object} $searchFormContainer - контейнер формы поиска
  * @param {Object} $sortFormContainer - контейнер формы сортировки
  * @return {Object} объект управления формой поиска 
  */
  modView.prototype.setSearchForm = function($searchFormContainer, $sortFormContainer) {
    var _instance = this;

    // подготовка списка статусов заявки
    var orderStatuses = [];
    for (var status in KT.getCatalogInfo.orderstatuses) {
      if (KT.getCatalogInfo.orderstatuses.hasOwnProperty(status)) {
        orderStatuses.push({value: status, text: KT.getCatalogInfo.orderstatuses[status][1]});
      }
    }

    var SearchForm = {
      elem: {
        $searchContainer: $searchFormContainer,
        $inputField: $searchFormContainer.find('.js-orl-filter-statusbar'),
        $placeholder: $searchFormContainer.find('.js-orl-filter-statusbar--placeholder'),
        $filters: $searchFormContainer.find('.js-orl-filter--selectors'),
        $sortContainer: $sortFormContainer,
        $orderQuan: $sortFormContainer.find('.js-orl-sorting--amount'),
        $orderQuanLabel: $sortFormContainer.find('.js-orl-sorting--amount-label'),
        $sortOptionsList: $sortFormContainer.find('.js-orl-sorting--actions'),
        $sortFields: null
      },
      enabledFilters: {
        'orderNumber': true,
        'startDate': true,
        'country': true,
        'city': true,
        'modificationDate': true,
        'company': (KT.profile.userType === 'op') ? true : false,
        'manager': true,
        'orderStatus': true,
        'tourleader': true,
        'offline': true,
        'archive': true
      },
      controls: {},
      controlsKeyMap: {},
      /**
      * Рендер формы поиска
      * @param {Object} requestParams - стартовые параметры запроса
      * @param {Object} sortOptions - стартовые параметры сортировки
      */
      render: function(requestParams, sortOptions) {
        var self = this;

        this.elem.$filters.html(Mustache.render(_instance.mds.tpl.orderListFilters, this.enabledFilters));
        this.initSearchFormControls(requestParams);
        this.initSortFormControls(requestParams, sortOptions);

        this.togglePlaceholder();
        this.elem.$inputField.on('click', function() {
          self.toggleForm();
        });
      },
      /**
      * Инициализация контролов формы поиска
      * @param {Object} requestParams - стартовые параметры запроса
      */
      initSearchFormControls: function(requestParams) {
        var startDateFrom = $('#orl-filter--start-date-from').clndrize({
          'template': _instance.mds.tpl.clndr,
          'eventName': 'Дата заезда - с'
        });

        var startDateTo = $('#orl-filter--start-date-to').clndrize({
          'template': _instance.mds.tpl.clndr,
          'eventName': 'Дата заезда - по'
        });

        var modificationDateFrom = $('#orl-filter--modification-date-from').clndrize({
          'template': _instance.mds.tpl.clndr,
          'eventName': 'Дата модификации - с'
        });

        var modificationDateTo = $('#orl-filter--modification-date-to').clndrize({
          'template': _instance.mds.tpl.clndr,
          'eventName': 'Дата модификации - по'
        });

        var $country = $('#orl-filter--country').selectize({
            openOnFocus: true,
            create: true,
            createOnBlur: true,
            valueField: 'value',
            labelField: 'text'
        });

        var $city = $('#orl-filter--city').selectize({
            openOnFocus: true,
            create: true,
            createOnBlur: true,
            valueField: 'value',
            labelField: 'text',
        });

        var $creator = $('#orl-filter--creator').selectize({
            openOnFocus: true,
            create: true,
            createOnBlur: true,
            valueField: 'value',
            labelField: 'text',
        });

        var $tourleader = $('#orl-filter--tourleader').selectize({
            openOnFocus: true,
            create: true,
            createOnBlur: true,
            valueField: 'value',
            labelField: 'text',
        });

        var $status = $('#orl-filter--status').selectize({
            openOnFocus: true,
            create: false,
            options: orderStatuses
        });

        var $offline = $('#orl-filter--offline').selectize({
            openOnFocus: true,
            create: false,
            options: [
              {value:0, text:'онлайн'},
              {value:1, text:'оффлайн'}
            ]
        });
        
        this.controls.orderId = {
          $target: $('#orl-filter--orderid'),
          field: '№ заявки:',
          key: 'orderId',
          getValue: function() { return this.$target.val(); },
          getTitle: function() { return this.$target.val(); },
          clear: function() { this.$target.val('').change(); },
          init: function(ival) { this.$target.val(ival); }
        };

        this.controls.dateStart = {
          $target: $('#orl-filter--start-date-from'),
          field: 'заезд с:',
          key: 'startDate',
          getValue: function() {
            if (this.$target.val() !== '') {
              try {
                return moment(this.$target.val(),'DD.MM.YYYY').format('YYYY-MM-DD');
              } catch(e) { return ''; }
            } else {
              return '';
            }
          },
          getTitle: function() {
            try {
              return this.$target.val();
            } catch(e) { return ''; }
          },
          clear: function() {
            startDateFrom.clinstance.removeEvents(function() { return true; });
            this.$target.val('').change();
          },
          init: function(ival) {
            var mival = moment(ival,'YYYY-MM-DD');
            this.$target.val(mival.format('DD.MM.YYYY'));
            startDateFrom.clinstance.setEvents([{date:ival,title:'Дата заезда'}]);
            startDateFrom.clinstance.setMonth(mival.month());
            startDateFrom.clinstance.setYear(mival.year());
          }
        };

        this.controls.dateEnd = {
          $target: $('#orl-filter--start-date-to'),
          field: 'заезд по:',
          key: 'finishDate',
          getValue: function() {
            if (this.$target.val() !== '') {
              try { return moment(this.$target.val(),'DD.MM.YYYY').format('YYYY-MM-DD'); }
              catch(e) { return ''; }
            } else {
              return '';
            }
          },
          getTitle: function() {
            try { return this.$target.val(); }
            catch(e) { return ''; }
          },
          clear: function() {
            startDateTo.clinstance.removeEvents(function() { return true; });
            this.$target.val('').change();
          },
          init: function(ival) {
            var mival = moment(ival,'YYYY-MM-DD');
            this.$target.val(moment(ival,'YYYY-MM-DD').format('DD.MM.YYYY'));
            startDateTo.clinstance.setEvents([{date:ival, title:'Дата заезда'}]);
            startDateTo.clinstance.setMonth(mival.month());
            startDateTo.clinstance.setYear(mival.year());
          }
        };

        this.controls.modificationDateFrom = {
          $target: $('#orl-filter--modification-date-from'),
          field: 'изменены после:',
          key: 'modificationDateFrom',
          getValue: function() {
            if (this.$target.val() !== '') {
              try {
                return moment(this.$target.val(),'DD.MM.YYYY').format('YYYY-MM-DD');
              } catch(e) { return ''; }
            } else {
              return '';
            }
          },
          getTitle: function() {
            try {
              return this.$target.val();
            } catch(e) { return ''; }
          },
          clear: function() {
            modificationDateFrom.clinstance.removeEvents(function() { return true; });
            this.$target.val('').change();
          },
          init: function(ival) {
            var mival = moment(ival,'YYYY-MM-DD');
            this.$target.val(mival.format('DD.MM.YYYY'));
            modificationDateFrom.clinstance.setEvents([{date:ival,title:'Дата модификации'}]);
            modificationDateFrom.clinstance.setMonth(mival.month());
            modificationDateFrom.clinstance.setYear(mival.year());
          }
        };

        this.controls.modificationDateTo = {
          $target: $('#orl-filter--modification-date-to'),
          field: 'изменены до:',
          key: 'modificationDateTo',
          getValue: function() {
            if (this.$target.val() !== '') {
              try {
                return moment(this.$target.val(),'DD.MM.YYYY').format('YYYY-MM-DD');
              } catch(e) { return ''; }
            } else {
              return '';
            }
          },
          getTitle: function() {
            try {
              return this.$target.val();
            } catch(e) { return ''; }
          },
          clear: function() {
            modificationDateTo.clinstance.removeEvents(function() { return true; });
            this.$target.val('').change();
          },
          init: function(ival) {
            var mival = moment(ival,'YYYY-MM-DD');
            this.$target.val(mival.format('DD.MM.YYYY'));
            modificationDateTo.clinstance.setEvents([{date:ival,title:'Дата модификации'}]);
            modificationDateTo.clinstance.setMonth(mival.month());
            modificationDateTo.clinstance.setYear(mival.year());
          }
        };

        this.controls.Country = {
          $target: $country,
          field: 'страна:',
          key: 'countryName',
          getValue: function() { return this.$target.val(); },
          getTitle: function() { return this.$target[0].selectize.options[this.$target.val()].text; },
          clear: function() { this.$target[0].selectize.clear(); },
          init: function(ival) {
            this.$target[0].selectize.addOption({'value':ival, 'text':ival});
            this.$target[0].selectize.addItem(ival);
          }
        };

        this.controls.City = {
          $target: $city,
          field: 'город:',
          key: 'cityName',
          getValue: function() { return this.$target.val(); },
          getTitle: function() { return this.$target[0].selectize.options[this.$target.val()].text; },
          clear: function() { this.$target[0].selectize.clear(); },
          init: function(ival) {
            this.$target[0].selectize.addOption({'value':ival, 'text':ival});
            this.$target[0].selectize.addItem(ival);
          }
        };

        this.controls.Manager = {
          $target: $creator,
          field: 'менеджер:',
          key: 'managerName',
          getValue: function() { return this.$target.val(); },
          getTitle: function() { return this.$target[0].selectize.options[this.$target.val()].text; },
          clear: function() { this.$target[0].selectize.clear(); },
          init: function(ival) {
            this.$target[0].selectize.addOption({'value':ival, 'text':ival});
            this.$target[0].selectize.addItem(ival);
          }
        };

        this.controls.Tourleader = {
          $target: $tourleader,
          field: 'турлидер:',
          key: 'touristName',
          getValue: function() { return this.$target.val(); },
          getTitle: function() { return this.$target[0].selectize.options[this.$target.val()].text; },
          clear: function() { this.$target[0].selectize.clear(); },
          init: function(ival) {
            this.$target[0].selectize.addOption({'value':ival, 'text':ival});
            this.$target[0].selectize.addItem(ival);
          }
        };

        this.controls.Status = {
          $target: $status,
          field: 'статус:',
          key: 'orderStatus',
          getValue: function() { return this.$target.val(); },
          getTitle: function() { return $(this.$target[0].selectize.getItem(this.$target.val())).text(); },
          clear: function() { this.$target[0].selectize.clear(); },
          init: function(ival) {
            this.$target[0].selectize.addOption({value:ival, text:KT.getCatalogInfo.orderstatuses[ival][1]});
            this.$target[0].selectize.addItem(ival);
          }
        };

        this.controls.Offline = {
          $target: $offline,
          field: 'тип заявки:',
          key: 'offline',
          getValue: function() { return this.$target.val(); },
          getTitle: function() { return $(this.$target[0].selectize.getItem(this.$target.val())).text(); },
          clear: function() { this.$target[0].selectize.clear(); },
          init: function(ival) {
            this.$target[0].selectize.addItem(ival);
          }
        };

        this.controls.Archived = {
          $target: $('#orl-filter--archived'),
          field: 'архивные',
          key: 'archived',
          getValue: function() { return this.$target.prop('checked'); },
          getTitle: function() { return this.$target.prop('checked'); },
          clear: function() { this.$target.prop('checked',false).change(); },
          init: function(ival) { this.$target.prop('checked',ival); }
        };

        if (this.enabledFilters.company) {
          var $clientCompany = $('#orl-filter--company').selectize({
            plugins: {'key_down': { start: 2 }},
            openOnFocus: true,
            create: false,
            selectOnTab: true,
            highlight: false,
            loadThrottle: 300,
            valueField: 'companyId',
            labelField: 'name',
            sortField:'seqid',
            options:[],
            score:function() {
              return function(item) {
                return 1000 / (item.seqid);
              };
            },
            load: function(query, callback) {
              var self = this;

              this.clearOptions();

              if (!query.length || query.length < 2) {
                return callback();
              }

              KT.Dictionary.getAsList('companies', {
                  'textFilter': query,
                  'fieldsFilter': [],
                  'lang': 'ru'
                })
                .then(function(companies) {
                  var $inputElem = self.$control;

                  companies.forEach(function(item, i) {
                    item.seqid = i + 1;
                  });
                  callback(companies);

                  if (companies.length === 0) {
                    $inputElem.addClass('warning');
                    setTimeout(function() {
                      $inputElem.removeClass('warning');
                    }, 2000);
                  }
                })
                .fail(function() {
                  callback();
                  self.refreshOptions(true);
                  var $inputElem = self.$control;
                  $inputElem.addClass('warning');
                  setTimeout(function() {
                    $inputElem.removeClass('warning');
                  }, 2000);
                });
            },
            onType:function(str) {
              if (str.length < 2) {
                this.close();
                this.clearOptions();
              }
            },
            onItemRemove: function() {
              this.clearOptions();
            },
            onChange: function(val) {
              if (val === '') {
                this.trigger('item_remove');
              }
            }
          });

          this.controls.Company = {
            $target: $clientCompany,
            field: 'компания:',
            key: 'clientId',
            getValue: function() { return this.$target.val(); },
            getTitle: function() {
              var title = $(this.$target[0].selectize.getItem(this.$target.val())).text();
              return (title !== '') ? title : '...';
            },
            clear: function() { this.$target[0].selectize.clear(); },
            init: function(ival) {
              var self = this;
              var companySuggest = this.$target[0].selectize;

              KT.Dictionary.getAsList('companies', {
                  'companyId': ival,
                  'fieldsFilter': [],
                  'lang': 'ru'
                })
                .done(function(companies) {
                  if (companies.length !== 0) {
                    companySuggest.addOption(companies[0]);
                    companySuggest.addItem(companies[0].companyId);
                  } else {
                    self.clear();
                  }
                })
                .fail(function() {
                  self.clear();
                });
            }
          };
        }

        var self = this;

        //установка стартовых значений, если есть, и привязка событий
        for (var controlName in this.controls) {
          var control = this.controls[controlName];
          var controlKey = control.key;

          self.controlsKeyMap[controlKey] = control;

          if (
            requestParams[controlKey] !== undefined &&
            requestParams[controlKey] !== null &&
            requestParams[controlKey] !== false &&
            requestParams[controlKey] !== ''
          ) {
            control.init(requestParams[controlKey]);
            self.renderControlLabel(control, true);
          }
          
          control.$target.on('change', {ctrl:control}, function(e) {
            var ctrl = e.data.ctrl;
            var controlValue = ctrl.getValue();

            if (
              controlValue !== '' && 
              controlValue !== null &&
              controlValue !== false &&
              controlValue !== []
            ) {
              self.renderControlLabel(ctrl);
            } else {
              self.clearControlLabel(ctrl.key);
            }

            self.togglePlaceholder();
          });
        }
      },
      /**
      * Инициализация контролов формы сортировки
      * @param {Object} requestParams - стартовые параметры запроса
      */
      initSortFormControls: function(requestParams, sortOptions) {
        var sortControls = '';
        for (var option in sortOptions) {
          if(sortOptions.hasOwnProperty(option)) {
            sortControls += Mustache.render(_instance.mds.tpl.sortOption, {
              'sortOption': option,
              'optionName': sortOptions[option][1]
            });
          }
        }
        this.elem.$sortOptionsList.html(sortControls);
        this.elem.$sortFields = this.elem.$sortOptionsList.find('input[name="orderField"]');
        this.setSortOption(requestParams.sortBy[0], requestParams.sortDir);
      },
      /** 
      * Возвращает контро по его ключу 
      * @param {string} controlKey - ключ контрола
      * @return {Object} контрол
      */
      getControlByKey: function(controlKey) {
        return this.controlsKeyMap[controlKey];
      },
      /**
      * Изменение подсказок для полей страны и менеджера
      * @param {Array} options - массив значений для подсказки
      * @param {Object} control - Объект контрола для подсказки (из this.controls)
      */
      updateControlSuggest: function(options, control) {
        var suggControl = control.$target[0].selectize;
        var currval = suggControl.getValue();
        //var currtext=$(suggControl.getItem(suggControl.getValue())).text();

        //suggControl.clearOptions();
        //options.unshift();
        var newOptions = [];

        for (var optvalue in options) {
          if (options.hasOwnProperty(optvalue)) {
            newOptions.push({'value':optvalue, 'text':options[optvalue]});
          }
        }

        suggControl.addOption(newOptions);

        if (currval !== '') {
          if (options[currval] === undefined) {
            suggControl.updateOption(currval, {'value':currval, 'text':currval});
          }
          suggControl.addItem(currval, true);
        } else { suggControl.clear(true); }

        return this;
      },
      /**
      * Рендер метки контрола в поле поиска 
      * @param {Object} control - элемент управления
      * @param {Boolean} [isApplied] - применено ли значение к 
      */
      renderControlLabel: function (control, isApplied) {
        var fieldLabel = control.field;
        var value = control.getTitle();
        var key = control.key;

        var labelText = (fieldLabel.indexOf(':') === -1) ? fieldLabel : fieldLabel+' '+value;
        var $label = this.elem.$inputField.children('.js-orl-filter--control-label').filter('[data-key="'+key+'"]');

        if ($label.length > 0) {
          $label.text(labelText);
        } else {
          $(Mustache.render(_instance.mds.tpl.filterControlLabel, {
            'key': key,
            'text': labelText,
            'isApplied': (isApplied === true) ? true : false
          })).appendTo(this.elem.$inputField);
        }

        return this;
      },
      /**
      * Возвращает выбранные значения фильтрации
      * @return {Object} - выбранные значения фильтров
      */
      getFilterValues: function() {
        var filterValues = {};

        for (var controlName in this.controls) {
          var control = this.controls[controlName];
          var controlValue = control.getValue();

          if (controlValue === '' || controlValue === []) {
            controlValue = null;
          }

          filterValues[control.key] = controlValue;
        }

        return filterValues;
      },
      /** Отмечает все метки как примененные для фильтрации */
      markLabelsApplied: function() {
        this.elem.$inputField.children('.js-orl-filter--control-label').addClass('is-applied');
      },
      /** 
      * Удаление метки контрола в поле поиска 
      * @param {String} controlKey - ключ контрола
      */
      clearControlLabel: function(controlKey) {
        this.elem.$inputField.children('div[data-key="' + controlKey + '"]').remove();
      },
      /** Очистка значений контролов */
      clearControls: function() {
        for (var control in this.controls) {
          this.controls[control].clear();
        }
      },
      /**
      * Установка опции сортировки
      * @param {String} option - ключ опции сортировки
      * @param {String} direction - направление сортировки
      */
      setSortOption: function(option, direction) {
        this.elem.$sortFields.prop('checked',false).parent().removeClass('sort-asc sort-desc checked');
        this.elem.$sortFields.filter('[value="' + option + '"]').parent().addClass('checked sort-' + direction);
      },
      /**
      * Рендер колтчества найденных заявок
      * @param {Number} ordersAmount - количество заявок 
      */
      renderOrdersAmount: function(ordersAmount) {
        this.elem.$orderQuan.text(ordersAmount);
        this.elem.$orderQuanLabel.text(
          declOfNum(ordersAmount, ['найденная заявка','найденные заявки','найденных заявок'])
        );
      },
      /** 
      * Управление состоянием формы поиска (свернута/развернута) 
      * @param {String} [toggle] - заданное состояние
      */
      toggleForm: function(toggle) {
        if (toggle !== undefined) {
          if (toggle === 'show') {
            this.elem.$searchContainer.addClass('focus');
          } else {
            this.elem.$searchContainer.removeClass('focus');
          }
        } else {
          if (this.elem.$searchContainer.hasClass('focus')) {
            this.elem.$searchContainer.removeClass('focus');
          } else {
            this.elem.$searchContainer.addClass('focus');
          }
        }
      },
      /** Управление плейсхолдером формы поиска */
      togglePlaceholder: function() {
        if (this.elem.$inputField.children('.orl-filter-statusbar__item').length > 0) {
          this.elem.$placeholder.css({'display':'none'});
        } else {
          this.elem.$placeholder.css({'display':'inline-block'});
        }
      }
    };

    return SearchForm;
  };

  /** 
  * Обновление вариантов для выбора полей фильтра 
  * @param {Object} suggestData - данные саджеста
  */
  modView.prototype.updateSearchFormSuggest = function(suggestData) {
    this.SearchForm
      .updateControlSuggest(suggestData.managers, this.SearchForm.controls.Manager)
      .updateControlSuggest(suggestData.tourleaders, this.SearchForm.controls.Tourleader)
      .updateControlSuggest(suggestData.countries, this.SearchForm.controls.Country)
      .updateControlSuggest(suggestData.cities, this.SearchForm.controls.City);
  };

  /**
  * Очистка списка заявок
  */
  modView.prototype.clearOrderList = function() {
    this.$orderListContainer.children('.js-orl-list-item').remove();
    this.initPreLoader();
    return this;
  };

  /**
  * Отображение изменений заявки
  * @param {Object} order - Новые данные заявки
  * @param {Boolean} hasStatusChanged - изменился ли статус заявки
  */
  modView.prototype.showOrderChanges = function(order, hasStatusChanged) {
    var $el = $(Mustache.render(this.mds.tpl.orderListItem, this.mapOrderInfo(order)));
    var elHeight = 73;

    this.$orderListContainer.find('.js-orl-list-item')
      .filter('[data-id="' + order.orderId + '"]')
        .animate({'height': '1px'}, 500, 'linear', function() {
          $(this)
            .html($el.html())
            .attr('data-id', order.orderId)
            .addClass('orderChanged');
        })
        .animate({'height': elHeight + 'px'}, 1000, 'swing', function() {
          $el = $(this);

          $el.css({'outline':'2px solid #fde93e','overflow':'visible'})
            .animate({
              boxShadow: '0 0 20px rgba(253,233,62,0.7),0 0 10px rgba(253,233,62,0.7),0 0 5px rgba(253,233,62,0.7)'
            }, 1000, 'overBounce');
            
          var $icon = $el.find('.js-orl-list-item--status-icon');

          if (hasStatusChanged === 1) {
            $icon.parent().find('.js-orl-list-item--status-bg')
              .pulse({'opacity': '1'}, {duration: 700, pulses: 4});
          }

          setTimeout(function(){
            $el.css({'outline': '0px solid #fde93e'})
              .animate({
                boxShadow: '0 0 0px rgba(253,233,62,0.7),0 0 0px rgba(253,233,62,0.7)'
              }, 100, 'linear');
          }, 5000);
        });
  };

  /**
  * Отображение удаления элемента списка
  * @param {Number} orderId - Id заявки
  */
  modView.prototype.showOrderRemoval = function(orderId) {
    console.log('removing: ' + orderId);
    this.$orderListContainer.find('.js-orl-list-item')
      .filter('[data-id="' + orderId + '"]')
       .animate({'height':'1px'}, 500, 'swing', function(){ $(this).remove(); });
  };

  /**
  * Добавление элемента списка
  * @param {Object} order - новые данные заявки
  * @param {Number} position - позиция для вставки
  * @param {Boolean} isNewItem - признак нового элемента
  *
  * @todo potential bug with no "insertAfter" element yet present, maybe add while-sleep hack?
  * @todo fix hardcoded height value, move styles to classes
  */
  modView.prototype.showOrderAdd = function(order, position, isNewItem) {
    var $placeholder = $('<div class="orl-list-item js-orl-list-item is-placeholder"></div>');
    $placeholder.css({'overflow':'hidden','height':'1px'});

    if (position === 0) {
      $placeholder = $placeholder.insertBefore(this.$orderListContainer.find('.js-orl-list-item').first());
    } else {
      $placeholder = $placeholder.insertAfter(this.$orderListContainer.find('.js-orl-list-item').eq(position - 1));
    }

    var $el = $(Mustache.render(this.mds.tpl.orderListItem, this.mapOrderInfo(order)));
    var elHeight = 73;//this.$orderListContainer.find('.oneRequest').first().height();

    var addClasses = (isNewItem) ? 'orderAdded' : '';

    $placeholder
      .html($el.html())
      .attr('data-id', order.orderId)
      .removeClass('is-placeholder')
      .addClass(addClasses)
      .animate({'height': elHeight + 'px'}, 1000, 'swing', function() {
        var $el = $(this);

        $el.css({'outline':'2px solid #56db3b','overflow':'visible'})
          .animate({
            boxShadow: '0 0 20px rgba(86,219,59,0.7),0 0 10px rgba(86,219,59,0.7),0 0 5px rgba(86,219,59,0.7)'
          }, 1000, 'overBounce');
          
        setTimeout(function() {
          $el.css({'outline':'0px solid #fde93e'})
            .animate({
              boxShadow:'0 0 0px rgba(86,219,59,0.7),0 0 0px rgba(86,219,59,0.7)'
            }, 100, 'linear');
        }, 5000);
    });
  };

  /**
  * Перемещение элемента списка
  * @param {Object} order - новые данные заявки
  * @param {Number} position - позиция для вставки
  * @param {Boolean} hasOrderChanged - флаг изменения данных заявки
  * @param {Boolean} hasStatusChanged - флаг изменения статуса заявки
  *
  * @todo fix hardcoded height value
  */
  modView.prototype.showOrderMove = function(order, position, hasOrderChanged, hasStatusChanged) {
    var _instance = this;

    console.log('перемещение: ' + position);
    console.log(this.$orderListContainer.find('.js-orl-list-item').first());

    var $placeholder = $('<div class="orl-list-item js-orl-list-item is-placeholder"></div>');
    $placeholder.css({'overflow':'hidden','height':'1px'});

    if (position === 0) {
      $placeholder = $placeholder.insertBefore(this.$orderListContainer.find('.js-orl-list-item').first());
    } else {
      $placeholder = $placeholder.insertAfter(this.$orderListContainer.find('.js-orl-list-item').eq(position - 1));
    }

    _instance.$orderListContainer.find('.js-orl-list-item')
      .filter('[data-id="' + order.orderId + '"]')
        .animate({'height':'1px'},300,'swing',function() {
          var $el = $(this).detach();

          if (hasOrderChanged) {
            $el = $(Mustache.render(_instance.mds.tpl.orderListItem, _instance.mapOrderInfo(order)));
          }

          var addClasses = (hasOrderChanged) ? 'orderChanged' : '';
          var elHeight = 73;//_instance.$orderListContainer.find('.oneRequest').first().height();

          $placeholder
            .html($el.html())
            .attr('data-id', order.orderId)
            .removeClass('is-placeholder')
            .addClass(addClasses)
            .animate({'height': elHeight + 'px'}, 1000, 'swing', function() {
              $el = $(this);
              var $icon = $el.find('.js-orl-list-item--status-icon');

              if (hasOrderChanged !== 0) {
                $el.css({'outline':'2px solid #fde93e'})
                  .animate({
                    boxShadow: '0 0 20px rgba(253,233,62,0.7),0 0 10px rgba(253,233,62,0.7),0 0 5px rgba(253,233,62,0.7)'
                  }, 500, 'easeOutBounce');

                if (hasStatusChanged === 2) {
                  $icon.parent().find('.js-orl-list-item--status-bg')
                    .pulse({'opacity': '1'}, {duration: 700, pulses: 4});
                }
              }

              $el.css({'overflow':'visible'});

              setTimeout(function() {
                $el
                  .css({'outline':'0px solid #fde93e'})
                  .animate({
                    boxShadow: '0 0 0px rgba(253,233,62,0.7),0 0 0px rgba(253,233,62,0.7)'
                  }, 100, 'linear');
              }, 5000);
            });
       });
  };

  /**
  * [draft] Управление прелоадером
  * @todo rework as common component
  */
  modView.prototype.initPreLoader = function() {
    this.$spinner = this.$spinner.appendTo(this.$orderListContainer);
    return this;
  };
  modView.prototype.stopPreLoader = function() {
    this.$spinner = this.$spinner.detach();
    return this;
  };

  /**
  * [draft] Управление кнопкой Moar!
  */
  modView.prototype.disableMoar = function() {
    this.$showMore.prop('disabled',true);
  };
  modView.prototype.enableMoar = function() {
    this.$showMore.prop('disabled',false);
  };

  /**
  * Подготовка массива данных для шаблона заявки в списке
  * @param {Object} item - Объект с исходной информацией о заявке
  */
  modView.prototype.mapOrderInfo = function(item) {
    var _instance = this;
    var localSum = 0;
    var requestedSum = 0;
    var orderServices = '';
    var dateAmend;
    var countryCode = 'unknown';
    var presentServices = [];
    var offline = false;

    if(item.countryIataCode !== undefined && item.countryIataCode !== null) {
      countryCode = String(item.countryIataCode).toLowerCase();
    }

    if (Array.isArray(item.services) && item.services.length > 0) {
      var SERVICE_STATUS_DONE = 8;

      item.services.forEach(function(service) {
        if (service.dateAmend !== '0000-00-00 00:00:00' && service.dateAmend !== null) {
          var da = moment(service.dateAmend,'YYYY-MM-DD HH:mm:ss').valueOf();

          if (dateAmend === undefined) {
            dateAmend = da;
          } else {
            dateAmend = (dateAmend < da) ? dateAmend : da;
          }
        }

        var servId = parseInt(service.serviceType);

        if (presentServices[servId] !== undefined) {
          /*jshint -W016 : bitwise operator */
          presentServices[servId].allServicesDone &= (service.status === SERVICE_STATUS_DONE);
          /*jshint +W016*/
          presentServices[servId].servicesList += '<br>' + service.serviceName;
        } else {
          presentServices[servId] = {
            'allServicesDone': (service.status === SERVICE_STATUS_DONE),
            'servicesList': KT.getCatalogInfo('servicetypes', servId, 'title') + '<br>' + service.serviceName,
            'serviceIcon': KT.getCatalogInfo('servicetypes', servId, 'icon').replace('"',"'")
          };
        }

        if (service.offline === true) { offline = true; }

        switch (KT.profile.userType) {
          case 'op':
            switch (KT.profile.prices) {
              case 'net':
                localSum += Number(service.localNetSum);
                requestedSum += Number(service.requestedNetSum);
                break;
              case 'gross':
                localSum += Number(service.localSum);
                requestedSum += Number(service.requestedSum);
                break;
            }
            break;
          case 'agent':
            switch (KT.profile.prices) {
              case 'net':
                localSum += Number(service.localSum) - Number(service.localCommission);
                requestedSum += Number(service.requestedSum) - Number(service.requestedCommission);
                break;
              case 'gross':
                localSum += Number(service.localSum);
                requestedSum += Number(service.requestedSum);
                break;
            }
            break;
        }
      });

      presentServices.forEach(function(service) {
        orderServices += Mustache.render(_instance.mds.tpl.orderListItemServices, service);
      });
    }

    var kmpManagerName = item.managerKMPLastName + ' ' +
    ((item.managerKMPFirstName !== '') ? item.managerKMPFirstName.substr(0,1) + '. ' : '') +
    ((item.managerKMPMiddleName !== '') ? item.managerKMPMiddleName.substr(0,1) + '.' : '');

    var clientManagerName = item.mgrLastName + ' ' +
    ((item.mgrFirstName !== '') ? item.mgrFirstName.substr(0,1) + '. ' : '') +
    ((item.mgrMiddleName !== '') ? item.mgrMiddleName.substr(0,1) + '.' : '');

    var creatorName = item.creatorLastName + ' ' +
    ((item.creatorFirstName !== '') ? item.creatorFirstName.substr(0,1) + '. ' : '') +
    ((item.creatorMiddleName !== '') ? item.creatorMiddleName.substr(0,1) + '.' : '');

    var touristName = ((item.touristLastName !== null) ? item.touristLastName : '') +
    ' ' + ((item.touristFirstName !== null) ? item.touristFirstName : '');

    var ostatus = parseInt(item.status);
    var order = {
      'isArchived': item.archive,
      'orderId': item.orderId,
      'dolc': moment(item.dolc, 'YYYY-MM-DD HH:mm:ss').format(KT.config.datetimeFormat),
      'dateAmend': (KT.profile.userType === 'op' || dateAmend === undefined) ? false : 
        moment(dateAmend).format(KT.config.datetimeOutFormat),
      'orderStatusIcon': KT.getCatalogInfo('orderstatuses',ostatus,'icon'),
      'orderStatusText': KT.getCatalogInfo('orderstatuses',ostatus,'title'),
      'touristName': htmlDecode(touristName),
      'touristCount': (item.touristsCount > 1) ? '+ '+(item.touristsCount-1) : '',
      'startDate': (item.startdate !== null) ? moment(item.startdate,'YYYY-MM-DD HH:mm:ss').format('DD.MM') : '*',
      'endDate': (item.enddate !== null) ? moment(item.enddate,'YYYY-MM-DD HH:mm:ss').format('DD.MM') : '*',
      'country': item.country,
      'countryCode': countryCode,
      'orderServices': orderServices,
      'localSum': localSum.toMoney(0,',',' '),
      'requestedSum': requestedSum.toMoney(0,',',' '),
      'requestedCurrency': KT.getCatalogInfo('lcurrency',KT.profile.viewCurrency,'icon'),
      'KmpManagerName': htmlDecode(kmpManagerName),
      'clientCompany': (KT.profile.userType !== 'op') ? null :
        item.agentCompany,
      'creator': htmlDecode(creatorName),
      'vip': item.vip,
      'offline': offline
    };

    switch (KT.profile.userType) {
      case 'op':
        if (ostatus === 1) {
          order.orderStatusText = 'Ручной режим';
          order.manualMode = true;
        }
      break;
    }

    return order;
  };

  /**
  * Возвращает сообщение диалога создания новой заявки 
  * @param {Array} actions - действия, доступные из модального окна (структура buttons)
  */
  modView.prototype.renderCreateOrderModal = function(actions) {
    var modal = KT.Modal.notify({
        type: 'info',
        title: 'Создание заявки',
        msg: Mustache.render(this.mds.tpl.createOrderModal, {}),
        buttons: actions
      });
    
    var $company = modal.$container.find('.js-modal-create-order--company');
    var $contract = modal.$container.find('.js-modal-create-order--contract');

    $company
      .selectize({
        plugins: {
          'key_down': { start: 2 },
          'jirafize': { completely: true }
        },
        openOnFocus: true,
        create: false,
        selectOnTab: true,
        highlight: false,
        loadThrottle: 300,
        valueField: 'companyId',
        labelField: 'name',
        sortField:'seqid',
        options:[],
        score:function() {
          return function(item) {
            return 1000 / (item.seqid);
          };
        },
        render: {
          item: function(item) {
            return '<div class="modal-create-order-company-select__control-item">'+item.name+'</div>';
          },
          option: function(item) {
            return '<div class="modal-create-order-company-select__control-option">'+item.name+'</div>';
          }
        },
        load: function(query, callback) {
          var self = this;

          this.clearOptions();

          if (!query.length || query.length < 2) {
            return callback();
          }

          KT.Dictionary.getAsList('companies', {
              'textFilter': query,
              'fieldsFilter': [],
              'lang': 'ru'
            })
            .done(function(companies) {
              var $inputElem = self.$control;

              companies.forEach(function(item, i) {
                item.seqid = i + 1;
              });
              callback(companies);

              if (companies.length === 0) {
                $inputElem.addClass('warning');
                setTimeout(function() {
                  $inputElem.removeClass('warning');
                }, 2000);
              }
            })
            .fail(function() {
              callback();
              self.refreshOptions(true);
              var $inputElem = self.$control;
              $inputElem.addClass('warning');
              setTimeout(function() {
                $inputElem.removeClass('warning');
              }, 2000);
            });
        },
        onType:function(str) {
          if (str.length < 2) {
            this.close();
            this.clearOptions();
          }
        },
        onItemAdd:function(value) {
          var contractControl = $contract[0].selectize;

          var contracts = this.options[value].Contracts.map(function(contract) {
            return {
              'ContractID': contract['ContractID'],
              'ContractID_UTK': contract['ContractID_UTK'],
              'ContractDate': (contract['ContractDate'] === null) ? null :
                moment(contract['ContractDate'], 'YYYY-MM-DD').format('DD.MM.YYYY'),
              'ContractExpiry': (contract['ContractExpiry'] === null) ? 'бессрочный' :
                ('до ' + moment(contract['ContractExpiry'], 'YYYY-MM-DD').format('DD.MM.YYYY')),
              'expired': contract['expired'],
            };
          });

          contractControl.clearOptions();
          contractControl.addOption(contracts);
          contractControl.addItem(contracts[0].ContractID);
          contractControl.enable();
        },
        onItemRemove: function() {
          this.clearOptions();

          var contractControl = $contract[0].selectize;
          contractControl.clear();
          contractControl.clearOptions();
          contractControl.disable();
        },
        onChange: function(val) {
          if (val === '') {
            this.trigger('item_remove');
          }
        }
      });
    
    $contract
      .selectize({
        plugins:{'key_down':{},'on_blur':{}},
        openOnFocus: true,
        create: false,
        selectOnTab: true,
        highlight: false,
        valueField: 'ContractID',
        labelField: 'ContractID_UTK',
        options:[],
        render: {
          item: function(item) {
            return '<div class="modal-create-order-company-select__control-item ' +
              ((item.expired) ? 'modal-create-order-company-select--expired' : '') + '">' +
              item.ContractID_UTK + ' (' + item.ContractExpiry + ')</div>';
          },
          option: function(item) {
            return '<div class="modal-create-order-company-select__control-option ' +
              ((item.expired) ? 'modal-create-order-company-select--expired' : '') + '">' +
              item.ContractID_UTK + ' (' + item.ContractExpiry + ')</div>';
          }
        }
      });

    modal.$content.data('$company', $company);
    modal.$content.data('$contract', $contract);
  };

  return modView;
}));

(function(global,factory){

    KT.crates.OrderList.controller = factory(KT.crates.OrderList);

}(this,function(crate) {
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

    /** Обработка обновления списка заявко */
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

//# sourceMappingURL=cui-orderList.js.map