/* global ktStorage */
(function(global,factory){

    KT.storage.ServiceStorage = factory(KT.crates.OrderEdit);

}(this, function(crate) {
  var AdditionalServicesFactory = crate.AdditionalServicesFactory;

  /**
  * Хранилище данных услуги
  * @module ServiceStorage
  * @constructor
  * @param {Integer} serviceId - ID услуги
  */
  var ServiceStorage = ktStorage.extend(function(serviceId) {
    this.namespace = 'ServiceStorage';

    this.serviceId = serviceId;

    // флаг, обозначающий отсутствие оффера в услуге
    this.isPartial = false;

    // флаг согласия с условиями бронирования
    this.isTOSAgreementSet = false;

    // название документа для условий бронирования:
    // нужно для идентификации документа для вывода со страниц услуг и оформления
    this.tosDocumentName = false;

    // структура дл записи цен
    this.prices = {
      inLocal: {
        currencyCode: 'RUB', /** @todo default currency constant */
        client: {
          net: null,
          gross: null,
          commission: {
            amount: null, /** @todo клиентская комиссия? */
            percent: null
          }
        },
        supplier: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        }
      },
      inView: {
        currencyCode: null,
        client: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        },
        supplier: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        }
      },
      inSupplier: {
        currencyCode: null,
        client: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        },
        supplier: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        }
      },
      inClient: {
        currencyCode: null,
        client: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        },
        supplier: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        }
      }
    };

    // данные оффера
    this.offerInfo = null;

    // флаг невозможности возврата средств при отмене бронирования
    this.isNonRefundable = false;

    // возможные штрафы за отмену
    this.clientCancelPenalties = null;
    this.supplierCancelPenalties = null;

    // начисленные штрафы
    this.penalties = null;
    this.penaltySums = null;

    //  данные запроса по предложению
    this.requestData = null;

    // данные по привязке туристов
    this.tourists = {};

    // доступные действия с услугой
    this.allowedTransitions = [];

    // сумма выставленных на услугу счетов
    // NOTE: валюта должна быть та же, что и у clientCurrency
    this.invoiceSum = 0;
    // сумма незаплаченных денег
    this.unpaidSum = 0;

    // наличие данных по TP
    this.hasTravelPolicy = false;
    // наличие нарушений TP
    this.hasTPViolations = false;

    // дополнительные услуги (оффера)
    this.additionalServiceOffers = null;
    // дополнительные услуги (добавленные)
    this.additionalServices = null;
    
    // структура дополнительных полей
    this.customFields = null;
  });

  KT.addMixin(ServiceStorage,'Dispatcher');

  /**
  * Инициализация хранилища
  * @param {Object} serviceData - данные услуги (/getOrder)
  */
  ServiceStorage.prototype.initialize = function(serviceData) {
    if (serviceData.serviceID !== this.serviceId) {
        KT.error(
          this.namespace+': ' +
          'попытка инициализации данными другой услуги,' +
          ' текущая: ' . this.serviceId +
          ' данные от: '. serviceData.serviceID
        );
    }

    var isNotEmpty = function(v) {
      return !(v === undefined || v === null || v === '');
    };

    // название услуги
    this.name = serviceData.serviceName;

    // описание услуги
    this.description = serviceData.serviceDescription;

    // статусная информация
    this.status = serviceData.status;
    this.isOffline = serviceData.offline;

    // код типа услуги
    this.typeCode = +serviceData.serviceType;

    // установка названия документа с условиями бронирования
    // cancellationDocumentName - в каком документе содержатся правила отмены, для вкладки "оформление"
    /** @todo создать отдельные классы для каждого типа услуг */
    switch(this.typeCode) {
      case 1:
        this.tosDocumentName = 'hotelBookTerms-'+this.serviceId;
        this.cancellationDocumentName = this.tosDocumentName;
        break;
      case 2:
        this.tosDocumentName = 'aviaBookTerms';
        // название документа для правил тарифа авиа
        this.fareRuleDocumentName = 'aviaFareRule-'+this.serviceId;
        this.cancellationDocumentName = this.fareRuleDocumentName;
        break;
    }

    // ID шлюза, связанного с услугой
    this.gatewayId = serviceData.supplierId; /** @todo атавизм? */

    // даты начала и окончания услуги
    this.startDate = moment(serviceData.startDateTime,'YYYY-MM-DD HH:mm:ss');
    this.endDate = moment(serviceData.endDateTime,'YYYY-MM-DD HH:mm:ss');

    // дата заказа услуги
    this.creationDate = moment(serviceData.dateOrdered,'YYYY-MM-DD HH:mm:ss');

    // дата оплаты (оплатить до)
    this.dateAmend = isNotEmpty(serviceData.dateAmend) ?
      moment(serviceData.dateAmend,'YYYY-MM-DD HH:mm:ss') : null;

    // ценовая информация
    this.prices.inLocal.client.gross = Number(serviceData.localSum);
    this.prices.inLocal.client.commission.amount = Number(serviceData.localCommission);
    this.prices.inLocal.supplier.gross = Number(serviceData.localNetSum);
    this.prices.inView.currencyCode = KT.profile.viewCurrency;
    this.prices.inView.client.gross = Number(serviceData.requestedSum);
    this.prices.inView.supplier.gross = Number(serviceData.requestedNetSum);
    this.prices.inSupplier.currencyCode = serviceData.supplierCurrencyCode;
    this.prices.inSupplier.client.gross = Number(serviceData.supplierPrice);
    this.prices.inSupplier.supplier.gross = Number(serviceData.supplierNetPrice);
    this.prices.inClient.currencyCode = serviceData.paymentCurrencyCode;
    this.prices.inClient.client.gross = Number(serviceData.paymentSum);

    this.paymentCurrency = this.prices.inClient.currency;

    /** @todo как-то странно тут скидка, не пришей кобыле хвост... */
    this.discount = isNotEmpty(serviceData.discount) ?
      Number(serviceData.discount) : 0;

    /** структура дополнительных полей */
    this.customFields = Array.isArray(serviceData.additionalData) ? 
      serviceData.additionalData : null;

    // разрешенные операции
    /** @todo checkworkflow */
    this.isActionAvailable = {
      'setInvoice': (
        [2,3,4,8].indexOf(this.status) !== -1 ||
        (KT.profile.userType === 'op' && this.status === 9)
      )
    };
  };

  /*
  * Обновление полной информации по услуге (getOrderOffer)
  */
  ServiceStorage.prototype.updateFullInfo = function(serviceData) {
    var self = this;

    this.status = +serviceData.serviceStatus;
    this.offerInfo = serviceData.offerInfo;
    this.requestData = serviceData.requestData;

    // сохранение ценовых компонентов
    var salesTerms = serviceData.serviceSalesTermsInfo;
    this.prices = {
      inLocal: {
        currencyCode: 'RUB',
        client: {
          net: Number(salesTerms.localCurrency.client.amountNetto),
          gross: Number(salesTerms.localCurrency.client.amountBrutto),
          commission: {
            amount: Number(salesTerms.localCurrency.client.commission.amount),
            percent: Number(salesTerms.localCurrency.client.commission.percent)
          }
        },
        supplier: {
          net: Number(salesTerms.localCurrency.supplier.amountNetto),
          gross: Number(salesTerms.localCurrency.supplier.amountBrutto),
          commission: {
            amount: Number(salesTerms.localCurrency.supplier.commission.amount),
            percent: Number(salesTerms.localCurrency.supplier.commission.percent)
          }
        }
      },
      inView: {
        currencyCode: salesTerms.viewCurrency.client.currency,
        client: {
          net: Number(salesTerms.viewCurrency.client.amountNetto),
          gross: Number(salesTerms.viewCurrency.client.amountBrutto),
          commission: {
            amount: Number(salesTerms.viewCurrency.client.commission.amount),
            percent: Number(salesTerms.viewCurrency.client.commission.percent)
          }
        },
        supplier: {
          net: Number(salesTerms.viewCurrency.supplier.amountNetto),
          gross: Number(salesTerms.viewCurrency.supplier.amountBrutto),
          commission: {
            amount: Number(salesTerms.viewCurrency.supplier.commission.amount),
            percent: Number(salesTerms.viewCurrency.supplier.commission.percent)
          }
        }
      },
      inSupplier: {
        currencyCode: salesTerms.supplierCurrency.supplier.currency,
        client: {
          net: Number(salesTerms.supplierCurrency.client.amountNetto),
          gross: Number(salesTerms.supplierCurrency.client.amountBrutto),
          commission: {
            amount: Number(salesTerms.supplierCurrency.client.commission.amount),
            percent: Number(salesTerms.supplierCurrency.client.commission.percent)
          }
        },
        supplier: {
          net: Number(salesTerms.supplierCurrency.supplier.amountNetto),
          gross: Number(salesTerms.supplierCurrency.supplier.amountBrutto),
          commission: {
            amount: Number(salesTerms.supplierCurrency.supplier.commission.amount),
            percent: Number(salesTerms.supplierCurrency.supplier.commission.percent)
          }
        }
      },
      inClient: {
        currencyCode: salesTerms.clientCurrency.client.currency,
        client: {
          net: Number(salesTerms.clientCurrency.client.amountNetto),
          gross: Number(salesTerms.clientCurrency.client.amountBrutto),
          commission: {
            amount: Number(salesTerms.clientCurrency.client.commission.amount),
            percent: Number(salesTerms.clientCurrency.client.commission.percent)
          }
        },
        supplier: {
          net: Number(salesTerms.clientCurrency.supplier.amountNetto),
          gross: Number(salesTerms.clientCurrency.supplier.amountBrutto),
          commission: {
            amount: Number(salesTerms.clientCurrency.supplier.commission.amount),
            percent: Number(salesTerms.clientCurrency.supplier.commission.percent)
          }
        }
      }
    };

    // данные по штрафам
    var localTaxes = salesTerms.localCurrency.client.taxesAndFees;
    this.taxes = (Array.isArray(localTaxes) && localTaxes.length > 0) ? 
      localTaxes : null;

    // данные по комиссии поставщика
    var localSupplierCommission = salesTerms.localCurrency.supplier.commission;
    this.supplierCommission = (Number(localSupplierCommission.amount) !== 0) ? localSupplierCommission : null;
    
    // сохранение суммы для оплаты
    this.unpaidSum = Number(serviceData.restPaymentAmount);
    this.paymentCurrency = serviceData.restPaymentAmountCurrency;

    // сохранение данных по штрафам
    this.penalties = serviceData.penalties;
    if (this.penalties !== null) {
      this.penaltySums = {
        inLocal: {
          currencyCode: this.penalties.client.localCurrency.currency,
          client: Number(this.penalties.client.localCurrency.amount),
          supplier: Number(this.penalties.supplier.localCurrency.amount)
        },
        inView: {
          currencyCode: this.penalties.client.viewCurrency.currency,
          client: Number(this.penalties.client.viewCurrency.amount),
          supplier: Number(this.penalties.supplier.viewCurrency.amount)
        },
        inClient: {
          currencyCode: this.penalties.client.clientCurrency.currency,
          client: Number(this.penalties.client.clientCurrency.amount),
          supplier: Number(this.penalties.supplier.clientCurrency.amount)
        }
      };
    }

    // определение возможности выставить счет
    this.isActionAvailable.setInvoice = (
      this.isActionAvailable.setInvoice &&
      this.unpaidSum > 0
    );

    // обработка специфичная для онлайн и оффлайн услуг
    if (this.offerInfo === null) {
      this.isPartial = true;

      /** @todo хак, т.к. из УТК такой информации не достается */

      switch (this.typeCode) {
        case 1:
          this.touristAges = {
            adults: serviceData.serviceTourists.length,
            children: 0
          };

          this.declaredTouristAges = {
            adults: serviceData.serviceTourists.length,
            children: 0
          };
          break;
        case 2:
          this.touristAges = {
            adults: serviceData.serviceTourists.length,
            children: 0,
            infants: 0
          };

          this.declaredTouristAges = {
            adults: serviceData.serviceTourists.length,
            children: 0,
            infants: 0
          };
          break;
      }
    } else {
      // данные по возрастному составу: текущий привязанный и согласно заказу
      /** @todo для каждого типа услуги нужен свой класс хранилища */
      switch (this.typeCode) {
        case 1:
          this.touristAges = {
            adults: null,
            children: null
          };

          this.declaredTouristAges = {
            adults: this.offerInfo.adult,
            children: this.offerInfo.child
          };
          break;
        case 2:
          this.touristAges = {
            adults: null,
            children: null,
            infants: null
          };

          this.declaredTouristAges = {
            adults: this.offerInfo.requestData.adult,
            children: this.offerInfo.requestData.child,
            infants: this.offerInfo.requestData.infant
          };

          if (Array.isArray(this.offerInfo.fareRules)) {
            this.offerInfo.fareRules.forEach(function(fareRule) {
              if (fareRule.aviaFareRule.shortRules.refund_before_rule === false) {
                self.isNonRefundable = true;
              }
            });
          }
          break;
      }

      // услугонезависимая обработка

      if (this.offerInfo.cancelPenalties !== null) {
        var clientPenaltiesLocal = this.offerInfo.cancelPenalties.localCurrency.client;
        var clientPenaltiesView = this.offerInfo.cancelPenalties.viewCurrency.client;
        var clientPenaltiesClient = this.offerInfo.cancelPenalties.clientCurrency.client;

        if (Array.isArray(clientPenaltiesLocal) && clientPenaltiesLocal.length > 0) {
          this.clientCancelPenalties = processCancelPenalties(
            clientPenaltiesLocal, clientPenaltiesView, clientPenaltiesClient
          );
        }

        var supplierPenaltiesLocal = this.offerInfo.cancelPenalties.localCurrency.supplier;
        var supplierPenaltiesView = this.offerInfo.cancelPenalties.viewCurrency.supplier;
        var supplierPenaltiesClient = this.offerInfo.cancelPenalties.clientCurrency.supplier;

        if (Array.isArray(supplierPenaltiesLocal) && supplierPenaltiesLocal.length > 0) {
          this.supplierCancelPenalties = processCancelPenalties(
            supplierPenaltiesLocal, supplierPenaltiesView, supplierPenaltiesClient
          );
        }

        if (Array.isArray(this.supplierCancelPenalties)) {
          this.supplierCancelPenalties.forEach(function(penalty) {
            if (!self.isNonRefundable && penalty.dateFrom.valueOf() < moment().endOf('day').valueOf()) {
              self.isNonRefundable = true;
            }
          });
        }
      }

      this.declaredTouristAmount = 0;
      for (var agegroup in this.declaredTouristAges) {
        if (this.declaredTouristAges.hasOwnProperty(agegroup)) {
          this.declaredTouristAmount += this.declaredTouristAges[agegroup];
        }
      }

      // определение наличия трэвел-политик
      this.hasTravelPolicy = (typeof this.offerInfo.travelPolicy === 'object' && this.offerInfo.travelPolicy !== null);
      this.hasTPViolations = (
        this.hasTravelPolicy && 
        Array.isArray(this.offerInfo.travelPolicy.travelPolicyFailCodes) &&
        this.offerInfo.travelPolicy.travelPolicyFailCodes.length > 0
      );

      // дополнительные услуги
      if (Array.isArray(serviceData.offerInfo.additionalServices) && serviceData.offerInfo.additionalServices.length > 0) {
        this.additionalServiceOffers = new AdditionalServicesFactory(serviceData.offerInfo.additionalServices, 'offers');
      }
      if (Array.isArray(serviceData.addServices)) {
        this.additionalServices = new AdditionalServicesFactory(serviceData.addServices, 'services');
      } else {
        this.additionalServices = new AdditionalServicesFactory([], 'services');
      }
    }
  };

  /**
  * Обработка плановых штрафов
  * @param {Object[]} penaltiesInLocal - штрафы в локальной валюте
  * @param {Object[]} penaltiesInView - штрафы в валюте просмотра
  * @param {Object[]} penaltiesInClient - штрафы в валюте оплаты
  * @return {Object[]} - скомпонованные  штрафы
  */
  function processCancelPenalties(penaltiesInLocal, penaltiesInView, penaltiesInClient) {
      if (penaltiesInLocal.length !== penaltiesInView.length) {
        console.error('amount of penalties in local currency and in view currency not equal!');
        return null;
      } else {
        var penalties = [];

        penaltiesInLocal.forEach(function(localPenalty, i) {
          penalties.push({
            'dateFrom': moment(localPenalty.dateFrom, 'YYYY-MM-DD HH:mm:ss'),
            'dateTo': moment(localPenalty.dateTo, 'YYYY-MM-DD HH:mm:ss'),
            'description': localPenalty.description,
            'penaltySum': {
              'inLocal': {
                'currency': localPenalty.penalty.currency,
                'amount': localPenalty.penalty.amount
              },
              'inView': {
                'currency': penaltiesInView[i].penalty.currency,
                'amount': penaltiesInView[i].penalty.amount
              },
              'inClient': {
                'currency': penaltiesInClient[i].penalty.currency,
                'amount': penaltiesInClient[i].penalty.amount
              }
            }
          });
        });

        return penalties;
      }
  }

  /**
  * Установка списка доступных действий с услугой
  */
  ServiceStorage.prototype.setAllowedTransitions = function(controls) {
    this.allowedTransitions = controls;
  };

  /**
  * Проверка доступности действия
  * @param {String} transition - название действия
  * @return {Boolean} - результат проверки
  */
  ServiceStorage.prototype.checkTransition = function(transition) {
    return (this.allowedTransitions.indexOf(transition) !== -1);
  };

  /**
  * Возращает список туристов с информацией о привязке к услуге в виже массива
  * @return {Array} - информация о туристах
  */
  ServiceStorage.prototype.getServiceTourists = function() {
    var tourists = [];

    for (var touristId in this.tourists) {
      if (this.tourists.hasOwnProperty(touristId)) {
        tourists.push(this.tourists[touristId]);
      }
    }

    return tourists;
  };

  /**
  * Возвращает возраст туриста на момент окончания услуги
  * @param {Object} birthdate - дата рождения туриста в структуре moment.js
  * @return {Integer} - возраст туриста
  */
  ServiceStorage.prototype.getAgeByServiceEnding = function(birthdate) {
    return moment.duration(this.endDate.valueOf() - birthdate.valueOf()).asYears();
  };

  /**
  * Возвращает возрастную группу туриста для данной услуги
  * @param {Integer} age - возраст туриста
  * @return {String} - возрастная группа (infants, adults, children)
  * @todo сделать классы для каждой услуги
  */
  ServiceStorage.prototype.getAgeGroup = function(age) {
    var agegroup = 'adults';
    switch (this.typeCode) {
      case 1:
        if (age < 12) { agegroup = 'children'; }
        else { agegroup = 'adults'; }
        break;
      case 2:
        if (age < 3) { agegroup = 'infants'; }
        else if (age < 12) { agegroup = 'children'; }
        else { agegroup = 'adults'; }
        break;
    }
    return agegroup;
  };

  /**
  * Проверяет, привязаны ли все необходимые туристы согласно заявленному
  * возрастному составу
  * @return {Boolean} результат проверки
  */
  ServiceStorage.prototype.checkAllTouristsLinked = function() {
    var allLinked = true;

    for (var agegroup in this.touristAges) {
      if (this.touristAges.hasOwnProperty(agegroup)) {
        if (this.touristAges[agegroup] !== this.declaredTouristAges[agegroup]) {
          allLinked = false;
        }
      }
    }

    return allLinked;
  };

  /**
  * Обновление информации по привязанным туристам (возрастной состав)
  * @param {Object]} tourists - список данных туристов [TouristStorage]
  * @todo для каждого типа услуги нужен свой класс хранилища
  */
  ServiceStorage.prototype.setTouristAges = function(tourists) {
    if (this.isPartial) { return false; }

    var touristId, age;
    switch (this.typeCode) {
      case 1:
        this.touristAges = {
          adults: 0,
          children: 0
        };

        for (touristId in tourists) {
          if (this.tourists.hasOwnProperty(touristId)) {
            age = tourists[touristId].age;
            if (age < 12) { this.touristAges.children += 1; }
            else { this.touristAges.adults += 1; }
          }
        }
        break;
      case 2:
        this.touristAges = {
          adults: 0,
          children: 0,
          infants: 0
        };

        for (touristId in this.tourists) {
          if (this.tourists.hasOwnProperty(touristId)) {
            age = tourists[touristId].age;
            if (age < 3) { this.touristAges.infants += 1; }
            else if (age < 12) { this.touristAges.children += 1; }
            else { this.touristAges.adults += 1; }
          }
        }
        break;
    }
  };

  /**
  * Подсчет суммы клиентских штрафов при отмене брони
  * @return {Object|false} данные штрафа (сумма, валюта) или false в случае отсутствия
  */
  ServiceStorage.prototype.countClientCancelPenalty = function() {
    if (this.isPartial) { return null; }
    if (this.clientCancelPenalties === null) { return null; }

    return this.clientCancelPenalties.reduce(function(total, penalty) {
      if (moment().isBetween(penalty.dateFrom, penalty.dateTo, null, '[]')) {
        total.inLocal += penalty.penaltySum.inLocal.amount;
        total.inView += penalty.penaltySum.inView.amount;
      }
      return total;
    }, {'inLocal': 0, 'inView': 0});
  };
  
  /**
  * Подсчет суммы штрафов поставщика при отмене брони
  * @return {Object|false} данные штрафа (сумма, валюта) или false в случае отсутствия
  */
  ServiceStorage.prototype.countSupplierCancelPenalty = function() {
    if (this.isPartial) { return null; }
    if (this.supplierCancelPenalties === null) { return null; }

    return this.supplierCancelPenalties.reduce(function(total, penalty) {
      if (moment().isBetween(penalty.dateFrom, penalty.dateTo, null, '[]')) {
        total.inLocal += penalty.penaltySum.inLocal.amount;
        total.inView += penalty.penaltySum.inView.amount;
      }
      return total;
    }, {'inLocal': 0, 'inView': 0});
  };

  /**
  * Сравнение цен услуги с переданными
  * согласно задаче, достаточно сравнить брутто-цены
  * @param {Object} salesTerms - ценообразователи в стуктуре КТ
  */
  ServiceStorage.prototype.compareSalesTerms = function(salesTerms) {
    return (this.prices.inClient.client.gross === Number(salesTerms.clientCurrency.client.amountBrutto));
  };

  /**
  * Добавление дополнительной услуги
  * @param {Object} addService - дополнительная услуга
  */
  ServiceStorage.prototype.addAdditionalService = function(addService) {
    this.additionalServices.addService(addService);
  };

  /**
  * Возвращает объект дополнительной услуги
  * @param {String} addServiceId - внутренний ID оффера дополнительной услуги
  * @return {AdditionalService} - объект дополнительной услуги
  */
  ServiceStorage.prototype.getAdditionalServiceOffer = function(addServiceId) {
    return this.additionalServiceOffers.additionalServices.hasOwnProperty(addServiceId) ? 
      this.additionalServiceOffers.additionalServices[addServiceId] : null;
  };

  /**
  * Удаление дополнительной услуги
  * @param {Integer} addServiceId - ID дополнительной услуги
  */
  ServiceStorage.prototype.removeAdditionalService = function(addServiceId) {
    this.additionalServices.removeService(addServiceId);
  };

  return ServiceStorage;
}));
