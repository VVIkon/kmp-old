(function(global,factory){

    KT.crates.OrderEdit.services.ServiceViewModel = factory(KT.crates.OrderEdit);

}(this,function(crate) {

  /**
  * Базовый класс view-модели услуги
  * @constructor
  * @param {ServiceStorage} ServiceStorage - данные услуги
  * @param {Object} templates - ссылка на коллекцию шаблонов модуля
  */
  
  var ServiceViewModel = function(ServiceStorage, templates) {
    this.ServiceStorage = ServiceStorage;
    this.tpl = templates;
    this.customFields = [];
    
    this.headerView = '';
    this.mainView = '';
    this.additionalServicesView = '';
    this.minimalPriceView = '';
    this.actionsView = '';
  };

  /** Механизм наследования */
  ServiceViewModel.extend = function (cfunc) {
    cfunc.prototype = Object.create(this.prototype);
    cfunc.prototype.ancestor = this.prototype;
    cfunc.constructor = cfunc;
    return cfunc;
  };

  /** Подготовка шаблонов для вывода услуги */
  ServiceViewModel.prototype.prepareViews = function() {
    throw new Error('ServiceViewModel:prepareViews not implemented');
  };

  /** Подготовка шаблона шапки */
  ServiceViewModel.prototype.prepareHeaderView = function() {
    throw new Error('ServiceViewModel:prepareHeaderView not implemented');
  };

  /** Подготовка основного шаблона */
  ServiceViewModel.prototype.prepareMainView = function() {
    throw new Error('ServiceViewModel:prepareMainView not implemented');
  };

  /** Подготовка шаблона действий с услугой */
  ServiceViewModel.prototype.prepareActionsView = function() {
    throw new Error('ServiceViewModel:prepareActionsView not implemented');
  };

  /** 
  * Обработка дополнительных полей. 
  * Метод возвращает массив необработанных доп. полей, соответственно в дочернем классе
  * можно определить собственную обработку для уникальных/специализированных доп. полей
  * @return {Array|null} - массив доп. полей или null если доп. полей нет
  */
  ServiceViewModel.prototype.processCustomFields = function() {
    var self = this;
    var ServiceStorage = this.ServiceStorage;
    var CustomFieldsFactory = new crate.CustomFieldsFactory(this.tpl);
    
    this.customFields = [];
    var unprocessedFields = [];

    if (Array.isArray(ServiceStorage.customFields)) {
      // хак - поля типа textArea отсортировать в конец для нормального отображения
      ServiceStorage.customFields.sort(function(a,b) {
        var typeSorting = (a.typeTemplate === 2) ?
          ((b.typeTemplate === 2) ? 0 : 1) :
          ((b.typeTemplate === 2) ? -1 : 0);
        
        if (typeSorting !== 0) {
          return typeSorting;
        } else {
          return (a.require) ?
            (b.require ? 0 : -1) :
            (b.require ? 1 : 0);
        }
      });

      ServiceStorage.customFields.forEach(function(fieldData) {
        var customField = CustomFieldsFactory.create(fieldData);
        if (customField !== null) {
          self.customFields.push(customField);
        } else {
          unprocessedFields.push(fieldData);
        }
      });

      return unprocessedFields;
    } else { return null; }
  };

  /** Инициализация элементов управления после рендера формы */
  ServiceViewModel.prototype.initControls = function() {
    throw new Error('ServiceViewModel:initControls not implemented');
  };

  /**
  * Сбор значений дополнительных полей
  * @return {Array|null} - массив значений доп. полей или null в случае ошибки
  */
  ServiceViewModel.prototype.getCustomFieldsValues = function() {
    var ServiceStorage = this.ServiceStorage;
    var customFieldsValues = [];
    /* 
    * Флаг отсутствия ошибок при обработке полей. 
    * Флаг специально, чтобы отметить все ошибочные поля, а не первое папавшееся
    */
    var noErrors = true;

    this.customFields.forEach(function(field) {
      var fieldValue = field.getValue();
      if (field.modifiable) {
        if (field.validate(fieldValue)) {
          customFieldsValues.push({
            'fieldTypeId': field.fieldTypeId,
            'value': (fieldValue !== '') ? fieldValue : null,
            'serviceId': ServiceStorage.serviceId
          });
        } else { noErrors = false; }
      }
    });

    return noErrors ? customFieldsValues : null;
  };

  /**
  * Связывание информации о туристах из заявки (getOrderTourists)
  * с информацией из услуги и формирование данных для блока туристов
  * @param {TouristStorage[]} tourists - массив информации по туристам
  * @param {Boolean} [overrideSave] - явное указание возможности сохранения привязки
  */
  ServiceViewModel.prototype.mapTouristsInfo = function() {
    throw new Error('ServiceViewModel:mapTouristsInfo not implemented');
  };

  /**
  * Получение структуры данных привязок туристов
  * @return {Object[]} структура данных привязок
  */
  ServiceViewModel.prototype.getTouristsMap = function() {
    if (this.touristsMap !== undefined) {
      return this.touristsMap;
    } else {
      console.error('Ошибка получения данных формы туристов: сначала необходимо вызвать mapTouristInfo()');
      return false;
    }
  };

  /**
  * Получение статуса возможности добавления туриста к услуге
  * (на основании текущей привязки туристов)
  * @return {Boolean} возможность добавления туриста
  */
  ServiceViewModel.prototype.checkTouristAddAllowance = function() {
    var ServiceStorage = this.ServiceStorage;

    var isSavingAllowed = (
        !ServiceStorage.isPartial &&
        ((ServiceStorage.status === 9 && KT.profile.userType === 'op') || ServiceStorage.status === 0)
      );

    if (!isSavingAllowed) { return false; }

    var isAddingAllowed = false;

    for (var ag in this.touristsAges) {
      if (this.touristsAges.hasOwnProperty(ag)) {
        if (this.touristsAges[ag].ordered > this.touristsAges[ag].current) {
          isAddingAllowed = true;
        }
      }
    }
    
    return isAddingAllowed;
  };

  /**
  * Установка общих для всех услуг параметров формы ручного редактирования
  * @return {Object} - общие параметры
  */
  ServiceViewModel.prototype.setCommonManualFormParams = function() {
    var ServiceStorage = this.ServiceStorage;

    return {
      'serviceId': ServiceStorage.serviceId,
      'clientCurrency': ServiceStorage.prices.inClient.currencyCode,
      'clientGrossPrice': ServiceStorage.prices.inClient.client.gross.toMoney(2,',',' '),
      'agentCommission': ServiceStorage.prices.inClient.client.commission.amount.toMoney(2,',',' '),
      'supplierGrossPrice': ServiceStorage.prices.inSupplier.supplier.gross.toMoney(2,',',' '),
      'supplierCurrency': ServiceStorage.prices.inSupplier.currencyCode,
      'clientCancelPenalties': (ServiceStorage.clientCancelPenalties === null) ? null : 
        ServiceStorage.clientCancelPenalties.map(function(penalty, i) {
          return {
            'penaltyIndex': i,
            'dateFrom': penalty.dateFrom.format(KT.config.dateFormat),
            'dateTo': penalty.dateTo.format(KT.config.dateFormat),
            'amount': Number(penalty.penaltySum.inClient.amount).toMoney(2,',',' '),
            'currency': penalty.penaltySum.inClient.currency,
            'currencyIcon': KT.getCatalogInfo('lcurrency', penalty.penaltySum.inClient.currency, 'icon')
          };
        })
    };
  };

  /**
  * Инициализация общих для всех услуг элементов управления окна ручного редактирования услуги
  * @param {Object} $wnd - [jQueryDom] объект окна
  * @param {Object} mds - объект модуля
  */
  ServiceViewModel.prototype.initManualFormControls = function($wnd, mds) {
    var ServiceStorage = this.ServiceStorage;

    // настройка переключения между вкладками
    $wnd.on('click','.js-ore-service-manualedit--tab-link',function() {
      if (!$(this).hasClass('active')) {
        var $root = $(this).closest('.js-ore-service-manualedit');

        $root.children('.js-ore-service-manualedit--tab-header')
          .children('.js-ore-service-manualedit--tab-link')
            .filter('.active').removeClass('active');
        $(this).addClass('active');

        var $tabs = $root.children('.js-ore-service-manualedit--tab');
        $tabs.removeClass('active')
          .filter('[data-tab="'+$(this).data('tab')+'"]')
            .addClass('active');
      }
    });

    // даты начала - окончания услуги
    $wnd.find('.js-ore-service-manualedit--start-date')
      .val(this.ServiceStorage.startDate.format('DD.MM.YYYY'))
      .clndrize({
        'template':KT.tpl.clndrDatepicker,
        'eventName':'Дата начала услуги',
        'showDate':this.ServiceStorage.startDate,
        'clndr': {
          'constraints': {
            'startDate':moment().format('YYYY-MM-DD'),
            'endDate':moment().add(1,'years').format('YYYY-MM-DD')
          }
        }
      });

    $wnd.find('.js-ore-service-manualedit--end-date')
      .val(this.ServiceStorage.endDate.format('DD.MM.YYYY'))
      .clndrize({
        'template':KT.tpl.clndrDatepicker,
        'eventName':'Дата окончания услуги',
        'showDate':this.ServiceStorage.endDate,
        'clndr': {
          'constraints': {
            'startDate':moment().format('YYYY-MM-DD'),
            'endDate':moment().add(1,'years').format('YYYY-MM-DD')
          }
        }
      });

    // цены, комиссии, штрафы
    var $currencyLabels = $wnd.find('.js-ore-service-manualedit--selected-currency');
    
    $wnd.find('.js-ore-service-manualedit--currency')
      .selectize({
        openOnFocus: true,
        create: false,
        allowEmptyOption: false,
        valueField: 'value',
        labelField: 'text',
        options: [
          {
            'value': ServiceStorage.prices.inClient.currencyCode,
            'text': 'Валюта продажи'
          },
          {
            'value': ServiceStorage.prices.inSupplier.currencyCode,
            'text': 'Валюта поставщика'
          },
          {
            'value': ServiceStorage.prices.inView.currencyCode,
            'text': 'Валюта просмотра'
          },
          {
            'value': ServiceStorage.prices.inLocal.currencyCode,
            'text': 'Локальная валюта'
          }
        ],
        items: [ServiceStorage.prices.inClient.currencyCode],
        render: {
          item: function(item) {
            return '<div class="item">' + item.value + ' (' + item.text + ')</div>';
          },
          option: function(item) {
            return '<div class="option">' + item.value + ' (' + item.text + ')</div>';
          }
        },
        onItemAdd: function(value) {
          $currencyLabels.text(value);
        }
      });

    $wnd.find('.js-ore-service-manualedit--client-gross-price')
      .setValidation('price', null, true)
      .on('change, focusout', function() {
        var price = parseFloat($(this).val().replace(/\s+/g,'').replace(',','.'));
        if (isNaN(price) || $(this).val() === '') {
          $(this).val(ServiceStorage.prices.inClient.client.gross.toMoney(2,',',' '));
          $(this)
            .addClass('error')
            .one('click, focusin', function() { $(this).removeClass('error'); });
        }
      });

    $wnd.find('.js-ore-service-manualedit--agent-commission')
      .setValidation('price', null, true)
      .on('change, focusout', function() {
        var commission = parseFloat($(this).val().replace(/\s+/g,'').replace(',','.'));
        if (isNaN(commission) || $(this).val() === '') {
          $(this).val(ServiceStorage.prices.inClient.client.commission.amount.toMoney(2,',',' '));
          $(this)
            .addClass('error')
            .one('click, focusin', function() { $(this).removeClass('error'); });
        }
      });

    $wnd.find('.js-ore-service-manualedit--client-cancel-penalty-amount')
      .setValidation('price', null, true)
      .on('change, focusout', function() {
        var price = parseFloat($(this).val().replace(/\s+/g,'').replace(',','.'));
        if (isNaN(price) || $(this).val() === '') {
          $(this).val($(this).data('penalty'));
        }
      });

    /* Установка статуса */
    var statuses = [];
    for (var status in KT.getCatalogInfo.servicestatuses) {
      status = +status;
      if ([0,2,5,7,8].indexOf(status) !== -1) { // см. app/__devel/js/core/config/catalogs.js
        statuses.push({
          'value': status,
          'name': KT.getCatalogInfo.servicestatuses[status][1],
          'icon': KT.getCatalogInfo.servicestatuses[status][0]
        });
      }
    }

    $wnd.find('.js-ore-service-manualedit--change-status')
      .selectize({
        openOnFocus: true,
        create: false,
        valueField: 'value',
        labelField: 'name',
        options: statuses,
        render: {
          item: function(item) {
            return '<div data-value="' + item.value + '" class="item" title="' + item.name + '">' + 
              '<i class="service-status-small service-sm-status-' + item.icon + '"></i> ' + item.name + 
              '</div>';
          },
          option: function(item) {
            return '<div data-value="'+item.value+'" data-selectable class="option">' +
              '<i class="service-status-small service-sm-status-' + item.icon + '"></i> ' + item.name + 
              '</div>';
          }
        }
      });
  };

  return ServiceViewModel;

}));