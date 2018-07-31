(function(global,factory) {

    KT.crates.OrderEdit.services.view = factory(KT.crates.OrderEdit);

}(this,function(crate) {
  var AviaServiceViewModel = crate.services.AviaServiceViewModel;
  var HotelServiceViewModel = crate.services.HotelServiceViewModel;
  var AdditionalServicesFactory = crate.AdditionalServicesFactory;

  /**
  * Редактирование заявки: услуги
  * @constructor
  * @param {Object} module - хранилище родительского модуля
  * @param {Object} [options] - Объект конфигурации класса
  */
  var modView = function(module,options) {
    this.mds = module;
    if (options === undefined) {options = {};}
    this.config = $.extend(true, {
      'templateUrl': '/cabinetUI/orders/getTemplates',
      'staticDocumentUrl': '/cabinetUI/orders/getStaticDocument',
      'templates': {
        serviceForm: 'orderEdit/services/serviceForm',
        serviceTourist: 'orderEdit/services/serviceTourist',
        serviceListEmpty: 'orderEdit/services/serviceListEmpty',
        // staticDoc: 'orderEdit/staticDoc',
        bookCancelModal: 'orderEdit/modals/bookCancel',
        pricesChangedModal: 'orderEdit/modals/pricesChanged',
        penaltiesChangedModal: 'orderEdit/modals/penaltiesChanged',
        bookChangeModal: 'orderEdit/modals/bookChange',
        sendToManagerModal: 'orderEdit/modals/sendToManager',
        // custom fields
        TextCF: 'orderEdit/customFields/textCF',
        TextAreaCF: 'orderEdit/customFields/textAreaCF',
        NumberCF: 'orderEdit/customFields/numberCF',
        DateCF: 'orderEdit/customFields/dateCF'
      }
    },options);

    // добавить шаблоны view-моделей услуг
    $.extend(this.config.templates, AviaServiceViewModel.templates);
    $.extend(this.config.templates, HotelServiceViewModel.templates);
    $.extend(this.config.templates, AdditionalServicesFactory.templates);

    this.mds.servicesTermsDocuments = {};

    this.$serviceList = $('#cab-services');
    this.$manualFormsRoot = $('body');

    // view-модели услуг
    this.serviceViewModels = {};
    // формы услуг
    this.serviceForms = {};

    // управление окном просмотра полной информации об отеле
    this.HotelInfoPages = this.setHotelInfoPages();
    // управление окнами редактирования услуг в ручном режиме
    this.ManualEditForms = this.setManualEditForms();
  };

  /**
  * Рендер услуг - общий метод
  * @param {OrderStorage} OrderStorage - хранилище данных заявки
  * @return {Promise} - процесс рендера
  */
  modView.prototype.renderServices = function(OrderStorage) {
    var _instance = this;
    var renderProcess = $.Deferred();

    var getAviaSuppliersMap = KT.Dictionary.getAsMap('suppliers', 'supplierCode', {'serviceId': 2, 'active': 1});
    var getHotelSuppliersMap = KT.Dictionary.getAsMap('suppliers', 'supplierCode', {'serviceId': 1, 'active': 1});

    $.when(getAviaSuppliersMap, getHotelSuppliersMap)
      .then(function(aviaSuppliersMap, hotelSuppliersMap) {
        var serviceIds = [];
        var termsDocuments = {};

        /*
        * сохраняем текущую позицию окна для возврата после перезагрузки форм,
        * если есть открытые услуги, сохраняем состояние,
        * очищаем список услуг
        */
        var savescroll = $('#main-scroller').scrollTop();
        var srvFormStatus = {};

        _instance.$serviceList.find('.js-service-form')
          .each(function() {
            srvFormStatus[$(this).attr('data-sid')] = $(this).hasClass('active');
          })
          .end()
          .empty();

        // обработка массива услуг
        OrderStorage.getServices().forEach(function(ServiceStorage) {
          var ServiceViewModel;
          var serviceId = ServiceStorage.serviceId;

          if (_instance.serviceViewModels.hasOwnProperty(serviceId)) {
            ServiceViewModel = _instance.serviceViewModels[serviceId];
          } else {
            switch(ServiceStorage.typeCode) {
              case 1:
                ServiceViewModel = new HotelServiceViewModel(ServiceStorage, _instance.mds.tpl, hotelSuppliersMap);
                break;
              case 2:
                ServiceViewModel = new AviaServiceViewModel(ServiceStorage, _instance.mds.tpl, aviaSuppliersMap);
                break;
              default:
                return;
            }
            _instance.serviceViewModels[serviceId] = ServiceViewModel;
          }

          ServiceViewModel.prepareViews();
          ServiceViewModel.mapTouristsInfo(OrderStorage.getTourists());
          $.extend(termsDocuments, ServiceViewModel.defineTermsDocuments());
          _instance.ManualEditForms.prepare(ServiceViewModel);

          var $serviceForm = $(
              Mustache.render(_instance.mds.tpl.serviceForm, {
                'serviceId':ServiceStorage.serviceId,
                'isCancelled': (ServiceStorage.status === 7 || ServiceStorage.penaltySums !== null),
                'serviceName': KT.getCatalogInfo('servicetypes', ServiceStorage.typeCode, 'title'),
                'serviceType': ServiceStorage.typeCode,
                'serviceTypeName': ServiceViewModel.serviceTypeName,
                'serviceIcon': KT.getCatalogInfo('servicetypes',ServiceStorage.typeCode,'icon'),
                'penalty': (ServiceStorage.penaltySums === null) ? null : {
                    'client': {
                      'inLocal': ServiceStorage.penaltySums.inLocal.client.toMoney(0,',',' '),
                      'inView': ServiceStorage.penaltySums.inView.client.toMoney(2,',',' '),
                      'viewCurrencyIcon': KT.getCatalogInfo(
                          'lcurrency', ServiceStorage.penaltySums.inView.currencyCode, 'icon'
                        )
                    },
                    'supplier': (KT.profile.userType !== 'op') ? null : {
                      'inLocal': ServiceStorage.penaltySums.inLocal.supplier.toMoney(0,',',' '),
                      'inView': ServiceStorage.penaltySums.inView.supplier.toMoney(2,',',' '),
                      'viewCurrencyIcon': KT.getCatalogInfo(
                          'lcurrency', ServiceStorage.penaltySums.inView.currencyCode, 'icon'
                        )
                    }
                  },
                'header': ServiceViewModel.headerView,
                'main': ServiceViewModel.mainView,
                'additionalServices': (!ServiceViewModel.hasAdditionalServices) ? null :
                  ServiceViewModel.additionalServicesView,
                'minimalPrice': ServiceViewModel.minimalPriceView,
                'travelPolicyViolations': (!ServiceStorage.hasTPViolations) ? null : 
                  {'list': ServiceStorage.offerInfo.travelPolicy.travelPolicyFailCodes},
                'allowTouristAdd': (
                    OrderStorage.isAddTouristAllowed &&
                    ServiceViewModel.checkTouristAddAllowance()
                  ) ? true : false,
                'tourists': '', //touristsList /** @deprecated */ */
                'hasCustomFields': (ServiceViewModel.customFields.length > 0)
              })
            )
            .appendTo(_instance.$serviceList)
            .data('unsaved', false);

          ServiceViewModel.initControls($serviceForm);

          _instance.serviceForms[serviceId] = $serviceForm;

          if (srvFormStatus[ServiceStorage.serviceId] === true) {
            $serviceForm.addClass('active');
          }

          _instance.serviceForms[ServiceStorage.serviceId] = $serviceForm;
          serviceIds.push(serviceId);
        });

        $('#main-scroller').scrollTop(savescroll);

        _instance.prepareServiceTermsDocuments(termsDocuments);

        serviceIds.forEach(function(serviceId) {
          _instance.serviceForms[serviceId]
            .find('.js-service-form-actions')
              .html(_instance.serviceViewModels[serviceId].actionsView)
              .end()
            .find('.js-service-form-tos-agreement')
              .on('click','.js-service-form-tos-agreement--tos-link',function() {
                var doc = $(this).attr('data-link');
                _instance.mds.servicesTermsDocuments[doc].open();
              });
          
          _instance.renderServiceTourists(serviceId);
        });

        _instance.getServiceTermsDocuments(termsDocuments);
        renderProcess.resolve();
      });

      return renderProcess.promise();
  };

  /** Вывод информации об отсутствии услуг для вывода */
  modView.prototype.renderEmptyServiceList = function() {
    this.$serviceList.html(Mustache.render(this.mds.tpl.serviceListEmpty, {}));
  };

  /**
  * Отображение лоадера в процессе обработки услуги
  * @todo перенести в центральный класс вьюх?
  * @param {Integer} serviceId - ID редактируемой услуги
  */
  modView.prototype.renderPendingServiceProcess = function(serviceId) {
    this.serviceForms[serviceId].find('.js-service-form-actions')
      .html(Mustache.render(KT.tpl.spinner, {'type':'medium'}));
  };

  /**
  * Раскрыть полную информацию об услуге и *дослайдить* до нее 
  * @param {Integer} serviceId - ID услуги
  */
  modView.prototype.navigateToService = function(serviceId) {
    var $service = this.serviceForms[serviceId];
    console.log('navigating to service: ' + serviceId + ', offset: ' + $service.offset().top);

    if ($service !== undefined) {
      $('#main-scroller').animate(
        {
          'scrollTop':  $('#main-scroller').scrollTop() + $service.offset().top - 30
        }, 
        400, 
        function() {
          $service.addClass('active');
        }
      );
    }
  };

  /**
  * Рендер блока туристов конкретной услуги
  * @param {Integer} serviceId - ID услуги
  * @todo переделать view-модели и этот метод
  */
  modView.prototype.renderServiceTourists = function(serviceId) {
    var _instance = this;

    var $serviceForm = _instance.serviceForms[serviceId];
    var ServiceViewModel = _instance.serviceViewModels[serviceId];
    var ServiceStorage = ServiceViewModel.ServiceStorage;

    ServiceViewModel.mapTouristsInfo(_instance.mds.OrderStorage.getTourists());

    var $serviceTourists = $serviceForm.find('.js-service-form-tourists');
    $serviceTourists.data('unsaved', false).empty();

    ServiceViewModel.touristsMap.forEach(function(tourist) {
      var $tourist = $(Mustache.render(_instance.mds.tpl.serviceTourist, tourist))
        .appendTo($serviceTourists);
      ServiceViewModel.renderTouristControls($tourist, tourist);
    });    

    if (ServiceStorage.checkAllTouristsLinked()) {
      $serviceForm.find('.js-service-form--add-tourist').remove();
    }
  };

  /**
  * Рендер доступных действий с услугой 
  * @param {Integer} serviceId - ID услуги
  */
  modView.prototype.renderServiceActions = function(serviceId) {
    var ServiceViewModel = this.serviceViewModels[serviceId];
    var $serviceForm = this.serviceForms[serviceId];

    ServiceViewModel.prepareActionsView();

    $serviceForm.find('.js-service-form-actions')
      .html(ServiceViewModel.actionsView);
  };

  /**
  * Сбор данных из блока туристов услуги 
  * @param {Integer} serviceId - ID услуги
  * @return {Object} данные по привязке и дополнительные данные туристов
  */
  modView.prototype.getServiceTouristsData = function(serviceId) {
    var touristData = {};
    var errors = false;

    this.serviceForms[serviceId].find('.js-service-form-tourist')
      .each(function() {
        var $bindControl = $(this).find('.js-service-form-tourist--service-bound');
        var touristId = +$bindControl.attr('data-touristid');

        touristData[touristId] = {
          'state': $bindControl.prop('checked'),
          'loyalityProviderId': null,
          'loyalityCardNumber': null
        };

        var $loyalityProgramSelect = $(this).find('.js-service-avia-loyalty-program');
        if ($loyalityProgramSelect.length !== 0) {
          var $loyalityProvider = $loyalityProgramSelect.find('.js-service-avia-loyalty-program--provider');
          var $loyalityNumber = $loyalityProgramSelect.find('.js-service-avia-loyalty-program--number');

          if ($loyalityNumber.val() !== '') {
            if ($loyalityProvider.val() === '') {
              errors = true;
              KT.notify('saveServiceFailedIncorrectLoyality');
              $loyalityNumber
                .addClass('error')
                .one('focus', function() {
                  $loyalityNumber.removeClass('error');
                });
            } else {
              touristData[touristId].loyalityProviderId = $loyalityProvider.val();
              touristData[touristId].loyalityCardNumber = $loyalityNumber.val();
            }
          }
        }
      });

    return (!errors) ? touristData : false;
  };

  /**
  * Подготовка окон для документов с условиями работы с услугой
  * @param {Object} termsDocuments - список документов, определенных для услуги
  */
  modView.prototype.prepareServiceTermsDocuments = function(termsDocuments) {
    for (var doc in termsDocuments) {
        this.mds.servicesTermsDocuments[doc] = $.featherlight(
          //$(Mustache.render(this.mds.tpl.staticDoc,{docName:doc})),
          Mustache.render(KT.tpl.lightbox, {
              classes: 'js-service-form--staticdoc', 
              attributes: 'data-doc="doc"'
            }
          ),
          {
            persist:true,
            closeIcon:'',
            openSpeed:0,
            closeSpeed:0
        });
        this.mds.servicesTermsDocuments[doc].close();
        this.mds.servicesTermsDocuments[doc].openSpeed = 250;
        this.mds.servicesTermsDocuments[doc].closeSpeed = 250;
    }
  };

  /**
  * Загрузка и отображение документов с условиями работы с услугой
  * @param {Object} termsDocuments - список документов
  */
  modView.prototype.getServiceTermsDocuments = function(termsDocuments) {
    var _instance = this;

    var renderer = function(document,data) {
      _instance.mds.servicesTermsDocuments[document].$content.html(data);
      return _instance.mds.servicesTermsDocuments[document].$content;
    };

    var loader = function(document,callback) {
      $.ajax({
        type: 'POST',
        data: JSON.stringify({'document':document}),
        contentType: 'application/json; charset=utf-8',
        url: _instance.config.staticDocumentUrl,
        success: function (data) {
          var loadedData;
          try {
            loadedData = JSON.parse(data);
            if (loadedData.status === 0) {
              callback(document,loadedData.document);
            } else {
              callback('Document not found');
            }
          } catch (e) {
            console.error("Не получилось загрузить статические документы =(");
            callback('Document not found');
          }
        }
      });
    };

    for (var doc in termsDocuments) {
      termsDocuments[doc].load(renderer,loader);
    }
  };

  /**
  * Только для авиа - обновление правил тарифа
  * @param {Integer} serviceId - ID услуги
  * @param {Object} [data] - данные правил тарифа, если они есть
  */
  modView.prototype.updateFareRules = function(serviceId, data) {
    var serviceView = this.serviceViewModels[serviceId];
    var fareRuleDocument = serviceView.ServiceStorage.fareRuleDocumentName;

    if (data === undefined) {
      // пустые правила тарифа
      serviceView.prepareEmptyFareRulesView();
    } else {
      serviceView.prepareFareRulesView();
    }
    this.mds.servicesTermsDocuments[fareRuleDocument].$content.html(serviceView.fareRulesView);
  };

  /**
  * Обновление формы дополнительных услуг 
  */
  modView.prototype.updateAdditionalServices = function(serviceId) {
    var ServiceViewModel = this.serviceViewModels[serviceId];
    var $serviceForm = this.serviceForms[serviceId];

    ServiceViewModel.prepareAdditionalServicesView();
    $serviceForm.find('.js-service-form--add-services')
      .html(ServiceViewModel.additionalServicesView);
    ServiceViewModel.initAddServicesControls($serviceForm);
  };

  /**
  * Сбор значений дополнительных полей услуги
  * @param {Integer} serviceId - ID услуги
  * @return {Array|null} - массив доп. полей или null в случае ошибки
  */
  modView.prototype.getCustomFieldsValues = function(serviceId) {
    var ServiceViewModel = this.serviceViewModels[serviceId];
    return ServiceViewModel.getCustomFieldsValues();
  };

  /**
  * Управление выводом окон редактироваия услуг в ручном режиме
  */
  modView.prototype.setManualEditForms = function() {
    var _instance = this;

    var ManualEditForms = {
      elem: {
        $root: $('body')
      },
      forms: {},
      prepare: function(ServiceViewModel) {
        if (KT.profile.userType !== 'op') { return; }

        var ServiceStorage = ServiceViewModel.ServiceStorage;
        var serviceId = ServiceStorage.serviceId;

        if (ServiceStorage.status === 9) {
          // подготовка или обнуление окна формы
          if (this.forms[serviceId] === undefined) {
            this.forms[serviceId] = $.featherlight(Mustache.render(KT.tpl.lightbox, {}), {
              persist:true,
              variant:'featherlight--fix-scroll',
              closeIcon:'',
              openSpeed:0,
              closeSpeed:0
            });
            this.forms[serviceId].close();
            this.forms[serviceId].openSpeed = 250;
            this.forms[serviceId].closeSpeed = 250;
          } else {
            this.forms[serviceId].$content.html(Mustache.render(KT.tpl.spinner, {}));
            this.forms[serviceId].$content.off();
          }
          this.forms[serviceId].manualFormRendered = false;
        } else {
          // очистка формы 
          if (this.forms[serviceId] !== undefined) {
            /** @todo find out where persisted featherlight is stored & kill it */
            delete this.forms[serviceId];
          }
        }
      },
      open: function(serviceId) {
        if (KT.profile.userType !== 'op') { return; }

        var ServiceViewModel = _instance.serviceViewModels[serviceId];
        var manualForm = this.forms[serviceId];

        if (manualForm === undefined) {
          console.warn('для услуги ' + serviceId + ' не создана форма работы в ручном режиме!');
          KT.notify('noManualEditForm');
          return;
        }

        manualForm.open();

        if (!manualForm.manualFormRendered) {
          manualForm.$content.html(ServiceViewModel.renderManualForm(_instance.mds));
          ServiceViewModel.initManualFormControls(manualForm.$content, _instance.mds);
          manualForm.manualFormRendered = true;
          // нормализация высоты табов
          var $tabs = manualForm.$content.find('.js-ore-service-manualedit--tab');
          var maxHeight = 0;
          $tabs.each(function() {
            if ($(this).height() > maxHeight) {
              maxHeight = $(this).height();
            }
          });
          $tabs.height(maxHeight);
        }
      },
      getSaveServiceDataParams: function(serviceId) {
        var ServiceStorage = _instance.serviceViewModels[serviceId].ServiceStorage;

        var $manualForm = this.forms[serviceId].$content.find('.js-ore-service-manualedit');

        var startDate = $manualForm.find('.js-ore-service-manualedit--start-date').val();
        var endDate = $manualForm.find('.js-ore-service-manualedit--end-date').val();

        var agencyProfit = $manualForm.find('.js-ore-service-manualedit--agent-commission').val();
        agencyProfit = (agencyProfit !== '') ? parseFloat(agencyProfit.replace(/\s+/g,'').replace(',','.')) : null;

        var clientPrice = $manualForm.find('.js-ore-service-manualedit--client-gross-price').val();
        clientPrice = (clientPrice !== '') ? parseFloat(clientPrice.replace(/\s+/g,'').replace(',','.')) : null;

        var currency = $manualForm.find('.js-ore-service-manualedit--currency').val();

        var clientCancelPenalties = [];
        $manualForm.find('.js-ore-service-manualedit--client-cancel-penalty').each(function() {
          var penaltyIndex = +$(this).data('id');
          var penaltyCurrency = $(this).data('currency');
          var penaltyAmount = $(this).find('.js-ore-service-manualedit--client-cancel-penalty-amount').val();
          var cancelPenalty = ServiceStorage.clientCancelPenalties[penaltyIndex];

          clientCancelPenalties.push({
            'dateFrom': cancelPenalty.dateFrom.format('YYYY-MM-DD HH:mm'),
            'dateTo': cancelPenalty.dateTo.format('YYYY-MM-DD HH:mm'),
            'description': cancelPenalty.description,
            'penalty': {
              'amount': (penaltyAmount !== '') ? parseFloat(penaltyAmount.replace(/\s+/g,'').replace(',','.')) : 0,
              'currency': penaltyCurrency
            }
          });
        });

        return {
          'serviceId': serviceId,
          'orderServiceData': {
            'dateStart': (startDate !== '') ? moment(startDate,'DD.MM.YYYY').format('YYYY-MM-DD') : null,
            'dateFinish': (endDate !== '') ? moment(endDate,'DD.MM.YYYY').format('YYYY-MM-DD') : null,
            'agencyProfit': agencyProfit,
            'cancelPenalties': {
              'client': clientCancelPenalties
            },
            'salesTerms': {
              'client':{
                'amountBrutto': clientPrice,
                'currency': currency
              }
            }
          }
        };
      },
      getSaveServiceStatusParams: function(serviceId) {
        var service = _instance.mds.OrderStorage.services[serviceId];
        var $manualForm = this.forms[serviceId].$content.find('.js-ore-service-manualedit');

        var newStatus = $manualForm.find('.js-ore-service-manualedit--change-status').val();
        var setOffline = $manualForm.find('.js-ore-service-manualedit--set-offline').prop('checked');
        var comment = $manualForm.find('.js-ore-service-manualedit--status-comment').val();

        var params = {
          'serviceId': serviceId,
          'serviceStatus': (newStatus !== '' && +newStatus !== service.status) ?
            +newStatus : null,
          'online': setOffline ? false : null,
          'comment': comment
        };

        return params;
      },
      getChangeBookDataParams: function(serviceId) {
        var $manualForm = this.forms[serviceId].$content.find('.js-ore-service-manualedit');
        if (_instance.serviceViewModels[serviceId].getChangeBookDataParams !== undefined) {
          return _instance.serviceViewModels[serviceId].getChangeBookDataParams($manualForm);
        } else { return false; }
      },
      getChangeTicketDataParams: function(serviceId) {
        var $manualForm = this.forms[serviceId].$content.find('.js-ore-service-manualedit');
        if (_instance.serviceViewModels[serviceId].getChangeTicketDataParams !== undefined) {
          return _instance.serviceViewModels[serviceId].getChangeTicketDataParams($manualForm);
        } else { return false; }
      },
      getAddTicketParams: function(serviceId) {
        var $manualForm = this.forms[serviceId].$content.find('.js-ore-service-manualedit');
        if (_instance.serviceViewModels[serviceId].getAddTicketParams !== undefined) {
          return _instance.serviceViewModels[serviceId].getAddTicketParams($manualForm);
        } else { return false; }
      }
    };

    return ManualEditForms;
  };

  /**
  * Управление выводом полной информации по отелям
  * @return {Object} - модуль управления выводом полной информации об отеле
  */
  modView.prototype.setHotelInfoPages = function() {
    var _instance = this;

    var HotelInfoPages = {
      loaded: {},
      dummyPhoto: '/resources/hotels/dummy.jpg',
      photoLinkTest: /^(http|[^\/]{2,}\.[^\/.]{2,}\/).*/,
      categoryMap: {
        'ONE':1,
        'TWO':2,
        'THREE':3,
        'FOUR':4,
        'FIVE':5
      },
      serviceGroupMap: {
        'Продленная регистрация': 'prolonged-regtime',
        'В номере': 'room-service',
        'Общие': 'general',
        'Стойка регистрации': 'reception',
        'Дети': 'children',
        'НА свежем воздухе': 'fresh-air',
        'Бизнес': 'business',
        'Развлечения': 'entertainment',
        'Питание и напитки': 'food-n-drink',
        'Спорт/Здоровье': 'sports',
        'Трансфер': 'transfer',
        'Парковка': 'parking',
        'Сейф': 'safe',
        'Персонал говорит': 'staff-lang',
        'Интернет': 'internet',
        'Домашние животные': 'pet'
      },
      createPage: function(hotelId) {
        if (!this.loaded.hasOwnProperty(hotelId)) {
          this.loaded[hotelId] = $.featherlight(Mustache.render(KT.tpl.lightbox, {}), {
            persist:true,
            closeIcon:'',
            openSpeed:250,
            closeSpeed:250
          });
          return true;
        } else {
          return false;
        }
      },
      render: function(hotel) {
        var self = this;

        /* вывести краткое описание наверх списка */
        if (Array.isArray(hotel.descriptions)) {
          var descriptionTypes = {};

          // убрать дубли
          hotel.descriptions.forEach(function(description) {
            descriptionTypes[description.descriptionType] = description;
          });

          hotel.descriptions = [];

          for (var type in descriptionTypes) {
            descriptionTypes[type].description = htmlDecode(descriptionTypes[type].description);
            // краткое описание в начало списка
            if (type !== 'short') {
              hotel.descriptions.push(descriptionTypes[type]);
            } else {
              hotel.descriptions.unshift(descriptionTypes[type]);
            }
          }
        }
        
        var serviceMap = {};
        hotel.services.forEach(function(service) {
          var icon = self.serviceGroupMap[service.serviceGroupCode];
          if (icon !== undefined) {
            if (serviceMap[icon] === undefined) {
              serviceMap[icon] = {
                'group': service.serviceGroupCode,
                'icon': icon,
                'list': []
              };
            }

            serviceMap[icon].list.push({'name':service.name});
          }
        });

        var services = [];
        for (var group in serviceMap) {
          if (serviceMap.hasOwnProperty(group)) {
            services.push(serviceMap[group]);
          }
        }
        services.sort(function(a,b) {
          if (a.list.length > b.list.length) { return 1; }
          else if (a.list.length < b.list.length) { return -1; }
          else { return 0; }
        });

        var hotelInfo = {
          'name': hotel.name,
          'icon': hotel,
          'hotelChain': (hotel.hotelChain === '') ? null : hotel.hotelChain,
          'mainImage': (
              hotel.mainImageUrl !== undefined &&
              hotel.mainImageUrl !== null &&
              this.photoLinkTest.test(String(hotel.mainImageUrl))
            ) ?
              hotel.mainImageUrl :
              this.dummyPhoto,
          'category': (hotel.category === null || this.categoryMap[hotel.category] === undefined) ? false :
            (function(category){
              var stars = [];
              for (var i = 0; i < category; i++) {
                stars.push(true);
              }
              return stars;
            }(this.categoryMap[hotel.category])),
          'city': hotel.cityName,
          'country': hotel.countryName, 
          'checkIn': (hotel.checkInTime !== null) ? 
            moment(hotel.checkInTime,'HH:mm:ss').format('HH:mm') : null, 
          'checkOut': (hotel.checkOutTime !== null) ? 
            moment(hotel.checkOutTime,'HH:mm:ss').format('HH:mm') : null, 
          'address': hotel.address,
          'distance': hotel.distance,
          'phone': hotel.phone,
          'fax': hotel.fax,
          'email': hotel.email,
          'siteurl': (KT.profile.userType === 'op') ? hotel.url : null,
          'photos': hotel.images,
          'services': services,
          'descriptions': hotel.descriptions
        };

        var $hotelInfo = this.loaded[hotel.hotelId].$content;

        $hotelInfo
          .html(Mustache.render(_instance.mds.tpl.hotelFullInfo, hotelInfo))
          .find('.js-hotel-info__gallery')
            .on('click','.js-hotel-info__gallery-item', function() {
              $(this).parent().children('.active').removeClass('active');
              $(this).addClass('active');
              var url = $(this).data('url');
              $hotelInfo.find('.js-hotel-info__main-photo').css({'background-image': 'url('+url+')'});
            })
            .addClass('baron baron__root baron__clipper _simple')
            .wrapInner('<div class="js-hotel-info__gallery-wrap"></div>')
            .wrapInner('<div class="js-hotel-info__gallery-scroller baron__scroller"></div>')
            .append('<div class="js-hotel-info__gallery-track baron__track">'+
                '<div class="baron__control baron__up">▲</div>'+
                '<div class="baron__free">'+
                '<div class="js-hotel-info__gallery-bar baron__bar"></div>'+
                '</div>'+
                '<div class="baron__control baron__down">▼</div>'+
                '</div>')
            .baron({
              root: $('.js-hotel-info__gallery'),
              scroller: '.js-hotel-info__gallery-scroller',
              bar: '.js-hotel-info__gallery-bar',
              track: '.js-hotel-info__gallery-track',
              scrollingCls: '_scrolling',
              draggingCls: '_dragging'
            });
      },
      open: function(hotelId) {
        this.loaded[hotelId].open();
      }
    };

    return HotelInfoPages;
  };

  /**
  * Рендер сообщения диалога согласия с изменением цены
  * @param {Object} newSalesTerms - новые ценовые данные предложения
  * @param {ServiceStorage} Service - изменившаяся услуга
  * @param {Function} [submitAction] - действие при подтверждении
  * @param {Function} [cancelAction] - действие при отмене
  * @return {String} - сообщение диалога
  */
  modView.prototype.showPricesChangedModal = function(newSalesTerms, Service, submitAction, cancelAction) {
    var prices = {};
    prices.client = {
      'oldPrice': Service.prices.inClient.client.gross.toMoney(2,',',' '),
      'newPrice': Number(newSalesTerms.clientCurrency.client.amountBrutto).toMoney(2,',',' '),
      'currency': KT.getCatalogInfo('lcurrency', newSalesTerms.clientCurrency.client.currency, 'icon')
    };
    if (KT.profile.userType === 'op') {
      prices.supplier = {
        'oldPrice': Service.prices.inSupplier.supplier.gross.toMoney(2,',',' '),
        'newPrice': Number(newSalesTerms.supplierCurrency.supplier.amountBrutto).toMoney(2,',',' '),
        'currency': KT.getCatalogInfo('lcurrency', newSalesTerms.supplierCurrency.supplier.currency, 'icon')
      };
    }

    var buttons = [];
    if (typeof submitAction !== 'function') {
      buttons.push({
        type:'warning',
        title:'ok'
      });
    } else {
      buttons.push({
        type:'warning',
        title:'принять',
        callback: submitAction
      });

      if (typeof cancelAction === 'function') {
        buttons.push({
          type:'warning',
          title:'отклонить',
          callback: cancelAction
        });
      }
    }

    KT.Modal.notify({
      type:'warning',
      title:'Изменение данных брони',
      msg: Mustache.render(this.mds.tpl.pricesChangedModal, prices),
      buttons: buttons
    });
  };

  /**
  * Рендер сообщения о невозвратности услуги
  */
  modView.prototype.showNonrefundableModal = function(submitAction, cancelAction) {
    KT.Modal.notify({
      type:'warning',
      title:'Невозвратная услуга',
      msg: '<p style="text-align:center">Услуга, которую Вы собираетесь забронировать, является невозвратной</p>',
      buttons:[{
          type:'warning',
          title:'Продолжить',
          callback: submitAction
        },
        {
          type:'warning',
          title:'Отмена',
          callback: cancelAction
      }]
    });
  };

  /**
  * Вызов окна изменения брони
  * @param {Integer} serviceId - ID услуги
  * @return {Promise} - результат работы с модальным окном
  */
  modView.prototype.showBookChangeModal = function(serviceId) {
    var _instance = this;
    var request = $.Deferred();

    var OrderStorage = _instance.mds.OrderStorage;
    var ServiceViewModel = _instance.serviceViewModels[serviceId];
    var Service = ServiceViewModel.ServiceStorage;
    var touristAges = $.extend(true, {}, Service.touristAges);

    ServiceViewModel.mapTouristsInfo(OrderStorage.getTourists(), true);

    var buttons = [
      {
        title:'да', 
        callback: function($modal) {
          var newTouristLinkage = {};
          var linkedAmount = 0;
  
          $modal.find('.js-modal-book-change--tourist-bound')
            .each(function() {
              var touristId = +$(this).attr('data-touristid');
              newTouristLinkage[touristId] = {
                'state': $(this).prop('checked'),
                'loyalityProviderId': null,
                'loyalityCardNumber': null
              };
              if (newTouristLinkage[touristId].state) { linkedAmount++; }
            });
  
          var linkageInfo = OrderStorage.createLinkageStructure(serviceId, newTouristLinkage);
  
          var service = OrderStorage.services[serviceId];
          if (linkedAmount !== service.declaredTouristAmount) {
            KT.notify('notAllTouristsLinked');
            return null;
          }
  
          var $dateIn = $modal.find('.js-modal-book-change--date-in');
          var $dateOut = $modal.find('.js-modal-book-change--date-out');
          var dateIn = $dateIn.val();
          var dateOut = $dateOut.val();
          dateIn = moment(
              (dateIn === '' || dateIn === null) ? $dateIn.data('default') : dateIn,
              'DD.MM.YYYY'
            ).format('YYYY-MM-DD');
          dateOut = moment(
              (dateOut === '' || dateOut === null) ? $dateOut.data('default') : dateOut,
              'DD.MM.YYYY'
            ).format('YYYY-MM-DD');
  
          request.resolve({
            'action': 'bookChange',
            'params': {
              'serviceId': serviceId,
              'serviceData': {
                'dateStart': dateIn,
                'dateFinish': dateOut,
                'touristData': linkageInfo
              }
            }
          });
        }
      },
      {
        title:'нет', 
        callback: function() { request.reject(); }
      }
    ];

    if (KT.profile.userType !== 'op') {
      buttons.push({
        title:'Передать менеджеру', 
        callback: function() {
          _instance.showSendToManagerModal(serviceId)
            .then(function(response) {
              request.resolve(response);
            })
            .fail(function() {
              request.reject();
            }); 
        }
      });
    }

    var $modal = KT.Modal.notify({
      type: 'info',
      title: 'Изменение данных брони',
      msg: Mustache.render(_instance.mds.tpl.bookChangeModal, {
        'serviceId': serviceId,
        'serviceIcon': KT.getCatalogInfo('servicetypes', Service.typeCode, 'icon'),
        'serviceName': Service.name,
        'dateIn': Service.startDate.format('DD.MM.YYYY'),
        'dateOut': Service.endDate.format('DD.MM.YYYY'),
        'tourists': ServiceViewModel.touristsMap
      }),
      buttons: buttons
    }).$content;

    $modal.find('.js-modal-book-change--date-in').clndrize({
      'template': KT.tpl.clndrDatepicker,
      'eventName': 'Дата заезда',
      'showDate': moment(),
      'clndr': {
        'constraints': {
          'startDate': moment().format('YYYY-MM-DD'),
          'endDate': moment().add(1,'years').format('YYYY-MM-DD')
        }
      }
    });

    $modal.find('.js-modal-book-change--date-out').clndrize({
      'template': KT.tpl.clndrDatepicker,
      'eventName': 'Дата выезда',
      'showDate': moment(),
      'clndr': {
        'constraints': {
          'startDate': moment().format('YYYY-MM-DD'),
          'endDate': moment().add(1,'years').format('YYYY-MM-DD')
        }
      }
    });

    $modal.on('change', '.js-modal-book-change--tourist-bound', function() {
      var touristId = +$(this).attr('data-touristid');
      var Tourist = OrderStorage.tourists[touristId];
      var ageGroup = Service.getAgeGroup(Service.getAgeByServiceEnding(Tourist.birthdate));

      if ($(this).prop('checked')) {
        if ((touristAges[ageGroup] + 1) <= Service.declaredTouristAges[ageGroup]) {
          touristAges[ageGroup] += 1;
        } else {
          $(this).prop('checked',false).closest('.simpletoggler').removeClass('active');
          KT.notify('touristLinkageNotAllowedByAge');
        }
      } else {
        touristAges[ageGroup] -= 1;
      }
    });

    /*
    var BookChangeModal = {
      $modal: null,
      serviceId: null,
      touristAges: {},

      showModal: function(serviceId, submitAction, cancelAction, toManagerAction) {
      },
      showSendToManagerModal: function(modalParams, toManagerAction, cancelAction) {
        var self = this;

        self.$modal = KT.Modal.notify({
          type: 'info',
          title: 'Передача услуги менеджеру для исправления других параметров',
          msg: Mustache.render(_instance.mds.tpl.sendToManagerModal, modalParams),
          buttons: [
            {title:'передать менеджеру', callback: toManagerAction},
            {title:'отмена', callback: function($modal) { self.clearData(); cancelAction($modal); }}
          ]
        }).$content;
      },
      getParams: function() {

      },
      getToManagerParams: function() {
        var self = this;
        var $comment = self.$modal.find('.js-modal-send-to-manager--comment');

        if ($comment.val() === '') {
          $comment.addClass('error')
            .attr('placeholder', 'Оставьте комментарий')
            .one('focus', function() { $(this).removeClass('error'); });
          return null;
        } else {
          return {
            'serviceId': self.serviceId,
            'comment': $comment.val()
          };
        }
      }
    };
    */

    return request.promise();
  };

  /**
  * Вызов модального окна передачи услуги на обработку менеджеру
  * @param {Integer} serviceId - ID услуги
  * @return {Promise} - результат работы с модальным окном
  */
  modView.prototype.showSendToManagerModal = function(serviceId) {
    var _instance = this;
    var request = $.Deferred();

    var ServiceViewModel = _instance.serviceViewModels[serviceId];
    var Service = ServiceViewModel.ServiceStorage;

    KT.Modal.notify({
      type: 'info',
      title: 'Передача услуги менеджеру для исправления других параметров',
      msg: Mustache.render(_instance.mds.tpl.sendToManagerModal, {
        'serviceId': serviceId,
        'serviceIcon': KT.getCatalogInfo('servicetypes', Service.typeCode, 'icon'),
        'serviceName': Service.name
      }),
      buttons: [
        {
          title:'передать менеджеру', 
          callback: function($modal) {
            var $comment = $modal.find('.js-modal-send-to-manager--comment');

            if ($comment.val() === '') {
              $comment.addClass('error')
                .attr('placeholder', 'Оставьте комментарий')
                .one('focus', function() { $(this).removeClass('error'); });
              return null;
            } else {
              request.resolve({
                'action': 'sendToManager',
                'params': {
                  'serviceId': serviceId,
                  'comment': $comment.val()
                }
              });
            }
          }
        },
        {
          title:'отмена', 
          callback: function() { 
            request.reject(); 
          }
        }
      ]
    });

    return request.promise();
  };

  /**
  * Вызов модального окна отмены брони
  * @param {Integer} serviceId - ID услуги
  * @return {Promise} - результат работы с модальным окном
  */
  modView.prototype.showBookCancelModal = function(serviceId) {
    var _instance = this;
    var request = $.Deferred();
    
    var ServiceViewModel = _instance.serviceViewModels[serviceId];
    var Service = ServiceViewModel.ServiceStorage;
    var cancelPenaltySum = Service.countClientCancelPenalty();
    
    var modalParams = {
      'serviceName': Service.name,
      'penalty': (cancelPenaltySum !== null && cancelPenaltySum.inLocal !== 0) ? {
          'localAmount': Number(cancelPenaltySum.inLocal).toMoney(0,',',' '),
          'localCurrency': KT.getCatalogInfo('lcurrency', KT.profile.localCurrency, 'icon'),
          'viewAmout': Number(cancelPenaltySum.inView).toMoney(0,',',' '),
          'viewCurrency': KT.getCatalogInfo('lcurrency', KT.profile.viewCurrency, 'icon'),
        } : null,
      'isInvoiceOptional': false
    };

    KT.Modal.notify({
      type: 'info',
      title: 'Отмена бронирования',
      msg: Mustache.render(_instance.mds.tpl.bookCancelModal, modalParams),
      buttons: [{
          type: 'common',
          title: 'да',
          callback: function($modal) {
            var $isSetInvoiceSelected = $modal.find('.js-modal-book-cancel--set-invoice');
            var commandParams = _instance.mds.OrderStorage.getServiceCommandParams('BookCancel', serviceId);
            commandParams['createPenaltyInvoice'] = ($isSetInvoiceSelected.length !== 0) ? 
              $isSetInvoiceSelected.prop('checked') : 
              true;

            request.resolve(commandParams);
          }
        },
        {
          type: 'common',
          title: 'нет',
          callback: function() {
            request.reject(true);
          }
      }]
    });
    
    return request.promise();
  };

  /**
  * Вызов модального окна с уведомлением об изменении штрафов 
  * @param {Integer} serviceId - ID услуги
  * @param {Object} newPenalties - новые данные штрафов
  * @return {Promise} - результат работы с модальным окном
  */
  modView.prototype.showPenaltiesChangedModal = function(serviceId, newPenalties) {
    var _instance = this;
    var request = $.Deferred();
    
    var ServiceViewModel = _instance.serviceViewModels[serviceId];
    var ServiceStorage = ServiceViewModel.ServiceStorage;
    var cancelPenalties = ServiceStorage.countClientCancelPenalty();

    /** @todo ждем бэкэнд для правильной валюты */
    var newClientPenalties = {
      'inLocal': newPenalties.localCurrency.client.reduce(function(total, penalty) {
        if (moment().isBetween(penalty.dateFrom, penalty.dateTo, null, '[]')) {
          return total + Number(penalty.penalty.amount);
        } else {
          return total;
        }
      }, 0),
      'inView':  newPenalties.viewCurrency.client.reduce(function(total, penalty) {
        if (moment().isBetween(penalty.dateFrom, penalty.dateTo, null, '[]')) {
          return total + Number(penalty.penalty.amount);
        } else {
          return total;
        }
      }, 0),
    };

    // параметры команды отмены брони
    var commandParams = _instance.mds.OrderStorage.getServiceCommandParams('BookCancel', serviceId);
    commandParams['createPenaltyInvoice'] = true;

    // если на момент отмены штрафов нет, то продолжаем
    if (newClientPenalties.inLocal === 0) {
      return request.resolve(commandParams);
    }
    
    var modalParams = {
      'client': {
        'localOldPenalty': Number(cancelPenalties.inLocal).toMoney(0,',',' '),
        'localNewPenalty': Number(newClientPenalties.inLocal).toMoney(0,',',' '),
        'localCurrency': KT.getCatalogInfo('lcurrency', KT.profile.localCurrency, 'icon')
      }
    };

    KT.Modal.notify({
      type: 'warning',
      title: 'Изменение штрафов за отмену',
      msg: Mustache.render(_instance.mds.tpl.penaltiesChangedModal, modalParams),
      buttons: [{
          type: 'warning',
          title: 'продолжить',
          callback: function() {
            request.resolve(commandParams);
          }
        },
        {
          type: 'error',
          title: 'отмена',
          callback: function() {
            request.reject();
          }
      }]
    });
    
    return request.promise();
  };

  return modView;

}));
