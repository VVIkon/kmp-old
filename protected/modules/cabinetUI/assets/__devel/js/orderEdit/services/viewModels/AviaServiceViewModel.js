(function(global,factory) {

    KT.crates.OrderEdit.services.AviaServiceViewModel = factory(KT.crates.OrderEdit);

}(this, function(crate) {
  var ServiceViewModel = crate.services.ServiceViewModel;

  var ticketStatusMap = {
    1: 'ISSUED',
    2: 'VOIDED',
    3: 'RETURNED',
    4: 'CHANGED'
  };

  /**
  * View-объект для отображения оффера перелета
  * @constructor
  * @param {ServiceStorage} ServiceStorage - данные услуги
  * @param {Object} templates - ссылка на коллекцию шаблонов модуля
  * @param {Object} suppliersMap - список поставщиков
  */
  var AviaServiceViewModel = ServiceViewModel.extend(function(ServiceStorage, templates, suppliersMap) {
    ServiceViewModel.call(this, ServiceStorage, templates);

    this.serviceTypeName = 'avia';

    this.touristsAges = {
      'adults': {
        'ordered':ServiceStorage.declaredTouristAges.adults,
        'current':0
      },
      'children': {
        'ordered':ServiceStorage.declaredTouristAges.children,
        'current':0
      },
      'infants': {
        'ordered':ServiceStorage.declaredTouristAges.infants,
        'current':0
      }
    };

    this.fareRulesView = '';

    this.tripData = [];
    this.tripDates = []; //dates
    this.tripPoints = []; //flights

    this.hasPnr = false;
    this.pnrNumber = false;
    this.tickets = false;
    this.receipts = false;
    this.ticketLink = false;

    this.touristsMap = [];

    if (!ServiceStorage.isPartial) {
      this.supplierCode = ServiceStorage.offerInfo.supplierCode;

      this.supplierName = (suppliersMap[this.supplierCode] !== undefined) ?
        suppliersMap[this.supplierCode].name :
        this.supplierCode;
    }

    this.manualFormParams = null;
  });

  /* Шаблоны для авиа */
  AviaServiceViewModel.templates = {
    aviaFormHeader: 'orderEdit/services/avia/aviaFormHeader',
    aviaFormMain: 'orderEdit/services/avia/aviaFormMain',
    aviaServiceActions: 'orderEdit/services/avia/aviaServiceActions',
    aviaTrip: 'orderEdit/services/avia/aviaTrip',
    aviaTripRoute: 'orderEdit/services/avia/aviaTripRoute',
    aviaLoyaltyCard: 'orderEdit/services/avia/aviaLoyaltyCard',
    aviaNoLoyaltyCards: 'orderEdit/services/avia/aviaNoLoyaltyCards',
    aviaFareRule: 'orderEdit/services/avia/aviaFareRule',
    aviaFareRuleUnavailable: 'orderEdit/services/avia/aviaFareRuleUnavailable',
    aviaMinimalPrice: 'orderEdit/services/avia/aviaMinimalPrice',
    aviaManualForm: 'orderEdit/services/avia/aviaManualForm'
  };

  /**  Подготовка шаблонов */
  AviaServiceViewModel.prototype.prepareViews = function() {
    var ServiceStorage = this.ServiceStorage;

    if (!ServiceStorage.isPartial) {
      this.processTrips();
      this.processPnr();
      this.processCustomFields();
    } else {
      this.tripDates.push({
        'wd': ServiceStorage.startDate.format('dd'),
        'dm': ServiceStorage.startDate.format('DD.MM')
      });
    }

    this.prepareHeaderView();
    this.prepareMainView();
    this.prepareActionsView();
  };

  /** Обработка маршрута перелета и формирование данных */
  AviaServiceViewModel.prototype.processTrips = function() {
    var self = this;

    this.tripData = [];
    this.tripDates = [];
    this.tripPoints = [];

    var hasPnr = (
      !this.ServiceStorage.isPartial && 
      this.ServiceStorage.offerInfo.pnr !== undefined && 
      this.ServiceStorage.offerInfo.pnr !== null
    );

    this.ServiceStorage.offerInfo.itinerary.forEach(function(trip) {
      var firstSegment = trip.segments[0];
      var lastSegment = trip.segments[(+trip.segments.length - 1)];

      var startdate = moment(firstSegment.departureDate,'YYYY-MM-DD HH:mm:ss');
      var marketingAirlineCode = firstSegment.marketingAirline;

      self.tripDates.push({
        'wd': startdate.format('dd'),
        'dm': startdate.format('DD.MM')
      });

      self.tripPoints.push({
        'dep':{
          'city': firstSegment.departureCityName,
          'iata': firstSegment.departureAirportCode
        },
        'arr':{
          'city': lastSegment.arrivalCityName,
          'iata': lastSegment.arrivalAirportCode
        }
      });

      var tripRoute = {
        'alName': (KT.airlineCodes[marketingAirlineCode] !== undefined) ?
          KT.airlineCodes[marketingAirlineCode].name : marketingAirlineCode,
        'alLogo': (
            KT.airlineCodes[marketingAirlineCode] !== undefined &&
            KT.airlineCodes[marketingAirlineCode].hasLogo === true
          ) ? marketingAirlineCode : false,
        'segments':[]
      };

      trip.segments.forEach(function(seg, segIndex) {
        var waitingTime = false;

        if (segIndex + 1 < trip.segments.length) {
          var nextseg = trip.segments[segIndex + 1];
          var fhm = moment(seg.arrivalDate,'YYYY-MM-DD HH:mm:ss');
          var thm = moment(nextseg.departureDate,'YYYY-MM-DD HH:mm:ss');
          waitingTime = moment.duration(thm.valueOf() - fhm.valueOf()).asMinutes();
        }

        var stops = (!Array.isArray(seg.stops) || seg.stops.length === 0) ? [] : 
          seg.stops.map(function(stop) {
            return {
              'location': stop.stopCityName,
              'hours': Math.floor(stop.stopDuration / 60),
              'minutes': stop.stopDuration % 60
            };
          });

        /**
        * Жуткая костылина для соблюдения разметки
        * @todo переделать верстку вывода оффера 
        */
        var inforows = 2;
        if (seg.operatingAirline !== seg.marketingAirline) {
          inforows++;
        }
        if (stops.length > 0) {
          inforows++;
        }

        var departureDate = moment(seg.departureDate, 'YYYY-MM-DD HH:mm:ss');
        var arrivalDate = moment(seg.arrivalDate, 'YYYY-MM-DD HH:mm:ss');

        tripRoute.segments.push({
          'inforows': inforows,
          'flightNum':seg.marketingAirline + ' ' + seg.flightNumber,
          'transporter': (seg.operatingAirline !== seg.marketingAirline) ?
            {
              'code': seg.operatingAirline,
              'name': (KT.airlineCodes[seg.operatingAirline] !== undefined) ?
                KT.airlineCodes[seg.operatingAirline].name : seg.operatingAirline
            } : false,
          'aircraft': seg.aircraft,
          'class': (seg.categoryClassType!==null) ? seg.categoryClassType.substr(0,1) : 'A',
          'className': (seg.categoryClassType!==null) ? seg.categoryClassType : false,
          'dhours': parseInt(seg.duration/60),
          'dminutes': seg.duration%60,
          'startpoint': {
            'city': seg.departureCityName,
            'airport': seg.departureAirportName,
            'terminal': seg.departureTerminal,
            'iata': seg.departureAirportCode,
            'date':departureDate.format(KT.config.dateFormat),
            'time':departureDate.format('HH:mm')
          },
          'endpoint': {
            'city': seg.arrivalCityName,
            'airport': seg.arrivalAirportName,
            'terminal': seg.arrivalTerminal,
            'iata': seg.arrivalAirportCode,
            'date':arrivalDate.format(KT.config.dateFormat),
            'time':arrivalDate.format('HH:mm')
          },
          'waiting': (waitingTime === false) ? false : {
            'hours': Math.floor(waitingTime / 60),
            'minutes': waitingTime % 60,
          },
          'stops': stops
        });
      });

      var baggageInfo = [];
      if (trip.segments.length === 1) {
        if (Array.isArray(trip.segments[0].baggage)) {
          baggageInfo.push(
            trip.segments[0].baggage.map(function(baggage) {
              return [
                baggage.measureQuantity,
                (baggage.measureCode === 'PC' ? 
                  declOfNum(baggage.measureQuantity, ['место', 'места', 'мест']) : 
                  baggage.measureCode
                )
              ].join(' ');
            }).join(', ')
          );
        }
      } else {
        trip.segments.forEach(function(segment) {
          if (Array.isArray(segment.baggage)) {
            baggageInfo.push(
              segment.departureAirportCode + ' - ' + segment.arrivalAirportCode + ': ' +
              segment.baggage.map(function(baggage) {
                return [
                  baggage.measureQuantity,
                  (baggage.measureCode === 'PC' ? 
                    declOfNum(baggage.measureQuantity, ['место', 'места', 'мест']) : 
                    baggage.measureCode
                  )
                ].join(' ');
              }).join(', ')
            );
          }
        });
      }

      var transfersAmount = trip.segments.length - 1;
      self.tripData.push(Mustache.render(self.tpl.aviaTrip, {
        'route': Mustache.render(self.tpl.aviaTripRoute, tripRoute),
        'hasPnr': hasPnr,
        'baggageInfo': baggageInfo,
        'transfersNum': (transfersAmount === 0) ? 'без пересадок' :
          transfersAmount + ' ' + declOfNum(transfersAmount, ['пересадка','пересадки','пересадок']),
      }));
    });
  };

  /** Обработка данных брони и билетов */
  AviaServiceViewModel.prototype.processPnr = function() {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    if (ServiceStorage.offerInfo.pnr !== undefined && ServiceStorage.offerInfo.pnr !== null) {
      self.hasPnr = true;
      self.pnrNumber = ServiceStorage.offerInfo.pnr.pnrNumber;
      self.pnrBaggageInfo = {
        'info': null
      };

      if (
          ServiceStorage.offerInfo.pnr.receipts !== undefined &&
          Array.isArray(ServiceStorage.offerInfo.pnr.receipts)
        ) {
          self.receipts = ServiceStorage.offerInfo.pnr.receipts;

        self.ticketLink = (self.receipts[0] !== undefined) ?
          self.receipts[0].receiptUrl : false;
      }

      if (
        ServiceStorage.offerInfo.pnr.tickets !== undefined &&
        Array.isArray(ServiceStorage.offerInfo.pnr.tickets) &&
        ServiceStorage.offerInfo.pnr.tickets.length !== 0
      ) {
        self.tickets = {};
        ServiceStorage.offerInfo.pnr.tickets.forEach(function(ticket) {
          if (!self.tickets.hasOwnProperty(ticket.touristId)) {
            self.tickets[ticket.touristId] = [];
          }
          self.tickets[ticket.touristId].push({
            'status': ticketStatusMap[ticket.ticketStatus],
            'number': ticket.ticketNumber
          });
        });
      }

      if (Array.isArray(ServiceStorage.offerInfo.pnr.baggage)) {
        ServiceStorage.offerInfo.pnr.baggage.map(function(baggage) {
          if (baggage.measureCode === 'PC') { 
            baggage.measureCode = declOfNum(baggage.measureQuantity, ['место', 'места', 'мест']);
          }
        });

        self.pnrBaggageInfo.info = ServiceStorage.offerInfo.pnr.baggage;
      }
    }
  };

  /** Обработка дополнительных полей  */
  AviaServiceViewModel.prototype.processCustomFields = function() {
    var self = this;
    var unprocessedFields = ServiceViewModel.prototype.processCustomFields.call(this);
    
    // Обработка специализированных доп. полей
    if (Array.isArray(unprocessedFields)) {
      unprocessedFields.forEach(function(fieldData) {
        switch (fieldData.typeTemplate) {
          case 5: // minimal price
            if (fieldData.value === null) { return; }
            var minimalPriceData = JSON.parse(fieldData.value);

            self.minimalPriceView = Mustache.render(self.tpl.aviaMinimalPrice, {
              'from': minimalPriceData.from,
              'to': minimalPriceData.to,
              'dateStart': moment(minimalPriceData.dateStart, 'YYYY-MM-DD HH:mm:ss')
                .format('HH:mm DD-MM-YYYY'),
              'dateFinish': moment(minimalPriceData.dateFinish, 'YYYY-MM-DD HH:mm:ss')
                .format('HH:mm DD-MM-YYYY'),
              'duration': {
                'hours': Math.floor(minimalPriceData.duration / 60),
                'minutes': minimalPriceData.duration % 60,
              },
              'changes': [
                  minimalPriceData.changes,
                  declOfNum(minimalPriceData.changes, ['пересадка','пересадки','пересадок'])
                ].join(' '),
              'price': Number(minimalPriceData.price).toMoney(0,',',' '),
              'currency': KT.getCatalogInfo('lcurrency', minimalPriceData.currency, 'icon')
            });
            break;
        }
      });
    }
  };

  /** Подготовка интерфейса шапки */
  AviaServiceViewModel.prototype.prepareHeaderView = function() {
    var ServiceStorage = this.ServiceStorage;

    var firstFlightNumber = '';
    if (!ServiceStorage.isPartial) {
      if (
        ServiceStorage.offerInfo.itinerary[0] !== undefined &&
        ServiceStorage.offerInfo.itinerary[0].segments[0] !== undefined
      ) {
        var firstSegment = ServiceStorage.offerInfo.itinerary[0].segments[0];
        firstFlightNumber = firstSegment.marketingAirline+'-'+firstSegment.flightNumber;
      }
    } else {
      firstFlightNumber = null;
    }

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

    this.headerView = Mustache.render(this.tpl.aviaFormHeader, {
      'firstFlightNum': firstFlightNumber,
      'amendDate':(ServiceStorage.dateAmend === null) ? false :
        ServiceStorage.dateAmend.format('DD.MM.YYYY'),
      'priceLocal': Number(ServiceStorage.prices.inLocal.client.gross).toMoney(0,',',' '),
      'priceInView': Number(ServiceStorage.prices.inView.client.gross).toMoney(0,',',' '),
      'priceFactors': (KT.profile.userType === 'op' && hasPriceFactors) ? {
        'taxes': (taxes !== null) ? {'list': taxes} : null,
        'supplierCommission': supplierCommission
      } : null,
      'viewCurrencyIcon': KT.getCatalogInfo(
          'lcurrency', ServiceStorage.prices.inView.currencyCode, 'icon'
        ),
      'statusIcon':KT.getCatalogInfo('servicestatuses',ServiceStorage.status,'icon'),
      'statusTitle': (KT.profile.userType === 'op' && ServiceStorage.status === 9) ?
        'Ручной режим' :
        KT.getCatalogInfo('servicestatuses',ServiceStorage.status,'title'),
      'dates': this.tripDates,
      'serviceName': (ServiceStorage.isPartial) ? ServiceStorage.name: null,
      'flights': this.tripPoints,
      'passengers': {
        'adult': ServiceStorage.declaredTouristAges.adults,
        'child': ServiceStorage.declaredTouristAges.children,
        'infant': ServiceStorage.declaredTouristAges.infants,
      },
      'pnrNumber': this.pnrNumber,
      'ticketLink': this.ticketLink
    });
  };

  /** Подготовка интерфейса главного блока */
  AviaServiceViewModel.prototype.prepareMainView = function() {
    var ServiceStorage = this.ServiceStorage;

    if (!ServiceStorage.isPartial) {
      var lastTicketingDate = (ServiceStorage.offerInfo.lastTicketingDate !== null) ?
        moment(ServiceStorage.offerInfo.lastTicketingDate,'YYYY-MM-DD HH:mm:ss').format('HH:mm DD-MM-YYYY') :
        null;

      this.mainView = Mustache.render(this.tpl.aviaFormMain, {
        //'minimalPrice': this.minimalPriceView,
        'offerSupplier': (KT.profile.userType === 'op') ? this.supplierName : false,
        'lastTicketingDate': lastTicketingDate,
        'flightTrips': this.tripData,
        'overNightFlight': !ServiceStorage.hasTravelPolicy ? false :
          ServiceStorage.offerInfo.travelPolicy.overNightFlight,
        'nightTransfer': !ServiceStorage.hasTravelPolicy ? false :
          ServiceStorage.offerInfo.travelPolicy.nightTransfer,
        'pnrBaggageInfo': this.pnrBaggageInfo,
        'ticketLink': this.ticketLink
      });
    } else { this.mainView = ''; }
  };

  /** Подготовка интерфейса действий с услугой */
  AviaServiceViewModel.prototype.prepareActionsView = function() {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    if (!ServiceStorage.isPartial) {
      var serviceActions = this.getServiceActions();

      this.actionsView = Mustache.render(self.tpl.aviaServiceActions, serviceActions);
      this.prepareFareRulesView();
    } else { this.actionsView = ''; }
  };

  /** Подготовка интерфейса правил тарифов перелета */
  AviaServiceViewModel.prototype.prepareFareRulesView = function() {
    var ServiceStorage = this.ServiceStorage;

    if (
      !Array.isArray(ServiceStorage.offerInfo.fareRules) ||
      ServiceStorage.offerInfo.fareRules.length === 0
    ) {
      this.fareRulesView = Mustache.render(KT.tpl.spinner, {});
      return;
    }

    var fareRule = {'rules': []};
    var isActiveTab = true;

    var locationMap = {};
    ServiceStorage.offerInfo.itinerary.forEach(function(trip) {
      trip.segments.forEach(function(segment) {
        locationMap[segment.arrivalAirportCode] = segment.arrivalCityName;
        locationMap[segment.departureAirportCode] = segment.departureCityName;
      });
    });

    ServiceStorage.offerInfo.fareRules.forEach(function(rule) {
      var shortRules = {};

      var iataCodes = rule.segment.flightSegmentName.split(/\s*-\s*/);
      var fullSegmentName = locationMap[iataCodes[0]] + ' - ' + locationMap[iataCodes[1]];

      for (var flag in rule.aviaFareRule.shortRules) {
        if (rule.aviaFareRule.shortRules.hasOwnProperty(flag)) {
          switch (rule.aviaFareRule.shortRules[flag]) {
            case true:
              shortRules[flag] = {'allowed':true};
              break;
            case false:
              shortRules[flag] = {'forbidden':true};
              break;
            default:
              shortRules[flag] = {'undefined':true};
              break;
          }
        }
      }
      
      fareRule.rules.push({
        'active': isActiveTab,
        'segment' : rule.segment.flightSegmentName,
        'fullSegmentName': fullSegmentName,
        'shortRules': shortRules,
        'rulesText': rule.aviaFareRule.rules
      });

      isActiveTab = false;
    });

    this.fareRulesView = Mustache.render(this.tpl.aviaFareRule, fareRule);
  };

  /** Подготовка пустой формы правил тарифов (не найдены) */
  AviaServiceViewModel.prototype.prepareEmptyFareRulesView = function() {
    this.fareRulesView = Mustache.render(this.tpl.aviaFareRuleUnavailable, {});
  };

  /** 
  * Инициализация элементов управления после рендера формы
  * @param {Object} $container - форма услуги
  * @todo в принципе передать управление контейнером услуги в класс viewModel?
  */
  AviaServiceViewModel.prototype.initControls = function($container) {
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
  * Связывание информации о туристах из заявки (getOrderTourists)
  * с информацией из услуги и формирование данных для блока туристов
  * @param {TouristStorage[]} tourists - массив информации по туристам
  * @param {Boolean} [overrideSave] - явное указание возможности сохранения привязки
  */
  AviaServiceViewModel.prototype.mapTouristsInfo = function(tourists, overrideSave) {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    this.touristsAges = {
      'adults': {
        'ordered':ServiceStorage.declaredTouristAges.adults,
        'current':0
      },
      'children': {
        'ordered':ServiceStorage.declaredTouristAges.children,
        'current':0
      },
      'infants': {
        'ordered':ServiceStorage.declaredTouristAges.infants,
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
      var linkedTourist = isLinked ? ServiceStorage.tourists[TouristStorage.touristId] : null;
      var touristExtra = '';

      if (!ServiceStorage.isPartial) {

        // сохранение числа привязанных туристов по возрастным группам
        if (isLinked) {
          var age = moment.duration(
              moment().valueOf() - TouristStorage.birthdate.valueOf()
            ).asYears();
          if (age < 3) { self.touristsAges.infants.current += 1; }
          else if (age < 12) { self.touristsAges.children.current += 1; }
          else { self.touristsAges.adults.current += 1; }
        }

        if (self.tickets !== undefined && self.tickets[TouristStorage.touristId] !== undefined) {
          var issuedTickets = self.tickets[TouristStorage.touristId].filter(function(ticket) {
              return (ticket.status === 'ISSUED');
            }).map(function(ticket) {
              return ticket.number;
            });
            
          touristExtra = '<div class="service-form-tourist__ticket-number">№ билет' +
            ((issuedTickets.length > 1) ? 'ов' : 'а') + ': ' + 
            issuedTickets.join(', ') + 
            '</div>';

        } else {
          if (
            (isLinked && linkedTourist.loyalityProviderId  !== null) ||
            TouristStorage.bonusCards.length > 0
          ) {
            touristExtra = Mustache.render(self.tpl.aviaLoyaltyCard, {
              'isSavingAllowed': isSavingAllowed,
              'loyalityProgram': isLinked ? linkedTourist.loyalityProviderId : null,
              'loyalityCardNumber': isLinked ? linkedTourist.loyalityCardNumber : null,
            });
          } else {
            touristExtra = Mustache.render(self.tpl.aviaNoLoyaltyCards, {});
          }
        }
      }

      if (overrideSave || isSavingAllowed || isLinked) {
        self.touristsMap.push({
          'allowSave': isSavingAllowed,
          'touristId': TouristStorage.touristId,
          'firstName': TouristStorage.firstname,
          'bonusCards': TouristStorage.bonusCards,
          'surName': TouristStorage.lastname,
          'attached': isLinked,
          'touristExtra': touristExtra
        });
      }
    });
  };

  /**
  * Рендер элементов управления туриста 
  * @param {Object} $tourist - блок туриста
  * @param {Object} tourist - данные туриста (touristsMap)
  */
  AviaServiceViewModel.prototype.renderTouristControls = function($tourist, tourist) {
    KT.Dictionary.getAsMap('loyalityPrograms', 'programId')
      .then(function(loyalityProgramsMap) {
        var bonusCardsOptions = tourist.bonusCards.map(function(card) {
          if (loyalityProgramsMap.hasOwnProperty(card.aviaLoyaltyProgramId)) {
            return $.extend(true, {
              'cardNumber': card.bonuscardNumber
            }, loyalityProgramsMap[card.aviaLoyaltyProgramId]);
          }
        });

        if (bonusCardsOptions.length === 0) { return; }

        var $bonusCard = $tourist.find('.js-service-avia-loyalty-program');
        // карт не будет, если услуга оформлена
        if ($bonusCard.length !== 0) {
          var $providerSelect = $bonusCard.find('.js-service-avia-loyalty-program--provider');
          var $cardNumber = $bonusCard.find('.js-service-avia-loyalty-program--number');
          var currentProvider = $providerSelect.val();

          $providerSelect.selectize({
            plugins: {'jirafize':{}},
            openOnFocus: true,
            create: false,
            options: bonusCardsOptions,
            selectOnTab: true,
            valueField: 'programId',
            searchField: ['IATAcode','loyalityProgramName', 'aircompanyName'],
            render:{
              item: function(item) {
                return '<div class="item" ' +
                  'data-tooltip="авиакомпания: ' + item.aircompanyName + '<br>' +
                  ' альянс: ' + item.allianceName + '">' + 
                  '<b>[' + item.IATAcode + ']</b> ' + item.loyalityProgramName + 
                  '</div>';
              },
              option: function(item) {
                return '<div class="option" ' +
                  'data-tooltip="авиакомпания: ' + item.aircompanyName + '<br>' +
                  ' альянс: ' + item.allianceName + '">' + 
                  '<b>[' + item.IATAcode + ']</b> ' + item.loyalityProgramName + 
                  '</div>';
              }
            },
            onItemAdd: function(value) {
              $cardNumber.val(this.options[value].cardNumber);
            },
            onItemRemove: function() {
              $cardNumber.val('');
            },
            onClear: function() {
              $cardNumber.val('');
            }
          });

          if (currentProvider !== '' && currentProvider !== null) {
            $providerSelect[0].selectize.addItem(+currentProvider);
          } else if (bonusCardsOptions.length > 0 && tourist.allowSave && !tourist.attached) {
            /*
            * выбор первого элемента только в том случае ,если турист не привязан:
            * нужно для того, чтобы при бронировании не было "мелькания" мильной карты на услуге
            */
            $providerSelect[0].selectize.addItem(bonusCardsOptions[0].programId);
          }
        }
      });
  };

  /**
  * Получение структуры доступных действий над услугой
  * @return {Object} идентификатор услуги и разрешения на действия
  */
  AviaServiceViewModel.prototype.getServiceActions = function() {
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
  AviaServiceViewModel.prototype.defineTermsDocuments = function() {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    /*
    * renderer - функция, отрисовывающая контент. 
    *  Принимает название документа и контент, Возвращает ссылку на DOM-объект окна
    * loader - функция загрузки документа.
    *  Принимает название документа и коллбэк
    */

    var termsDocuments = {
      'companyTOS': {
        'load':function(renderer, loader) {
          loader('companyTOS', renderer);
        }
      }
    };
    termsDocuments[ServiceStorage.tosDocumentName] = {
      'load':function(renderer, loader) {
        loader('aviaBookTerms', renderer);
      }
    };

    termsDocuments[ServiceStorage.fareRuleDocumentName] = {
      'load':function(renderer) {
        var $fareRule = renderer(ServiceStorage.fareRuleDocumentName, self.fareRulesView);
        $fareRule.on('click','.js-avs-fare-rule--tab-link', function() {
          if (!$(this).hasClass('active')) {
            var $root = $(this).closest('.js-avs-fare-rule');
            
            $root.children('.js-avs-fare-rule--tab-header')
              .children('.js-avs-fare-rule--tab-link')
                .filter('.active')
                  .removeClass('active');
            $(this).addClass('active');

            var $tabs = $root.children('.js-avs-fare-rule--tab');
            $tabs.removeClass('active')
              .filter('[data-tab="' + $(this).data('tab') + '"]')
                .addClass('active');
          }
        });
      }
    };

    return termsDocuments;
  };

  /**
  * Рендер формы ручного редактирования улуги
  * @param {Object} mds - объект модуля
  * @return {String} - код формы редактирования услуги
  */
  AviaServiceViewModel.prototype.renderManualForm = function(mds) {
    var ServiceStorage = this.ServiceStorage;

    var commonParams = ServiceViewModel.prototype.setCommonManualFormParams.call(this);

    var manualFormParams = $.extend(commonParams, {
        'flightTariff': ServiceStorage.offerInfo.flightTariff,
        'pnr': (this.hasPnr) ?
          ServiceStorage.offerInfo.pnr.pnrNumber : null,
        'tickets': null
      });
    
    if (this.hasPnr) {
      var tickets = this.ServiceStorage.offerInfo.pnr.tickets;
      if (tickets !== undefined && tickets.length !== 0) {
        manualFormParams.tickets = [];

        tickets.forEach(function(ticket) {
          var tourist = mds.OrderStorage.tourists[ticket.touristId];

          manualFormParams.tickets.push({
            'pnr': ServiceStorage.offerInfo.pnr.pnrNumber,
            'number': ticket.ticketNumber,
            'tourist': {
              'id': tourist.touristId,
              'fullname': [
                  tourist.lastname,
                  tourist.firstname,
                  (tourist.middlename === null ? '' : tourist.middlename)
                ].join(' '),
              'document': {
                'docname': KT.config.touristDocuments[tourist.document.type].docname,
                'number': tourist.document.series + ' ' + tourist.document.number
              }
            },
            'status': ticketStatusMap[+ticket.ticketStatus],
            'disabled': (ticketStatusMap[+ticket.ticketStatus] !== 'ISSUED'),
            'newNumber': (ticket.newTicket !== undefined) ? ticket.newTicket: null
          });
        });
      }
    }

    this.manualFormParams = manualFormParams;
    return Mustache.render(this.tpl.aviaManualForm, manualFormParams);
  };

  /**
  * Инициализация элементов управления окна ручного редактирования услуги
  * @param {Object} $wnd - [jQueryDom] объект окна
  * @param {Object} mds - объект модуля
  * @todo Убрать зависимость от модуля
  */
  AviaServiceViewModel.prototype.initManualFormControls = function($wnd, mds) {
    var self = this;

    ServiceViewModel.prototype.initManualFormControls.call(this, $wnd, mds);

    var ServiceStorage = this.ServiceStorage;

    /* Изменение параметров брони */
    KT.Dictionary.getAsList('suppliers', {'serviceId': ServiceStorage.typeCode, 'active': 1})
      .then(function(aviaSuppliers) {
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
            options: aviaSuppliers,
            items: [self.supplierCode] //!!!
          });
      });

    var lastTicketingDate = (ServiceStorage.offerInfo.lastTicketingDate !== null) ?
      moment(ServiceStorage.offerInfo.lastTicketingDate,'YYYY-MM-DD HH:mm:ss') : null;

    $wnd.find('.js-ore-service-manualedit--last-ticketing-date')
      .val((lastTicketingDate !== null) ? lastTicketingDate.format('DD.MM.YYYY') : '')
      .clndrize({
        'template': KT.tpl.clndrDatepicker,
        'eventName': 'Выписать до',
        'showDate': (lastTicketingDate !== null) ? lastTicketingDate : '',
        'clndr': {
          'constraints': {
            'startDate': moment().format('YYYY-MM-DD'),
            'endDate': this.ServiceStorage.startDate.format('YYYY-MM-DD')
          }
        }
      });
    
    /* Редактирование авиабилетов */
    var ticketsList = (this.manualFormParams.tickets === null) ? [] : 
      this.manualFormParams.tickets.filter(function(ticket) {
        return (ticket.status === 'ISSUED');
      });

    var $ticketsList = $wnd.find('.js-ore-service-manualedit--avia-tickets-list');
    var $changeTicketForm = $wnd.find('.js-ore-service-manualedit--change-avia-ticket');
    var $addTicketForm = $wnd.find('.js-ore-service-manualedit--add-avia-ticket');
    var $changingTicketSelect = $wnd.find('.js-ore-service-manualedit--current-ticket-number');
    var $addNewTicketButton = $wnd.find('.js-ore-service-manualedit--add-avia-ticket-button');

    // редактирование билетов

    $changingTicketSelect.selectize({
        openOnFocus: true,
        create: false,
        valueField: 'number',
        labelField: 'number',
        options: ticketsList
      });

    $ticketsList.on('click', '.js-ore-service-manualedit--avia-ticket:not(.disabled)', function() {
        $addTicketForm.removeClass('active');
        /*
        $changeTicketForm.data('changingTicket', {
          'number': $(this).data('ticket'),
          'pnr': $(this).data('pnr'),
          'touristId': $(this).data('touristid')
        }); */
        $changeTicketForm.addClass('active');
        $changingTicketSelect[0].selectize.addItem($(this).data('ticket'));
      });

    // форма добавления билетов
    $addNewTicketButton
      .on('click', function() {
        $changeTicketForm.removeClass('active');
        $addTicketForm.addClass('active');
      });

    var tourists = [];
    mds.OrderStorage.getTourists().forEach(function(Tourist) {
      // билеты можно добавить/редактировать только привязанным туристам
      if (ServiceStorage.tourists.hasOwnProperty(Tourist.touristId)) {
        tourists.push({
          'value': Tourist.touristId,
          'name': [
              Tourist.lastname,
              Tourist.firstname
            ].join(' '),
          'document': [
              KT.config.touristDocuments[Tourist.document.type].docname,
              Tourist.document.series,
              Tourist.document.number
            ].join(' ')
        });
      }
    });

    $wnd.find('.js-ore-service-manualedit--new-ticket-tourist')
      .selectize({
        openOnFocus: true,
        create: false,
        valueField: 'value',
        labelField: 'name',
        options: tourists,
        render: {
          item: function(item) {
            return '<div data-value="' + item.value + '" class="item" data-tooltip="' + item.document + '">' +
            item.name + '</div>';
          },
          option: function(item) {
            return '<div data-value="' + item.value + '" data-selectable class="option">' +
            item.name + '<br>(' + item.document + ')</div>';
          }
        }
      });
  };

  /**
  * Получение параметров для команды изменения брони
  * @param {Object} $manualForm - [jQuery DOM] форма ручного редактирования услуги
  */
  AviaServiceViewModel.prototype.getChangeBookDataParams = function($manualForm) {
    var ServiceStorage = this.ServiceStorage;

    var pnr = $manualForm.find('.js-ore-service-manualedit--new-pnr-number').val();
    var supplier = $manualForm.find('.js-ore-service-manualedit--supplier').val();
    var tariff = $manualForm.find('.js-ore-service-manualedit--tariff').val();
    var lastTicketingDate = $manualForm.find('.js-ore-service-manualedit--last-ticketing-date').val();

    if (pnr === '') {
      if (ServiceStorage.offerInfo.pnr.pnrNumber !== undefined) {
        pnr = ServiceStorage.offerInfo.pnr.pnrNumber;
      } else {
        KT.notify('reservationNumberNotSet');
        return false;
      }
    }

    if (supplier === '') { supplier = this.supplierCode; } //!!!!

    lastTicketingDate = (lastTicketingDate !== '') ?
      moment(lastTicketingDate,'DD.MM.YYYY').format('YYYY-MM-DD HH:mm:ss') : null;

    return {
      'serviceId': ServiceStorage.serviceId,
      'reservationData': [{
        'reservationAction' : 'update',
        'PNR': pnr,
        'aviaReservation': {
          'PNR': pnr,
          'segments': null,
          'supplierCode': supplier,
          'status' : 1
        },
        'flightTariff': tariff,          
        'lastTicketingDate': lastTicketingDate
      }]
    };
  };

  /**
  * Получение параметров для команды изменения билетов
  * @param {Object} $manualForm - [jQuery DOM] форма ручного редактирования услуги
  */
  AviaServiceViewModel.prototype.getChangeTicketDataParams = function($manualForm) {
    var $changeTicketForm = $manualForm.find('.js-ore-service-manualedit--change-avia-ticket');
    var changingTicketSelect = $changeTicketForm.find('.js-ore-service-manualedit--current-ticket-number')[0].selectize;
    
    var changingTicketNumber = changingTicketSelect.getValue();
    var changingTicket;

    if (changingTicketNumber === '' || changingTicketNumber === null) {
      KT.notify('changingTicketNotSelected');
      return false;
    } else {
      changingTicket = changingTicketSelect.options[changingTicketNumber];
    }

    var newTicketNumber = $changeTicketForm.find('.js-ore-service-manualedit--new-ticket-number').val();
    if (newTicketNumber === '') {
      KT.notify('ticketNumberNotEntered');
      return false;
    }

    return {
      'serviceId': this.ServiceStorage.serviceId,
      'ticketData': {
        'ticketAction': 'update',
        'ticketNumber': changingTicket.number,
        'ticketData': {
          'pnr': changingTicket.pnr,
          'touristId': changingTicket.tourist.id,
          'ticketNumber': newTicketNumber,
          'ticketStatus': 1,
          'newTicket': null
        }
      }
    };
  };

  /**
  * Получение параметров для команды создания билета
  * @param {Object} $manualForm - [jQuery DOM] форма ручного редактирования услуги
  */
  AviaServiceViewModel.prototype.getAddTicketParams = function($manualForm) {
    var $addTicketForm = $manualForm.find('.js-ore-service-manualedit--add-avia-ticket');
    var newTicketNumber = $addTicketForm.find('.js-ore-service-manualedit--new-ticket-number').val();
    if (newTicketNumber === '') {
      KT.notify('ticketNumberNotEntered');
      return false;
    }
    var touristId = $addTicketForm.find('.js-ore-service-manualedit--new-ticket-tourist').val();
    if (touristId === '') {
      KT.notify('ticketTouristNotSet');
      return false;
    }

    return {
      'serviceId': this.ServiceStorage.serviceId,
      'ticketData': {
        'ticketAction': 'add',
        'ticketNumber': null,
        'ticketData': {
          'pnr': this.ServiceStorage.offerInfo.pnr.pnrNumber,
          'touristId': +touristId,
          'ticketNumber': newTicketNumber,
          'ticketStatus': 1,
          'newTicket': null
        }
      }
    };
  };

  return AviaServiceViewModel;

}));
