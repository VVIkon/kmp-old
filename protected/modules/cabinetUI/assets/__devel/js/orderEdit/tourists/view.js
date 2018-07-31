/* global transliterate */
(function(global,factory) {

    KT.crates.OrderEdit.tourists.view = factory(KT.crates.OrderEdit);

}(this, function(crate) {
  /**
  * Редактирование заявки: туристы
  * @constructor
  * @param {Object} module - хранилище родительского модуля
  * @param {Object} [options] - Объект конфигурации класса
  */
  var modView = function(module,options) {
    this.mds = module;
    if (options === undefined) { options = {}; }
    this.config = $.extend(true,{
      'templateUrl':'/cabinetUI/orders/getTemplates',
      'templates':{
        touristForm:'orderEdit/tourists/touristForm',
        touristFormActions:'orderEdit/tourists/touristFormActions',
        touristFormBonusCard: 'orderEdit/tourists/touristFormBonusCard',
        touristFormNoBonusCards: 'orderEdit/tourists/touristFormNoBonusCards',
        touristFormBonusCardActions: 'orderEdit/tourists/touristFormBonusCardActions',
        touristListControls:'orderEdit/tourists/touristListControls',
        touristListEmpty:'orderEdit/tourists/touristListEmpty',
        // custom fields
        TextCF: 'orderEdit/customFields/textCF',
        TextAreaCF: 'orderEdit/customFields/textAreaCF',
        NumberCF: 'orderEdit/customFields/numberCF',
        DateCF: 'orderEdit/customFields/dateCF'
      }
    },options);

    this.$tabLabel = $('#tab-headers').find('.tab-headers__link[data-tab="tourists"]');

    this.$touristList = $('#order-edit-tourists');
    this.$touristListControls = $('#order-edit-tourists--controls');

    // ссылки на формы туристов по ID туриста
    this.touristForms = {};

    // флаг наличия турлидера в заявке
    this.tlFlag = false; 

    // дополнительные поля туристов
    this.customFields = {};

    // список объектов документов (тип, валидация, ...)
    this.doctypes = [];
    for (var doctype in KT.config.touristDocuments) {
      if (KT.config.touristDocuments.hasOwnProperty(doctype)) {
        this.doctypes.push({
          value: doctype,
          text: KT.config.touristDocuments[doctype].docname
        });
      }
    }

    // список стран для выбора в поле "Гражданство"
    this.countries = [];
    for(var country in KT.countryCodes) {
      if (KT.countryCodes.hasOwnProperty(country)) {
        this.countries.push({
          value:country,
          text:KT.countryCodes[country]
        });
      }
    }
  };

  /** Отображение списка туристов
  * @param {OrderStorage} OrderStorage - данные заявки
  */
  modView.prototype.renderTouristList = function(OrderStorage) {
    var _instance = this;

    _instance.$touristList.empty();
    _instance.touristForms = {};

    var actions = {
      'allowAdd': OrderStorage.isAddTouristAllowed
    };

    _instance.$touristListControls.html(
      Mustache.render(_instance.mds.tpl.touristListControls, actions)
    );

    var Tourists = OrderStorage.getTourists();

    if (Tourists.length === 0) {
      _instance.$tabLabel.addClass('empty');
      _instance.$touristList.html(Mustache.render(_instance.mds.tpl.touristListEmpty, {}));
      return;
    } else {
      _instance.$tabLabel.removeClass('empty');
    }

    // турлидер(-ы) должен быть в начале списка
    Tourists.sort(function(a,b) {
      if (a.isTourleader) {
        return (b.isTourleader) ? 0 : -1;
      } else {
        return (b.isTourleader) ? 1 : 0;
      }
    });

    var isTourleaderSet = (OrderStorage.tourleader === null) ? false : true;
    var isOffline = (OrderStorage.isOffline !== true) ? false : true;
    var orderClientType = OrderStorage.companyRoleType;

    Tourists.forEach(function(Tourist) {
      var touristInfo = _instance.mapTouristInfo(Tourist, isTourleaderSet, OrderStorage.isOffline);

      touristInfo.actions = Mustache.render(_instance.mds.tpl.touristFormActions, isOffline ? {} : {
        'allowedSave': true,
        'allowedUserCreation': (orderClientType === 'corp'),
        'allowedReset': true,
        'allowedDelete': true,
      });

      _instance.touristForms[Tourist.touristId] = $(Mustache.render(_instance.mds.tpl.touristForm, touristInfo))
        .appendTo(_instance.$touristList);

      _instance.initControls(Tourist.touristId);
      _instance.initCustomFields(Tourist);
    });

  };

  /**
  * Добавление новой формы туриста
  * @param {OrderStorage} OrderStorage - хранилище данных заявки
  */
  modView.prototype.addTouristForm = function(OrderStorage) {
    var _instance = this;
    var isTourleaderSet = (OrderStorage.tourleader === null) ? false : true;
    var orderClientType = OrderStorage.companyRoleType;
    var isNewOrder = (OrderStorage.orderId === 'new');
    
    var tempTouristId = 'tmp' + moment().valueOf();

    if (this.$touristList.children('.js-ore-tourist').length === 0) {
      this.$touristList.empty();
    }

    var formActions = Mustache.render(this.mds.tpl.touristFormActions, {
        'allowedSave': true,
        'allowedUserCreation': (orderClientType === 'corp'),
        'allowedReset': !isNewOrder,
        'allowedDelete': !isNewOrder
    });

    this.touristForms[tempTouristId] = $(Mustache.render(this.mds.tpl.touristForm, {
        'touristId': tempTouristId,
        'isNewTourist': true,
        'isEditable': true,
        'allowedTouristSelect': (orderClientType === 'corp'),
        'isTourleaderSet':isTourleaderSet,
        'bonusCards': Mustache.render(_instance.mds.tpl.touristFormNoBonusCards, {}),
        'bonusCardsActions': Mustache.render(_instance.mds.tpl.touristFormBonusCardActions, {
            'add': true
          }),
        'actions': formActions
      }))
      .addClass('active')
      .appendTo(this.$touristList);

    if (orderClientType === 'corp') {
      this.touristForms[tempTouristId].find('.js-ore-tourist--suggest')
        .selectize({
          plugins: {'key_down': { start: 2 }},
          openOnFocus: true,
          create: false,
          selectOnTab: true,
          highlight: false,
          loadThrottle: 300,
          valueField: 'docId',
          labelField: 'name',
          sortField:'seqid',
          options:[],
          render: {
            item: function(user) {
              return '<div class="ore-tourist-suggest__item">' +
                user.lastName + ' ' + user.firstName + 
                ' (' + user.docSerial + ' ' + user.docNumber + ')' +
                '</div>';
            },
            option: function(user) {
              return '<div class="ore-tourist-suggest__option">' +
                user.lastName + ' ' + user.firstName + 
                ' (' + user.docSerial + ' ' + user.docNumber + ')' +
                '</div>';
            }
          },
          score:function() {
            return function(item) {
              return 1000 / (item.seqid);
            };
          },
          load: function(query, callback) {
            var self = this;

            this.clearOptions();

            if (!query.length || query.length < 2) {
              return callback();
            }

            KT.apiClient.getClientUserSuggest({
              data: {
                'companyId': _instance.mds.OrderStorage.client.id,
                'substringFIO': query
              },
              error: function() {
                callback();
              },
              success: function(response) {
                var $selinp = self.$control;

                if (+response.status === 0 && Array.isArray(response.body)) {
                  response.body.forEach(function(item, i) {
                    item.seqid = i + 1;
                  });
                  callback(response.body);

                  if (response.body.length === 0) {
                    $selinp.addClass('warning');
                    setTimeout(function() {
                      $selinp.removeClass('warning');
                    }, 2000);
                  }
                } else {
                  callback();
                  self.refreshOptions(true);
                  $selinp.addClass('warning');
                  setTimeout(function() {
                    $selinp.removeClass('warning');
                  }, 2000);
                }
              }
            });
          },
          onType:function(str) {
            if (str.length < 2) {
              this.close();
              this.clearOptions();
            }
          },
          onItemRemove: function() {
            this.clearOptions();
          },
          onChange: function(val) {
            if (val === '') {
              this.trigger('item_remove');
            }
          }
        });
    }

    var yh = this.touristForms[tempTouristId].offset().top;

    /** @todo this is for baron scroller, redefine in KT core? */
    $('#main-scroller').stop().animate({
        scrollTop: $('#main-scroller').scrollTop() + (yh-20)
      }, 300, 'swing');

    this.initControls(tempTouristId);
  };

  /**
  * Отображение лоадера в процессе обработки туриста
  * @todo перенести в центральный класс вьюх?
  * @param {Object} $touristForm - форма редактирования туриста
  */
  modView.prototype.renderPendingProcess = function($touristForm) {
    $touristForm.find('.js-ore-tourist--actions')
      .html(Mustache.render(KT.tpl.spinner, {'type':'medium'}));
  };

  /**
  * Обновление информации по определенному туристу
  * @param {TouristStorage} Tourist - турист
  * @param {Boolean} isOffline - признак оффлайновости заявки
  */
  modView.prototype.refreshTouristForm = function(Tourist, isOffline) {
    if (isOffline !== true) { isOffline = false; }
    var isNewOrder = (this.mds.OrderStorage.orderId === 'new');
    var orderClientType = this.mds.OrderStorage.companyRoleType;

    this.$tabLabel.removeClass('empty');
    var $target = this.touristForms[Tourist.touristId];
    
    var touristInfo = this.mapTouristInfo(Tourist, false, isOffline);
    touristInfo.actions = Mustache.render(this.mds.tpl.touristFormActions, isOffline ? {} : {
      'allowedSave': true,
      'allowedUserCreation': (orderClientType === 'corp'),
      'allowedReset': true,
      'allowedDelete': !isNewOrder,
    });

    var $newForm = $(Mustache.render(this.mds.tpl.touristForm, touristInfo));
    $target.off().replaceWith($newForm);
    $newForm.addClass('active');

    this.touristForms[Tourist.touristId] = $newForm;

    this.initControls(Tourist.touristId);
    this.initCustomFields(Tourist);
  };

  /**
  * Обновление действий с формой туриста
  * @param {Obejct} $tourist - форма турисиа
  * @param {Boolean} isOffline - признак оффлайновости заявки
  */
  modView.prototype.refreshTouristFormActions = function($tourist, isOffline) {
    var isNewOrder = (this.mds.OrderStorage.orderId === 'new');
    var orderClientType = this.mds.OrderStorage.companyRoleType;

    $tourist.find('.js-ore-tourist--actions')
      .html(Mustache.render(this.mds.tpl.touristFormActions, isOffline ? {} : {
        'allowedSave': true,
        'allowedUserCreation': (orderClientType === 'corp'),
        'allowedReset': true,
        'allowedDelete': !isNewOrder,
      }));
  };

  /**
  * Удаление формы туриста
  * @param {Integer|String} touristId - ID туриста (или временный ID для новой формы)
  */
  modView.prototype.removeTouristForm = function(touristId) {
    var $tourists = this.$touristList.find('.js-ore-tourist');
    var $touristForm = this.touristForms[touristId];
    var touristsAmount = $tourists.length;
    var adjustScroll = $('#main-scroller').scrollTop();
    var formOffset = $touristForm.offset().top;

    if (formOffset < 15) {
      adjustScroll -= (15 - formOffset);
      if (adjustScroll < 0) { adjustScroll = 0; }
      $('#main-scroller').animate({'scrollTop': adjustScroll}, 400);
    }
    $touristForm.slideUp(400, function() { $(this).remove(); });

    if (touristsAmount === 1) {
      this.$tabLabel.addClass('empty');
    }
  };

  /**
  * Инициализация элементов управления (календари, селекты, ...)
  * @param {String|Integer} touristId - идентификатор туриста
  */
  modView.prototype.initControls = function(touristId) {
    var _instance = this;
    var $touristForm = _instance.touristForms[touristId];

    $touristForm.find('.simpletoggler input').trigger('change');
    
    $touristForm.find('input[name="birthdate"]')
      .each(function() {
          $(this).clndrize({
            'template':KT.tpl.clndrDatepicker,
            'eventName':'Дата рождения',
            'showDate':moment().subtract(20,'years'),
            'clndr': {
              'constraints': {
                'startDate':'1915-01-01',
                'endDate':moment().format('YYYY-MM-DD')
              }
            }
          });
      });

    $touristForm.find('select[name="citizenship"]')
      .selectize({
        openOnFocus: true,
        create: false,
        options: _instance.countries,
        selectOnTab:true,
        sortField:'text',
        searchField: 'text',
        render: {
          item: function(item) {
            return '<div data-value="'+item.value+'" class="item" title="'+item.text+'">'+item.text+'</div>';
          },
          option: function(item) {
            return '<div data-value="'+item.value+'" data-selectable class="option">'+item.text+'</div>';
          }
        }
      });
    
    $touristForm.find('input[name="docenddate"]')
      .each(function() {
        $(this).clndrize({
          'template':KT.tpl.clndrDatepicker,
          'eventName':'Дата окончания',
          'clndr': {
            'constraints': {
              'startDate':moment().add(2,'days').format('YYYY-MM-DD'),
              'endDate':moment().add(10,'years').format('YYYY-MM-DD')
            }
          }
        });
      });

    $touristForm.find('select[name="doctype"]')
      .selectize({
        openOnFocus: true,
        create: false,
        selectOnTab:true,
        options: _instance.doctypes,
        searchField: 'text',
        onItemAdd: function(v) {
          v = +v;
          if (v === 1 || v === 2) {
            var $cz = this.$input.closest('.js-ore-tourist').find('select[name="citizenship"]');
            if ($cz.val() === '') {
              this.$input.closest('.js-ore-tourist').find('select[name="citizenship"]')[0].selectize.addItem('RU');
            }
          }
        }
      });

    $touristForm.find('input[name="country-prefix"],input[name="city-prefix"],input[name="phone"]')
      .on('keypress',function(e) {
          var char = String.fromCharCode(e.which);
          var check = /[0-9]/;
          if (!check.test(char)) {
            return false;
          }
        });

    KT.Dictionary.getAsList('loyalityPrograms')
      .then(function(loyalityPrograms) {
        $touristForm.find('.js-ore-tourist--bonus-card-program')
          .each(function() {
            var currentValue = $(this).val();

            initLoyalityProgramSelect($(this), {
              options: loyalityPrograms
            });

            if (currentValue !== '') {
              $(this)[0].selectize.addItem(+currentValue);
            }
          });
      });

    _instance.livecheckDocument($touristForm);
  };

  /** 
  * Добавляет форму ввода мильной карты к форме редактирования туриста 
  * @param {Object} $touristForm - форма туриста
  */
  modView.prototype.addBonusCardForm = function($touristForm) {
    var _instance = this;
    var $cardsList = $touristForm.find('.js-ore-tourist--bonus-cards');

    KT.Dictionary.getAsList('loyalityPrograms')
      .then(function(loyalityPrograms) {
        if ($cardsList.children('.js-ore-tourist--bonus-card').length === 0) {
          $cardsList.empty();
        }

        var $loyalityProgramSelect = $(Mustache.render(_instance.mds.tpl.touristFormBonusCard, {}))
          .appendTo($cardsList)
          .find('.js-ore-tourist--bonus-card-program');

        initLoyalityProgramSelect($loyalityProgramSelect, {
          options: loyalityPrograms
        });
      });
  };

  /** 
  * Удаление формы ввода мильной карты
  * @param {Object} $bonusCard - форма мильной карты
  */
  modView.prototype.removeBonusCardForm = function($bonusCard) {
    var $cardsList = $bonusCard.closest('.js-ore-tourist--bonus-cards');
    $bonusCard.remove();

    if ($cardsList.children('.js-ore-tourist--bonus-card').length === 0) {
      $cardsList.html(Mustache.render(this.mds.tpl.touristFormNoBonusCards, {}));
    }
  };

  /**
  * Инициализация дополнительных полей туриста 
  * @param {TouristStorage} Tourist - данные туриста
  */
  modView.prototype.initCustomFields = function(Tourist) {
    if (Tourist.customFields === null || Tourist.customFields.length === 0) {
      return;
    }

    var CustomFieldsFactory = new crate.CustomFieldsFactory(this.mds.tpl);
    var touristId = Tourist.touristId;

    // хак - поля типа textArea отсортировать в конец для нормального отображения
    Tourist.customFields.sort(function(a,b) {
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

    var self = this;
    self.customFields[touristId] = [];

    Tourist.customFields.forEach(function(fieldData) {
      var customField = CustomFieldsFactory.create(fieldData);
      if (customField !== null) {
        self.customFields[touristId].push(customField);
      }
    });

    var $customFields = $();

    self.customFields[touristId].forEach(function(customField) {
      $customFields = $customFields.add(customField.render());
    });

    this.touristForms[touristId].find('.js-ore-tourist-custom-fields').html($customFields);

    self.customFields[touristId].forEach(function(customField) {
      customField.initialize();
    });
  };

  /**
  * Блокировка формы туриста в процессе получения данных пользователя
  * @param {Object} $touristForm - объект формы туриста
  * @param {Function} callback - хак для красивости интерфейса
  * @todo некрасиво, подумать над вариантами
  */
  modView.prototype.lockClientSelect = function($touristForm, callback) {
    var $dataForm = $touristForm.find('.js-ore-tourist--form-wrapper');
    $touristForm.addClass('is-locked').removeClass('active');
    
    var $clientSuggest = $touristForm.find('.js-ore-tourist--suggest');
    if ($clientSuggest.length !== 0) { 
      $clientSuggest[0].selectize.disable();
    }

    $dataForm.css({'display':'block'}).slideUp(500, function() {
      $touristForm.find('.js-ore-tourist--header-lock').css({'display': 'block'});
      if (typeof callback === 'function') { callback(); }
    });
  };

  /**
  * Разблокировка формы туриста в процессе получения данных пользователя
  * @param {Object} $touristForm - объект формы туриста
  */
  modView.prototype.unlockClientSelect = function($touristForm) {
    var $dataForm = $touristForm.find('.js-ore-tourist--form-wrapper');
    $touristForm.removeClass('is-locked').addClass('active');

    var $clientSuggest = $touristForm.find('.js-ore-tourist--suggest');
    if ($clientSuggest.length !== 0) { 
      $clientSuggest[0].selectize.enable();
    }

    $dataForm.css({'display':'none'}).slideDown(500);
  };

  /**
  * Сбор и валидация данных по туристу из формы
  * @param {Object} $form - форма туриста
  * @return {Object|false} - данные туриста или false в случае ошибки
  * @todo fix hack with dynamic serialnum
  */
  modView.prototype.getTouristFormData = function($form) {
    var $phoneblock = $form.find('.js-ore-tourist--phone-input');
    var $c = {
      'sex': $form.find('input[name="sex"]'),
      'firstName': $form.find('input[name="firstname"]').livetrim(),
      'lastName': $form.find('input[name="lastname"]').livetrim(),
      'middleName': $form.find('input[name="middlename"]').livetrim(),
      'birthdate': $form.find('input[name="birthdate"]'),
      'email': $form.find('input[name="email"]').livetrim(),
      'phone': {
        'countryPrefix': $phoneblock.find('input[name="country-prefix"]'),
        'cityPrefix': $phoneblock.find('input[name="city-prefix"]'),
        'number': $phoneblock.find('input[name="phone"]')
      },
      'document': {
        'type': $form.find('select[name="doctype"]'),
        'firstName': $form.find('input[name="docfirstname"]').livetrim().livecapitalize(),
        'lastName': $form.find('input[name="doclastname"]').livetrim().livecapitalize(),
        'middleName': $form.find('input[name="docmiddlename"]').livetrim().livecapitalize(),
        'serial': $form.find('input[name="docseries"]').livetrim(),
        'number': $form.find('input[name="docnumber"]').livetrim(),
        'citizenship': $form.find('select[name="citizenship"]'),
        'expiryDate': $form.find('input[name="docenddate"]')
      },
      'bonusCards': $form.find('.js-ore-tourist--bonus-card')
    };

    // валидация email
    function vemail(v) {
      if (v !== '' && (/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z0-9.-]+$/ig).test(v)) { return true; } 
      else { return false; }
    }
    // валидация имени/фамилии туриста
    function vname(v) {
      if (v !== '' && (/^[^0-9\.,\s\t\nъЪ]*$/g).test(v) && (/^([A-z-]+|[А-яёЁ-]+)$/g).test(v)) { return true; } 
      else { return false; }
    }
    // проверка на число
    function digit(v) {
      if (v !== '' && (/^[0-9]+$/g).test(v)) { return true; } 
      else { return false; }
    }

    var errorflag = {val: false};
    var touristId = $form.data('touristid');
    if (
      String($form.data('touristid')).indexOf('tmp') === -1 &&
      String($form.data('touristid')).indexOf('doc') === -1
    ) { touristId = +touristId; }

    var userId = $form.data('userid');

    var formdata = {
      'userId': (userId !== undefined && userId !== '') ? +userId : null,
      'contractId': this.mds.OrderStorage.contractId,
      'touristId': touristId,
      'isTourLeader': ($form.data('leader') === 'true') ? true : false,
      'sex': $c.sex.prop('checked') ? 1 : 0,
      'firstName': validateControl($c.firstName, vname, errorflag, 'Введите имя'),
      'lastName': validateControl($c.lastName, vname, errorflag, 'Введите фамилию'),
      'middleName': $c.middleName.val(),
      'birthdate': validateDate($c.birthdate, errorflag, 'YYYY-MM-DD'),
      'email': validateControl($c.email, vemail, errorflag, 'Введите email'),
      'phone': ($c.phone.number.val() === '') ? null :
          (
            '+' +
            validateControl($c.phone.countryPrefix, digit, errorflag) + '(' +
            validateControl($c.phone.cityPrefix, digit, errorflag) + ')' +
            validateControl($c.phone.number, digit, errorflag)
          ),
      'bonusCards': []
    };

    var docType = parseInt($c.document.type.val());
    var documentConfig = KT.config.touristDocuments[docType];

    if (documentConfig !== undefined) {
      var docTouristNameTest = documentConfig.getTouristNameFullValidation();
      var docSerialTest = documentConfig.getDocumentSerialFullValidation();
      var docNumberTest = documentConfig.getDocumentNumberFullValidation();
      var documentId = $form.find('.js-ore-tourist--document').data('documentid');

      formdata.document = {
        'user_documentID': (documentId !== undefined && documentId !== '') ? +documentId : null,
        'documentType': docType,
        'firstName': validateControl($c.document.firstName, function (v) {
              if (v !== '' && docTouristNameTest.test(v)) { return true; } 
              else { return false; }
            }, errorflag, 'Введите имя'),
        'lastName': validateControl($c.document.lastName, function (v) {
              if (v !== '' && docTouristNameTest.test(v)) { return true; } 
              else { return false; }
            }, errorflag, 'Введите фамилию'),
        'middleName': $c.document.middleName.val(),
        'serialNumber': ($c.document.serial.val() === '') ? null :
            String(validateControl($c.document.serial, function (v) {
              if (docSerialTest.test(v)) { return true; } else { return false; }
            },errorflag)).toUpperCase(),
        'number': ($c.document.number.val() === '') ? null :
            String(validateControl($c.document.number, function (v) {
              if (docNumberTest.test(v)) { return true; } else { return false; }
            },errorflag)).toUpperCase(),
        'expiryDate': (!documentConfig.hasExpiryDate ||
              ($c.document.number.val() === '' && $c.document.serial.val() === '')
            ) ? null : 
            validateDate($c.document.expiryDate, errorflag, 'YYYY-MM-DD'),
        'citizenship': ($c.document.citizenship.val() !== '') ? 
            $c.document.citizenship[0].selectize.getValue() : 
            (function($c, errorflag) {
                errorflag.val = true;
                $c.document.citizenship[0].selectize.$control
                  .addClass('error')
                  .one('click',function() { $(this).removeClass('error'); });
                return false;
            }($c, errorflag))
      };
    } else {
      formdata.document = {};
      errorflag.val = true;
      $c.document.type[0].selectize.$control.addClass('error');
      $c.document.type[0].selectize.$control_input.one('focus', function() {
        $c.document.type[0].selectize.$control.removeClass('error');
      });
      makeInvalid($c.document.firstName);
      makeInvalid($c.document.lastName);
      makeInvalid($c.document.serial);
      makeInvalid($c.document.number);
      console.error('Не определены правила валидации для документа: ' + $form.find('select[name="doctype"]').val());
    }

    /** Доп. правила валидации ФИО туриста */
    if ([
          formdata.document['firstName'],
          formdata.document['middleName'],
          formdata.document['lastName']
        ].join('').length > 55
    ) {
      errorflag.val = true;
      makeInvalid($c.document.firstName);
      makeInvalid($c.document.lastName);
      makeInvalid($c.document.middleName);
    }

    if (
      formdata.document['firstName'] === null ||
      (/[ьЬ]/).test(String(formdata.document['firstName']).charAt(0))
    ) {
      errorflag.val = true;
      makeInvalid($c.document.firstName);
    }

    if (
      formdata.document['lastName'] === null ||
      (/[ьЬ]/).test(String(formdata.document['lastName']).charAt(0))
    ) {
      errorflag.val = true;
      makeInvalid($c.document.lastName);
    }

    if (
      formdata.document['middleName'] !== '' &&
      (/[ьЬ]/).test(String(formdata.document['middleName']).charAt(0))
    ) {
      errorflag.val = true;
      makeInvalid($c.document.middleName);
    }

    // бонусные карты
    if ($c.bonusCards.length !== 0) {
      $c.bonusCards.each(function() {
        var $programId = $(this).find('.js-ore-tourist--bonus-card-program');
        if ($programId.val() !== '') {
          var $cardNumber = $(this).find('.js-ore-tourist--bonus-card-number');
          if ($cardNumber.val() === '') {
            errorflag.val = true;
            $cardNumber
              .addClass('error')
              .one('focus', function() {
                $cardNumber.removeClass('error');
              });
          } else {
            var cardId = $(this).data('cardid');

            formdata.bonusCards.push({
              'id': (cardId !== undefined) ? cardId : null,
              'bonuscardNumber': $cardNumber.val(),
              'aviaLoyaltyProgramId': $programId.val()
            });
          }
        }
      });
    }

    // дополнительные поля
    if (Array.isArray(this.customFields[touristId])) {
      formdata.userAdditionalFields = [];

      this.customFields[touristId].forEach(function(field) {
        if (!field.modifiable) { return; }

        var fieldValue = field.getValue();
        if (field.validate(fieldValue)) {
          formdata.userAdditionalFields.push({
            'fieldTypeId': field.fieldTypeId,
            'value': (fieldValue !== '') ? fieldValue : null,
          });
        } else { 
          errorflag.val = true;
        }
      });
    }

    return (errorflag.val) ? false : formdata;
  };

  /**
  * Сбор и валидация данных из формы для создания пользователя
  * @param {Object} $form - форма туриста
  * @return {Object|false} - данные туриста или false в случае ошибки
  */
  modView.prototype.getUserFormData = function($form) {
    var _instance = this;

    var formdata = this.getTouristFormData($form);
    if (formdata === false) { return false; }

    var userData = {
      'user': {
        'userId': formdata.userId,
        'clientId': _instance.mds.OrderStorage.client.id,
        'sex': formdata.sex,
        'firstName': formdata.firstName,
        'middleName': formdata.middleName,
        'lastName': formdata.lastName,
        'birthdate': formdata.birthdate,
        'citizenshipId': null, /** @deprecated */
        'сontactPhone': formdata.phone,
        'email': formdata.email,
        'bonusCards': Array.isArray(formdata.bonusCards) ?
          formdata.bonusCards.map(function(item) {
            delete item.id;
            return item;
          }) : formdata.bonusCards
      },
      'document': {
        'userDocId': formdata.document.user_documentID,
        'docType': formdata.document.documentType,
        'firstName': formdata.document.firstName,
        'middleName': formdata.document.middleName,
        'lastName': formdata.document.lastName,
        'docSerial': formdata.document.serialNumber,
        'docNumber': formdata.document.number,
        'docExpiryDate': formdata.document.expiryDate,
        'citizenship': formdata.document.citizenship,
      }
    };

    return userData;
  };

  /**
  * Контроль ввода серии и номера документа на лету
  * @param {Object} $form - (jQuery DOM) форма туриста
  * @todo parse rules should be here?
  */
  modView.prototype.livecheckDocument = function($form) {
    var docType = parseInt( $form.find('select[name="doctype"]').val() );
    var $fioFields = $form.find('input').filter('[name="docfirstname"],[name="docmiddlename"],[name="doclastname"]');
    var $series = $form.find('input[name="docseries"]');
    var $number = $form.find('input[name="docnumber"]');
    var $expiryDate = $form.find('input[name="docenddate"]');

    var documentConfig = KT.config.touristDocuments[docType];

    if (documentConfig !== undefined) {
      if (documentConfig.hasExpiryDate) {
        $expiryDate.prop('disabled', false).trigger('change');

        if (documentConfig.lifetime !== undefined) {
          var calendar = $expiryDate.data('plugin_clndrize').config.clndr;
          calendar.constraints.endDate = moment().add(documentConfig.lifetime).format('YYYY-MM-DD');
        }
      } else {
        $expiryDate.prop('disabled', true).val('').trigger('change');
      }

      $expiryDate.closest('.clndr-datepicker-wrap').removeClass('error');

      $fioFields
        .off('.livecheck')
        .removeClass('error')
        .on('keypress.livecheck',function(e) {
          var char = String.fromCharCode(e.which);
          var check = documentConfig.touristNameValidation[0];
          if (!check.test(char)) {
            return false;
          }
        });

      $series
        .off('.livecheck')
        .removeClass('error')
        .on('keypress.livecheck',function(e) {
          var char = String.fromCharCode(e.which);
          var check = documentConfig.numberValidation[0];
          if (!check.test(char)) {
            return false;
          }
        })
        .attr('maxlength', documentConfig.numberValidation[2].replace(/[{}]/g, '').split(',').pop());

      $number
        .off('.livecheck')
        .removeClass('error')
        .on('keypress.livecheck',function(e) {
          var char = String.fromCharCode(e.which);
          var check = documentConfig.numberValidation[1];
          if (!check.test(char)) {
            return false;
          }
        })
        .attr('maxlength',documentConfig.numberValidation[3].replace(/[{}]/g, '').split(',').pop());
    }
  };

  /** Убрать возможность установки заказчика */
  modView.prototype.removeTLSetter = function() {
    this.$touristList.find('.js-ore-tourist--tourleader-block').remove();
    return this;
  };

  /** Сместить заказчика наверх списка туристов */
  modView.prototype.rearrange = function() {
    this.$touristList.find('.js-ore-tourist').filter('[data-leader="true"]').detach().prependTo(this.$touristList);
    return this;
  };

  /**
  * Маппинг данных туриста для вывода формы
  * @param {TouristStorage} tourist - Данные туриста
  * @param {Boolean} isTourleaderSet - Установлен ли турлидер в заявке
  * @param {Boolean} isOffline - оффлайновая ли заявка
  */
  modView.prototype.mapTouristInfo = function(Tourist, isTourleaderSet, isOffline) {
    var _instance = this;
    if (isOffline !== true) { isOffline = false; }

    var touristInfo = {
      'isEditable': !isOffline,
      'touristId': Tourist.touristId,
      'userId': (Tourist.userId !== undefined) ? Tourist.userId : null,
      'isTourleader': Tourist.isTourleader,
      'firstname': Tourist.firstname,
      'lastname': Tourist.lastname,
      'middlename': (Tourist.middlename !== null) ? Tourist.middlename : '',
      'email': (Tourist.email !== null) ? Tourist.email : '',
      'phoneCountryCode': (Tourist.phone.countryCode !== null) ?
        Tourist.phone.countryCode : '',
      'phoneCityCode': (Tourist.phone.cityCode !== null) ?
        Tourist.phone.cityCode : '',
      'phoneNumber': (Tourist.phone.number !== null) ?
        Tourist.phone.number : '',
      'phone': (Tourist.phone.countryCode !== null) ?
        (Tourist.phone.countryCode + '(' + Tourist.phone.cityCode + ')' + Tourist.phone.number) :
        Tourist.phone.number,
      'isTourleaderSet': isTourleaderSet,
      'isMale': (Tourist.sex === 'male') ? true : false,
      'birthdate': (Tourist.birthdate !== null) ? Tourist.birthdate.format('DD.MM.YYYY') : '',
      'document': {
        'documentId': (Tourist.document.documentId !== undefined) ? 
          Tourist.document.documentId : null,
        'type': Tourist.document.type,
        'series': Tourist.document.series,
        'number': Tourist.document.number,
        'firstname': Tourist.document.firstname,
        'lastname': Tourist.document.lastname,
        'middlename': (Tourist.document.middlename !== null) ?
          Tourist.document.middlename : '',
        'citizenship': Tourist.document.citizenship,
        //'issueDate': tourist.document.issueDate.format('DD.MM.YYYY'),
        'expiryDate': (Tourist.document.expiryDate !== null) ? 
          Tourist.document.expiryDate.format('DD.MM.YYYY') : ''
      },
      'bonusCards': (Tourist.bonusCards.length === 0) ? 
        Mustache.render(_instance.mds.tpl.touristFormNoBonusCards, {}) :
        Tourist.bonusCards.map(function(card) {
          return Mustache.render(_instance.mds.tpl.touristFormBonusCard, card);
        }).join(''),
      'bonusCardsActions': Mustache.render(_instance.mds.tpl.touristFormBonusCardActions, {
          'add': true
        }),
      'hasCustomFields': (Tourist.customFields !== null && Tourist.customFields.length > 0)
    };

    return touristInfo;
  };

  /**
  * Скопировать значения полей ФИО из данных туриста в документ
  * @param {Object} $el - (jQuery DOM) кнопка формы
  */
  modView.prototype.copyNamesToDoc = function($el) {
    var $form = $el.closest('.js-ore-tourist');
    $form.find('input[name="doclastname"]')
      .removeClass('error')
      .val( transliterate($form.find('input[name="lastname"]').val()) );
    $form.find('input[name="docfirstname"]')
      .removeClass('error')
      .val( transliterate($form.find('input[name="firstname"]').val()) );
    $form.find('input[name="docmiddlename"]')
      .removeClass('error')
      .val( transliterate($form.find('input[name="middlename"]').val()) );
  };

  /** 
  * Вывод модального окна отмены добавления туриста 
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showRevertAddTouristModal = function(submitAction) {
    KT.Modal.notify({
      type: 'warning',
      title: 'Удаление туриста из заявки',
      msg: '<p>Вы действительно хотите отменить добавление туриста?</p>',
      buttons:[{
          type: 'warning',
          title: 'нет'
        },
        {
          type: 'error',
          title: 'да',
          callback: submitAction
      }]
    });
  };

  /** 
  * Вывод модального окна удаления туриста 
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showRemoveTouristModal = function(submitAction) {
    KT.Modal.notify({
      type: 'warning',
      title: 'Удаление туриста из заявки',
      msg: '<p>Вы действительно хотите удалить туриста из заявки?</p>',
      buttons:[{
          type: 'warning',
          title: 'нет'
        },
        {
          type: 'error',
          title: 'да',
          callback: submitAction
      }]
    });
  };

  /**
  * Отобразить ошибку значения в поле
  * @param {Object} $el - [jQuery DOM] поле формы
  * @param {String} [msg] - текст для плейсхолдера контрола
  */
  function makeInvalid($el, msg) {
    if (msg === undefined || msg === null) { msg = ''; }
    $el
      .addClass('error')
      .attr('data-pl', $el.attr('placeholder'))
      .attr('placeholder', msg)
      .one('focus', function() {
        $(this)
          .removeClass('error')
          .attr('placeholder', $(this).attr('data-pl'))
          .removeAttr('data-pl');
      });
  }

  /**
  * Проверка значений форм
  * @param {Object} $el - (jQuery DOM) элемент для проверки
  * @param {Function} rule - функция проверки значения, принимает значение и возвращает true/false
  * @param {Object} flag - объект флага для сохранения состояния ошибки
  * @param {String} [msg] - текст для плейсхолдера контрола
  * @return {*|Boolean} - возращает значение элемента или false в случае непрошедшей валидации
  */
  function validateControl($el, rule, flag, msg) {
    var cv = $el.val();
    if (msg === undefined || msg === null) { msg = ''; }

    if (rule(cv)) {
      return cv;
    } else {
      makeInvalid($el, msg);
      flag.val = true;
      return null;
    }
  }

  /**
  * Проверка даты в контролах Clndrize
  * @param {Object} $el - (jQuery DOM) элемент для проверки (input)
  * @param {Object} flag - объект флага для сохранения состояния ошибки
  * @param {String} frmt - формат возвращаемой даты
  * @return {*|Boolean} - возращает дату в заданном формате или false в случае непрошедшей валидации
  */
  function validateDate($el,flag, frmt) {
    var cv = $el.val();

    if (cv !== '' && (/^\d{2}\.\d{2}\.\d{4}$/ig).test(cv)) {
      var constraints = $el.data('plugin_clndrize').config.clndr.constraints;
      var cvdate = moment(cv,'DD.MM.YYYY');
      if (
        cvdate >= moment(constraints.startDate,'YYYY-MM-DD') &&
        cvdate <= moment(constraints.endDate,'YYYY-MM-DD')
      ) {
        $el.attr('title','');
        return moment(cv,'DD.MM.YYYY').format(frmt);
      } else {
        $el.attr('title','Введенная дата выходит за допустимые границы');
      }
    }

    $el
      .closest('.clndr-datepicker-wrap')
        .addClass('error')
      .end()
      .one('focus click',function() {
        $(this).closest('.clndr-datepicker-wrap').removeClass('error');
      });

    flag.val = true;
    return null;
  }

  /**
  * Инициализация элемента выбора программы лояльности
  * @param {Object} $control - элемент выбора 
  * @param {Object} [params] - дополнительные параметры
  */
  function initLoyalityProgramSelect($control, params) {
    var controlOptions = {
      plugins: {'jirafize':{}},
      openOnFocus: true,
      create: false,
      options: [],
      selectOnTab: true,
      valueField: 'programId',
      searchField: ['IATAcode','loyalityProgramName', 'aircompanyName'],
      render: {
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
      }
    };

    if (typeof params === 'object') {
      $.extend(controlOptions, params);
    }

    $control.selectize(controlOptions);
  }

  return modView;
}));
