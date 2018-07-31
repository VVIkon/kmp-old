(function(global, extendApiClient) {
  
      extendApiClient(KT.apiClient);
  
  }(this,function(ApiClient) {
    'use strict';

    /**
    * Получение расписания запланированных отчетов 
    * @param {Integer|null} [companyId] - Id компании, для которой запрашивается расписание, 
    *  или null для расписания общих отчетов (отчет по всем компаниям)
    */
    ApiClient.getReportsSchedule = function(companyId) {
      var request = $.Deferred();

      var payload = {
        'companyId': (companyId !== undefined) ? companyId : null
      };

      KT.rest({
          caller: "reports - getSchedule",
          data: payload,
          url: this.urls.getSchedule
        })
        .then(function(response) {
          /** 
          * @todo по идее, возрвщаться будут не только такси отправки отчетов, 
          * а все, поэтому нужен будет фильтр 
          */
          request.resolve(response);
        })
        .fail(function() { request.reject(); });

      return request.promise();
    };

    /**
    * Создание плана отправки отчета
    * @param {Object} params - параметры записи
    */
    ApiClient.setReportsSchedule = function(params) {
      return KT.rest({
        caller: "reports - setSchedule",
        data: params,
        url: this.urls.setSchedule
      });
    };

    /**
    * Удаление задачи отправки отчета
    * @param {Integer} taskId - ID удаляемой задачи 
    */
    ApiClient.dropReportTask = function(taskId) {
      var payload = {
        'DeleteTaskId': taskId
      };

      return KT.rest({
        caller: "reports - dropScheduleTask",
        data: payload,
        url: this.urls.setSchedule
      });
    };

    /**
    * Создание отчета 
    * @param {Object} params - параметры отчета
    */
    ApiClient.createReport = function(params) {
      return KT.rest({
        caller: "reports - createReport",
        data: params,
        url: this.urls.createReport
      });
    };    
  
  }));