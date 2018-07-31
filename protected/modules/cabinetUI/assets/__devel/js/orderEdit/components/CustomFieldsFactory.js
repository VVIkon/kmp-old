/* Фабрика обработчиков дополнительных полей */
/* Внимание! есть отличия от аналогичного файла в модуле ClientAdmin  */
(function(global,factory) {

    KT.crates.OrderEdit.CustomFieldsFactory = factory();
    
}(this, function() {

  /** 
  * Базовый класс кастомных полей
  * @param {Object} fieldConfig - конфиг поля
  * @param {String} tpl - строка шаблона Mustache
  */
  var CustomField = function(fieldConfig) {
    this.fieldTypeId = fieldConfig.fieldTypeId;
    this.typeTemplate = fieldConfig.typeTemplate;
    this.fieldTypeName = fieldConfig.fieldTypeName;

    this.required = fieldConfig.require;
    this.modifiable = fieldConfig.modifyAvailable;

    this.valueOptions = fieldConfig.availableValueList;
    this.value = fieldConfig.value;

    // весь блок элемента управления
    this.$field = null;
    // сам элемент (input, select, etc.)
    this.$control = null;
    // элемент для сигнализации ошибки
    this.$errorTarget = null;
    // элемент, получающий фокус
    this.$focusTarget = null;
  };

  /** 
  * Генерирует html-код поля
  * @return {Object} [jQuery DOM] html-код поля
  */
  CustomField.prototype.render = function() {
    this.$field = $(Mustache.render(this.tpl, {
      'fieldTypeId': this.fieldTypeId,
      'fieldTypeName': this.fieldTypeName,
      'required': this.required
    }));

    this.$control = this.$field.find('.js-custom-field--control');

    if (!this.modifiable) {
      this.$control.prop('disabled', true);
    }

    return this.$field;
  };

  /** Инициализация поля */
  CustomField.prototype.initialize = function() { throw new Error('CustomField:init not implemented'); };

  /** 
  * Получение значения поля 
  * @return {*} -значение поля
  */
  CustomField.prototype.getValue = function() {
    if (this.modifiable) {
      return this.$control.val();
    } else {
      return this.value;
    }
  };

  /** 
  * Проверка значения поля. Если не пройдена, поле отмечается классом ошибки
  * @param {*} [fieldValue] - если указано значение, проверяется оно, иначе сначала будет вызван getValue()
  * @return {Boolean} результат проверки
  */
  CustomField.prototype.validate = function(fieldValue) {
    if (fieldValue === undefined) {
      fieldValue = this.getValue();
    }

    if (fieldValue === '') {
      var self = this;

      if (this.required) {
        self.$errorTarget.addClass('error');
        self.$focusTarget.one('focus change', function() {
          self.$errorTarget.removeClass('error');
        });
        return false;
      } else {
        return true;
      }
    } else {
      return true;
    }
  };

  /** Механизм наследования */
  CustomField.extend = function (cfunc) {
    cfunc.prototype = Object.create(this.prototype);
    cfunc.prototype.ancestor = this.prototype;
    cfunc.constructor = cfunc;
    return cfunc;
  };

  /*=================================
  *  Фабрика кастомных полей
  *=================================*/
  /** Список обработчиков доп. полей */
  var fieldTypeMap;

  /**
  * @param {Object} tpl - список шаблонов 
  */
  var CustomFieldsFactory = function(tpl) {
    this.tpl = tpl;
  };
    
  /**
  * Создает объект управления дополнительным полем
  * @param {Object} fieldConfig - конфигурация поля (sk_user_corporate_fields)
  */
  CustomFieldsFactory.prototype.create = function(fieldConfig) {
    if (fieldTypeMap.hasOwnProperty(fieldConfig.typeTemplate)) {
      return new fieldTypeMap[fieldConfig.typeTemplate](fieldConfig, this.tpl);
    } else {
      return null;
    }
  };

  /*=================================
  *  Классы полей
  *=================================*/

  /* Строка текста */
  var TextCF = CustomField.extend(function(fieldConfig, tpl) {
    CustomField.call(this, fieldConfig);
    this.tpl = tpl['TextCF'];
  });

  TextCF.prototype.initialize = function() {
    if (!Array.isArray(this.valueOptions)) {
      this.$control
        .jirafize({
          position: 'left',
          buttons: {
            name: 'clear',
            type: 'reset',
            callback: function($el) { $el.val('').change(); }
          }
        });

      this.$errorTarget = this.$control;
      this.$focusTarget = this.$control;

      if (this.value !== null) {
        this.$control.val(this.value);
      }
    } else {
      this.$control
        .selectize({
          openOnFocus: true,
          create: false,
          maxItems: 1,
          options: this.valueOptions.map(function(option) {
              return {'value': option, 'label': option};
            }),
          selectOnTab:true,
          labelField:'label'
        });

      this.$errorTarget = this.$control[0].selectize.$control;
      this.$focusTarget = this.$control[0].selectize.$control_input;
      
      if (this.value !== null) {
        this.$control[0].selectize.addItem(this.value);
      }
      
      this.getValue = function() {
        return this.$control[0].selectize.getValue();
      };
    }
  };

  /* Текстовое поле */
  /** @todo сделать textarea, а не копию TextCF */
  var TextAreaCF = CustomField.extend(function(fieldConfig, tpl) {
    CustomField.call(this, fieldConfig);
    this.tpl = tpl['TextAreaCF'];
  });

  TextAreaCF.prototype.initialize = function() {
    this.$control
      .jirafize({
        position: 'left',
        buttons: {
          name: 'clear',
          type: 'reset',
          callback: function($el) { $el.val('').change(); }
        }
      });
      
    this.$errorTarget = this.$control;
    this.$focusTarget = this.$control;
    
    if (this.value !== null) {
      this.$control.val(this.value);
    }
  };

  /* Число */
  var NumberCF = CustomField.extend(function(fieldConfig, tpl) {
    CustomField.call(this, fieldConfig);
    this.tpl = tpl['NumberCF'];
  });

  NumberCF.prototype.initialize = function() {
    if (!Array.isArray(this.valueOptions)) {
      this.$control
        .jirafize({
          position: 'left',
          buttons: {
            name: 'clear',
            type: 'reset',
            callback: function($el) { $el.val('').change(); }
          }
        });

      this.$errorTarget = this.$control;
      this.$focusTarget = this.$control;

      if (this.value !== null) {
        this.$control.val(this.value);
      }

      this.$control.on('keypress', function(e) {
          var char = String.fromCharCode(e.which);
          var check = /[0-9.,]/;
          if (!check.test(char)) {
            return false;
          }
        });

      this.getValue = function() {
        var v = this.$control.val();
        if (v !== '') {
          return Number(String(v).replace(',','.'));
        } else {
          return v;
        }
      };
    } else {
      this.$control
        .selectize({
          openOnFocus: true,
          create: false,
          maxItems: 1,
          options: this.valueOptions.map(function(option) {
              return {'value': option, 'label': option};
            }),
          selectOnTab:true,
          labelField:'label'
        });

      this.$errorTarget = this.$control[0].selectize.$control;
      this.$focusTarget = this.$control[0].selectize.$control_input;
      
      if (this.value !== null) {
        this.$control[0].selectize.addItem(this.value);
      }

      this.getValue = function() {
        var v = this.$control[0].selectize.getValue();
        return (v !== '') ? Number(v) : v;
      };
    }
  };

  /* Дата */
  var DateCF = CustomField.extend(function(fieldConfig, tpl) {
    CustomField.call(this, fieldConfig);
    this.tpl = tpl['DateCF'];
  });

  DateCF.prototype.initialize = function() {
    if (this.value !== null && this.value !== undefined) {
      this.$control.val(moment(this.value,'YYYY-MM-DD').format('DD.MM.YYYY'));
    }

    this.$control
      .clndrize({
        'template': KT.tpl.clndrDatepicker,
        'eventName': 'Дата',
        'showDate': moment()
      });

    this.$errorTarget = this.$control;
    this.$focusTarget = this.$control;
    
    this.getValue = function() {
      var v = this.$control.val();
      return (v !== '') ? moment(v,'DD.MM.YYYY').format('YYYY-MM-DD') : v;
    };
  };

  /*=================================
  *  Маппинг типов полей
  *=================================*/
  fieldTypeMap = {
    1: TextCF,
    2: TextAreaCF,
    3: NumberCF,
    4: DateCF
  };

  return CustomFieldsFactory;

}));