/* Набор правил корпоративных политик для авиа */
(function(global,factory) {
  
      KT.crates.ClientAdmin.aviaTPRuleCondSet = factory(KT.crates.ClientAdmin);
      
  }(this, function(crate) {
    'use strict';
    
    var TPRuleCond = crate.TPRuleCond;
    var TPRuleCommonConds = crate.TPRuleCommonConds;

    /*==========Классы условий корпоративных политик===========*/

    // Количество пересадок
    var transferNumCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['>=','<='];
      this.dataField = 'transferNum';
      this.template = 'NumberTPRC';
    });
    transferNumCond.prototype.condName = 'Количество пересадок';
    
    // Пересадка между (временной интервал)
    var transferBtwTimeCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.name;
      this.operators = ['=','!='];
      this.dataField = 'transferBtwTime';
      this.template = 'HourIntervalHitTPRC';
    });
    transferBtwTimeCond.prototype.condName = 'Пересадка между (временной интервал)';

    // Время пересадки в интервале
    var transferBtwTimeDurationCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=','!=','>=','<='];
      this.dataField = 'transferBtwTimeDuration';
      this.template = 'HourIntervalIntersectionTPRC';
    });
    transferBtwTimeDurationCond.prototype.condName = 'Время пересадки в интервале';

    // Перелет междду указанным временем
    var flightOverTimeCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=','!='];
      this.dataField = 'flightOverTime';
      this.template = 'HourIntervalHitTPRC';
    });
    flightOverTimeCond.prototype.condName = 'Перелет между указанным временем';

    // Количество трипов
    var tripNumCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=', '>=','<='];
      this.dataField = 'tripNum';
      this.template = 'NumberTPRC';
    });
    tripNumCond.prototype.condName = 'Количество трипов';

    // Класс перелета
    var flightClassCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['IN', 'NOT IN'];
      this.dataField = 'flightClass';
      this.template = 'OptionListTPRC';

      this.optionList = [
        {value: 'ECONOMY', name: 'Эконом'},
        {value: 'BUSINESS', name: 'Бизнес'},
        {value: 'FIRST', name: 'Первый'}
      ];
    });
    flightClassCond.prototype.condName = 'Класс перелета';

    flightClassCond.prototype.initControl = function() {
      this.$container.find('.js-travel-policy-rule-cond--control')
        .selectize({
          openOnFocus: true,
          allowEmptyOption: false,
          create: false,
          selectOnTab: true,
          valueField: 'value',
          labelField: 'name',
          searchField: 'name',
          maxItems: 10,
          options: this.optionList,
          items: (Array.isArray(this.value)) ? this.value : [],
          onInitialize: function() {
            var self = this;
            this.$control.on('click', '.item', function() {
              self.removeItem($(this).attr('data-value'));
              self.refreshOptions(true);
            });
          }
        });
    };

    // Подкласс перелета
    var subFlightClassCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['IN', 'NOT IN'];
      this.dataField = 'subFlightClass';
      this.template = 'OptionListTPRC';

      this.optionList = [
        'W', 'S', 'Y', 'B', 'H', 'K', 'L',
        'M', 'N', 'Q', 'T', 'X', 'O', 'V',
        'G', 'J', 'C', 'D', 'Z', 'I', 'P', 'F', 'A', 'R'
      ].map(function(code) { return {value: code}; });
    });
    subFlightClassCond.prototype.condName = 'Подкласс перелета';

    subFlightClassCond.prototype.initControl = function() {
      this.$container.find('.js-travel-policy-rule-cond--control')
        .selectize({
          openOnFocus: true,
          allowEmptyOption: false,
          create: false,
          selectOnTab: true,
          valueField: 'value',
          labelField: 'value',
          searchField: 'value',
          maxItems: 10,
          options: this.optionList,
          items: (Array.isArray(this.value)) ? this.value : [],
          onInitialize: function() {
            var self = this;
            this.$control.on('click', '.item', function() {
              self.removeItem($(this).attr('data-value'));
              self.refreshOptions(true);
            });
          }
        });
    };

    // Общее время перелета
    var flightDurationCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=', '>=', '<='];
      this.dataField = 'flightDuration';
      this.template = 'NumberTPRC';
      this.placeholder = 'Время (в минутах)';
    });
    flightDurationCond.prototype.condName = 'Общее время перелета';
    
    // Страна направления первого трипа
    var firstTripCountryCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=', '!='];
      this.dataField = 'firstTripCountry';
      this.template = 'OptionListTPRC';
    });
    firstTripCountryCond.prototype.condName = 'Страна направления первого трипа';

    firstTripCountryCond.prototype.initControl = function() {
      var cond = this;

      this.$container.find('.js-travel-policy-rule-cond--control')
        .selectize({
          openOnFocus: true,
          allowEmptyOption: false,
          create: false,
          selectOnTab: true,
          valueField: 'countryId',
          labelField: 'name',
          searchField: 'name',
          maxItems: 1,
          options: [],
          items: [],
          onInitialize: function() {
            var self = this;
            this.load(function(callback) {
              KT.Dictionary.getAsList('countries')
                .then(function(countries) {
                  callback(countries);
                  if (cond.value !== undefined) {
                    self.addItem(cond.value);
                  }
                });
            });
          }
        });
    };
    
    // Страна вылета первого трипа
    var firstTripDepartureCountryCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=', '!='];
      this.dataField = 'firstTripDepartureCountry';
      this.template = 'OptionListTPRC';
    });
    firstTripDepartureCountryCond.prototype.condName = 'Страна вылета первого трипа';

    firstTripDepartureCountryCond.prototype.initControl = function() {
      var cond = this;

      this.$container.find('.js-travel-policy-rule-cond--control')
        .selectize({
          openOnFocus: true,
          allowEmptyOption: false,
          create: false,
          selectOnTab: true,
          valueField: 'countryId',
          labelField: 'name',
          searchField: 'name',
          maxItems: 1,
          options: [],
          items: [],
          onInitialize: function() {
            var self = this;
            this.load(function(callback) {
              KT.Dictionary.getAsList('countries')
                .then(function(countries) {
                  callback(countries);
                  if (cond.value !== undefined) {
                    self.addItem(cond.value);
                  }
                });
            });
          }
        });
    };

    // Город прилета первого трипа
    var firstTripArrivalCityCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=', '!='];
      this.dataField = 'firstTripArrivalCity';
      this.template = 'OptionListTPRC';
    });
    firstTripArrivalCityCond.prototype.condName = 'Город прилета первого трипа';

    firstTripArrivalCityCond.prototype.initControl = function() {
      var cond = this;

      /** @todo сделать шаблоны отображения как в авиа? */
      this.$container.find('.js-travel-policy-rule-cond--control')
        .selectize({
          plugins: {
            'key_down': {start: 2},
            'on_blur': {start: 2},
            'jirafize': {completely: true}
          },
          openOnFocus: false,
          create: false,
          selectOnTab: true,
          highlight: false,
          maxItems: 1,
          loadThrottle: 300,
          valueField: 'cityId',
          labelField: 'city',
          sortField: 'seqid',
          score: function() {
            return function(item) {
              return 1000 / (item.seqid + 1);
            };
          },
          options: [],
          load: function(query, callback) {
            var self = this;
            self.clearOptions();

            if (!query.length || query.length < 2) {
              return callback();
            }

            self.currentQuery = query;

            KT.apiClient.getLocationSuggest(query, 'avia')
              .then(function(response) {
                if (response.query !== self.currentQuery) {
                  return callback();
                } else {
                  self.$wrapper.removeClass(self.settings.loadingClass);
                }
                var iataflag = false;

                if (Array.isArray(response.body.locations)) {
                  if (query.length === 3 && (/^[A-Z]{3}$/).test(query)) {
                    iataflag = true;
                  }

                  response.body.locations.forEach(function(location, i) {
                    location.seqid = i;
                    if (iataflag && location.cityIata === query) {
                      iataflag = location.cityId;
                    }
                  });
                }

                callback(response.body.locations);

                var $selinp = self.$control;

                if (Array.isArray(response.body.locations)) {
                  if (response.body.locations.length === 0) {
                    self.refreshOptions(true);
                    $selinp.addClass('warning');
                    setTimeout(function() {
                      $selinp.removeClass('warning');
                    }, 2000);
                  } else if (iataflag !== false) {
                    self.addItem(iataflag);
                  } else if (!self.$control_input.is(':focus')) {
                    self.addItem(response.body.locations[0].cityId);
                  } else if (response.body.locations.length === 1) {
                    self.oneitemflag = true;
                    self.addItem(response.body.locations[0].cityId);
                  }
                } else {
                  self.refreshOptions(true);
                  $selinp.addClass('warning');
                  setTimeout(function() {
                    $selinp.removeClass('warning');
                  }, 2000);
                }
              })
              .fail(function(query) {
                if (query === self.currentQuery) {
                  callback();
                }
              });
          },
          onType: function(str) {
            if (str.length < 2) {
              this.close();
              this.clearOptions();
            }
          },
          onItemRemove: function() {
            this.clearOptions();
          },
          onInitialize: function() {
            var self = this;
            if (cond.value !== undefined) {
              this.load(function(callback) {
                KT.apiClient.getLocationById(cond.value, 'avia')
                  .then(function(locations) {
                    callback(locations);
                    self.addItem(cond.value);
                  });
              });
            }
          }
        });
    };
    
    // Город вылета
    var departureCityCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=', '!='];
      this.dataField = 'departureCity';
      this.template = 'OptionListTPRC';
    });
    departureCityCond.prototype.condName = 'Город вылета';

    departureCityCond.prototype.initControl = function() {
      var cond = this;
      
      /** @todo сделать шаблоны отображения как в авиа? */
      this.$container.find('.js-travel-policy-rule-cond--control')
        .selectize({
          plugins: {
            'key_down': {start: 2},
            'on_blur': {start: 2},
            'jirafize': {completely: true}
          },
          openOnFocus: false,
          create: false,
          selectOnTab: true,
          highlight: false,
          maxItems: 1,
          loadThrottle: 300,
          valueField: 'cityId',
          labelField: 'city',
          sortField: 'seqid',
          score: function() {
            return function(item) {
              return 1000 / (item.seqid + 1);
            };
          },
          options: [],
          load: function(query, callback) {
            var self = this;
            self.clearOptions();

            if (!query.length || query.length < 2) {
              return callback();
            }

            self.currentQuery = query;

            KT.apiClient.getLocationSuggest(query, 'avia')
              .then(function(response) {
                if (response.query !== self.currentQuery) {
                  return callback();
                } else {
                  self.$wrapper.removeClass(self.settings.loadingClass);
                }
                var iataflag = false;

                if (Array.isArray(response.body.locations)) {
                  if (query.length === 3 && (/^[A-Z]{3}$/).test(query)) {
                    iataflag = true;
                  }

                  response.body.locations.forEach(function(location, i) {
                    location.seqid = i;
                    if (iataflag && location.cityIata === query) {
                      iataflag = location.cityId;
                    }
                  });
                }

                callback(response.body.locations);

                var $selinp = self.$control;

                if (Array.isArray(response.body.locations)) {
                  if (response.body.locations.length === 0) {
                    self.refreshOptions(true);
                    $selinp.addClass('warning');
                    setTimeout(function() {
                      $selinp.removeClass('warning');
                    }, 2000);
                  } else if (iataflag !== false) {
                    self.addItem(iataflag);
                  } else if (!self.$control_input.is(':focus')) {
                    self.addItem(response.body.locations[0].cityId);
                  } else if (response.body.locations.length === 1) {
                    self.oneitemflag = true;
                    self.addItem(response.body.locations[0].cityId);
                  }
                } else {
                  self.refreshOptions(true);
                  $selinp.addClass('warning');
                  setTimeout(function() {
                    $selinp.removeClass('warning');
                  }, 2000);
                }
              })
              .fail(function(query) {
                if (query === self.currentQuery) {
                  callback();
                }
              });
          },
          onType: function(str) {
            if (str.length < 2) {
              this.close();
              this.clearOptions();
            }
          },
          onItemRemove: function() {
            this.clearOptions();
          },
          onInitialize: function() {
            var self = this;
            if (cond.value !== undefined) {
              this.load(function(callback) {
                KT.apiClient.getLocationById(cond.value, 'avia')
                  .then(function(locations) {
                    callback(locations);
                    self.addItem(cond.value);
                  });
              });
            }
          }
        });
    };

    // Валидирующая авиакомпания
    var validatingAirlineCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=', 'IN', 'NOT IN'];
      this.dataField = 'validatingAirline';
      this.template = 'OptionListTPRC';
    });
    validatingAirlineCond.prototype.condName = 'Валидирующая авиакомпания';

    validatingAirlineCond.prototype.initControl = function() {
      var cond = this;

      this.$container.find('.js-travel-policy-rule-cond--control')
        .selectize({
          openOnFocus: true,
          allowEmptyOption: false,
          create: false,
          selectOnTab: true,
          valueField: 'code',
          labelField: 'name',
          searchField: 'name',
          maxItems: 10,
          options: [],
          items: [],
          render: {
            item: function(item) {
              return '<div class="item" data-tooltip="' + item.name + '">' + item.code + '</div>';
            }
          },
          onInitialize: function() {
            var self = this;

            this.$control.on('click','.item',function(){
              self.removeItem($(this).attr('data-value'));
              self.refreshOptions(true);
            });
              
            this.load(function(callback) {
              KT.Dictionary.getAsList('airlines')
                .then(function(airlines) {
                  callback(airlines);
                  if (Array.isArray(cond.value)) {
                    cond.value.forEach(function(item) {
                      self.addItem(item);
                    });
                  }
                });
            });
          }
        });
    };
    
    // Тип перелета
    var flightTypeCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=', '!='];
      this.dataField = 'flightType';
      this.template = 'OptionListTPRC';
    });
    flightTypeCond.prototype.condName = 'Тип перелета';

    flightTypeCond.prototype.initControl = function() {
      this.$container.find('.js-travel-policy-rule-cond--control')
        .selectize({
          openOnFocus: true,
          allowEmptyOption: false,
          create: false,
          selectOnTab: true,
          valueField: 'value',
          labelField: 'name',
          searchField: 'name',
          maxItems: 1,
          options: [
            {value: 'OW', name: 'В одну сторону'},
            {value: 'RT', name: 'Туда - обратно'},
            {value: 'MC', name: 'Сложный маршрут'}
          ],
          items: (this.value !== undefined) ? [this.value] : ['OW']
        });
    };

    /*================Набор условий==================*/

    var aviaTPRuleCondSet = {
      '0': { // поиск
        'offerPrice': TPRuleCommonConds.offerPriceCond,
        'transferNum' : transferNumCond,
        'transferBtwTime' : transferBtwTimeCond,
        'transferBtwTimeDuration': transferBtwTimeDurationCond,
        'flightOverTime': flightOverTimeCond,
        'tripNum': tripNumCond,
        'flightClass': flightClassCond,
        'subFlightClass': subFlightClassCond,
        'flightDuration': flightDurationCond,
        'firstTripCountry': firstTripCountryCond,
        'firstTripDepartureCountry': firstTripDepartureCountryCond,
        'firstTripArrivalCity' : firstTripArrivalCityCond,
        'departureCity' : departureCityCond,
        'validatingAirline' : validatingAirlineCond,
        'flightType' : flightTypeCond
      },
      '1': { // оформление
        'offerValue': TPRuleCommonConds.offerValueCond,
        'comparePriceWithMinimal': TPRuleCommonConds.comparePriceWithMinimalCond,
        'comparePricePercentWithMinimal': TPRuleCommonConds.comparePricePercentWithMinimalCond,
        'addFieldValue': TPRuleCommonConds.addFieldValueCond,
      },
      '2': { // создание услуги
        'minimalPrice': TPRuleCommonConds.minimalPriceCond
      }
    };

    return aviaTPRuleCondSet;
  }));