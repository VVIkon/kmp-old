(function(global,factory) {
  
      KT.crates.Reports.controller = factory(KT.crates.Reports);
  
  }(this,function(crate) {
    'use strict';
    
    /**
    * Настройка отчетов
    * @param {Object} module - ссылка на модуль
    */
    var repController = function(module) {
      /** Module storage - модуль со всеми его компонентами */
      this.mds = module;
      this.mds.view = new crate.view(this.mds);

      // список с регламентом доступа
      this.mds.accessList = {
        'editOrders': false,
        'editAllOrders': false
      };
    };
  
    /** Инициализация событий */
    repController.prototype.init = function() {
      var _instance = this;
      var modView = _instance.mds.view;

      /*==========Обработчики событий хранилищ============================*/
      KT.on('ReportsStorage.initialized', function(e, ReportsStorage) {
        modView.Reports.renderReportsSchedule(ReportsStorage);
      });
      
      /*==========Обработчики событий представления============================*/

      KT.on('ReportsView.companySelected', function(e, companyData) {
        modView.Reports.showScheduleLoading();
        _instance.mds.ReportsStorage = new KT.storage.ReportsStorage(companyData);

        KT.apiClient.getReportsSchedule(_instance.mds.ReportsStorage.companyId)
          .then(function(reports) {
            if (reports.status === 0) {
              _instance.mds.ReportsStorage.initialize(reports.body);
            } else {
              KT.notify('failedToGetReportsSchedule', reports.errors);
            }
          });
      });

      /** Обработка запроса на создание отчета */
      modView.$reports.on('click', '.js-travel-policy-form--action-save', function() {
        var reportConfig = modView.Reports.getReportFormData();

        if (reportConfig === false) {
          KT.notify('ReportFormFieldsNotSet');
          return;
        }

        modView.Reports.toggleReportSavingLoader(true);

        KT.apiClient.createReport(reportConfig)
          .then(function(response) {
            modView.Reports.toggleReportSavingLoader(false);
            
            if (response.status === 0) {
              KT.notify('reportCreated');
            } else {
              KT.notify('creatingReportFailed', response.errors);
            }
          });
      });
      
      /** Обработка запроса на создание задачи регулярной отправки отчета */
      modView.$reports.on('click', '.js-travel-policy-form--action-add-task', function() {
        var taskConfig = modView.Reports.getReportTaskData();

        if (taskConfig === false) {
          KT.notify('ReportFormFieldsNotSet');
          return;
        }

        modView.Reports.toggleTaskAddingLoader(true);

        KT.apiClient.setReportsSchedule(taskConfig)
          .then(function(response) {
            modView.Reports.toggleTaskAddingLoader(false);
            
            if (response.status === 0) {
              modView.Reports.showScheduleLoading();
              KT.notify('reportTaskCreated');

              KT.apiClient.getReportsSchedule(_instance.mds.ReportsStorage.companyId)
                .then(function(reports) {
                  if (reports.status === 0) {
                    _instance.mds.ReportsStorage.initialize(reports.body);
                  } else {
                    KT.notify('failedToGetReportsSchedule', reports.errors);
                  }
                });
            } else {
              KT.notify('creatingReportTaskFailed', response.errors);
            }
          });
      });

      /** Обработка запроса на удаление задачи отправки отчета */
      modView.$reports.on('click', '.js-reports-schedule--drop-task', function() {
        $(this).prop('disabled', true);
        var $reportTask = $(this).closest('.js-reports-schedule--report');
        var taskId = $reportTask.data('taskid');

        KT.apiClient.dropReportTask(taskId)
          .then(function(response) {
            if (response.status === 0) {
              KT.notify('reportTaskDropped');
              $reportTask.remove();
            } else {
              KT.notify('reportTaskDroppingFailed', response.errors);
              modView.Reports.renderReportsSchedule(_instance.mds.ReportsStorage);
            }
          });
      });
      
    };
  
    /** 
    * Инициализация модуля
    * @param {Object} accessList - объект прав доступа
    */
    repController.prototype.load = function() {
      var _instance = this;

      _instance.mds.ReportsStorage = new KT.storage.ReportsStorage();

      var getTemplates = KT.getTemplates(
        _instance.mds.view.config.templateUrl,
        _instance.mds.view.config.templates
      );

      var getReportsSchedule = KT.apiClient.getReportsSchedule(null);
      
      //==== загрузка шаблонов и данных
      getTemplates.then(function(templates) {
        console.log('process templates');
        _instance.mds.tpl = templates;
        _instance.mds.view.render();
      });

      $.when(getTemplates, getReportsSchedule)
        .then(function(_, reports) {
          if (reports.status === 0) {
            _instance.mds.ReportsStorage.initialize(reports.body);
          } else {
            KT.notify('failedToGetReportsSchedule', reports.errors);
          }
        });
    };
  
    return repController;
  }));
  
  (function() {
    KT.on('KT.initializedCore', function() {
      /** Инициализация модуля */
      KT.mdx.Reports.controller = new KT.crates.Reports.controller(KT.mdx.Reports);
      KT.mdx.Reports.controller.init();

      // проверка прав на доступ к настройке отчетов
      KT.apiClient.checkUserAccess({'permissions': [43]})
        .then(function(response) {
          if (+response.status === 0 && response.body.hasAccess) {
            var checkAccessRequests = [];
            var accessList = KT.mdx.Reports.accessList;

            if (KT.profile.userType === 'op') {
              // проверка прав на доступ к редактированию всех заявок
              checkAccessRequests.push(KT.apiClient.checkUserAccess({'permissions': [40]})
                .then(function(r) { 
                  if (+r.status === 0) {
                    accessList['editAllOrders'] = r.body.hasAccess;
                  } 
                }));
  
            } else {
              // проверка прав на доступ к редактированию заявок своей компании
              checkAccessRequests.push(KT.apiClient.checkUserAccess({'permissions': [41]})
                .then(function(r) { 
                  if (+r.status === 0) {
                    accessList['editOrders'] = r.body.hasAccess;
                  } 
                }));
            }
              
            $.when.apply($, checkAccessRequests)
              .then(function() {
                KT.mdx.Reports.controller.load();
              });

          } else {
            window.location.assign(KT.appEntries.orderlist);
          }
        });
    });
  }());
  