(function(global,factory){

    KT.crates.ClientAdmin.view = factory();

}(this,function() {
  'use strict';
  
  /**
  * Администрирование клиента
  * @param {Object} module - ссылка на модуль
  * @param {Object} [options] - конфигурация
  */
  var modView = function(module, options) {
    this.mds = module;
    if (options === undefined) { options = {}; }
    this.config = $.extend(true, {
      'templateUrl': '/cabinetUI/admin/getTemplates',
      'templates': {
        companySelectForm: 'companySelectForm',
        clientAdminHeader: 'clientAdminHeader',
        tabHeaders: 'tabHeaders'
      }
    },options);

    this.$startScreen = $('#start-screen');
    this.$clientAdminHeader = $('#client-admin-header');

    this.mds.tpl = {};
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

  /** 
  * Инициализация табов 
  * @param {Object} accessList - список доступа к вкладкам
  */
  modView.prototype.initTabs = function(accessList) {
    var _instance = this;

    $('#tab-headers')
      .off()
      .html(Mustache.render(this.mds.tpl.tabHeaders, accessList))
      .on('click','.js-tab-header',function(e) {
        e.preventDefault();
        var tab = $(this).attr('data-tab');
        _instance.setActiveTab(tab);
      });

    var availableTabs = Object.keys(accessList).filter(function(tab) {
      if (accessList[tab] === true) {
        _instance.enableTab(tab);
        return true;
      } else { return false; }
    });

    _instance.setActiveTab(availableTabs[0]);
  };

  /**
  * Показывает диалог выбора компании
  * @return {Promise} - возвращает promise с массивом с выбранной компанией (для соместимости с ответом getDictionary)
  */
  modView.prototype.showSelectCompanyForm = function() {
    var request = $.Deferred();
    var _instance = this;
    console.log('render select form');
    
    var $companyForm = $(Mustache.render(this.mds.tpl.companySelectForm, {}));
    var $companyControl = $companyForm.find('.js-ci-company-select--company');
    var $companyFormSubmit = $companyForm.find('.js-ci-company-select--submit'); 

    this.$startScreen.html($companyForm);

    $companyControl.selectize({
      plugins: {
        'key_down': { start: 2 },
        'jirafize': { completely: true }
      },
      openOnFocus: true,
      create: false,
      selectOnTab: true,
      highlight: false,
      loadThrottle: 300,
      valueField: 'companyId',
      labelField: 'name',
      sortField:'seqid',
      options:[],
      score:function() {
        return function(item) {
          return 1000 / (item.seqid);
        };
      },
      render: {
        item: function(item) {
          return '<div class="ci-company-select__control-item">'+item.name+'</div>';
        },
        option: function(item) {
          return '<div class="ci-company-select__control-option">'+item.name+'</div>';
        }
      },
      load: function(query, callback) {
        var self = this;

        this.clearOptions();

        if (!query.length || query.length < 2) {
          return callback();
        }

        KT.Dictionary.getAsList('companies', {
            'textFilter': query,
            'fieldsFilter': [],
            'lang': 'ru'
          }, true)
          .done(function(companies) {
            var $inputElem = self.$control;
            
            companies.forEach(function(item, i) {
              item.seqid = i + 1;
            });

            callback(companies);

            if (companies.length === 0) {
              $inputElem.addClass('warning');
              setTimeout(function() {
                $inputElem.removeClass('warning');
              }, 2000);
            }
          })
          .fail(function() {
            callback();
            self.refreshOptions(true);
            var $inputElem = self.$control;
            $inputElem.addClass('warning');
            setTimeout(function() {
              $inputElem.removeClass('warning');
            }, 2000);
          });
      },
      onType:function(str) {
        if (str.length < 2) {
          this.close();
          this.clearOptions();
        }
      }
    });

    $companyFormSubmit.on('click', function() {
      var companySelect = $companyControl[0].selectize;
      var companyId = companySelect.getValue();

      if (companyId !== '') {
        _instance.$startScreen.empty();
        request.resolve([companySelect.options[companyId]]);
      }
    });
    
    return request.promise();    
  };

  /**
  * Рендер хедера страницы (для операторов)
  * @param {Object} CompanyStorage - хранилище данных редактируемой компании
  */
  modView.prototype.renderHeader = function(CompanyStorage) {
    var $header = $(Mustache.render(this.mds.tpl.clientAdminHeader, {
      'companyName': CompanyStorage.name
    }));

    this.$clientAdminHeader.html($header);

    var self = this;
    this.$clientAdminHeader.find('.js-ci-header--change-company').on('click', function() {
      self.setActiveTab('startScreen');
    });
  };

  return modView;
}));
