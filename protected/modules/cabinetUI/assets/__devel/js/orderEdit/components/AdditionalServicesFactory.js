/* 
* Фабрика обработчиков дополнительных услуг проживания.
* Для целей отображения и управления некоторые доп. услуги агрегируются - 
* например, услуги раннего заезда (или позднего выезда) с одинаковой ценой
* агрегируются в одну с временным интервалом
*/
(function(global,factory) {

    KT.crates.OrderEdit.AdditionalServicesFactory = factory();
    
}(this, function() {
  /** 
  * Базовый класс дополнительной услуги 
  * @constructor
  * @param {String|Integer} innderId - внутренний ID услуги
  * @param {Object} addService - данные дополнительной услуги
  * @param {Object} shared - общие данные от фабрики (ex. шаблоны)
  */
  var AdditionalService = function(innerId, addService, shared) {
    this.innerId = innerId;
    this.shared = shared;
    this.serviceName = addService.name;
    this.serviceType = addService.serviceSubType;
    this.salesTermsInfo = addService.salesTermsInfo;
  };

  /** 
  * Функция генерации внутреннего ID услуги/оффера.
  * Каждая услуга должна определять собственную функцию.
  * Возвращает внутренний ID 
  */
  AdditionalService.generateId = function(addService) {
    return addService.serviceType + ':' + addService.idAddService;
  };

  /** Механизм наследования */
  AdditionalService.extend = function (cfunc) {
    cfunc.prototype = Object.create(this.prototype);
    cfunc.prototype.ancestor = this.prototype;
    cfunc.constructor = cfunc;
    cfunc.extend = this.extend;
    cfunc.generateId = function() {
      throw new Error(this.serviceName + ': generateId function not defined!');
    };
    return cfunc;
  };

  /** 
  * Позволяет выбрать дополнительные параметры для добавления дополнительной услуги.
  * нужно для услуг - агрегатов.
  * @return Promise<Object> - парамеры для addExtraService (без serviceId)
  */
  AdditionalService.prototype.chooseParams = function() {
    throw new Error(this.serviceName + ': chooseParams function not defined');
  };

  /*================================
  * Услуга дополнительного питания 
  *=================================*/
  /** Оффер дополнительного питания */
  var AdditionalMealOffer = AdditionalService.extend(function(innerId, addService) {
    AdditionalService.apply(this, arguments);
    this.serviceId = addService.idAddService;
    this.serviceTypeName = 'Дополнительное питание';
    this.serviceIcon = 'service-meal';
  });

  /** 
  * Генерирует внутренний ID для списка, необходимо прежде всего для услуг-агрегатов
  * @param {Object} addService - объект доп. услуги из API
  */
  AdditionalMealOffer.generateId = function(addService) {
    return 'meal' + addService.idAddService;
  };

  /** 
  * Добавление данных доп. услуги к агрегатной услуге 
  */
  AdditionalMealOffer.prototype.addService = function() {
    throw new Error('Не может быть двух услуг доп. питания с одинаковым ID!');
  };

  /**
  * Возвращает название доп. услуги 
  * @return {String} - название
  */
  AdditionalMealOffer.prototype.getServiceName = function() {
    return this.serviceName;
  };

  /**
  * Возвращает название доп. услуги с учетом того, что не будет видимой подсказки типа услуги
  * @return {String} - название
  */
  AdditionalMealOffer.prototype.getFullServiceName = function() {
    return this.serviceName;
  };

  /** 
  * Позволяет выбрать дополнительные параметры для добавления дополнительной услуги.
  * нужно для услуг - агрегатов.
  * @return Promise<Object> - парамеры для addExtraService (без serviceId)
  */
  AdditionalMealOffer.prototype.chooseParams = function() {
    var request = $.Deferred();
    var self = this;

    KT.Modal.notify({
      type: 'info',
      title: this.serviceTypeName,
      msg: Mustache.render(self.shared.tpl.AdditionalMealSelectModal, {}),
      buttons: [{
        type: 'common',
        title: 'добавить',
        callback: function($modal) {
          var isRequired = $modal.find('.js-additional-service--required').prop('checked');
          KT.Modal.closeModal();
          request.resolve({
            'addServiceOfferId': self.serviceId,
            'required': isRequired
          });
        }
      },{
        type: 'common',
        title: 'отмена',
        callback: function() {
          KT.Modal.closeModal();
          request.reject(); 
        }
      }]
    });
    
    return request.promise();
  };

  /** Позволяет выбрать дополнительные параметы */

  /** Услуга дополнительного питания */
  var AdditionalMeal = AdditionalMealOffer.extend(function(innerId, addService) {
    AdditionalMealOffer.apply(this, arguments);
    this.status = +addService.status;
    this.oneOfTypeAllowed = !Boolean(addService.bookingSomeServices);
    this.bookedWithService = addService.bookedWithService;
  });

  AdditionalMeal.generateId = function(addService) {
    return addService.idAddService;
  };

  /*==============================
  * Услуга раннего заезда
  *===============================*/
  var EarlyArrivalOffer = AdditionalService.extend(function(innerId, addService) {
    AdditionalService.apply(this, arguments);
    this.timeToIdMap = {};
    this.timeToIdMap[addService.specParamAddService.time] = addService.idAddService;

    this.serviceTypeName = 'Ранний заезд';
    this.serviceIcon = 'clockwatch';  
  });

  /** 
  * Генерирует внутренний ID для списка, необходимо прежде всего для услуг-агрегатов
  * @param {Object} addService - объект доп. услуги из API 
  */
  EarlyArrivalOffer.generateId = function(addService) {
    return 'arr' + addService.salesTermsInfo.localCurrency.client.amountBrutto;
  };

  /** 
  * Добавление данных доп. услуги к агрегатной услуге 
  */
  EarlyArrivalOffer.prototype.addService = function(addService) {
    this.timeToIdMap[addService.specParamAddService.time] = addService.idAddService;
  };

  /**
  * Возвращает название доп. услуги 
  * @return {String} - название
  */
  EarlyArrivalOffer.prototype.getServiceName = function() {
    var serviceName;
    var timings = Object.keys(this.timeToIdMap);

    if (timings.length === 1) {
      serviceName = ('в ' + timings[0] + '');
    } else {
      timings = timings.map(function(time) {
        var timeparts = time.split(':');
        return parseInt(timeparts[0]) * 100 + parseInt(timeparts[1]);
      });
      var minTiming = String(Math.min.apply(Math, timings));
      var maxTiming = String(Math.max.apply(Math, timings));
      minTiming = ('0000' + minTiming).substr(-4).replace(/^(\d{2})(\d{2})$/, '$1:$2');
      maxTiming = ('0000' + maxTiming).substr(-4).replace(/^(\d{2})(\d{2})$/, '$1:$2');

      serviceName = ('с ' + minTiming + ' по ' + maxTiming + '');
    }
    return serviceName;
  };

  /**
  * Возвращает название доп. услуги с учетом того, что не будет видимой подсказки типа услуги
  * @return {String} - название
  */
  EarlyArrivalOffer.prototype.getFullServiceName = function() {
    return [this.serviceTypeName, '[' + this.getServiceName() + ']'].join(' ');
  };

  /** 
  * Позволяет выбрать дополнительные параметры для добавления дополнительной услуги.
  * нужно для услуг - агрегатов.
  * @return Promise<Object> - парамеры для addExtraService (без serviceId)
  */
  EarlyArrivalOffer.prototype.chooseParams = function() {
    var request = $.Deferred();
    var self = this;

    var arrivalTimes = Object.keys(this.timeToIdMap).map(function(time) {
      return {'time': time, 'id': self.timeToIdMap[time]};
    });

    KT.Modal.notify({
      type: 'info',
      title: this.serviceTypeName,
      msg: Mustache.render(self.shared.tpl.EarlyArrivalSelectModal, {'arrivalTimes': arrivalTimes}),
      buttons: [{
        type: 'common',
        title: 'добавить',
        callback: function($modal) {
          var $checkedOption = $modal.find('.js-early-arrival--time').filter(':checked');
          var isRequired = $modal.find('.js-additional-service--required').prop('checked');

          if ($checkedOption.length > 0) {
            KT.Modal.closeModal();
            var selectedServiceId = $checkedOption.val();
            request.resolve({
              'addServiceOfferId': selectedServiceId,
              'required': isRequired
            });
          }
        }
      },{
        type: 'common',
        title: 'отмена',
        callback: function() {
            KT.Modal.closeModal();
            request.reject(); 
        }
      }]
    });
    
    
    return request.promise();
  };

  /** Услуга раннего заезда */
  var EarlyArrival = EarlyArrivalOffer.extend(function(innerId, addService) {
    EarlyArrivalOffer.apply(this, arguments);
    this.status = +addService.status;
    this.oneOfTypeAllowed = !Boolean(addService.bookingSomeServices);
    this.bookedWithService = addService.bookedWithService;
  });

  EarlyArrival.generateId = function(addService) {
    return addService.idAddService;
  };

  /*=============================
  * Услуга позднего выезда
  *==============================*/
  var LateDepartureOffer = EarlyArrivalOffer.extend(function() {
    EarlyArrivalOffer.apply(this, arguments);
    this.serviceTypeName = 'Поздний выезд';
  });

  /** 
  * Генерирует внутренний ID для списка, необходимо прежде всего для услуг-агрегатов
  * @param {Object} addService - объект доп. услуги из API 
  */
  LateDepartureOffer.generateId = function(addService) {
    return 'dep' + addService.salesTermsInfo.localCurrency.client.amountBrutto;
  };

    /** 
  * Позволяет выбрать дополнительные параметры для добавления дополнительной услуги.
  * нужно для услуг - агрегатов.
  * @return Promise<Object> - парамеры для addExtraService (без serviceId)
  */
  LateDepartureOffer.prototype.chooseParams = function() {
    var request = $.Deferred();
    var self = this;

    var departureTimes = Object.keys(this.timeToIdMap).map(function(time) {
      return {'time': time, 'id': self.timeToIdMap[time]};
    });

    KT.Modal.notify({
      type: 'info',
      title: this.serviceTypeName,
      msg: Mustache.render(self.shared.tpl.LateDepartureSelectModal, {'departureTimes': departureTimes}),
      buttons: [{
        type: 'common',
        title: 'добавить',
        callback: function($modal) {
          var $checkedOption = $modal.find('.js-late-departure--time').filter(':checked');
          var isRequired = $modal.find('.js-additional-service--required').prop('checked');

          if ($checkedOption.length > 0) {
            KT.Modal.closeModal();
            var selectedServiceId = $checkedOption.val();
            request.resolve({
              'addServiceOfferId': selectedServiceId,
              'required': isRequired
            });
          }
        }
      },{
        type: 'common',
        title: 'отмена',
        callback: function() {
            KT.Modal.closeModal();
            request.reject(); 
        }
      }]
    });
    
    
    return request.promise();
  };

  /** Услуга позднего выезда */
  var LateDeparture = LateDepartureOffer.extend(function(innerId, addService) {
    LateDepartureOffer.apply(this, arguments);
    this.status = +addService.status;
    this.oneOfTypeAllowed = !Boolean(addService.bookingSomeServices);
    this.bookedWithService = addService.bookedWithService;
  });

  LateDeparture.generateId = function(addService) {
    return addService.idAddService;
  };

  /*===============================================
  * Карта соответствия ID типа доп. услуги классу
  *================================================*/

  var addServiceOfferTypesMap = {
    1: AdditionalMealOffer,
    2: EarlyArrivalOffer,
    3: LateDepartureOffer
  };

  var addServiceTypesMap = {
    1: AdditionalMeal,
    2: EarlyArrival,
    3: LateDeparture
  };

  /*===================================
  * Фабрика дополнительных услуг.
  *====================================*/

  /** 
  * Создание объекта управления дополнительными услугами.
  * Генерирует список дополнительных услуг в внутреннем формате и предоставляет интерфейс управления списком
  * @param {Object} addServices - массив доп. услуг из API
  * @param {String} factoryType - тип фабрики: оффера (offers) или услуги (services).
  */
  var AdditionalServicesFactory = function(addServices, factoryType) {
    this.additionalServices = {};

    this.shared = {};

    switch (factoryType) {
      case 'offers':
        this.factoryType = 'offers';
        break;
      case 'services':
        this.factoryType = 'services';
        break;
      default:
        throw new Error('unknown AdditionalServices factory type: ' + factoryType);
    }

    try {
      var self = this;
      addServices.forEach(function(addService) {
        self.addService(addService);
      });
    } catch (err) {
      console.error('Ошибка обработки дополнительных услуг:');
      console.log(err);
    }
  };

  /**
  * Шаблоны для дополнительных услуг
  */
  AdditionalServicesFactory.templates = {
    AdditionalMealSelectModal: 'orderEdit/additionalServices/AdditionalMealSelectModal',
    EarlyArrivalSelectModal: 'orderEdit/additionalServices/EarlyArrivalSelectModal',
    LateDepartureSelectModal: 'orderEdit/additionalServices/LateDepartureSelectModal'
  };

  /** Установка шаблонов */
  AdditionalServicesFactory.prototype.setTemplates = function(tpl) {
    this.shared.tpl = tpl;
  };

  /**
  * Добавление дополнительной услуги к списку
  * @param {Object} addService - дополнительная услуга из API 
  */
  AdditionalServicesFactory.prototype.addService = function(addService) {
      var addServiceClass;
      switch (this.factoryType) {
        case 'offers':
          addServiceClass = addServiceOfferTypesMap[addService.serviceSubType];
          break;
        case 'services':
          addServiceClass = addServiceTypesMap[addService.serviceSubType];
      }

      if (addServiceClass === undefined) {
        console.warn('unknown additional service type: ' + addService.serviceSubType);
        return;
      }

      // генерация внутреннего ID доп. услуги
      var innerId = addServiceClass.generateId(addService);
      // если такой ID уже есть, то услуга агрегатная - добавляем к ней данные
      if (this.additionalServices.hasOwnProperty(innerId)) {
        this.additionalServices[innerId].addService(addService);
      } else {
        this.additionalServices[innerId] = new addServiceClass(innerId, addService, this.shared);
      }
  };

  /**
  * Удаление дополнительной услуги из списка
  * @param {Integer|String} addServiceId - ID удаляемой услуги
  */
  AdditionalServicesFactory.prototype.removeService = function(addServiceId) {
    delete this.additionalServices[addServiceId];
  };

  AdditionalServicesFactory.prototype.getServicesList = function() {
    var additionalServicesList = [];
    for (var innerId in this.additionalServices) {
      additionalServicesList.push(this.additionalServices[innerId]);
    }
    return additionalServicesList;
  };

  return AdditionalServicesFactory;

}));