(function(global,factory){

    KT.crates.OrderEdit.view = factory();

}(this,function() {
  /**
  * Редактирование заявки
  * @constructor
  * @param {Object} module - хранилище родительского модуля
  * @param {Number} orderId - Id заявки
  * @param {Object} [options] - Объект конфигурации класса
  */
  var modView = function(module, orderId, options) {
    this.mds = module;
    if (options === undefined) { options = {}; }
    this.config = $.extend(true, {
      'templateUrl':'/cabinetUI/orders/getTemplates',
      'templates':{
        headerOrderInfo:'orderEdit/headerInfo',
        headerControls:'orderEdit/headerControls',
        headerSpinner:'orderEdit/headerSpinner',
        invoices:'orderEdit/invoices',
        invoicesRow:'orderEdit/invoicesRow',
        documents:'orderEdit/documents',
        documentsRow:'orderEdit/documentsRow',
        documentsEmpty:'orderEdit/documentsEmpty',
        history:'orderEdit/history',
        historyRow:'orderEdit/historyRow',
        sendViaEmailForm:'orderEdit/sendViaEmailForm'
      }
    },options);

    this.mds.tpl = {};

    this.orderId = orderId;

    this.$headerInfo = $('#order-edit-header__info');
    this.$headerControls = $('#order-edit-header__controls');
    this.$breadcrumb = $('#order-edit-header__ordernum');

    this.$addServiceBlock = $('.order-footer .add-service-block');

    /** Установка хлебной крошки */
    this.$breadcrumb.html(
      this.orderId === 'new' ?
      'Новая заявка' :
      'Заявка ...'
    );

    /** @todo move it */
    this.initTabs();

    this.SetInvoiceForm = this.setSetInvoiceForm();
    this.SetInvoiceForm.init();

    this.Documents = this.setDocuments();
    this.Documents.init();

    this.History = this.setHistory();
    this.History.init();

    /*
    this.$btnInvoices.featherlight(this.invoices.$wrapper, {
      persist:'shared',
      closeIcon:''
    }); */
  };

  /** Разблокировка раздела */
  modView.prototype.enableTab = function(tab) {
    $('#tab-headers')
      .find('.js-tab-header')
      .filter('[data-tab="'+tab+'"]')
      .prop('disabled', false);
  };
  /** Блокировка раздела */
  modView.prototype.disableTab = function(tab) {
    $('#tab-headers')
      .find('.js-tab-header')
      .filter('[data-tab="'+tab+'"]')
      .prop('disabled', true);
  };

  /**
  * Установка текущей активной вкладки
  * @param {String} tab - код вкладки
  */
  modView.prototype.setActiveTab = function(tab) {
    $('#tab-headers')
      .find('.js-tab-header')
        .removeClass('active')
        .filter('[data-tab="'+tab+'"]')
          .addClass('active')
          .prop('disabled', false)
        .end();
    $('.js-content-tab')
      .removeClass('active')
      .filter('[data-tab="'+tab+'"]')
        .addClass('active')
      .end();
    $('#main-scroller').scrollTop(0);
  };

  /** Инициализация табов */
  modView.prototype.initTabs = function() {
    var _instance = this;

    $('#tab-headers').on('click','.js-tab-header',function(e){
      e.preventDefault();
      var tab = $(this).attr('data-tab');
      _instance.setActiveTab(tab);
    });
  };

  /**
  * Отображение элементов управления в шапке заявки
  * @param {Object} config - конфигурация элементов управления 
  */
  modView.prototype.renderHeaderControls = function(config) {
    this.$headerControls.html(Mustache.render(this.mds.tpl.headerControls, config));
    if (config.documents) {
      this.Documents.bindOpener(this.$headerControls.find('.js-ore-show-documents'));
    }
    if (config.history) {
      this.History.bindOpener(this.$headerControls.find('.js-ore-show-history'));
    }
  };

  /**
  * Отображение информации о заявке в шапке
  * @param {Object} orderInfo - информация о заявке
  */
  modView.prototype.renderHeaderInfo = function(OrderStorage) {
    var tourleaderName = '';
    var hasTourleader = false;
    var gatewayOrderIds = false;

    if (this.orderId !== 'new' && KT.profile.userType === 'op') {
      this.$breadcrumb.html(
        'Заявка ' + OrderStorage.orderNumber +
        (
          (OrderStorage.creationDate === null) ? '' :
           ' (от ' + OrderStorage.creationDate.format('DD.MM.YYYY') + ')'
        )
      );

      if (OrderStorage.orderIdGp !== null || OrderStorage.orderIdUtk !== null) {
        gatewayOrderIds = {
          'gp':OrderStorage.orderIdGp,
          'utk':OrderStorage.orderIdUtk
        };
      }
    }

    if (OrderStorage.tourleader === null) {
      tourleaderName = 'Ф.И.О. туриста';
    } else {
      hasTourleader = true;
      tourleaderName = OrderStorage.tourleader.lastname +
        ((OrderStorage.tourleader.firstname === null) ? '' :
          ' ' + OrderStorage.tourleader.firstname);
    }

    var getFullName = function(userInfo) {
      return userInfo.lastname + ' ' +
        ((userInfo.firstname !== null) ?
          (userInfo.firstname.substr(0,1) + '. ') : '') +
        ((userInfo.middlename !== null) ?
          (userInfo.middlename.substr(0,1) + '.') : '');
    };

    var kmpManager = (OrderStorage.kmpManager === null) ? null : {
      'id': OrderStorage.kmpManager.id,
      'allowChat': (KT.profile.subscribedForChat && OrderStorage.kmpManager.id !== KT.profile.user.id),
      'fullName': getFullName(OrderStorage.kmpManager)
    };

    var clientManager = (OrderStorage.clientManager === null) ? null : {
      'id': OrderStorage.clientManager.id,
      'allowChat': (KT.profile.subscribedForChat && OrderStorage.clientManager.id !== KT.profile.user.id),
      'fullName': getFullName(OrderStorage.clientManager)
    };

    var creator = (OrderStorage.creator === null) ? null : {
      'id': OrderStorage.creator.id,
      'allowChat': (KT.profile.subscribedForChat && OrderStorage.creator.id !== KT.profile.user.id),
      'fullName': getFullName(OrderStorage.creator)
    };
    
    var holdingCompany = OrderStorage.client.holdingCompany;

    // для холдингов тоже надо выводить компанию
    var clientCompany = (KT.profile.userType !== 'op' && holdingCompany === null) ? null :
      ('"' + OrderStorage.client.name + '"').replace('""','"');
    
    var localSum = 0;
    var requestedSum = 0;

    OrderStorage.getServices().forEach(function(Service) {
      switch (KT.profile.prices) {
        case 'gross':
          localSum += Service.prices.inLocal.client.gross;
          requestedSum += Service.prices.inView.client.gross;
          break;
        case 'net':
          switch (KT.profile.userType) {
            case 'op':
              localSum += Service.prices.inLocal.supplier.gross;
              requestedSum += Service.prices.inView.supplier.gross;
              break;
            case 'agent':
              localSum += Service.prices.inLocal.client.gross - Service.prices.inLocal.client.commission.amount;
              requestedSum += Service.prices.inView.client.gross - Service.prices.inView.client.commission.amount;
              break;
            default:
              localSum += Service.prices.inLocal.client.gross;
              requestedSum += Service.prices.inView.client.gross;
              break;
          }
          break;
      }
    });

    var order = {
      'vip': OrderStorage.isVip,
      'orderStatusIcon': KT.getCatalogInfo('orderstatuses', OrderStorage.status, 'icon'),
      'orderStatusTitle': KT.getCatalogInfo('orderstatuses', OrderStorage.status, 'title'),
      'orderId': (OrderStorage.orderId !== 'new') ? OrderStorage.orderId : null,
      'orderNumber': OrderStorage.orderNumber,
      'gatewayOrderIds': gatewayOrderIds,
      'allowOrderChat': (OrderStorage.orderId !== 'new' && KT.profile.subscribedForChat),
      'creationDate': (OrderStorage.creationDate === null) ? 'не указана' :
        OrderStorage.creationDate.format('YYYY-MM-DD HH:mm:ss'),
      'creator': creator,
      'kmpManager': kmpManager,
      'clientManager': clientManager,
      'clientCompany': clientCompany,
      'holdingCompany': holdingCompany,
      'touristSet': hasTourleader,
      'touristName': htmlDecode(tourleaderName),
      'touristCount': (OrderStorage.touristsAmount > 1) ? '+ ' + (OrderStorage.touristsAmount - 1) : '',
      'touristTel': hasTourleader ? OrderStorage.tourleader.phone : null,
      'touristEmail': hasTourleader ? OrderStorage.tourleader.email : null,
      'localSum': localSum.toMoney(0,',',' '),
      'requestedSum': requestedSum.toMoney(2,',',' '),
      'requestedCurrency': KT.getCatalogInfo('lcurrency',KT.profile.viewCurrency,'icon')
    };

    if (KT.profile.userType === 'op' && OrderStorage.status === 1) {
      order.orderStatusTitle = 'Ручной режим';
    }

    this.$headerInfo.html( Mustache.render(this.mds.tpl.headerOrderInfo, order) );
  };

  /**
  * Инициализация формы выставления счетов
  * @return {Object} - объект управления формой выставления счетов
  */
  modView.prototype.setSetInvoiceForm = function() {
    var _instance = this;

    var SetInvoiceForm = {
      elem: {
        $content: null,
        $inputs: null,
        $totalInvoiceSum: null
      },
      controls: {},
      wnd: null,
      /** Инициализация модуля */
      init: function() {
        this.wnd = $.featherlight(Mustache.render(KT.tpl.lightbox, {
          'classes': ['ore-set-invoice', 'js-ore-set-invoice']
        }), {
          persist: true,
          closeIcon: '',
          openSpeed: 0,
          closeSpeed: 0
        });
        this.wnd.close();
        this.wnd.openSpeed = 200;
        this.wnd.closeSpeed = 200;
        this.elem.$content = this.wnd.$content;
      },
      /**
      * Отображение формы выставления счетов
      * @param {ServiceStorage[]} services - сервисы заявки (ServiceStorage)
      */
      render: function(services) {
        var invoiceRows = '';
        var currencySign;

        services.forEach(function(service) {
          currencySign = KT.getCatalogInfo('lcurrency',service.paymentCurrency,'icon');
          if (currencySign === 'unknown') {
            currencySign = service.paymentCurrency;
          }

          var serviceData = {
            'serviceId': service.serviceId,
            'statusIcon': KT.getCatalogInfo('servicestatuses', service.status, 'icon'),
            'statusTitle': (KT.profile.userType === 'op' && service.status === 9) ?
              'Ручной режим' :
              KT.getCatalogInfo('servicestatuses', service.status, 'title'),
            'serviceName': service.name,
            'price': service.prices.inClient.client.gross.toMoney(2,',',' '),
            'disabled': (!service.isActionAvailable.setInvoice || !service.checkTransition('PayStart')),
            'leftToPay': (!service.isActionAvailable.setInvoice) ? 0 :
              Number(service.unpaidSum).toMoney(2,',',' '),
            'currencyIcon': currencySign
          };

          invoiceRows += Mustache.render(_instance.mds.tpl.invoicesRow, serviceData);
        });

        this.elem.$content.html(Mustache.render(_instance.mds.tpl.invoices, {
          'services': invoiceRows,
          'currencyCode': currencySign
        }));
        this.elem.$inputs = this.elem.$content.find('.js-ore-set-invoice--service-sum');
        this.elem.$totalInvoiceSum = this.elem.$content.find('.js-ore-set-invoice--total-invoice');

        this.controls.selectPayment = $('#SetInv-SelectPayment').selectize({
            openOnFocus: true,
            create: false,
            createOnBlur:true
        });
      },
      /** Открывает форму выставления счетов */
      open: function() {
        this.wnd.open();
      },
      /** Закрывает форму выставления счетов */
      close: function() {
        this.wnd.close();
      },
      /**
      * Привязка элемента для открытия формы выставления счетов
      * @param {Object} $elem - кнопка для открытия формы 
      */
      bindOpener: function($elem) {
        var self = this;
        $elem.on('click', function() {
          if ($(this).not(':disabled')) {
            self.wnd.open();
          }
        });
      },
      /** 
      * Проверка значения поля ввода суммы счета
      * @param {Object} $serviceRow - объект услуги
      * @param {Number} maxInvoiceSum - максимальная сумма счета для услуги
      */
      checkInvoiceInput: function($serviceRow, maxInvoiceSum) {
        var $input = $serviceRow.find('.js-ore-set-invoice--service-sum');
        var $checkbox = $serviceRow.find('.js-ore-set-invoice--service-check');
        $input.prop('disabled', true);
        $checkbox.on('change.onhold', function(e) { e.stopPropagation(); });

        var inputValue = parseFloat($input.val().replace(/\s+/g,'').replace(',','.'));
        if (isNaN(inputValue)) {
          /** @todo fire error */
          console.log('NaN entered');
          $input.val('');
          $checkbox.prop('checked', false);
        } else {
          if (inputValue < 0) { inputValue = 0; }
          if (inputValue > maxInvoiceSum) {
            inputValue = maxInvoiceSum;
          }

          if (inputValue !== 0) {
            $input.val(inputValue.toMoney(2,',',' '));
            $checkbox.prop('checked', true);
          } else {
            $input.val('');
            $checkbox.prop('checked', false);
          }
        }

        $checkbox.off('change.onhold');

        var totalInvoiceSum = 0;
        this.elem.$inputs.each(function() {
          var currentValue = parseFloat($(this).val().replace(/\s+/g,'').replace(',','.'));
          totalInvoiceSum += isNaN(currentValue) ? 0 : currentValue;
        });

        this.elem.$totalInvoiceSum.text(totalInvoiceSum.toMoney(2,',',' '));
        $input.prop('disabled', false);
      },
      /** 
      * Отметить услугу для выставления счета 
      * @param {Object} $serviceRow - объект услуги
      * @param {Number} defaultInvoiceSum - сумма счета по умолчанию
      */
      markServiceForInvoice: function($serviceRow, defaultInvoiceSum) {
        var $input = $serviceRow.find('.js-ore-set-invoice--service-sum');
        $input.prop('disabled', true);

        if (defaultInvoiceSum !== 0) {
          $input.val(defaultInvoiceSum.toMoney(2,',',' '));
        } else {
          $input.val('');
        }

        var totalInvoiceSum = 0;
        this.elem.$inputs.each(function() {
          var currentValue = parseFloat($(this).val().replace(/\s+/g,'').replace(',','.'));
          totalInvoiceSum += isNaN(currentValue) ? 0 : currentValue;
        });

        this.elem.$totalInvoiceSum.text(totalInvoiceSum.toMoney(2,',',' '));
        $input.prop('disabled', false);
      },
      /** 
      * Сбор данных из формы выставления счета 
      * @return {Object|false} данные для выставления счета (false в случае ошибок)
      */
      getInvoiceFormData: function() {
        var invServices = [];
        var hasZeroSum = false;

        this.elem.$content
          .find('.js-ore-set-invoice--service-check:checked')
            .each(function() {
              var $row = $(this).closest('.js-ore-set-invoice--service');
              var sum = Number($row.find('.js-ore-set-invoice--service-sum').val().replace(/\s+/g,'').replace(',','.'));

              if (sum === 0) {
                hasZeroSum = true;
              } else if (sum > 0) {
                sum = sum.toFixed(2);
                invServices.push({
                  'id': +$row.attr('data-serviceid'),
                  'sum': sum
                });
              }
        });

        return (invServices.length === 0 && hasZeroSum) ? false : invServices;
      },
      /** Очистка формы выставления счета */
      clear: function() {
        this.elem.$content.html(Mustache.render(KT.tpl.spinner, {}));
      }
    };

    return SetInvoiceForm;
  };

  /**
  * Инициализация формы управления документами заявки
  * @return {Object} - объект управления формой выставления счетов
  */
  modView.prototype.setDocuments = function() {
    var _instance = this;

    var Documents = {
      elem: {
        $container: null,
        $uploadForm: null,
        $uploadLabel: null,
        $progressLabel: null,
        $progressBar: null,
        $sendDocumentsForm: null
      },
      wnd: null,
      /** Инициализация модуля */
      init: function() {
        this.wnd = $.featherlight(Mustache.render(KT.tpl.lightbox, {
          'classes': ['ore-documents', 'js-ore-documents']
        }), {
          persist: true,
          closeIcon: '',
          openSpeed: 0,
          closeSpeed: 0
        });
        this.wnd.close();
        this.wnd.openSpeed = 200;
        this.wnd.closeSpeed = 200;
        this.elem.$content = this.wnd.$content;
      },
      /**
      * Отображение формы управления документами заявки
      * @param {Object[]} documents - массив документов заявки
      */
      render: function(documents) {
        var documentsList = '';

        if (Array.isArray(documents) && documents.length > 0) {
          documents.forEach(function(document) {
            if (document.fileName === '') { document.fileName = 'Документ'; }
            documentsList += Mustache.render(_instance.mds.tpl.documentsRow, document);
          });
        } else {
          documentsList = Mustache.render(_instance.mds.tpl.documentsEmpty, {});
        }

        this.elem.$content.html(Mustache.render(_instance.mds.tpl.documents, {
          'documents': documentsList
        }));
        this.elem.$uploadForm = this.elem.$content.find('.js-ore-documents--upload');
        this.elem.$uploadLabel = this.elem.$content.find('.js-ore-documents--upload-label');
        this.elem.$progressLabel = this.elem.$content.find('.js-ore-documents--upload-progress-text');
        this.elem.$progressBar = this.elem.$content.find('.js-ore-documents--upload-progress-bar');
        this.elem.$sendDocumentsForm = this.elem.$content.find('.js-ore-documents--send-form');

        if (_instance.mds.accessList.sendDocument) {
          this.renderSendForm();
        }
      },
      /**
      * Привязка элемента для открытия формы
      * @param {Object} $elem - кнопка для открытия формы 
      */
      bindOpener: function($elem) {
        var self = this;
        $elem.on('click', function() {
          if ($(this).not(':disabled')) {
            self.wnd.open();
          }
        });
      },
      /**
      * Отображение прогресса загрузки документов
      * @param {Integer} percent - процент загрузки
      */
      showUploadProgress: function(percent) {
        if (+percent !== 100) {
          this.elem.$progressLabel.text(percent + ' %');
          this.elem.$progressBar.css({'width':percent + '%'});
        } else {
          this.elem.$progressLabel.text('обработка документа...');
          this.elem.$progressBar.removeClass('active').css({'width':'100%'});
        }
      },
      /** Обработка отмены загрузки документа */
      cancelDocumentUpload: function() {
        this.elem.$uploadForm[0].reset();
        this.elem.$uploadForm
          .find('.js-ore-documents--upload-field').change()
          .end()
          .find('.js-ore-documents--upload-control').show()
          .end()
          .find('.js-ore-documents--upload-progress').hide();
        this.elem.$progressLabel.text('0 %');
        this.elem.$progressBar.css({'width':'0%'}).removeClass('active');
      },
      /** Очистка блока документов */
      clear: function() {
        this.elem.$content.html(Mustache.render(KT.tpl.spinner, {}));
      },
      /** 
      * Рендер формы отправки документов по почте 
      * @param {String} [email] - если указан, будет подставлен данный адрес
      */
      renderSendForm: function(email) {
        this.elem.$sendDocumentsForm.html(Mustache.render(_instance.mds.tpl.sendViaEmailForm, {
          'label': 'Отправить выбранные документы по почте',
          'email': email
        }));
      },
      /**
      * Формирование данных для отправки документов по почте
      * @return {Array|null} - массив данных для отправки или null в случае ошибки
      */
      getSendingDocumentsData: function() {
        var $emailInput = this.elem.$sendDocumentsForm.find('.js-send-via-email--email');
        var email = $emailInput.val();

        if (!/.+@.+/.test(email)) {
          $emailInput
            .addClass('error')
            .one('focus', function() { $(this).removeClass('error'); });
          return null;
        }

        var selectedDocuments = this.elem.$content.find('.js-ore-documents--document-select')
          .filter(':checked')
          .map(function() {
            var $docSelector = $(this);
            return {
              'id': +$docSelector.data('documentid'),
              'name': $docSelector.data('name')
            };
          })
          .get();

        if (selectedDocuments.length === 0) {
          KT.notify('noDocumentsSelected');
          return null;
        } else {
          return {
            'email': email,
            'documents': selectedDocuments  
          };
        }
      },
      /** Процесс отправки документов */
      showDocumentsSending: function() {
        this.elem.$sendDocumentsForm.html(Mustache.render(KT.tpl.spinner, {type: 'button'}));
      }
    };

    return Documents;
  };

  /**
  * Инициализация формы вывода истории
  * @return {Object} - объект управления формой вывода истории заявки
  */
  modView.prototype.setHistory = function() {
    var _instance = this;

    var History = {
      elem: {
        $content: null,
        $sendHistoryForm: null
      },
      wnd: null,
      /** Инициализация модуля */
      init: function() {
        this.wnd = $.featherlight(Mustache.render(KT.tpl.lightbox, {
          'classes': ['ore-history', 'js-ore-history']
        }), {
          persist: true,
          closeIcon: '',
          openSpeed: 0,
          closeSpeed: 0
        });
        this.wnd.close();
        this.wnd.openSpeed = 200;
        this.wnd.closeSpeed = 200;
        this.elem.$content = this.wnd.$content;
      },
      /**
      * Рендер формы вывода истории
      * @param {Array} history - история заявки 
      */
      render: function(history) {
        if (Array.isArray(history)) {
          var self = this;

          var historyRows = history.map(function(record) {
            record.eventTime = moment(record.eventTime,'YYYY-MM-DD HH:mm:ss').format('D MMMM YYYY, HH:mm');
            record.serviceStatusTitle = KT.getCatalogInfo('servicestatuses', record.serviceStatus,'title');
            record.serviceStatusIcon = KT.getCatalogInfo('servicestatuses', record.serviceStatus,'icon');
            record.orderStatusTitle = KT.getCatalogInfo('orderstatuses', record.orderStatus,'title');
            record.orderStatusIcon = KT.getCatalogInfo('orderstatuses', record.orderStatus,'icon');
            record.success = (+record.result === 0) ? true : false;

            return Mustache.render(_instance.mds.tpl.historyRow, record);
          });

          this.elem.$content.html(Mustache.render(_instance.mds.tpl.history, {
            'history': historyRows.join('')
          }));
          
          this.elem.$content.on('click', 'button[data-action="cancel"]', function() {
            self.wnd.close();
          });

          this.elem.$sendHistoryForm = this.elem.$content.find('.js-ore-history--send-form');

          if (_instance.mds.accessList.sendReport) {
            this.renderSendForm();
          }
        }
      },
      /**
      * Привязка элемента для открытия формы вывода истории
      * @param {Object} $elem - кнопка для открытия формы 
      */
      bindOpener: function($elem) {
        var self = this;
        $elem.on('click', function() {
          if ($(this).not(':disabled')) {
            self.wnd.open();
          }
        });
      },
      /** 
      * Выводит форму отправки истории по почте
      * @param {String} [email] - если указан, будет поставлен данный адрес
      */
      renderSendForm: function(email) {
        this.elem.$sendHistoryForm.html(Mustache.render(_instance.mds.tpl.sendViaEmailForm, {
          'label': 'Отправить историю по почте',
          'email': email
        }));
      },
      /**
      * Формирование данных для команды отправки истории на почту
      * @return {Object|null} - данные для отправки или null в случае ошибки
      */
      getHistoryReportData: function() {
        var OrderStorage = _instance.mds.OrderStorage;

        var $emailInput = this.elem.$sendHistoryForm.find('.js-send-via-email--email');
        var email = $emailInput.val();

        if (!/.+@.+/.test(email)) {
          $emailInput
            .addClass('error')
            .one('focus', function() { $(this).removeClass('error'); });
          return null;
        }

        var history = OrderStorage.getHistory();
        if (!Array.isArray(history)) {
          return null;
        }

        var historyRows = history.map(function(record) {
          var eventTime = moment(record.eventTime,'YYYY-MM-DD HH:mm:ss').format('D MMMM YYYY, HH:mm');
          var eventName = record.event;
          var eventStatus = (+record.result === 0) ? 'завершено' : 'ошибка';
          var eventComment = record.eventComment;
          var orderStatus = KT.getCatalogInfo('orderstatuses', record.orderStatus,'title');
          var manager = record.userName;
          var serviceInfo = (record.serviceName === null) ? '-' :
            record.serviceName + ', статус: ' + KT.getCatalogInfo('servicestatuses', record.serviceStatus,'title');

          return [
            eventTime,
            eventName,
            eventStatus,
            eventComment,
            orderStatus,
            manager,
            serviceInfo
          ];
        });

        var historyData = {
          'mainHeader': 'История заявки ' + OrderStorage.orderNumber + ' (' + moment().format('DD-MM-YYYY HH:mm') + ')',
          'headerTexts': [],
          'footerTexts': [],
          'table': {
            'headers': [
              ['Дата и время', 'Событие', 'Статус', 'Комментарий', 'Статус заявки', 'Менеджер', 'Услуга']
            ],
            'rowgroups': [
              {
                'groupheader': [],
                'rows': historyRows,
                'groupfooter': []
              }
            ],
            'footers': []
          }
        };

        return {
          'eventId': 209,
          'companyId': KT.profile.companyId,
          'email': email,
          'reportData': historyData
        };
      },
      /** Процесс формирования отчета */
      showHistoryReportSending: function() {
        this.elem.$sendHistoryForm.html(Mustache.render(KT.tpl.spinner, {type: 'button'}));
      }
    };

    return History;
  };

  /** Очистка шапки */
  modView.prototype.clearTopInfo = function() {
    /** @todo поменять на общий spinner */
    this.$headerInfo.html(Mustache.render(this.mds.tpl.headerSpinner));
  };

  return modView;
}));
