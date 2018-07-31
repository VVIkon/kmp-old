/* global ktStorage */
(function(global,factory){

    KT.storage.TouristStorage = factory();

}(this,function() {
  /**
  * Хранилище данных туриста
  * @module TouristStorage
  * @constructor
  * @param {Integer} serviceId - ID услуги
  */
  var TouristStorage = ktStorage.extend(function(touristId) {
    this.namespace = 'TouristStorage';

    this.touristId = touristId;

    // услуги, с которыми связан турист
    this.linkedServices = [];

    // карты программ лояльности
    this.bonusCards = [];

    // структура дополнительных полей
    this.customFields = null;
  });

  KT.addMixin(TouristStorage,'Dispatcher');

  /**
  * Инициализация хранилища
  * @param {Object} touristData - данные туриста (/getOrderTourist)
  */
  TouristStorage.prototype.initialize = function(touristData) {
    if (touristData.touristId !== this.touristId) {
        KT.error(
          this.namespace+': ' +
          'попытка инициализации данными другого туриста,' +
          ' текущий: ' . this.touristId +
          ' данные от: '. serviceData.touristId
        );
    }

    var isNotEmpty = function(v) {
      return !(v === undefined || v === null || v === '');
    };

    // ФИО туриста
    this.firstname = htmlDecode(touristData.firstName);
    this.lastname = htmlDecode(touristData.lastName);
    this.middlename = isNotEmpty(touristData.middleName) ?
      htmlDecode(touristData.middleName) : null;

    // пол
    this.sex = (touristData.sex === 1) ? 'male' : 'female';

    // контакты
    this.email = isNotEmpty(touristData.email) ? touristData.email : null;
    this.phone = {
      countryCode: null,
      cityCode: null,
      number: null
    };

    if (isNotEmpty(touristData.phone)) {
      var phone = String(touristData.phone).replace(/\s/g,'');
      var phoneparsed = phone.match(/^\+?(\d+)\((\d+)\)(\d+)$/);

      if (phoneparsed === null) {
        this.phone.number = phone;
      } else {
        this.phone.countryCode = phoneparsed[1];
        this.phone.cityCode = phoneparsed[2];
        this.phone.number = phoneparsed[3];
      }
    }

    // признак турлидера
    this.isTourleader = touristData.isTourLeader;

    // дата рождения и возраст
    this.birthdate = (touristData.birthdate !== null) ? moment(touristData.birthdate,'YYYY-MM-DD') : null;
    this.age = (this.birthdate !== null ) ? 
      moment.duration(moment().valueOf() - this.birthdate.valueOf()).asYears() : 
      null;

    // документы
    var documentData = touristData.document;
    this.document = {
      series: documentData.serialNumber,
      number: documentData.number,
      type: documentData.documentType,
      firstname: htmlDecode(documentData.firstName),
      lastname: htmlDecode(documentData.lastName),
      middlename: isNotEmpty(documentData.middleName) ?
        htmlDecode(documentData.middleName) : null,
      //issueDate: moment(documentData.issueDate,'YYYY-MM-DD'),
      expiryDate: (documentData.expiryDate !== null) ? 
        moment(documentData.expiryDate,'YYYY-MM-DD') : null,
      //issueDepartment: documentData.issueDepartment,
      citizenship: documentData.citizenship
    };

    var self = this;

    // привязка к сервисам
    if (Array.isArray(touristData.services)) {
      touristData.services.forEach(function(service) {
        self.linkedServices.push({
          'serviceId': service.serviceId,
          'loyalityProviderId': service.aviaLoyalityProgrammId,
          'loyalityCardNumber': service.bonuscardNumber
        });
      });
    }

    // карты программ лояльности
    this.bonusCards = Array.isArray(touristData.bonusCards) ? touristData.bonusCards : [];

    // дополнительные поля
    this.customFields = (
        Array.isArray(touristData.touristAdditionalData) && 
        touristData.touristAdditionalData.length > 0
      ) ? touristData.touristAdditionalData : null;
  };

  /**
  * Инициализация хранилища по данным пользователя
  * @param {Object} userData - данные пользователя (ль getClientUser) 
  */
  TouristStorage.prototype.initializeFromUser = function(userData) {
    var isNotEmpty = function(v) {
      return !(v === undefined || v === null || v === '');
    };

    // ID связанного пользователя
    this.userId = userData.user.userId;

    // ФИО туриста
    this.firstname = htmlDecode(userData.user.name);
    this.lastname = htmlDecode(userData.user.surname);
    this.middlename = isNotEmpty(userData.user.secondName) ?
      htmlDecode(userData.user.secondName) : null;

    // пол
    this.sex = (userData.user.prefix === 1) ? 'male' : 'female';

    // контакты
    this.email = isNotEmpty(userData.user.email) ? userData.user.email : null;
    this.phone = {
      countryCode: null,
      cityCode: null,
      number: null
    };

    if (isNotEmpty(userData.user.contactPhone)) {
      var phone = String(userData.user.contactPhone).replace(/\s/g,'');
      var phoneparsed = phone.match(/^\+?(\d+)\((\d+)\)(\d+)$/);

      if (phoneparsed === null) {
        this.phone.number = phone;
      } else {
        this.phone.countryCode = phoneparsed[1];
        this.phone.cityCode = phoneparsed[2];
        this.phone.number = phoneparsed[3];
      }
    }

    // признак турлидера
    this.isTourleader = false;

    // дата рождения и возраст
    this.birthdate = (userData.user.birthDate !== null) ? moment(userData.user.birthDate,'YYYY-MM-DD') : null;
    this.age = (userData.user.birthDate !== null ) ? 
      moment.duration(moment().valueOf() - this.birthdate.valueOf()).asYears() : 
      null;

    // документы
    this.document = {
      documentId: userData.document.docId,
      series: userData.document.docSerial,
      number: userData.document.docNumber,
      type: userData.document.docType,
      firstname: htmlDecode(userData.document.firstName),
      lastname: htmlDecode(userData.document.lastName),
      middlename: isNotEmpty(userData.document.middleName) ?
        htmlDecode(userData.document.middleName) : null,
      //issueDate: moment(documentData.issueDate,'YYYY-MM-DD'),
      expiryDate: (userData.document.docExpiryDate !== null) ? 
        moment(userData.document.docExpiryDate,'YYYY-MM-DD') : null,
      //issueDepartment: documentData.issueDepartment,
      citizenship: userData.document.citizenship
    };

    // карты программ лояльности
    this.bonusCards = Array.isArray(userData.user.bonusCards) ? userData.user.bonusCards : [];

    // дополнительные поля
    this.customFields = Array.isArray(userData.addData) ? 
      userData.addData : null;
  };

  /**
  * Возвращает строку с номером телефона
  * @return {String|null} - номер телефона или null если его нет
  */
  TouristStorage.prototype.getPhoneNumber = function() {
    if (this.phone.number !== null) {
      if (this.phone.countryCode !== null) {
        return '+' + this.phone.countryCode +
          '(' + this.phone.cityCode + ')' +
          this.phone.number;
      } else {
        return this.phone.number;
      }
    } else {
      return null;
    }
  };

  return TouristStorage;
}));
