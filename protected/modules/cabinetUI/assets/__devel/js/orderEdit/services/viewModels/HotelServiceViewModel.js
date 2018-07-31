(function(global, factory){

    KT.crates.OrderEdit.services.HotelServiceViewModel = factory(KT.crates.OrderEdit);

}(this, function(crate) {
  var ServiceViewModel = crate.services.ServiceViewModel;

  /** карта категорий отеля */
  var categoryMap = {
    'FIVE': 5,
    'FOUR': 4,
    'THREE': 3,
    'TWO': 2,
    'ONE': 1
  };
  
  var voucherStatusMap = {
    1: 'ISSUED',
    2: 'VOIDED',
    3: 'RETURNED',
    4: 'CHANGED'
  };

  /**
  * View-объект для отображения оффера проживания
  * @constructor
  * @param {ServiceStorage} ServiceStorage - данные услуги
  * @param {Object} templates - ссылка на коллекцию шаблонов модуля
  * @param {Object} suppliersMap - список поставщиков
  */
  var HotelServiceViewModel = ServiceViewModel.extend(function(ServiceStorage, templates, suppliersMap) {
    ServiceViewModel.call(this, ServiceStorage, templates);

    this.serviceTypeName = 'hotel';

    this.touristsAges = {
      'adults': {
        'ordered':ServiceStorage.declaredTouristAges.adults,
        'current':0
      },
      'children': {
        'ordered':ServiceStorage.declaredTouristAges.children,
        'current':0
      }
    };

    this.bookTermsView = '';

    this.hotelName = ServiceStorage.name;
    this.hotelAddress = '';
    this.hotelPhoto = '';
    this.category = null;
    this.roomType = '';

    this.reservationData = false;
    this.vouchers = [];
    this.voucherLink = false;
    this.hasAdditionalServices = false;

    this.touristsMap = [];

    if (!ServiceStorage.isPartial) {      
      this.supplierCode = ServiceStorage.offerInfo.supplierCode;
      this.supplierName = (suppliersMap[this.supplierCode] !== undefined) ?
          suppliersMap[this.supplierCode].name :
          this.supplierCode;
          
      this.tosDocumentName = ServiceStorage.tosDocumentName;
    }
  });

  /* Шаблоны для отелей */
  HotelServiceViewModel.templates = {
    hotelFormHeader: 'orderEdit/services/hotel/hotelFormHeader',
    hotelFormMain: 'orderEdit/services/hotel/hotelFormMain',
    hotelServiceActions: 'orderEdit/services/hotel/hotelServiceActions',
    hotelBookTerms: 'orderEdit/services/hotel/hotelBookTerms',
    hotelFullInfo: 'orderEdit/services/hotel/hotelFullInfo',
    hotelAdditionalServices: 'orderEdit/services/hotel/hotelAdditionalServices',
    hotelMinimalPrice: 'orderEdit/services/hotel/hotelMinimalPrice',
    hotelManualForm: 'orderEdit/services/hotel/hotelManualForm'
  };

  /**  Подготовка шаблонов */
  HotelServiceViewModel.prototype.prepareViews = function() {
    if (!this.ServiceStorage.isPartial) {
      this.processHotelInfo();
      this.processReservation();
      this.processCustomFields();
    }

    this.prepareHeaderView();
    this.prepareMainView();
    this.prepareAdditionalServicesView();
    this.prepareActionsView();
    this.prepareBookTermsView();
  };

  /** Обработка информации об отеле */
  HotelServiceViewModel.prototype.processHotelInfo = function() {
    var ServiceStorage = this.ServiceStorage;

    this.hotelName = (ServiceStorage.offerInfo.hotelInfo !== null) ? 
        ServiceStorage.offerInfo.hotelInfo.name : 'нет названия';

    this.hotelAddress = (ServiceStorage.offerInfo.hotelInfo !== null) ? 
        ServiceStorage.offerInfo.hotelInfo.address : 'нет адреса';

    this.hotelPhoto = (ServiceStorage.offerInfo.hotelInfo !== null) ? 
        ServiceStorage.offerInfo.hotelInfo.mainImageUrl : '';

    this.category = (
        ServiceStorage.offerInfo.hotelInfo === null || 
        ServiceStorage.offerInfo.hotelInfo.category === null
      ) ? false : (function(c) {
        var stars = [];
        if (categoryMap[c] !== undefined) {
          for (var i = 0; i < categoryMap[c]; i++) {
            stars.push(true);
          }
        }
        return stars;
      }(ServiceStorage.offerInfo.hotelInfo.category));

    this.roomType = ServiceStorage.offerInfo.roomType;
  };

  /** Обработка данных брони и ваучеров */
  HotelServiceViewModel.prototype.processReservation = function() {
    var ServiceStorage = this.ServiceStorage;

    this.reservationData = (ServiceStorage.offerInfo.hotelReservations !== null) ?
          {'number': ServiceStorage.offerInfo.hotelReservations.reservationNumber} : 
          false;

    this.vouchers = (
        ServiceStorage.offerInfo.hotelReservations !== null && 
        Array.isArray(ServiceStorage.offerInfo.hotelReservations.hotelVouchers)
      ) ? ServiceStorage.offerInfo.hotelReservations.hotelVouchers.map(function(voucher) {
        voucher.status = voucherStatusMap[voucher.status];
        return voucher;
      }) : null;

    var activeVouchers = (this.vouchers !== null) ? this.vouchers.filter(function(voucher) {
      return voucher.status === 'ISSUED';
    }) : [];
    
    this.voucherLink = (activeVouchers.length > 0) ? activeVouchers[0].receiptUrl : false;
  };

  /** Обработка дополнительных полей  */
  HotelServiceViewModel.prototype.processCustomFields = function() {
    var self = this;
    var unprocessedFields = ServiceViewModel.prototype.processCustomFields.call(this);

    // Обработка специализированных доп. полей
    if (Array.isArray(unprocessedFields)) {
      unprocessedFields.forEach(function(fieldData) {
        switch (fieldData.typeTemplate) {
          case 5: // minimal price
            if (fieldData.value === null) { return; }
            var minimalPriceData = JSON.parse(fieldData.value);

            self.minimalPriceView = Mustache.render(self.tpl.hotelMinimalPrice, {
              'hotelName': minimalPriceData.hotelName,
              'room': minimalPriceData.roomType,
              'mealType': minimalPriceData.mealType,
              'price': Number(minimalPriceData.price).toMoney(0,',',' '),
              'currency': KT.getCatalogInfo('lcurrency', minimalPriceData.currency, 'icon')
            });
            break;
        }
      });
    }
  };

  /** Подготовка интерфейса шапки */
  HotelServiceViewModel.prototype.prepareHeaderView = function() {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    var dateFrom = ServiceStorage.startDate;
    var dateTo = ServiceStorage.endDate;
    var nightsCount = dateTo.startOf('day').diff(dateFrom.startOf('day'), 'days');
    var daysCount = nightsCount + 1;

    var taxes = (ServiceStorage.taxes !== null) ? 
      ServiceStorage.taxes.map(function(tax) {
          return {
            'description': tax.description,
            'amount': Number(tax.amount).toMoney(0,',',' '),
            'currencyIcon': KT.getCatalogInfo('lcurrency', tax.currency, 'icon'),
          };
        }) : null;

    var supplierCommission = (ServiceStorage.supplierCommission !== null) ? {
      'amount': Number(ServiceStorage.supplierCommission.amount).toMoney(0,',',' '),
      'currencyIcon': KT.getCatalogInfo('lcurrency', ServiceStorage.supplierCommission.currency, 'icon'),
    } : null;

    var hasPriceFactors = (taxes !== null || supplierCommission !== null);

    this.headerView = Mustache.render(this.tpl.hotelFormHeader, {
      'priorityOffer': !ServiceStorage.hasTravelPolicy ? false :
        ServiceStorage.offerInfo.travelPolicy.priorityOffer,
      'isPartial': ServiceStorage.isPartial,
      'roomType': (!ServiceStorage.isPartial) ? ServiceStorage.offerInfo.roomType : '',
      'hotelId': (!ServiceStorage.isPartial) ? ServiceStorage.offerInfo.hotelId : '',
      'hotelName': self.hotelName,
      'hotelAddress': self.hotelAddress,
      'hotelPhoto': self.hotelPhoto,
      'category': self.category,
      'priceLocal': Number(ServiceStorage.prices.inLocal.client.gross).toMoney(0,',',' '),
      'priceInView': Number(ServiceStorage.prices.inView.client.gross).toMoney(0,',',' '),
      'viewCurrencyIcon': KT.getCatalogInfo(
          'lcurrency', ServiceStorage.prices.inView.currencyCode,'icon'
        ),
      'priceFactors': (KT.profile.userType === 'op' && hasPriceFactors) ? {
        'taxes': (taxes !== null) ? {'list': taxes} : null,
        'supplierCommission': supplierCommission
      } : null,
      'statusIcon': KT.getCatalogInfo('servicestatuses', ServiceStorage.status, 'icon'),
      'statusTitle': (KT.profile.userType === 'op' && ServiceStorage.status === 9) ?
        'Ручной режим' :
        KT.getCatalogInfo('servicestatuses', ServiceStorage.status, 'title'),
      'dateFrom': {
        'wd': dateFrom.format('dd'),
        'dm': dateFrom.format('DD.MM')
      },
      'dateTo': {
        'wd': dateTo.format('dd'),
        'dm': dateTo.format('DD.MM')
      },
      'days': {
        'count': daysCount,
        'label': declOfNum(daysCount,['день','дня','дней'])
      },
      'residents': {
        'adults': ServiceStorage.declaredTouristAges.adults,
        'children': ServiceStorage.declaredTouristAges.children,
      },
      'reservationData': self.reservationData,
      'voucherLink': self.voucherLink
    });
  };

  /** Подготовка интерфейса главного блока */
  HotelServiceViewModel.prototype.prepareMainView = function() {
    var ServiceStorage = this.ServiceStorage;

    var dateFrom = ServiceStorage.startDate;
    var dateTo = ServiceStorage.endDate;
    var nightsCount = dateTo.startOf('day').diff(dateFrom.startOf('day'), 'days');
    var daysCount = nightsCount + 1;

    if (!ServiceStorage.isPartial) {
      this.mainView = Mustache.render(this.tpl.hotelFormMain, {
        'offerSupplier': (KT.profile.userType === 'op') ? this.supplierName : false,
        'roomType': this.roomType,
        'roomTypeDescription':(
            typeof ServiceStorage.offerInfo.roomTypeDescription === 'string' &&
            ServiceStorage.offerInfo.roomTypeDescription !== ''
          ) ? ServiceStorage.offerInfo.roomTypeDescription.replace(/<\/?[^>]+>/gi, '') : false,
        'mealType': ServiceStorage.offerInfo.mealType,
        'daysCount': daysCount + ' ' + declOfNum(daysCount,['день','дня','дней']),
        'nightsCount': nightsCount + ' ' + declOfNum(nightsCount,['ночь','ночи','ночей']),
        'fareName':(
            typeof ServiceStorage.offerInfo.fareName === 'string' &&
            ServiceStorage.offerInfo.fareName !== ''
          ) ? ServiceStorage.offerInfo.fareName.replace(/<\/?[^>]+>/gi, '') : false,
        'fareDescription':(
            typeof ServiceStorage.offerInfo.fareDescription === 'string' &&
            ServiceStorage.offerInfo.fareDescription !== ''
          ) ? ServiceStorage.offerInfo.fareDescription.replace(/<\/?[^>]+>/gi, '') : false,
        'voucherLink': this.voucherLink
      });
    }
  };

  /** Подготовка интерфеса дополнительных услуг */
  HotelServiceViewModel.prototype.prepareAdditionalServicesView = function() {
    if (this.ServiceStorage.isPartial) { return; }
    if (this.ServiceStorage.additionalServiceOffers === null) { return; }

    this.hasAdditionalServices = true;

    var availableAddServices = this.ServiceStorage.additionalServiceOffers.getServicesList();
    var issuedAddServices = this.ServiceStorage.additionalServices.getServicesList();

    /** @todo встроить шаблоны в компоненты */
    this.ServiceStorage.additionalServiceOffers.setTemplates(this.tpl);
    this.ServiceStorage.additionalServices.setTemplates(this.tpl);

    var issuedServiceTypes = {};
    issuedAddServices.forEach(function(addService) {
      issuedServiceTypes[addService.serviceType] = {
        'oneOfTypeAllowed': addService.oneOfTypeAllowed
      };
    });

    var self = this;

    this.additionalServicesView = Mustache.render(this.tpl.hotelAdditionalServices, {
      'issued': issuedAddServices
        .map(function(addService) {
          var localSalesTerms = addService.salesTermsInfo.localCurrency.client;
          var viewSalesTerms = addService.salesTermsInfo.viewCurrency.client;
          
          return {
            'id': addService.innerId,
            'name': addService.getServiceName(),
            'typeName': addService.serviceTypeName,
            'icon': addService.serviceIcon,
            'localPrice': Number(localSalesTerms.amountBrutto).toMoney(0, ',', ' '),
            'localCurrency': KT.getCatalogInfo('lcurrency', localSalesTerms.currency, 'icon'),
            'viewPrice': Number(viewSalesTerms.amountBrutto).toMoney(2, ',', ' '),
            'viewCurrency': KT.getCatalogInfo('lcurrency', viewSalesTerms.currency, 'icon'),
            'status': (addService.status === 0) ? null : {
              'waitingBook': (addService.status === 1),
              'isBooked': (addService.status === 2),
              'isCancelled': (addService.status === 6),
              'isVoided': (addService.status === 7)
            },
            'bookedWithService': (addService.bookedWithService),
            'removeAllowed': (
              self.ServiceStorage.checkTransition('RemoveExtraService') &&
              addService.status === 0
            )
          };
        })
        .filter(function(addService) { return addService !== false; }),
      'addingServicesAllowed': (this.reservationData === false),
      'available': availableAddServices
        .map(function(addService) {
          var localSalesTerms = addService.salesTermsInfo.localCurrency.client;
          var viewSalesTerms = addService.salesTermsInfo.viewCurrency.client;
          
          return {
            'id': addService.innerId,
            'name': addService.getServiceName(),
            'typeName': addService.serviceTypeName,
            'icon': addService.serviceIcon,
            'addingAllowed': (
                !issuedServiceTypes.hasOwnProperty(addService.serviceType) || 
                !issuedServiceTypes[addService.serviceType].oneOfTypeAllowed
              ),
            'localPrice': Number(localSalesTerms.amountBrutto).toMoney(0, ',', ' '),
            'localCurrency': KT.getCatalogInfo('lcurrency', localSalesTerms.currency, 'icon'),
            'viewPrice': Number(viewSalesTerms.amountBrutto).toMoney(2, ',', ' '),
            'viewCurrency': KT.getCatalogInfo('lcurrency', viewSalesTerms.currency, 'icon')
          };
        })
        .filter(function(addService) { return addService !== false; })
    });
  };

  /** Подготовка интерфейса действий с услугой */
  HotelServiceViewModel.prototype.prepareActionsView = function() {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    if (!ServiceStorage.isPartial) {
      var serviceActions = this.getServiceActions();

      this.actionsView = Mustache.render(self.tpl.hotelServiceActions, serviceActions);
    } else { this.actionsView = ''; }
  };

  /** Подготовка интерфейса условий бронирования */
  HotelServiceViewModel.prototype.prepareBookTermsView = function() {
    var ServiceStorage = this.ServiceStorage;

    var penalties = null;

    if (Array.isArray(ServiceStorage.clientCancelPenalties)) {
      penalties = [];

      ServiceStorage.clientCancelPenalties.forEach(function(penalty) {
        penalties.push({
          'dateFrom': penalty.dateFrom.format(KT.config.dateFormat),
          'dateTo': penalty.dateTo.format(KT.config.dateFormat),
          'description': penalty.description,
          'localAmount': Number(penalty.penaltySum.inLocal.amount).toMoney(2, ',', ' '),
          'localCurrency': KT.getCatalogInfo('lcurrency', penalty.penaltySum.inLocal.currency, 'icon'),
          'viewAmount': Number(penalty.penaltySum.inView.amount).toMoney(2, ',', ' '),
          'viewCurrency': KT.getCatalogInfo('lcurrency', penalty.penaltySum.inView.currency, 'icon'),
        });
      });
    }

    this.bookTermsView = Mustache.render(this.tpl.hotelBookTerms, {
      'hotelName': this.hotelName,
      'roomType': this.roomType,
      'penalties': penalties
    });
  };

  /** 
  * Инициализация элементов управления после рендера формы
  * @param {Object} $container - форма услуги
  * @todo в принципе передать управление контейнером услуги в класс viewModel?
  */
  HotelServiceViewModel.prototype.initControls = function($container) {
    this.initAddServicesControls($container);

    if (this.customFields.length > 0) {
      var $customFields = $();

      this.customFields.forEach(function(customField) {
        $customFields = $customFields.add(customField.render());
      });

      $container.find('.js-service-form-custom-fields').html($customFields);

      this.customFields.forEach(function(customField) {
        customField.initialize();
      });
    }
  };

  /**
  * Инициализация элементов управления формы дополнительных услуг
  * @param {Object} $container - формв услуги
  */
  HotelServiceViewModel.prototype.initAddServicesControls = function($container) {
    if (this.hasAdditionalServices) {
      var $availableAddServicesList = $container.find('.js-service-form--available-add-services-list');
      var $showAddServiceList = $container.find('.js-service-form--show-add-services-list');
      var $hideAddServiceList = $container.find('.js-service-form--hide-add-services-list');

      $showAddServiceList.on('click', function() {
        $hideAddServiceList.addClass('active');
        $showAddServiceList.removeClass('active');
        $availableAddServicesList.slideDown(200).addClass('active');
      });

      $hideAddServiceList.on('click', function() {
        $hideAddServiceList.removeClass('active');
        $showAddServiceList.addClass('active');
        $availableAddServicesList.slideUp(200).removeClass('active');
      });
    }
  };

  /**
  * Связывание информации о туристах из заявки (getOrderTourists)
  * с информацией из услуги и формирование данных для блока туристов
  * @param {TouristStorage[]} tourists - массив информации по туристам
  * @param {Boolean} [overrideSave] - явное указание возможности сохранения привязки
  */
  HotelServiceViewModel.prototype.mapTouristsInfo = function(tourists, overrideSave) {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    this.touristsAges = {
      'adults': {
        'ordered': ServiceStorage.declaredTouristAges.adults,
        'current':0
      },
      'children': {
        'ordered': ServiceStorage.declaredTouristAges.children,
        'current':0
      }
    };
    this.touristsMap = [];

    if (tourists.length === 0) { return; }
    if (overrideSave === undefined) { overrideSave = false; }
    var isSavingAllowed = (
        !ServiceStorage.isPartial &&
        ((ServiceStorage.status === 9 && KT.profile.userType === 'op') || ServiceStorage.status === 0)
      );

    tourists.forEach(function(TouristStorage) {
      var isLinked = ServiceStorage.tourists.hasOwnProperty(TouristStorage.touristId);

      if (!ServiceStorage.isPartial) {

        // сохранение числа привязанных туристов по возрастным группам
        if (isLinked) {
          var age = moment.duration(
              moment().valueOf() - TouristStorage.birthdate.valueOf()
            ).asYears();
          if (age < 12) { self.touristsAges.children.current += 1; }
          else { self.touristsAges.adults.current += 1; }
        }
      }

      if (overrideSave || isSavingAllowed || isLinked) {
        self.touristsMap.push({
          'allowSave': isSavingAllowed,
          'touristId': TouristStorage.touristId,
          'firstName': TouristStorage.firstname,
          'surName': TouristStorage.lastname,
          'attached': isLinked,
          'touristExtra': ''
        });
      }
    });
  };

  /**
  * Рендер элементов управления блока туристоп
  * @param {Object} $tourist - блок туриста
  * @param {Object} tourist - данные туриста (touristsMap)
  */
  HotelServiceViewModel.prototype.renderTouristControls = function() {};

  /**
  * Получение структуры доступных действий над услугой
  * @return {Object} идентификатор услуги и разрешения на действия
  */
  HotelServiceViewModel.prototype.getServiceActions = function() {
    var ServiceStorage = this.ServiceStorage;

    var serviceActions = {
      'serviceId': ServiceStorage.serviceId,
      'ManualEdit': (KT.profile.userType === 'op' && ServiceStorage.status === 9) ? true : false,
      'save': (this.customFields.length > 0)
    };

    ServiceStorage.allowedTransitions.forEach(function(transition) {
      if (transition === "PayStart") {
        serviceActions[transition] = (KT.profile.userType !== 'corp');
      } else {
        serviceActions[transition] = true;
      }
    });

    // хак для разного стиля кнопок, переделать?
    if (KT.profile.userType !== 'op' && serviceActions['Manual']) {
      serviceActions['Manual'] = false;
      serviceActions['ToManager'] = true;
    }

    serviceActions.agreementSet = ServiceStorage.isTOSAgreementSet;

    if (!serviceActions.BookStart) {
      serviceActions.agreementDisabled = true;
      serviceActions.agreementSet = true;
    }

    return serviceActions;
  };

  /**
  * Создает список документов с условиями работы с услугой с методами их получения
  * @return {Object} объект с документами и методами их получения
  */
  HotelServiceViewModel.prototype.defineTermsDocuments = function() {
    var _instance = this;

    var termsDocuments = {
      'companyTOS': {
        'load':function(renderer,loader) {
          loader('companyTOS',renderer);
        }
      }
    };
    termsDocuments[_instance.tosDocumentName] = {
      'load':function(renderer) {
        renderer(_instance.tosDocumentName, _instance.bookTermsView);
      }
    };

    return termsDocuments;
  };

  /**
  * Рендер формы ручного редактирования улуги
  * @return {String} - код формы редактирования услуги
  */
  HotelServiceViewModel.prototype.renderManualForm = function() {
    var commonParams = ServiceViewModel.prototype.setCommonManualFormParams.call(this);

    var manualFormParams = $.extend(commonParams, {
          'reservation': (
            this.ServiceStorage.offerInfo.hotelReservations !== null &&
            this.ServiceStorage.offerInfo.hotelReservations.reservationNumber !== null
          ) ? this.ServiceStorage.offerInfo.hotelReservations.reservationNumber : null
      });

    return Mustache.render(this.tpl.hotelManualForm, manualFormParams);
  };

  /**
  * Инициализация элементов управления окна ручного редактирования услуги
  * @param {Object} $wnd - [jQueryDom] объект окна
  * @param {Object} mds - объект модуля
  * @todo Убрать зависимость от модуля
  */
  HotelServiceViewModel.prototype.initManualFormControls = function($wnd, mds) {
    var self = this;
    ServiceViewModel.prototype.initManualFormControls.call(this, $wnd, mds);

    var ServiceStorage = this.ServiceStorage;

    /* Изменение параметров брони */
    KT.Dictionary.getAsList('suppliers', {'serviceId': ServiceStorage.typeCode, 'active': 1})
      .then(function(hotelSuppliers) {
        /*
        var suppliers = [];
        for (var supplierCode in aviaSuppliers) {
          if (aviaSuppliers.hasOwnProperty(supplierCode)) {
            suppliers.push({
              'value': supplierCode,
              'name': aviaSuppliers[supplierCode].name
            });
          }
        } */

        $wnd.find('.js-ore-service-manualedit--supplier')
          .selectize({
            openOnFocus: true,
            create: false,
            valueField: 'supplierCode',
            labelField: 'name',
            options: hotelSuppliers,
            items: [self.supplierCode] //!!!
          });
      });
  };

  /**
  * Получение параметров для команды изменения брони
  * @param {Object} $manualForm - [jQuery DOM] форма ручного редактирования услуги
  */
  HotelServiceViewModel.prototype.getChangeBookDataParams = function($manualForm) {
    var newReservationNumber = $manualForm.find('.js-ore-service-manualedit--new-reservation-number').val();
    var supplier = $manualForm.find('.js-ore-service-manualedit--supplier').val();

    if (newReservationNumber === '') {
      if (this.ServiceStorage.offerInfo.hotelReservations !== null) {
        newReservationNumber = this.ServiceStorage.offerInfo.hotelReservations.reservationNumber;
      } else {
        return false;
      }
    }

    if (supplier === '') { supplier = this.supplierCode; }

    return  {
      'serviceId': this.serviceId,
      'reservationData': [{
        'reservationNumber': newReservationNumber,
        'supplierCode': supplier
      }]
    };
  };

  return HotelServiceViewModel;
  
}));
