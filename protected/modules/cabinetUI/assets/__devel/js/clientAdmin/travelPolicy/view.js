(function(global,factory) {
  
    KT.crates.ClientAdmin.travelPolicy.view = factory(KT.crates.ClientAdmin);

}(this, function(crate) {
  'use strict';

  /**
  * Редактирование заявки: услуги
  * @constructor
  * @param {Object} module - хранилище родительского модуля
  * @param {Object} [options] - Объект конфигурации класса
  */
  var modView = function(module, options) {
    this.mds = module;
    if (options === undefined) {options = {};}
    this.config = $.extend(true, {
      'templateUrl': '/cabinetUI/admin/getTemplates',
      'templates': {
        travelPolicyRules: 'travelPolicy/travelPolicyRules',
        travelPolicyRule: 'travelPolicy/travelPolicyRule',
        travelPolicyForm: 'travelPolicy/travelPolicyForm',
        travelPolicyFormRule: 'travelPolicy/travelPolicyFormRule',
        travelPolicyFormCondition: 'travelPolicy/travelPolicyFormCondition',
        travelPolicyFormEffect: 'travelPolicy/travelPolicyFormEffect',

        // шаблоны условий правил политик
        NumberTPRC:  'travelPolicyRuleConditions/NumberTPRC',
        PriceTPRC:  'travelPolicyRuleConditions/PriceTPRC',
        BooleanTPRC:  'travelPolicyRuleConditions/BooleanTPRC',
        HourIntervalHitTPRC:  'travelPolicyRuleConditions/HourIntervalHitTPRC',
        HourIntervalIntersectionTPRC:  'travelPolicyRuleConditions/HourIntervalIntersectionTPRC',
        OptionListTPRC:  'travelPolicyRuleConditions/OptionListTPRC',
        ComplexTPRC:  'travelPolicyRuleConditions/ComplexTPRC',
        // шаблоны ввода значений разных типов для сложных условий
        TPRCboolValue: 'travelPolicyRuleConditions/valueTemplates/TPRCboolValue',
        TPRCstringValue: 'travelPolicyRuleConditions/valueTemplates/TPRCstringValue',

        // шаблоны действий правил политик
        markOfferTPRE: 'travelPolicyRuleEffects/markOfferTPRE',
        markOfferTPREboolValue: 'travelPolicyRuleEffects/markOfferTPREboolValue',
        markOfferTPREstringValue: 'travelPolicyRuleEffects/markOfferTPREstringValue',
        // шаблоны ввода значений разных типов для сложных действий
        TPREboolValue: 'travelPolicyRuleEffects/valueTemplates/TPREboolValue',
        TPREstringValue: 'travelPolicyRuleEffects/valueTemplates/TPREstringValue',
      }
    },options);

    this.$travelPolicy = $('#travel-policy');

    this.$travelPolicyRules = this.$travelPolicy.find('#travel-policy-rules');

    this.TravelPolicyRules = this.setTravelPolicyRules(this.$travelPolicyRules);
    
    this.init();
  };

  /** Инициализация */
  modView.prototype.init = function() {
    this.TravelPolicyRules.init();
  };

  modView.prototype.render = function() {
    this.$travelPolicyRules.html(Mustache.render(KT.tpl.spinner, {}));
  };

  /**
  * Инициализация формы редактирования корпоративных политик
  * @param {Object} $container - контейнер для размещения объекта
  * @return {Object} - объект управления формой корпоративных политик
  */
  modView.prototype.setTravelPolicyRules = function($container) {
    var _instance = this;

    var TravelPolicyRules = {
      elem: {
        $container: $container,
        // элементы списка
        $aviaRules: null,
        $hotelRules: null,
        // элементы формы
        $formContainer: null,
        $saveRuleAction: null
      },
      // ссылка на хранилище корпоративных политик
      TravelPolicyStorage: null,
      // флаг: является ли компания частью холдинга    
      companyInHolding: false,
      // флаг: является ли компания главной (все не-филиалы - главные)
      companyIsMain: true,
      // фабрика правил корпоративных политик
      TravelPolicyRulesFactory: null,
      // объект текущего правила
      currentRule: null,
      /** Инициализация объекта управления */
      init: function() { 
        var self = this;

        this.elem.$container.on('click', '.js-travel-policy-rules--action-add-rule', function() {
          self.renderForm();
        });

        this.elem.$container.on('click', '.js-travel-policy-rule--action-edit', function() {
          var ruleId = +$(this).closest('.js-travel-policy-rule').data('ruleid');
          var ruleConfig = self.TravelPolicyStorage.getRule(ruleId);
          self.renderForm(ruleConfig);
        });
      },
      /** Рендер объекта управления */
      render: function(TravelPolicyStorage) {
        this.TravelPolicyStorage = TravelPolicyStorage;
        var CompanyStorage = TravelPolicyStorage.CompanyStorage;
        this.TravelPolicyRulesFactory = new crate.TravelPolicyRulesFactory(_instance.mds.tpl, CompanyStorage);

        // другого метода определить пока нет, предполагаем, что каждый корпоратов в холдинге
        this.companyInHolding = (CompanyStorage.roleType === 'corp');
        this.companyIsMain = (CompanyStorage.holdingCompany === null);

        var $travelPolicyRules = $(Mustache.render(_instance.mds.tpl.travelPolicyRules, { }));
        this.elem.$aviaRules = $travelPolicyRules.find('.js-travel-policy-rules--avia');
        this.elem.$hotelRules = $travelPolicyRules.find('.js-travel-policy-rules--hotel');

        this.renderRulesList('avia');
        this.renderRulesList('hotel');

        this.elem.$container.html($travelPolicyRules);

        this.elem.$formContainer = this.elem.$container.find('.js-travel-policy-rules--form-container');
        this.renderForm();
      },
      /**
      * Отображение процесса загрузки списка правил
      * @param {String} type - тип списка правил (avia|hotel)
      */
      showRulesListLoading: function(type) {
        var $listContainer;
        
        switch (type) {
          case 1:
          case 'hotel':
            $listContainer = this.elem.$hotelRules;
            break;
          case 2:
          case 'avia':
            $listContainer = this.elem.$aviaRules;
            break;
          default: return;
        }

        $listContainer.html('<tr><td>' + Mustache.render(KT.tpl.spinner,{}) + '</td></tr>');
      },
      /**
      * Рендер списка правил
      * @param {String} type - тип списка правил (avia|hotel)
      */
      renderRulesList: function(type) {
        var TravelPolicyStorage = this.TravelPolicyStorage;
        var $listContainer;

        switch (type) {
          case 1:
          case 'hotel':
            $listContainer = this.elem.$hotelRules;
            break;
          case 2:
          case 'avia':
            $listContainer = this.elem.$aviaRules;
            break;
          default:
            return;
        }

        var rulesList = TravelPolicyStorage.getRulesList(type);

        var companyIsAffiliate = (this.companyInHolding && !this.companyIsMain);

        if (companyIsAffiliate) {
          rulesList = rulesList.filter(function(rule) {
            return !(rule.forAllCompanyInHolding && !rule.active);
          });
        }

        $listContainer.html(
          rulesList
            .map(function(rule) {
              var ruleFromHeavens = (rule.forAllCompanyInHolding && companyIsAffiliate);

              rule.actions = {
                'edit': (!ruleFromHeavens) ? rule.active : false,
                'view': ruleFromHeavens,
                'disable': (!ruleFromHeavens) ? rule.active : false,
                'enable': (!ruleFromHeavens) ? !rule.active : false
              };
              return Mustache.render(_instance.mds.tpl.travelPolicyRule, rule);
            })
            .join('')
        );
      },
      /**
      * Рендер формы добавления/редактирования правил
      * @param {Object} [ruleConfig] - данные редактируемого правила
      */
      renderForm: function(ruleConfig) {
        var formParams = {};
        this.currentRule = this.TravelPolicyRulesFactory.create();

        if (ruleConfig !== undefined) {
          this.currentRule.initialize(ruleConfig);
          $.extend(formParams, {
            'id': this.currentRule.id,
            'name': this.currentRule.name,
            'disableEdit': this.currentRule.disableEdit
          });
        }

        var $ruleForm = $(Mustache.render(_instance.mds.tpl.travelPolicyForm, formParams));
        this.$saveRuleAction = $ruleForm.find('.js-travel-policy-form--action-save');

        var $rule = this.currentRule.render();
        $ruleForm.find('.js-travel-policy-form--rule').html($rule);
        this.elem.$formContainer.html($ruleForm);

        this.currentRule.initControls();
      },
      /** 
      * Получение данных формы редактирования корпоративной политики
      * @return {Object} - параметры корпоративной политики для сохранения 
      */
      getFormData: function() {
        if (this.currentRule === null) {
          console.error('Правило не задано');
          return null;
        }

        try {
          return this.currentRule.getRuleConfig();
        } catch (err) {
          console.error(err);
          return null;
        }
      }
    };

    return TravelPolicyRules;
  };

  return modView;
  
}));
  