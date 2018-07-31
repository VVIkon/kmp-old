/* global ktStorage */
(function(global,factory){
  
      KT.storage.ReportsStorage = factory();
  
  }(this,function() {
    'use strict';

    /**
    * Хранилище отчетов
    * @module ReportsStorage
    * @constructor
    * @param {Object} [companyData] - данные компании, для которой загружается расписание, 
    *  или null если для общих отчетов
    */
    var ReportsStorage = ktStorage.extend(function(companyData) {
      this.namespace = 'ReportsStorage';

      if (companyData !== undefined) {
        this.companyId = companyData.companyId;
        this.companyName = companyData.name; 
      } else {
        this.companyId = null;
        this.companyName = null; 
      }

      this.reportsSchedule = [];
    });
  
    KT.addMixin(ReportsStorage, 'Dispatcher');
  
    /**
    * Инициализация хранилища
    * @param {Array} reportsSchedule - расписание отправки очетов
    */
    ReportsStorage.prototype.initialize = function(reportsSchedule) {
      this.reportsSchedule = reportsSchedule;
      this.dispatch('initialized', this);
    };

    /**
    * Возвращает расписание отправки отчетов
    * @return {Array} - список настроенных для отправки отчетов 
    */
    ReportsStorage.prototype.getReportsSchedule = function() {
      return this.reportsSchedule.map(function(task) {
        return $.extend(true, {}, task);
      });
    };
  
    return ReportsStorage;
  }));
  