/* global ktStorage */
(function(global,factory){

    KT.storage.InvoiceStorage = factory();

}(this,function() {
  /**
  * Хранилище выставленного счета
  * @module InvoiceStorage
  * @constructor
  * @param {Integer} invoiceId - ID услуги
  */
  var InvoiceStorage = ktStorage.extend(function(invoiceId) {
    this.namespace = 'InvoiceStorage';

    this.invoiceId = invoiceId;
  });

  KT.addMixin(InvoiceStorage,'Dispatcher');

  // статусы счета
  InvoiceStorage.prototype.statuses = {
    'WAIT': 1,
    'INVOICED': 2,
    'PARTIAL_PAID': 3,
    'PAID': 4,
    'CANCELLED': 5
  };

  /**
  * Инициализация хранилища
  * @param {Object} invoiceData - данные счета (/getOrder)
  */
  InvoiceStorage.prototype.initialize = function(invoiceData) {
    if (invoiceData.invoiceId !== this.invoiceId) {
        KT.error(
          this.namespace+': ' +
          'попытка инициализации данными другого инвойса,' +
          ' текущая: ' . this.invoiceId +
          ' данные от: '. invoiceData.invoiceId
        );
    }

    // номер счета
    this.number = invoiceData.invoiceNum;

    // статус счета
    this.status = +invoiceData.status;

    // описание счета
    this.description = invoiceData.description;

    // дата выставления
    this.creationDate = moment(invoiceData.creationDate,'YYYY-MM-DD HH:mm:ss');

    // сумма счета и валюта
    this.sum = Number(invoiceData.invoiceSum);
    this.currency = invoiceData.invoiceCur;

    // детализация по услугам
    this.serviceDetails = {};

    var self = this;

    invoiceData.InvoiceServices.forEach(function(service) {
      self.serviceDetails[service.serviceId] = {
        'sum': Number(service.serviceSum),
        'name': service.serviceName
      };
    });
  };

  /**
  * Возвращает детализацию счета по услугам в виде массива
  * @return {Array} - информация по услуге в составе счета
  */
  InvoiceStorage.prototype.getServiceDetails = function() {
    var serviceDetails = [], srv;

    for (var serviceId in this.serviceDetails) {
      if (this.serviceDetails.hasOwnProperty(serviceId)) {
        srv = $.extend(true,{},this.serviceDetails[serviceId]);
        srv.serviceId = serviceId;
        serviceDetails.push(srv);
      }
    }

    return serviceDetails;
  };

  return InvoiceStorage;
}));
