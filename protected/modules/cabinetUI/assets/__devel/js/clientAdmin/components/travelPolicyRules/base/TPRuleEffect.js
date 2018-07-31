/* Набор правил корпоратиных полити для авиа */
(function(global,factory) {
  
      KT.crates.ClientAdmin.TPRuleEffect = factory();
      
  }(this, function() {
    'use strict';

    /** 
    * Базовый класс действия правила корпоративной политики 
    * Только для переопределения!
    * @param {Object} tpl - список шаблонов
    * @param {Object} CompanyStorage - хранилище данных компании
    * @param {Object} [config] - конфигурация действия
    */
    var TPRuleEffect = function(tpl, CompanyStorage, config) {
      this.tpl = tpl;
      this.CompanyStorage = CompanyStorage;

      this.name = 'base rule action';
      this.key = null;
      this.template = null;

      if (config !== undefined) {
        this.params = config.params;
        this.disableEdit = config.disableEdit;
      }

      this.$container = null;
    };
    
    /** Механизм наследования */
    TPRuleEffect.extend = function (cfunc) {
      cfunc.prototype = Object.create(this.prototype);
      cfunc.prototype.ancestor = this.prototype;
      cfunc.extend = this.extend;      
      cfunc.constructor = cfunc;
      return cfunc;
    };

    /** 
    * Рендер блока настройки правила
    * @return {Object} - блок настройки правила
    */
    TPRuleEffect.prototype.render = function() {
      if (this.template === null) {
        this.$container = $();
        return this.$container;
      } else {
        this.$container = $(Mustache.render(this.tpl[this.template], this));
        return this.$container;
      }
    };

    /** Инициализация элемента управления */
    TPRuleEffect.prototype.init = function() {
      
    };

    /**
    * Возвращает параметры действия в структуре, соответствующей шаблону
    * @return {*} - значение
    */
    TPRuleEffect.prototype.getParams = function() {
      return null;
    };

    /** 
    * Возвращает конфигурацию действия для сохранения
    * @return {Object} - конфигурация действия (so_TP_RuleAction)
    */
    TPRuleEffect.prototype.getEffectConfig = function() {
      return {
        'action': this.key,
        'actionType': this.actionType,
        'params': this.getParams()
      };
    };

    return TPRuleEffect;
  }));