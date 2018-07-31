/* global moment */
/* global KT */
/* global ktStorage */

(function(global,factory){

    KT.storage.OrderStorage = factory();

}(this,function() {
  /**
  * Хранилище данных заявки
  * @module OrderStorage
  * @constructor
  * @param {Integer} orderId - ID заявки
  */
  var OrderStorage = ktStorage.extend(function(orderId) {
    this.namespace = 'OrderStorage';

    // состояние загрузки данных
    this.loadStates = {
      'orderdata': null,
      'servicedata': null,
      'invoicedata': null,
      'touristdata': null,
      'transitionsdata': null
    };

    // идентификатор заявки (KT)
    this.orderId = orderId;
    // номер заявки
    this.orderNumber = null;
    // список услуг (ServiceStorage)
    this.services = {};
    // список счетов
    this.invoices = {};
    // список туристов (TouristStorage)
    this.tourists = {};
    // документы заявки
    this.documents = [];
    // история заявки
    this.history = [];

    // счетчик оставшихся активнх запросов по валидации действий над услугами
    // см. checkWorkflow - validate
    this.pendingValidations = 0;

    // результат валидации для группы услуг: {действие: вердикт}
    this.validatedActions = {};
  });

  KT.addMixin(OrderStorage,'Dispatcher');

  /**
  * Инициализация хранилища данными из getOrder
  * @param {Object} orderData - данные заявки
  */
  OrderStorage.prototype.initialize = function(orderData) {
    if (orderData.orderId !== this.orderId) {
        KT.error(
          this.namespace+': ' +
          'попытка инициализации данными другой заявки,' +
          ' текущая: ' . this.orderId +
          ' данные от: '. orderData.orderId
        );
    }

    var isNotEmpty = function(v) {
      return !(v === undefined || v === null || v === '');
    };

    this.orderNumber = orderData.orderNumber;

    // Идентификаторы заявки в шлюзах
    this.orderIdGp = isNotEmpty(orderData.orderIdGp) ? orderData.orderIdGp : null;
    this.orderIdUtk = isNotEmpty(orderData.orderIdUtk) ? orderData.orderIdUtk : null;

    // статусная информация
    this.status = +orderData.status;
    this.isVip = orderData.VIP;
    this.isArchived = orderData.archive;
    this.isOffline = false;
    this.isAddTouristAllowed = false;

    // ID контракта заявки
    this.contractId = orderData.contractID;

    // даты создания/начала/окончания заявки
    /** @todo дата создания пока не приходит */
    this.creationDate = (orderData.orderDate !== null) ?
      moment(orderData.orderDate,'YYYY-MM-DD HH:mm:ss') : null;
    this.startDate = moment(orderData.startDate,'YYYY-MM-DD HH:mm:ss');
    this.endDate = moment(orderData.endDate,'YYYY-MM-DD HH:mm:ss');

    // данные создателя заявки
    this.creator = (orderData.creator.id === null) ? null : {
      id: orderData.creator.id,
      firstname: orderData.creator.firstName,
      lastname: orderData.creator.lastName,
      middlename: isNotEmpty(orderData.creator.middleName) ?
        orderData.creator.middleName : null
    };

    // тип компании, для которой создается заявка (агентство, корпоратор)
    this.companyRoleType = (function(roleType) {
      switch (roleType) {
        case 1: return 'op';
        case 2: return 'agent';
        case 3: return 'corp';
        default: return KT.profile.userType;
      }
    }(orderData.companyRoleType));

    // данные ответственного менеджера КМП заявки
    this.kmpManager = (orderData.managerKMP.id === null) ? null : {
      id: orderData.managerKMP.id,
      firstname: orderData.managerKMP.firstName,
      lastname: orderData.managerKMP.lastName,
      middlename: isNotEmpty(orderData.managerKMP.middleName) ?
        orderData.managerKMP.middleName : null
    };

    // данные ответственного менеджера клиента заявки
    this.clientManager = (orderData.clientManager.id === null) ? null : {
      id: orderData.clientManager.id,
      firstname: orderData.clientManager.firstName,
      lastname: orderData.clientManager.lastName,
      middlename: isNotEmpty(orderData.clientManager.middleName) ?
        orderData.clientManager.middleName : null
    };

    // данные клиента заявки
    this.client = {
      id: orderData.agentId,
      name: orderData.agencyName,
      holdingCompany: (orderData.companyMainOffice !== undefined) ? 
        orderData.companyMainOffice : null
    };

    // данные турлидера заявки
    this.tourleader = (
        orderData.touristFirstName !== null &&
        orderData.touristLastName !== null
      ) ? {
        firstname: orderData.touristFirstName,
        lastname: orderData.touristLastName,
        middlename: null, /** @todo отчество турлидера не приходит */
        phone: isNotEmpty(orderData.liderPhone) ? orderData.liderPhone : null,
        email: isNotEmpty(orderData.liderEmail) ? orderData.liderEmail : null
      } : null;

    // количество туристов
    this.touristsAmount = orderData.touristsNums;

    // данные привязки туристов к услугам
    this.servicesTouristLinkage = {};

    var self = this;

    // заполнение услуг
    if (Array.isArray(orderData.services)) {
      orderData.services.forEach(function(service) {
        if (self.services[service.serviceID] === undefined) {
          self.services[service.serviceID] = new KT.storage.ServiceStorage(service.serviceID);
        }

        self.services[service.serviceID].initialize(service);
        if (self.services[service.serviceID].isOffline) {
          self.isOffline = true;
        }
      });
    }

    /** @todo это все должно уйти в checkworkflow, наверное? */
    var allowedStatuses = [ 0,1,2,5,9,10 ];
    if (allowedStatuses.indexOf(this.status) !== -1) {
      this.isAddTouristAllowed = true;
    }

    this.loadStates.orderdata = 'loaded';
    this.dispatch('initialized', this);
  };

  /**
  * Инициализация хранилища для новой заявки (установка настроек по умолчанию)
  * @param {Object} bareData - начальные данные заявки (клиент, создатель, ...)
  */
  OrderStorage.prototype.initializeBare = function(bareData) {
    // Идентификаторы заявки в шлюзах
    this.orderIdGp = null;
    this.orderIdUtk = null;

    // статусная информация
    this.status = 0;
    this.isVip = false;
    this.isArchived = false;
    this.isOffline = false;
    this.isAddTouristAllowed = true;

    // ID контракта заявки
    this.contractId = bareData.contractId;

    // даты создания/начала/окончания заявки
    /** @todo дата создания пока не приходит */
    this.creationDate = moment();
    this.startDate = this.creationDate;
    this.endDate = this.creationDate;

    // данные создателя заявки
    this.creator = {
      'id': KT.profile.user.id,
      'firstname': KT.profile.user.firstName,
      'lastname': KT.profile.user.lastName,
      'middlename': KT.profile.user.middleName
    };

    // тип компании, для которой создается заявка (агентство, корпоратор)
    this.companyRoleType = (function(roleType) {
      switch (roleType) {
        case 1: return 'op';
        case 2: return 'agent';
        case 3: return 'corp';
        default: return KT.profile.userType;
      }
    }(bareData.clientType));

    // данные ответственного менеджера КМП заявки
    this.kmpManager = null;

    // данные ответственного менеджера клиента заявки
    this.clientManager = null;

    // данные клиента заявки
    this.client = {
      id: bareData.clientId,
      name: bareData.clientName,
      holdingCompany: (bareData.companyMainOffice !== undefined) ? 
        bareData.companyMainOffice : null
    };

    // данные турлидера заявки
    this.tourleader = null;

    // количество туристов
    this.touristsAmount = 0;

    this.dispatch('initialized', this);
  };

  /**
  * Сохранение информации по документам заявки 
  * @param {Array} documents - документы
  */
  OrderStorage.prototype.setDocuments = function(documents) {
    this.documents = documents;
    this.dispatch('setDocuments', {'items': documents});
  };

  /**
  * Сохранение истории заявки
  * @param {Array} history - история заявки 
  */
  OrderStorage.prototype.setHistory = function(history) {
    this.history = history;
    this.dispatch('setHistory', {
      'records': history.map(function(record) {
        return $.extend(true, {}, record);
      })
    });
  };

  /**
  * Возвращает историю заявки
  * @return {Array|null} - история заявки 
  */
  OrderStorage.prototype.getHistory = function() {
    return (!Array.isArray(this.history)) ? null : 
      this.history.map(function(record) {
        return $.extend(true, {}, record);
      });
  };

  /**
  * Сохранение информации по услугам
  * @param {Array} services - информация по услугам (/getOrderOffer)
  */
  OrderStorage.prototype.setServices = function(services) {
    var self = this;

    services.forEach(function(service) {
      if (self.services[service.serviceId] === undefined) {
        KT.error(this.namespace + ': ' + 'неизвестная услуга:' + service.serviceId);
        return;
      }

      self.services[service.serviceId].updateFullInfo(service);
    });

    // если туристы уже загружены, обновить для услуг информацию по возрастному составу
    if (this.loadStates.touristdata !== null) {
      this.updateServiceTourists();
    }

    this.loadStates.servicedata = 'loaded';
    this.dispatch('setServices',this);
  };

  /**
  * Возвращает список услуг как массив
  * @return {ServiceStorage[]} массив услуг
  */
  OrderStorage.prototype.getServices = function() {
    var services = [];

    for(var serviceId in this.services) {
      if (this.services.hasOwnProperty(serviceId)) {
        services.push(this.services[serviceId]);
      }
    }

    return services;
  };

  /**
  * Возвращает список идентификаторов услуг в заявке
  * @return {Integer[]} массив идентификаторов
  */
  OrderStorage.prototype.getServiceIds = function() {
    var serviceIds = [];

    for(var serviceId in this.services) {
      if (this.services.hasOwnProperty(serviceId)) {
        serviceIds.push(serviceId);
      }
    }

    return serviceIds;
  };

  /**
  * Сохраняет информацию по счетам
  * @param {Array} invoices - массив счетов (/getOrderInvoices)
  */
  OrderStorage.prototype.setInvoices = function(invoices) {
    var self = this;

    for (var serviceId in this.services) {
      if (this.services.hasOwnProperty(serviceId)) {
        this.services[serviceId].invoiceSum = 0;
      }
    }

    invoices.forEach(function(invoice) {
      if (self.invoices[invoice.invoiceId] === undefined) {
        self.invoices[invoice.invoiceId] = new KT.storage.InvoiceStorage(invoice.invoiceId);
      }
      self.invoices[invoice.invoiceId].initialize(invoice);
    });

    this.loadStates.invoicedata = 'loaded';
    this.dispatch('setInvoices',this);
  };

  /**
  * Возвращает список счетов как массив
  * @return {InvoiceStorage[]} массив счетов
  */
  OrderStorage.prototype.getInvoices = function() {
    var invoices = [];

    for(var invoiceId in this.invoices) {
      if (this.invoices.hasOwnProperty(invoiceId)) {
        invoices.push(this.invoices[invoiceId]);
      }
    }

    return invoices;
  };

  /**
  * Сохранение информации по туристам
  * @param {Array} tourists - информация по туристам (/getOrderTourists)
  */
  OrderStorage.prototype.setTourists = function(tourists) {
    var self = this;

    this.servicesTouristLinkage = {};

    tourists.forEach(function(tourist) {
      if (self.tourists[tourist.touristId] === undefined) {
        self.tourists[tourist.touristId] = new KT.storage.TouristStorage(tourist.touristId);
      }
      self.tourists[tourist.touristId].initialize(tourist);
      var TouristStorage = self.tourists[tourist.touristId];

      TouristStorage.linkedServices.forEach(function(linkedService) {
        if (!self.servicesTouristLinkage.hasOwnProperty(linkedService.serviceId)) {
          self.servicesTouristLinkage[linkedService.serviceId] = {};
        }
        self.servicesTouristLinkage[linkedService.serviceId][TouristStorage.touristId] = {
          'touristId': TouristStorage.touristId,
          'isAttached': true, /** @deprecated?  */
          'firstname': TouristStorage.firstname,
          'lastname': TouristStorage.lastname,
          'middlename': TouristStorage.middlename,
          'loyalityProviderId': linkedService.loyalityProviderId,
          'loyalityCardNumber': linkedService.loyalityCardNumber
        };
      });
    });

    // если услуги уже загружены, обновить для услуг информацию по возрастному составу
    if (this.loadStates.servicedata !== null) {
      this.updateServiceTourists();
    }

    this.loadStates.touristdata = 'loaded';
    this.dispatch('setTourists', this);
  };

  /**
  * Возвращает список туристов как массив
  * @return {TouristStorage[]} массив туристов
  */
  OrderStorage.prototype.getTourists = function() {
    var tourists = [];

    for (var touristId in this.tourists) {
      if (this.tourists.hasOwnProperty(touristId)) {
        tourists.push(this.tourists[touristId]);
      }
    }

    return tourists;
  };

  /**
  * Обновление информации о туристах в составе услуг (возрастной состав)
  * @param {Integer} [serviceId] - если указано, обновить данные только по этой услуге 
  */
  OrderStorage.prototype.updateServiceTourists = function(updateServiceId) {
    var self = this;

    var updateServiceTourists = function(serviceId) {
        if (self.servicesTouristLinkage.hasOwnProperty(serviceId)) {
          self.services[serviceId].tourists = self.servicesTouristLinkage[serviceId];
        }
        self.services[serviceId].setTouristAges(self.tourists);
    };

    if (updateServiceId !== undefined) {
      updateServiceTourists(updateServiceId);
    } else {
      for (var serviceId in this.services) {
        if (this.services.hasOwnProperty(serviceId)) {
          updateServiceTourists(serviceId);
        }
      }
    }
  };

  /**
  * Сохранение туриста в структуре заявки
  * @param {TouristStorage} Tourist - турист
  */
  OrderStorage.prototype.saveTourist = function(Tourist) {
    this.tourists[Tourist.touristId] = Tourist;

    if (Tourist.isTourleader || this.tourleader === null) {

      this.tourleader = {
        firstname: Tourist.firstname,
        lastname: Tourist.lastname,
        middlename: null, /** @todo отчество турлидера не приходит */
        phone: Tourist.getPhoneNumber(),
        email: Tourist.email
      };
    }

    // количество туристов
    this.touristsAmount = this.getTourists().length;

    this.dispatch('savedTourist', {'touristId': Tourist.touristId});
  };

  /**
  * Сохранение статусов привязки туристов к услуге
  * @param {Integer} serviceId - ID услуги
  * @param {Object} linkageInfo - данные привязки туристов
  */
  OrderStorage.prototype.saveServiceLinkage = function(serviceId, linkageInfo) {
    var self = this;
    var service = this.services[serviceId];

    linkageInfo.forEach(function(touristLinkage) {
      var tourist = self.tourists[touristLinkage.touristId];
      if (touristLinkage.link === true) {
        tourist.linkedServices.push({
          'serviceId': service.serviceId,
          'loyalityProviderId': touristLinkage.aviaLoyalityProgrammId,
          'loyalityCardNumber': touristLinkage.bonuscardNumber
        });
        service.tourists[tourist.touristId] = {
          'touristId': tourist.touristId,
          'isAttached': true, /** @deprecated?  */
          'firstname': tourist.firstname,
          'lastname': tourist.lastname,
          'middlename': tourist.middlename,
          'loyalityProviderId': touristLinkage.aviaLoyalityProgrammId,
          'loyalityCardNumber': touristLinkage.bonuscardNumber
        };
      } else {
        tourist.linkedServices = tourist.linkedServices.filter(function(linkedService) {
          return (linkedService.serviceId !== serviceId);
        });
        delete service.tourists[tourist.touristId];
      }
    });
    
    service.setTouristAges(this.tourists);

    this.dispatch('savedServiceLinkage', {
      'serviceId' : serviceId
    });
  };

  /**
  * Удаление туриста
  * @param {Integer} touristId - ID удаляемого туриста
  */
  OrderStorage.prototype.removeTourist = function(touristId) {
    if (this.tourists[touristId].isTourleader) {
      this.tourleader = null;
    }
    delete this.tourists[touristId];
    this.dispatch('touristRemoved', {'touristId': touristId});
  };

  /**
  * Сохранение данные о доступных действиях
  * @param {Array} transitions - списов доступных действий (/checkWorkflow)
  */
  OrderStorage.prototype.setAllowedTransitions = function(transitions) {
    var self = this;

    transitions.forEach(function(service) {
      self.services[service.serviceId].setAllowedTransitions(service.controls);
    });

    this.loadStates.transitionsdata = 'loaded';
    this.dispatch('setAllowedTransitions',this);
  };
  
  /**
  * Формирование структуры привязки туристов к услуге
  * @param {Integer} serviceId - ID редактируемой услуги
  * @param {Object} newTouristLinkage - список туристов со статусами привязки и дополнительной информацией
  * @return {Array} - параметры привязки туристов для передачи в BE
  */
  OrderStorage.prototype.createLinkageStructure = function(serviceId, newTouristLinkage) {
    var linkageInfo = [];
    var linkedTourists = {};
    var service = this.services[serviceId];

    /* Сохранить уже привязанных туристов */
    service.getServiceTourists().forEach(function(tourist) {
      linkedTourists[tourist.touristId] = true;
    });

    for (var touristId in newTouristLinkage) {
      if (newTouristLinkage.hasOwnProperty(touristId)) {
        var isAttached = newTouristLinkage[touristId].state;
        if (isAttached) {
          var tourist = this.tourists[touristId];

          if (tourist.document.expiryDate !== null) {
            var docExpiry = tourist.document.expiryDate.valueOf();
            if (docExpiry <= service.endDate.valueOf()) {
              KT.notify('touristLinkageNotAllowedByDocument');
              return false;
            }
          }

          linkageInfo.push({
            'touristId': +touristId,
            'bonuscardNumber': newTouristLinkage[touristId].loyalityCardNumber,
            'aviaLoyalityProgrammId': newTouristLinkage[touristId].loyalityProviderId,
            'link': true
          });
        } else if (linkedTourists[touristId] === true && !isAttached) {
          linkageInfo.push({
            'touristId': +touristId,
            'bonuscardNumber': null,
            'aviaLoyalityProgrammId': null,
            'link': false
          });
        }
      }
    }

    return linkageInfo;
  };

  /**
  * Формирование параметров для вызова команд для работы с услугой
  * @param {String} command - название команды
  * @param {Integer} serviceId - ID услуги для проверки
  * @return {Object|false} - набор параметров (actionParams) или false в случае ошибки формирования
  */
  OrderStorage.prototype.getServiceCommandParams = function(command, serviceId) {
    switch(command) {
      // Команда запуска бронирования
      case 'BookStart':
        if (this.services[serviceId] === undefined) {
          console.error('BookStart: услуга ' + serviceId + ' не найдена');
          return false;
        }
        var service = this.services[serviceId];

        return {
          'serviceId':serviceId,
          'agreementSet':service.isTOSAgreementSet
        };
      // Команда отмены брони
      case 'BookCancel':
        return {
          'serviceId': serviceId,
          'viewCurrency': KT.profile.viewCurrency,
          'createPenaltyInvoice': true
        };
      // Команда выписки билетов
      case 'IssueTickets':
        return {
          'serviceId': serviceId
        };
      // Команда выставления счетов
      case 'PayStart':
        return {
          'serviceId': serviceId
        };
      // Команда выставления статуса ручного режима
      case 'Manual':
        return {
          'serviceId': serviceId
        };
      // Команда запроса на изменение / изменения услуги
      case 'ServiceChange':
        return {
          'serviceId': serviceId
        };
      // Команда отмены услуги
      case 'ServiceCancel':
        return {
          'serviceId': serviceId
        };

      default:
        return false;
    }
  };

  /**
  * Сохранение результата валидации действий над услугами
  * @param {String} action - валидируемое действие
  * @param {Object|false} validationResult - данные процедуры валидации или false в случае ошибки
  */
  OrderStorage.prototype.setValidatedAction = function(action, validationResults) {
    var self = this;

    self.pendingValidations--;

    if (validationResults === false) {
      self.validatedActions[action] = {'validated': false};
    } else {
      self.validatedActions[action] = {'validated': true};

      validationResults.forEach(function(service) {
        if (service.validationResult === false) {
          self.validatedActions[action].validated = false;
        }
      });
    }

    if (this.pendingValidations === 0) {
      this.dispatch('setValidatedActions', this);
    }
  };

  return OrderStorage;
}));
