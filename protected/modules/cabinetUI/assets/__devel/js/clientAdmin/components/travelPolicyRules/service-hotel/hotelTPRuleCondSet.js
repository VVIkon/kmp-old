/* Набор правил корпоративных политик для проживания */
(function(global,factory) {
  
      KT.crates.ClientAdmin.hotelTPRuleCondSet = factory(KT.crates.ClientAdmin);
      
  }(this, function(crate) {
    'use strict';
    
    var TPRuleCond = crate.TPRuleCond;
    var TPRuleCommonConds = crate.TPRuleCommonConds;

    /*==========Классы условий корпоративных политик===========*/
    
    // Стоимость за ночь
    var nightPriceCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['>=','<='];
      this.dataField = 'nightPrice';
      this.template = 'PriceTPRC';
    });
    nightPriceCond.prototype.condName = 'Стоимость за ночь';

    // Город проживания
    var cityCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=', '!='];
      this.dataField = 'city';
      this.template = 'OptionListTPRC';
    });
    cityCond.prototype.condName = 'Город проживания';

    cityCond.prototype.initControl = function() {
      var cond = this;

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
                KT.apiClient.getLocationById(cond.value, 'hotel')
                  .then(function(locations) {
                    callback(locations);
                    self.addItem(cond.value);
                  });
              });
            }
          }
        });
    };

    // Страна проживания
    var countryCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=', '!='];
      this.dataField = 'country';
      this.template = 'OptionListTPRC';
    });
    countryCond.prototype.condName = 'Страна проживания';

    countryCond.prototype.initControl = function() {
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

    // Категория отеля
    var hotelCategoryCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=', '<=', '>='];
      this.dataField = 'hotelCategory';
      this.template = 'OptionListTPRC';

      this.optionList = [
        {value: 1, name: '1* звезда'},
        {value: 2, name: '2* звезды'},
        {value: 3, name: '3* звезды'},
        {value: 4, name: '4* звезды'},
        {value: 5, name: '5* звезд'},
      ];
    });
    hotelCategoryCond.prototype.condName = 'Категория отеля';

    hotelCategoryCond.prototype.initControl = function() {
      this.$container.find('.js-travel-policy-rule-cond--control')
        .selectize({
          openOnFocus: true,
          allowEmptyOption: false,
          create: false,
          selectOnTab: true,
          valueField: 'value',
          labelField: 'name',
          maxItems: 1,
          options: this.optionList,
          items: (this.value !== undefined) ? [this.value] : []
        });
    };

    // Отельная сеть
    var hotelChainIdCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=', '!='];
      this.dataField = 'hotelChainId';
      this.template = 'OptionListTPRC';
    });
    hotelChainIdCond.prototype.condName = 'Отельная сеть';

    hotelChainIdCond.prototype.initControl = function() {
      var cond = this;

      this.$container.find('.js-travel-policy-rule-cond--control')
        .selectize({
          openOnFocus: true,
          allowEmptyOption: false,
          create: false,
          selectOnTab: true,
          valueField: 'hotelChainId',
          labelField: 'name',
          maxItems: 1,
          options: [],
          items: [],
          onInitialize: function() {
            var self = this;
            this.load(function(callback) {
              KT.Dictionary.getAsList('hotelChains')
                .then(function(hotelChains) {
                  callback(hotelChains);
                  if (cond.value !== undefined) {
                    self.addItem(cond.value);
                  }
                });
            });
          }
        });
    };

    // Название отеля
    var hotelNameCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['IN','NOT IN'];
      this.dataField = 'hotelName';
      this.template = 'OptionListTPRC';
    });
    hotelNameCond.prototype.condName = 'Название отеля'; 
    
    hotelNameCond.prototype.initControl = function() {
      this.$container.find('.js-travel-policy-rule-cond--control')
        .selectize({
          openOnFocus: true,
          selectOnTab: true,
          createOnBlur: true,
          highlight: false,
          maxItems: 10,
          loadThrottle: 300,
          valueField: 'hotel',
          labelField: 'hotel',
          searchField: 'hotel',
          options: (!Array.isArray(this.value)) ? [] : 
            this.value.map(function(name) {
              return {hotel: name};
            }),
          items: (Array.isArray(this.value)) ? this.value : [],
          render: {
            option_create: function(data, escape) {
              return '<div class="create">Добавить <strong>' + escape(data.input) + '</strong>&hellip;</div>';
            }
          },
          create: function(input) {
            return {
              'hotel': input
            };
          },
          load: function(query, callback) {
            var self = this;
            self.clearOptions();

            if (!query.length || query.length < 2) {
              return callback();
            }

            self.currentQuery = query;

            KT.apiClient.getHotelSuggest(query)
              .then(function(response) {
                if (response.query !== self.currentQuery) {
                  return callback();
                } else {
                  self.$wrapper.removeClass(self.settings.loadingClass);
                }

                if (!Array.isArray(response.body.hotels)) {
                  callback();
                } else {
                  callback(response.body.hotels);
                }
              })
              .fail(function(query) {
                if (query === self.currentQuery) {
                  callback();
                }
              });
          },
          onItemRemove: function() {
            this.clearOptions();
          },
          onInitialize: function() {
            var self = this;
            
            this.$control.on('click','.item',function() {
              self.removeItem($(this).attr('data-value'));
              self.refreshOptions(true);
            });
          }
        });
    };

    // Мест в номере
    var placesInRoomCond = TPRuleCond.extend(function() {
      TPRuleCond.apply(this, arguments);

      this.name = this.condName;
      this.operators = ['=','>=', '<='];
      this.dataField = 'placesInRoom';
      this.template = 'NumberTPRC';
    });
    placesInRoomCond.prototype.condName = 'Мест в номере'; 


    /*================Набор условий==================*/

    var hotelTPRuleCondSet = {
      '0': { // поиск
        'offerPrice': TPRuleCommonConds.offerPriceCond,
        'nightPrice' : nightPriceCond,
        'city' : cityCond,
        'country': countryCond,
        'hotelCategory': hotelCategoryCond,
        'hotelChainId': hotelChainIdCond,
        'hotelName': hotelNameCond,
        'placesInRoom': placesInRoomCond
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

    return hotelTPRuleCondSet;
  }));