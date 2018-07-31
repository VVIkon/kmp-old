(function(global,factory) {
  
      KT.crates.ClientAdmin.controller = factory(KT.crates.ClientAdmin);
  
  }(this,function(crate) {
    'use strict';
    
    /**
    * Администрирование клиента
    * @param {Object} module - ссылка на модуль
    */
    var caController = function(module) {
      /** Module storage - модуль со всеми его компонентами */
      this.mds = module;
      this.mds.view = new crate.view(this.mds);

      // список с регламентом доступа к определенным разделам админки
      this.mds.accessList = {
        'companyInfo': false,
        'travelPolicy': false
      };

      this.mds.companyInfo.controller = new crate.companyInfo.controller(this.mds);
      this.mds.travelPolicy.controller = new crate.travelPolicy.controller(this.mds);
    };
  
    /** Инициализация событий */
    caController.prototype.init = function() {
      var _instance = this;
      var modView = _instance.mds.view;
      
      /*==========Обработчики событий представления============================*/

      /** Обработка нажатия кнопки "изменить компанию" */
      modView.$clientAdminHeader.on('click', '.js-ci-header--change-company', function() {
        console.log('show select form');
        modView.showSelectCompanyForm()
          .then(function(companyData) {
            if (companyData.length === 0) {
              console.error('Ошибка получения данных о компании!');
              return;
            }

            _instance.mds.view.initTabs(_instance.mds.accessList);
            _instance.mds.CompanyStorage = new KT.storage.CompanyStorage(companyData[0].companyId);
            _instance.mds.CompanyStorage.initialize(companyData[0]);

            if (KT.profile.userType === 'op') {
              modView.renderHeader(_instance.mds.CompanyStorage);
            }
          });

        modView.setActiveTab('startScreen');
      });
      
    };
  
    /** 
    * Инициализация модуля управления админкой 
    */
    caController.prototype.load = function() {
      var _instance = this;

      //==== сбор данных по требуемым шаблонам
      $.extend(_instance.mds.view.config.templates, _instance.mds.companyInfo.view.config.templates);
      $.extend(_instance.mds.view.config.templates, _instance.mds.travelPolicy.view.config.templates);
  
      //==== инициализация контроллеров субмодулей
      _instance.mds.companyInfo.controller.init();
      _instance.mds.travelPolicy.controller.init();
  
      //==== загрузка шаблонов 
      var getTemplates = KT.getTemplates(
        _instance.mds.view.config.templateUrl,
        _instance.mds.view.config.templates
      );

      //==== проверка прав доступа
      var checkAccessRequests = [];
      var accessList = _instance.mds.accessList;

      // проверка прав на доступ к редактированию доп. полей
      checkAccessRequests.push(KT.apiClient.checkUserAccess({'permissions': [57]})
        .then(function(r) { 
          if (+r.status === 0) {
            accessList['companyInfo'] = r.body.hasAccess;
          } 
        }));

      // проверка прав на доступ к редактированию корпоративных политик
      checkAccessRequests.push(KT.apiClient.checkUserAccess({'permissions': [62]})
        .then(function(r) { 
          if (+r.status === 0) {
            accessList['travelPolicy'] = r.body.hasAccess;
          } 
        }));

      //==== инициализация
      getTemplates.then(function(templates) {
        _instance.mds.tpl = templates;

        var companyInfo = (KT.profile.userType === 'op') ?
          _instance.mds.view.showSelectCompanyForm() :
          KT.Dictionary.getAsList('companies', {
            'companyId': +KT.profile.companyId,
            'fieldsFilter': [],
            'lang': 'ru'
          });
        
        var dataRequests = checkAccessRequests;
        dataRequests.unshift(companyInfo);

        $.when.apply($, dataRequests)
          .then(function(companyData) {
            if (companyData.length === 0) {
              console.error('Ошибка получения данных о компании!');
              return;
            }

            _instance.mds.view.initTabs(_instance.mds.accessList);

            _instance.mds.CompanyStorage = new KT.storage.CompanyStorage(companyData[0].companyId);
            _instance.mds.CompanyStorage.initialize(companyData[0]);

            if (KT.profile.userType === 'op') {
              _instance.mds.view.renderHeader(_instance.mds.CompanyStorage);
            }
          });
      });
    };
  
    return caController;
  }));
  
  (function() {
    KT.on('KT.initializedCore', function() {
      /** Инициализация модуля */
      KT.mdx.ClientAdmin.controller = new KT.crates.ClientAdmin.controller(KT.mdx.ClientAdmin);
      KT.mdx.ClientAdmin.controller.init();

      // проверка прав на доступ к админке
      KT.apiClient.checkUserAccess({'permissions': [63]})
        .then(function(response) {
          if (+response.status === 0 && response.body.hasAccess) {
            KT.mdx.ClientAdmin.controller.load();
          } else {
            window.location.assign(KT.appEntries.orderlist);
          }
        });
    });
  }());
  