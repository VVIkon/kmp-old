(function(global,factory){
  
      KT.crates.Reports.view = factory();
  
}(this,function() {
  'use strict';
  
  /**
  * Управление отчетами
  * @param {Object} module - ссылка на модуль
  * @param {Object} [options] - конфигурация
  */
  var modView = function(module, options) {
    this.mds = module;
    if (options === undefined) { options = {}; }

    this.namespace = 'ReportsView';

    this.config = $.extend(true, {
      'templateUrl': '/cabinetUI/reports/getTemplates',
      'templates': { 
        reports: 'reports',
        reportsForm: 'reportsForm',
        reportsFormSubtype: 'reportsFormSubtype',
        reportsSchedule: 'reportsSchedule',
        // шаблоны для формы ввода детализации периода отправки отчета
        periodDetailDay: 'periodDetailDay',
        periodDetailDayMonth: 'periodDetailDayMonth'
      }
    },options);
  
    this.mds.tpl = {};

    this.$reports = $('#reports');

    this.Reports = this.setReports(this.$reports);

    this.init();
  };
  
  KT.addMixin(modView, 'Dispatcher');

  /** Инициализация */
  modView.prototype.init = function() {
    this.Reports.init();
  };

  modView.prototype.render = function() {
    this.Reports.render();
  };

  /**
  * Инициализация блока управления отчетами
  * @param {Object} $container - контейнер для размещения объекта
  * @return {Object} - объект управления отчетами
  */
  modView.prototype.setReports = function($container) {
    var _instance = this;

    var Reports = {
      elem: {
        $container: $container,
        $reportsForm: null,
        $reportsSchedule: null,
        // элементы формы
        formcontrols: {}
      },
      // типы отчетов
      reportTypes: {
        1: {
          'name': 'Отчет по бронированиям',
          'group': 'client',
          'subtypes': {
            '1': {
              'name': 'по датам создания заявки'
            },
            '2': {
              'name': 'по датам начала путешествия'
            }
          }
        },
        2: {
          'name': 'Отчет по нарушению КП',
          'group': 'client',
          'subtypes': {
            '1': {
              'name': 'по датам создания заявки'
            },
            '2': {
              'name': 'по датам начала путешествия'
            }
          }
        },
        4: {
          'name': 'Отчет MSR (авиа)',
          'group': 'client',
          'subtypes': {
            '1': {
              'name': 'по датам создания заявки'
            },
            '2': {
              'name': 'по датам начала путешествия'
            }
          }
        },
        6: {
          'name': 'Отчет по проживанию',
          'group': 'client'
        },
        7: {
          'name': 'Отчет по авиабилетам',
          'group': 'client'
        }
      },
      // периоды отчета
      schedulePeriods: {
        1: {
          'key': 'daily',
          'name': 'Ежедневно',
          'periodDetail': null
        },
        2: {
          'key': 'weekly',
          'name': 'Еженедельно',
          'periodDetail': {
            template: 'periodDetailDay',
            initControls: function($container) {
              var weekdays = moment.weekdays();
              weekdays.push(weekdays.shift());
              var weekdayOptions = weekdays.map(function(day, idx) {
                return {
                  'dayId': ++idx,
                  'name': day
                };
              });

              this.$daySelect = $container.find('.js-reports-form--period-day');
              this.$daySelect.selectize({
                openOnFocus: true,
                allowEmptyOption: false,
                create: false,
                selectOnTab: true,
                valueField: 'dayId',
                labelField: 'name',
                maxItems: 1,
                options: weekdayOptions,
                items: [weekdayOptions[0].dayId]
              });
            },
            getValue: function() {
              return this.$daySelect[0].selectize.getValue();
            }
          }
        },
        3: {
          'key': 'monthly',
          'name': 'Ежемесячно',
          'periodDetail': {
            template: 'periodDetailDay',
            initControls: function($container) {
              var monthDayOptions = [];
              for (var i = 1; i < 31; i++) {
                monthDayOptions.push({'value': i});
              }

              this.$daySelect = $container.find('.js-reports-form--period-day');
              this.$daySelect.selectize({
                openOnFocus: true,
                allowEmptyOption: false,
                create: false,
                selectOnTab: true,
                valueField: 'value',
                labelField: 'value',
                maxItems: 1,
                options: monthDayOptions,
                items: [monthDayOptions[0].value]
              });
            },
            getValue: function() {
              return this.$daySelect[0].selectize.getValue();
            }
          }
        },
        4: {
          'key': 'quarterly',
          'name': 'Ежеквартально',
          'periodDetail': {
            template: 'periodDetailDayMonth',
            initControls: function($container) {
              var monthOptions = [
                {'value': 1, 'name': 'Первый'},
                {'value': 2, 'name': 'Второй'},
                {'value': 3, 'name': 'Третий'}
              ];

              var monthDayOptions = [];
              for (var i = 1; i < 31; i++) {
                monthDayOptions.push({'value': i});
              }

              this.$monthSelect = $container.find('.js-reports-form--period-month');
              this.$daySelect = $container.find('.js-reports-form--period-day');

              this.$monthSelect.selectize({
                openOnFocus: true,
                allowEmptyOption: false,
                create: false,
                selectOnTab: true,
                valueField: 'value',
                labelField: 'name',
                maxItems: 1,
                options: monthOptions,
                items: [monthOptions[0].value]
              });

              this.$daySelect.selectize({
                openOnFocus: true,
                allowEmptyOption: false,
                create: false,
                selectOnTab: true,
                valueField: 'value',
                labelField: 'value',
                maxItems: 1,
                options: monthDayOptions,
                items: [monthDayOptions[0].value]
              });
            },
            getValue: function() {
              var month = this.$monthSelect[0].selectize.getValue();
              var day = this.$daySelect[0].selectize.getValue();
              return [day, month].join('.');
            }
          }
        },
        5: {
          'key': 'annually',
          'name': 'Ежегодно',
          'periodDetail': {
            template: 'periodDetailDayMonth',
            initControls: function($container) {
              var monthOptions = moment.months().map(function(month, idx) {
                return {
                  'monthId': ++idx,
                  'name': month
                };
              });

              /* 0-based month index */
              function getMonthDayOptions(month) {
                var daysInMonth = moment().month(month).daysInMonth();
                var monthDayOptions = [];
                for (var i = 1; i < (daysInMonth + 1); i++ ) {
                  monthDayOptions.push({'value': i});
                }
                return monthDayOptions;
              }

              this.$monthSelect = $container.find('.js-reports-form--period-month');
              this.$daySelect = $container.find('.js-reports-form--period-day');

              var self = this;

              this.$monthSelect.selectize({
                openOnFocus: true,
                allowEmptyOption: false,
                create: false,
                selectOnTab: true,
                valueField: 'monthId',
                labelField: 'name',
                maxItems: 1,
                options: monthOptions,
                items: [monthOptions[0].monthId],
                onItemAdd: function(idx) {
                  var daySelect = self.$daySelect[0].selectize;
                  daySelect.clearOptions();
                  daySelect.addOption(getMonthDayOptions(idx - 1));
                  daySelect.addItem(1);
                }
              });

              this.$daySelect.selectize({
                openOnFocus: true,
                allowEmptyOption: false,
                create: false,
                selectOnTab: true,
                valueField: 'value',
                labelField: 'value',
                maxItems: 1,
                options: getMonthDayOptions(0),
                items: [1]
              });
            },
            getValue: function() {
              var month = this.$monthSelect[0].selectize.getValue();
              var day = this.$daySelect[0].selectize.getValue();
              return [day, month].join('.');
            }
          }
        }
      },
      // флаг, указывающий на возможность выбора компании для отчета
      allowCompanySelect: false,
      /** Инициализация */
      init: function() {
        this.allowCompanySelect = (KT.profile.userType === 'op');

        var self = this;

        this.reportTypesList = Object.keys(this.reportTypes).map(function(type) {
          return $.extend(true, {
            'reportType': type
          }, self.reportTypes[type]);
        });

        this.schedulePeriodsList = Object.keys(this.schedulePeriods).map(function(periodId) {
          return $.extend(true, {
            'periodId': periodId
          }, self.schedulePeriods[periodId]);
        });
      },
      /** Отрисовка */
      render: function() {
        var $reports = $(Mustache.render(_instance.mds.tpl.reports, {}));

        this.elem.$reportsForm = $reports.find('#reports-form');
        this.elem.$reportsSchedule = $reports.find('#reports-schedule');

        this.elem.$reportsForm.html(Mustache.render(_instance.mds.tpl.reportsForm, {
          'allowCompanySelect': this.allowCompanySelect,
          'email': KT.profile.user.email
        }));

        this.elem.$container.html($reports);

        this.initFormControls();
      },
      /** Инициализация элементов формы создания отчета */
      initFormControls: function() {
        var $form = this.elem.$reportsForm;
        var controls = this.elem.formcontrols;

        controls.$companySelect = $form.find('.js-reports-form--company');
        controls.$allCompaniesSwitch = $form.find('.js-reports-form--all-companies-switch');
        controls.$reportType = $form.find('.js-reports-form--report-type');
        controls.$reportTypeConfigContainer = $form.find('.js-reports-form--report-type-config');
        controls.$dateFrom = $form.find('.js-reports-form--date-from');
        controls.$dateTo = $form.find('.js-reports-form--date-to');
        controls.$email = $form.find('.js-reports-form--email');
        controls.$period = $form.find('.js-reports-form--period');
        controls.$periodDetailContainer = $form.find('.js-reports-form--period-detail-container');
        controls.$reportFormat = $form.find('.js-reports-form--file-type');

        controls.$saveReportBtn = $form.find('.js-travel-policy-form--action-save');
        controls.$addTaskBtn = $form.find('.js-travel-policy-form--action-add-task');
        controls.$saveReportLoader = $form.find('.js-travel-policy-form--action-save-loader');
        controls.$addTaskLoader = $form.find('.js-travel-policy-form--action-add-task-loader');

        controls.$companySelect.selectize({
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
          sortField: 'seqid',
          maxItems: 1,
          options: [],
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
    
            KT.Dictionary.getAsList('companies', {
                'textFilter': query,
                'fieldsFilter': [],
                'lang': 'ru'
              })
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
          },
          onItemAdd: function(companyId) {
            _instance.dispatch('companySelected', this.options[companyId]);
          },
          onItemRemove: function() {
            _instance.dispatch('companySelected');
          },
          onClear: function() {
            _instance.dispatch('companySelected');
          }
        });

        controls.$allCompaniesSwitch.on('change', function() {
          var companySelectControl = controls.$companySelect[0].selectize;
          
          if ($(this).prop('checked')) {
            companySelectControl.clear();
            companySelectControl.disable();
          } else {
            companySelectControl.enable();
          }
        });

        // функция вывода блока выбора подтипа отчета
        function renderReportSubtypeSelect(reportTypeConfig) {
          controls.$reportTypeConfigContainer.html(
            Mustache.render(_instance.mds.tpl.reportsFormSubtype, {})
          );

          var subtypes = Object.keys(reportTypeConfig.subtypes).map(function(subtype) {
            return {
              'subtype': subtype,
              'name': reportTypeConfig.subtypes[subtype].name
            };
          });

          controls.$reportTypeConfigContainer.find('.js-reports-form--report-subtype')
            .selectize({
              openOnFocus: true,
              allowEmptyOption: false,
              create: false,
              selectOnTab: true,
              valueField: 'subtype',
              labelField: 'name',
              maxItems: 1,
              options: subtypes,
              items: [subtypes[0].subtype],
            });
        }

        controls.$reportType.selectize({
          openOnFocus: true,
          allowEmptyOption: false,
          create: false,
          selectOnTab: true,
          valueField: 'reportType',
          labelField: 'name',
          maxItems: 1,
          options: this.reportTypesList,
          items: [this.reportTypesList[0].reportType],
          onItemAdd: function(value) {
            var reportType = this.options[value];
            if (reportType.subtypes !== undefined) {
              renderReportSubtypeSelect(reportType);
            } else {
              controls.$reportTypeConfigContainer.empty();
            }
          }
        });

        if (this.reportTypesList[0].subtypes !== undefined) {
          renderReportSubtypeSelect(this.reportTypesList[0]);
        }

        controls.$dateFrom.clndrize({
          'template': KT.tpl.clndrDatepicker,
          'eventName': 'Дата начала отчета',
          'showDate': moment()
        });
        
        controls.$dateTo.clndrize({
          'template': KT.tpl.clndrDatepicker,
          'eventName': 'Дата окончания отчета',
          'showDate': moment()
        });

        var schedulePeriods = this.schedulePeriods;
        function renderPeriodDetail(periodType) {
          var periodConfig = schedulePeriods[periodType];

          if (periodConfig.periodDetail === null) {
            controls.$periodDetailContainer.empty();
          } else {
            controls.$periodDetailContainer.html(
              Mustache.render(_instance.mds.tpl[periodConfig.periodDetail.template], {})
            );
            periodConfig.periodDetail.initControls(controls.$periodDetailContainer);
          }
        }

        controls.$period.selectize({
          openOnFocus: true,
          allowEmptyOption: false,
          create: false,
          selectOnTab: true,
          valueField: 'periodId',
          labelField: 'name',
          maxItems: 1,
          options: this.schedulePeriodsList,
          items: [this.schedulePeriodsList[0].periodId],
          onItemAdd: function(periodType) {
            renderPeriodDetail(periodType);
          }
        });
      },
      /** 
      * Управление отображением процесса создания отчета 
      * @param {Boolean} inProcess - флаг состояния процесса (в процессе/остановлено)
      */
      toggleReportSavingLoader: function(inProcess) {
        var controls = this.elem.formcontrols;

        if (inProcess) {
          controls.$saveReportLoader.html(
            Mustache.render(KT.tpl.spinner, {type: 'inline'})
          );
          controls.$saveReportBtn.prop('disabled', true);
        } else {
          controls.$saveReportLoader.empty();
          controls.$saveReportBtn.prop('disabled', false);
        }
      },
      /** 
      * Управление отображением процесса создания задачи по отправке отчета
      * @param {Boolean} inProcess - флаг состояния процесса (в процессе/остановлено)
      */
      toggleTaskAddingLoader: function(inProcess) {
        var controls = this.elem.formcontrols;

        if (inProcess) {
          controls.$addTaskLoader.html(
            Mustache.render(KT.tpl.spinner, {type: 'inline'})
          );
          controls.$addTaskBtn.prop('disabled', true);
        } else {
          controls.$addTaskLoader.empty();
          controls.$addTaskBtn.prop('disabled', false);
        }
      },
      /**
      * Отображение процесса загрузки расписания отчетов
      */
      showScheduleLoading: function() {
        this.elem.$reportsSchedule.html(Mustache.render(KT.tpl.spinner, {}));
      },
      /**  
      * Возвращает данные формы для создания отчета
      * @param {Boolean} [suppressDates] - если указано, возвращаются данные без дат отчета
      * @return {Object} - конфигурация отчета
      */
      getReportFormData: function(suppressDates) {
        var controls = this.elem.formcontrols;

        var reportConfig = {};
        var errors = false;

        if (KT.profile.userType === 'op') {
          if (controls.$allCompaniesSwitch.prop('checked')) {
            reportConfig.companyId = null;
          } else {
            reportConfig.companyId = controls.$companySelect[0].selectize.getValue();
            if (reportConfig.companyId === '') {
              makeInvalid(controls.$companySelect[0].selectize.$control);
              errors = true;
            }
          }
        } else {
          reportConfig.companyId = KT.profile.companyId;
        }

        reportConfig.reportType = controls.$reportType[0].selectize.getValue();
        if (reportConfig.reportType === '') {
          makeInvalid(controls.$reportType[0].selectize.$control);
          return false;
        }

        if (this.reportTypes[reportConfig.reportType].subtypes !== undefined) {
          var reportSubTypeControl = controls.$reportTypeConfigContainer
            .find('.js-reports-form--report-subtype')[0].selectize;

          reportConfig.reportConstructType = reportSubTypeControl.getValue();
          if (reportConfig.reportConstructType === '') {
            reportSubTypeControl.$control
              .addClass('error')
              .on('focusin', function() { $(this).removeClass('error'); });
            errors = true;
          }
        }

        if (controls.$dateFrom.val() === '') {
          if (!suppressDates) {
            makeInvalid(controls.$dateFrom);
            errors = true;
          } else {
            reportConfig.dateFrom = moment().format('YYYY-MM-DD');
          }
        } else {
          reportConfig.dateFrom = moment(controls.$dateFrom.val(), 'DD.MM.YYYY').format('YYYY-MM-DD');
        }
        
        if (controls.$dateTo.val() === '') {
          if (!suppressDates) {
            makeInvalid(controls.$dateTo);
            errors = true;
          } else {
            reportConfig.dateTo = moment().format('YYYY-MM-DD');
          }
        } else {
          reportConfig.dateTo = moment(controls.$dateTo.val(), 'DD.MM.YYYY').format('YYYY-MM-DD');
        }

        reportConfig.email = controls.$email.val();
        if (reportConfig.email === '') {
          makeInvalid(controls.$email);
          errors = true;
        }

        reportConfig.outFormat = controls.$reportFormat.filter(':checked').val();

        return (errors) ? false : reportConfig;
      },
      /**
      * Возвращает данные формы для создания задачи периодической отправки 
      * @return {Object} - конфигурация задачи
      */
      getReportTaskData: function() {
        var controls = this.elem.formcontrols;
        var reportConfig = this.getReportFormData(true);

        if (reportConfig === false) { return false; }

        var period = controls.$period[0].selectize.getValue();
        if (period === '') {
          makeInvalid(controls.$period[0].selectize.$control);
          return false;
        }

        var periodDetail = null;
        var periodConfig = this.schedulePeriods[period];

        if (periodConfig.periodDetail !== null) {
          periodDetail = periodConfig.periodDetail.getValue();
        }

        var taskConfig = {
          'companyId': reportConfig.companyId,
          'taskName': this.reportTypes[reportConfig.reportType].name,
          'period': period,
          'periodDetail': periodDetail,
          'taskOperation': 'CreateReport',
          'taskService': 'orderService',
          'taskParams': reportConfig
        };

        return taskConfig;
      },
      /**
      * Рендер расписания запланированных отчетов
      * @param {ReportsStorage} ReportsStorage - хранилище расписания отчетов 
      */
      renderReportsSchedule: function(ReportsStorage) {
        var self = this;

        var tasks = ReportsStorage.getReportsSchedule().map(function(task) {
          task.periodName = self.schedulePeriods[task.period].name;
          var reportType = self.reportTypes[task.taskParams.reportType];

          if (reportType === undefined) {
            console.error('Неизвестный тип отчета: ' + task.taskParams.reportType);
            return null;
          }

          try {
          task.taskParams.reportTypeName = (reportType.subtypes === undefined) ? 
            reportType.name :
            [
              reportType.name, 
              reportType.subtypes[task.taskParams.reportConstructType].name
            ].join(' ');
          } catch (e) {
            console.error(
              'Тип отчета: ' + task.taskParams.reportType + 
              ', неизвестный подтип отчета: ' + task.taskParams.reportConstructType
            );
            return null;
          }

          return task;
        }).filter(function(task) { return task !== null; });

        this.elem.$reportsSchedule.html(Mustache.render(_instance.mds.tpl.reportsSchedule, {
          'schedule': tasks,
          'company': (!this.allowCompanySelect) ? null : {
            'name': ReportsStorage.companyName
          }
        }));
      }
    };

    return Reports;
  };  


  /**
  * Отобразить ошибку значения в поле
  * @param {Object} $el - [jQuery DOM] поле формы
  */
  function makeInvalid($el) {
    $el
      .addClass('error')
      .on('focusin', function() { 
        $(this).removeClass('error'); 
      });
  }

  return modView;
}));
