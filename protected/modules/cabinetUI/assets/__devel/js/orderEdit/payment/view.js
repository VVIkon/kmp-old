(function(global,factory) {

    KT.crates.OrderEdit.payment.view = factory();

}(this, function() {
  /**
  * Редактирование заявки: оформление
  * @constructor
  * @param {Object} module - сслыка на радительский модуль
  * @param {Object} [options] - Объект конфигурации класса
  */
  var modView = function(module,options) {
    this.mds = module;
    if (options === undefined) { options = {}; }
    this.config = $.extend(true,{
      'templateUrl':'/cabinetUI/orders/getTemplates',
      'templates':{
        serviceList:'orderEdit/payment/serviceList',
        serviceListRow:'orderEdit/payment/serviceListRow',
        serviceActions:'orderEdit/payment/serviceActions',
        serviceNoActions: 'orderEdit/payment/serviceNoActions',
        invoiceListItem:'orderEdit/payment/invoiceListItem',
        invoiceListItemService:'orderEdit/payment/invoiceListItemService',
        invoiceListEmpty:'orderEdit/payment/invoiceListEmpty',
        TOSAgreementModal:'orderEdit/modals/acceptBookingTerms',
        multiBookCancelModal: 'orderEdit/modals/multiBookCancel'
      }
    },options);

    this.$serviceList = $('#order-edit-payment--services');
    this.$invoiceList = $('#order-edit-payment--invoices');
    this.$invoiceActions = $('#order-edit-payment--invoice-actions');

    /** @todo this should be in model? */
    this.discountSum = 0;
  };

  /**
  * Отображение списка услуг заявки
  * @param {ServiceStorage[]} services - сервисы заявки
  * @todo add param for setdiscount event or rework?
  */
  modView.prototype.renderServiceList = function(services) {
    var _instance = this;

    var serviceList = '';
    var totalNet = 0,
        totalGross = 0,
        totalCommission = 0,
        totalDiscount = 0;
    var showNetPrice = (KT.profile.userType === 'op');

    services.forEach(function(service) {
      var serviceInfo = {
        'serviceId': service.serviceId,
        'serviceIcon': KT.getCatalogInfo('servicetypes', service.typeCode, 'icon'),
        'serviceTypeName': KT.getCatalogInfo('servicetypes',service.typeCode, 'title'),
        'statusIcon': KT.getCatalogInfo('servicestatuses', service.status, 'icon'),
        'statusTitle': KT.getCatalogInfo('servicestatuses', service.status, 'title'),
        'serviceName': service.name,
        'net': showNetPrice ? service.prices.inLocal.supplier.gross.toMoney(2,',',' ') : null,
        'gross': service.prices.inLocal.client.gross.toMoney(2,',',' '),
        'offline': service.isOffline,
        'travelPolicyViolations': (!service.hasTPViolations) ? null : 
          {'list': service.offerInfo.travelPolicy.travelPolicyFailCodes}
      };

      if (KT.profile.userType === 'op' && service.status === 9) {
        serviceInfo.statusTitle = 'Ручной режим';
      }

      totalDiscount += service.discount;
      totalNet += service.prices.inLocal.supplier.gross;
      totalGross += service.prices.inLocal.client.gross;
      totalCommission += service.prices.inLocal.client.commission.amount;

      serviceList += Mustache.render(_instance.mds.tpl.serviceListRow, serviceInfo);
    });

    var serviceActions = Mustache.render(_instance.mds.tpl.serviceNoActions,{});

    var serviceTable = {
      'serviceList': serviceList,
      'showNetPrice': showNetPrice,
      'totalNet': showNetPrice ? totalNet.toMoney(2,',',' ') : null,
      'totalGross': totalGross.toMoney(2,',',' '),
      'totalCommission': totalCommission.toMoney(2,',',' '),
      'serviceActions': serviceActions,
      'discount': (KT.profile.userType === 'agent') ? totalDiscount.toMoney(2,',','') : null,
      'profit': (KT.profile.userType === 'agent') ? (totalCommission - totalDiscount).toMoney(2,',',' ') : null
    };

    _instance.$serviceList.html(Mustache.render(_instance.mds.tpl.serviceList, serviceTable));
    /** 
    * @todo не очень красиво, наверное, стоит подумать, как лучше блокировать чекбоксы услуг 
    * до загрузки доступных переходов
    */
    if (_instance.mds.OrderStorage.loadStates.transitionsdata === null) {
      _instance.$serviceList
        .find('.js-ore-payment-services--check-service')
          .prop('disabled', true);
    }

    if(KT.profile.userType === 'agent') {
      var $discountField = _instance.$serviceList.find('.js-ore-payment-services--discount');
      
      $discountField.jirafize({
        position: 'right',
        margin: '20px',
        buttons:{
          name: 'submit',
          type: 'submit',
          callback: function() {
            $discountField.prop('disabled',true);
            KT.dispatch('PaymentView.setDiscount', {
              'discount':parseFloat($discountField.val().replace(/ /g,'').replace(',','.')).toFixed(2)
            });
          }
        }
      });
    }
  };

  /**
  * Вывод элементов для управления услугами
  * @param {Object|false} actions - список действия или false если действия недоступны
  */
  modView.prototype.renderServiceActions = function(actions) {
    this.$serviceList
      .find('.js-ore-payment-services--check-service')
        .not('[data-offline="true"]')
          .prop('disabled', false);

    if (actions === false) {
      this.$serviceList.find('.js-ore-payment-services--service-actions')
        .html(Mustache.render(this.mds.tpl.serviceNoActions, {}));
    } else {
      this.$serviceList.find('.js-ore-payment-services--service-actions')
        .html(Mustache.render(this.mds.tpl.serviceActions, actions));
    }
  };

  /** Очистка списка доступных действий с услугами */
  modView.prototype.clearServiceActions = function() {
    this.$serviceList
      .find('.js-ore-payment-services--check-service')
        .empty();
  };

  /**
  * Возвращает идентификаторы выбранных для совершения действия услуг
  * @return {Integer[]} - массив ID услуг
  */
  modView.prototype.getSelectedServices = function() {
      var serviceIds = [];
      var $serviceControls = this.$serviceList.find('.js-ore-payment-services--check-service');

      // получим ID всех выбранных услуг
      $serviceControls
        .filter(':checked')
          .each(function() {
            serviceIds.push(+$(this).data('serviceid'));
      });

      return serviceIds;
  };

  /**
  * Отображение списка счетов заявки
  * @param {InvoiceStorage[]} invoices - массив счетов
  */
  modView.prototype.renderInvoiceList = function(Invoices) {
    var invoiceList = '';
    var _instance = this;

    Invoices.forEach(function(Invoice) {
      var serviceDetails = '';
      var currencySign = KT.getCatalogInfo('lcurrency',Invoice.currency,'icon');
      if (currencySign === 'unknown') {
        currencySign = Invoice.currency;
      }

      Invoice.getServiceDetails().forEach(function(service) {
        serviceDetails += Mustache.render(_instance.mds.tpl.invoiceListItemService, {
          'serviceId': service.serviceId,
          'name': service.name,
          'sum': service.sum.toMoney(2,',',' '),
          'currencySign': currencySign
        });
      });

      var invoiceInfo = {
        'invoiceId': Invoice.invoiceId,
        'statusIcon': KT.getCatalogInfo('invoicestatuses', Invoice.status, 'icon'),
        'statusTitle': KT.getCatalogInfo('invoicestatuses', Invoice.status, 'title'),
        'number': Invoice.number,
        'creationDate': Invoice.creationDate.format('DD.MM.YY'),
        'description': Invoice.description !== null ?
          Invoice.description : '[Без названия]',
        'sum': Invoice.sum.toMoney(2,',',' '),
        'currencySign': currencySign,
        'serviceDetails': serviceDetails,
        'actions': {
          /** Возможность удаления счетов убрана (возможно, временно) */
          'cancel': false // (Invoice.status !== 5) ? true : false
        }
      };

      invoiceList += Mustache.render(_instance.mds.tpl.invoiceListItem, invoiceInfo);
    });

    if (invoiceList === '') {
      invoiceList = Mustache.render(_instance.mds.tpl.invoiceListEmpty,{});
    }

    _instance.$invoiceList.html(invoiceList);
  };

  /** Очистка данных списка счетов */
  modView.prototype.clearInvoiceList = function() {
    this.$invoiceList.html(Mustache.render(KT.tpl.spinner,{}));
  };

  /** Очистка данных списка услуг */
  modView.prototype.clearServiceList = function() {
    this.$serviceList.html(Mustache.render(KT.tpl.spinner,{}));
  };

  /**
  * Рендер диалога бронирования нескольких услуг
  * @param {Object} tosData - данные по условиям юронирования
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showChainBookingModal = function(tosData, submitAction) {
    var _instance = this;

    KT.Modal.notify({
      type:'info',
      title:'Бронирование услуг',
      msg: Mustache.render(this.mds.tpl.TOSAgreementModal, tosData),
      buttons:[{
          type:'common',
          title:'да',
          callback: submitAction
        },
        {
          type:'common',
          title:'нет',
          callback: function() {
            KT.Modal.closeModal();
          }
      }]
    }).$container
      .find('#order-edit-payment--tos-agreement')
        .on('click', '.js-modal-accept-book-terms--tos-link', function() {
          var tosDocName = $(this).data('tosdoc');
          _instance.mds.servicesTermsDocuments[tosDocName].open();
        });
  };

  /**
  * Рендер диалога множественной отмены бронирования
  * @param {ServiceStorage[]} Services - массив услуг
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showChainBookCancelModal = function(Services, submitAction) {
    var modalParams = {
      'hasPenalty': false,
      'services': []
    };

    Services.forEach(function(Service) {
      var cancelPenaltySum = Service.countClientCancelPenalty();

      var servicePenalty = {
        'serviceId': Service.serviceId,
        'name': Service.name,
        'penalty': (cancelPenaltySum !== null && cancelPenaltySum.inLocal !== 0) ? {
            'localAmount': Number(cancelPenaltySum.inLocal).toMoney(0,',',' '),
            'localCurrency': KT.getCatalogInfo('lcurrency', KT.profile.localCurrency, 'icon'),
            'viewAmout': Number(cancelPenaltySum.inView).toMoney(0,',',' '),
            'viewCurrency': KT.getCatalogInfo('lcurrency', KT.profile.viewCurrency, 'icon'),
          } : null,
        'isInvoiceOptional': false // (KT.profile.userType === 'op' && servicePenalty.penalty !== false) ? true : false;
      };

      modalParams.services.push(servicePenalty);

      if (servicePenalty.penalty !== null) {
        modalParams.hasPenalty = true;
      }
    });
    
    KT.Modal.notify({
      type: 'info',
      title:'Отмена бронирования',
      msg: Mustache.render(this.mds.tpl.multiBookCancelModal, modalParams),
      buttons:[{
          type:'common',
          title:'да',
          callback: submitAction
        },
        {
          type:'common',
          title:'нет',
          callback: function() {
            KT.Modal.closeModal();
          }
      }]
    });

    return ;
  };

  /**
  * Рендер диалога множественной выписки билетов/ваучеров
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showChainIssueModal = function(submitAction) {
    KT.Modal.notify({
      type:'info',
      title:'Выписка ваучеров',
      msg: '<p>Выписать ваучеры для всех выбранных услуг?</p>',
      buttons:[{
          type:'common',
          title:'да',
          callback: submitAction
        },
        {
          type:'common',
          title:'нет',
          callback: function() {
            KT.Modal.closeModal();
          }
      }]
    });
  };

  /**
  * Рендер диалога множественного перевода услуг в ручной режим
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showChainSetToManualModal = function(submitAction) {
    KT.Modal.notify({
      type:'info',
      title:'Перевод в ручной режим',
      msg: '<p>Перевести выбранные услуги в ручной режим?</p>',
      buttons:[{
          type:'common',
          title:'да',
          callback: submitAction
        },
        {
          type:'common',
          title:'нет',
          callback: function() {
            KT.Modal.closeModal();
          }
      }]
    });
  };

  /**
  * Рендер диалога отмены счета
  * @param {InvoiceStorage} InvoiceStorage - данные счета
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showCancelInvoiceModal = function(InvoiceStorage, submitAction) {
    KT.Modal.notify({
      type:'info',
      title:'Отмена счета',
      msg: 'Вы действительно хотите отменить счет № ' + InvoiceStorage.number,
      buttons:[{
          type:'common',
          title:'да',
          callback: submitAction
        },
        {
          type:'common',
          title:'нет',
          callback: function() {
            KT.Modal.closeModal();
          }
      }]
    });
  };

  return modView;
}));
