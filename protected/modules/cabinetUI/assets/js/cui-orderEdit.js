KT.crates.OrderEdit = {};
KT.crates.OrderEdit.services = {};
KT.crates.OrderEdit.tourists = {};
KT.crates.OrderEdit.payment = {};

KT.mdx.OrderEdit = {};
KT.mdx.OrderEdit.tpl = {};
KT.mdx.OrderEdit.services = {};
KT.mdx.OrderEdit.tourists = {};
KT.mdx.OrderEdit.payment = {};

/**
* Конфигурация документов туриста 
*/
(function(global, factory) {

    KT.config.touristDocuments = factory();

}(this, function() {
    /* Объект конифгурации документа */
    var DocConf = function(config) {
      $.extend(this, config); 
    };

    /* Формирование теста для полей ФИО в документе */
    DocConf.prototype.getTouristNameFullValidation = function() {
      return new RegExp( ('^('+this.touristNameValidation[0].source+')+$') );
    };
    /* Формирование теста для поля серии документа */
    DocConf.prototype.getDocumentSerialFullValidation = function() {
      return new RegExp(
          '^' + this.numberValidation[0].source +
          this.numberValidation[2] + '$'
      );
    };
    /* Формирование теста для поля номера документа */
    DocConf.prototype.getDocumentNumberFullValidation = function() {
      return new RegExp(
        '^' + this.numberValidation[1].source +
        this.numberValidation[3] + '$'
      );
    };

    /*
    * Внимание! формат количества символов исключительно в форме {\d} или {\d,\d},
    * см. функцию livecheckDocument в tourists/view.js
    */
    var documentConfig = {
      1: new DocConf({
        docname: 'Паспорт гражданина РФ',
        numberValidation: [/[0-9]/,/[0-9]/,'{4}','{6}'],
        touristNameValidation: [/[A-z-]/],
        hasExpiryDate: false,
      }),
      2: new DocConf({
        docname: 'Загран паспорт гражданина РФ',
        numberValidation: [/[0-9]/,/[0-9]/,'{2}','{7}'],
        touristNameValidation: [/[A-z-]/],
        hasExpiryDate: true,
      }),
      6: new DocConf({
        docname: 'Военный билет солдата (матроса, сержанта, старшины)',
        numberValidation: [/[А-я]/,/[0-9]/,'{2}','{7}'],
        touristNameValidation: [/[A-z-]/],
        hasExpiryDate: false,
      }),
      7: new DocConf({
        docname: 'Военный билет офицера',
        numberValidation: [/[А-я]/,/[0-9]/,'{2}','{7}'],
        touristNameValidation: [/[A-z-]/],
        hasExpiryDate: false,
      }),
      9: new DocConf({
        docname: 'Удостоверение личности моряка',
        numberValidation: [/[XIVLMCxivlmc]/,/[0-9]/,'{2}','{7}'],
        touristNameValidation: [/[A-z-]/],
        hasExpiryDate: true,
        lifetime: moment.duration(5, 'years')
      }),
      10: new DocConf({
        docname: 'Свидетельство о рождении',
        numberValidation: [/[А-я]|[XIVLMCxivlmc-]/,/[0-9]/,'{1,6}','{6}'],
        touristNameValidation: [/[A-z-]/],
        hasExpiryDate: false
      }),
      18: new DocConf({
        docname: 'Другой документ',
        numberValidation: [/./,/./,'{0,10}','{0,30}'],
        touristNameValidation: [/./],
        hasExpiryDate: false
      })
    };

    return documentConfig;
}));
$.extend(KT.notifications,{
  'bookingFailedNoTourists': {
    type:'warning',
    title:'Не удалось забронировать услугу',
    msg:'Вы еще не добавили туристов в заявку!',
    killtarget:'body',
    timeout:10000
  },
  'saveServiceFailedNoTourists': {
    type:'warning',
    title:'Не удалось сохранить услугу',
    msg:'Вы еще не добавили туристов в заявку!',
    killtarget:'body',
    timeout:10000
  },
  'saveServiceFailedIncorrectLoyality': {
    type:'warning',
    title:'Не удалось сохранить услугу',
    msg:'Некорректные данные программы лояльности',
    killtarget:'body',
    timeout:10000
  },
  'linkingTouristFailed': {
    type:'error',
    title:'Не удалось сохранить привязку!',
    msg:'',
    killtarget:'body'
  },
  'removingTouristFailed': {
    type:'error',
    title:'Не удалось удалить туриста!',
    msg:'',
    killtarget:'body'
  },
  'bookingFailed': {
    type:'error',
    title:'Бронирование не запустилось',
    msg:'Не удалось запустить процесс бронирования, обратитесь к менеджеру КМП для уточнения причин'
  },
  'bookingChangeFailed': {
    type:'error',
    title:'Не удалось изменить данные брони',
    msg:'Не удалось изменить данные брони, обратитесь к менеджеру КМП для уточнения причин'
  },
  'bookingCancelFailed': {
    type:'error',
    title:'Бронь не отменена',
    msg:'Не удалось отменить бронирование, обратитесь к менеджеру КМП для уточнения причин'
  },
  'issuingTicketsFailed': {
    type:'error',
    title:'Выписка билетов не удалась!',
    msg:'',
    killtarget:'body',
    timeout:10000
  },
  'saveTouristFailed': {
    type:'error',
    title:'Не удалось сохранить туриста',
    msg:'',
    killtarget:'body'
  },
  'loadingDocumentsFailed': {
    type:'error',
    title:'Не удалось загрузить документы',
    msg:'',
    killtarget:'body'
  },
  'loadingHistoryFailed': {
    type:'error',
    title:'Не удалось загрузить историю заявки',
    msg:'',
    killtarget:'body'
  },
  'settingDicountFailed': {
    type:'error',
    title:'Не удалось сохранить скидку',
    msg:''
  },
  'changingServiceDataFailed': {
    type:'error',
    title:'Не удалось изменить данные услуги',
    msg:''
  },
  'changingReservationDataFailed': {
    type:'error',
    title:'Не удалось изменить данные брони',
    msg:''
  },
  'changingTicketsDataFailed': {
    type:'error',
    title:'Не удалось изменить данные билетов',
    msg:''
  },
  'cancellingInvoiceFailed': {
    type:'error',
    title:'Не удалось отменить счет',
    msg:''
  },
  'savingUserFailed': {
    type:'error',
    title:'Не удалось сохранить сотрудника',
    msg:''
  },
  'savingCustomFieldsFailed': {
    type:'error',
    title:'Не удалось сохранить данные услуги',
    msg:''
  },
  'settingServiceToManualFailed': {
    type:'error',
    title:'Не удалось отправить запрос на изменение услуги менеджеру',
    msg:''
  },
  'AddingAdditionalServiceFailed': {
    type:'error',
    title:'Не удалось добавить дополнительную услугу',
    msg:''
  },
  'RemovingAdditionalServiceFailed': {
    type:'error',
    title:'Не удалось удалить дополнительную услугу',
    msg:''
  }
});

$.extend(KT.notifications,{
  'linkingTourists': {
    type:'success',
    title:'Выполняется привязка туристов...',
    msg:'',
    killtarget:'body',
    timeout:1000
  },
  'touristsLinked': {
    type:'success',
    title:'Привязка туристов прошла успешно!',
    msg:'',
    killtarget:'body'
  },
  'bookingChanged': {
    type:'success',
    title:'Данные брони успешно изменены',
    msg:''
  },
  'bookingStarted': {
    type:'success',
    title:'Бронирование началось!',
    msg:'Процесс бронирования успешно запущен'
  },
  'bookingFinished': {
    type:'success',
    title:'Бронирование завершено!',
    msg:'Услуга успешно забронирована'
  },
  'bookingCancelled': {
    type:'success',
    title:'Бронь отменена!',
    msg:'Отмена брони прошла успешно'
  },
  'ticketsIssued': {
    type:'success',
    title:'Выписка билетов совершена успешно!',
    msg:'Маршрутная квитанция отправлена на указанный e-mail',
    killtarget:'body',
    timeout:10000
  },
  'touristAdded': {
    type:'success',
    title:'Информация сохранена успешно!',
    msg:'Турист <b>{{name}}</b> добавлен в заявку',
    killtarget:'body'
  },
  'touristUpdated': {
    type:'success',
    title:'Информация сохранена успешно!',
    msg:'Информация по туристу <b>{{name}}</b> обновлена',
    killtarget:'body'
  },
  'touristRemoved': {
    type:'success',
    title:'Операция выполнена успешно!',
    msg:'Турист <b>{{name}}</b> удален из заявки',
    killtarget:'body'
  },
  'documentUploaded': {
    type:'success',
    title:'Документ успешно загружен!',
    msg:'',
    ontop:true,
    killtarget:'body'
  },
  'discountSet': {
    type:'success',
    title:'Скидка успешно выставлена!',
    msg:'Новый размер скидки: {{discount}}'
  },
  'reservationDataChanged': {
    type:'success',
    title:'Данные брони успешно изменены!',
    msg:''
  },
  'serviceDataChanged': {
    type:'success',
    title:'Данные услуги успешно изменены!',
    msg:''
  },
  'serviceStatusSet': {
    type:'success',
    title:'Статус услуги успешно изменен!',
    msg:''
  },
  'ticketsDataChanged': {
    type:'success',
    title:'Данные билетов успешно изменены!',
    msg:''
  },
  'invoiceCancelled': {
    type:'success',
    title:'Счет успешно отменен!',
    msg:''
  },
  'userSaved': {
    type:'success',
    title:'Данные сотрудника успешно сохранены!',
    msg:''
  },
  'customFieldsSaved': {
    type:'success',
    title:'Внесенные данные успешно сохранены!',
    msg:''
  },
  'serviceSetToManual': {
    type:'success',
    title:'Услуга передана менеджеру на редактирование',
    msg:''
  }
});

$.extend(KT.notifications,{
  'bookingFailedNoTourists': {
    type:'warning',
    title:'Не удалось забронировать услугу',
    msg:'Вы еще не добавили туристов в заявку!',
    killtarget:'body',
    timeout:10000
  },
  'saveServiceFailedNoTourists': {
    type:'warning',
    title:'Не удалось сохранить услугу',
    msg:'Вы еще не добавили туристов в заявку!',
    killtarget:'body',
    timeout:10000
  },
  'bookingTermsNotAccepted': {
    type:'warning',
    title:'Вы не можете начать бронирование',
    msg:'Необходимо подтвердить согласие с условиями бронирования',
    killtarget:'body',
    timeout:10000
  },
  'bookingChangeNotSupported': {
    type:'warning',
    title:'Невозможно изменить бронь',
    msg:'Поставщик не поддерживает изменение брони'
  },
  'bookingChangeProhibited': {
    type:'warning',
    title:'Невозможно изменить бронь',
    msg:'Поставщик отказал в изменении брони для данной услуги'
  },
  'notAllTouristsLinked': {
    type:'warning',
    title:'Вы не можете выполнить операцию',
    msg:'Необходимо прикрепить к услуге заявленное число туристов',
    killtarget:'body',
    timeout:10000
  },
  'touristLinkageNotAllowedByDocument': {
    type:'warning',
    title:'Туриста нельзя привязать к услуге',
    msg:'У туриста заканчивается срок действия документа',
    killtarget:'body',
    timeout:10000
  },
  'touristLinkageNotAllowedByAge': {
    type:'warning',
    title:'Данного туриста нельзя привязать к услуге',
    msg:'В эту услугу нельзя добавить еще одного туриста данной возрастной группы',
    killtarget:'body',
    timeout:10000
  },
  'incorrectTouristData': {
    type:'warning',
    title:'Некорретные данные!',
    msg:'Проверьте форму на правильность заполнения',
    killtarget:'body'
  },
  'uploadDocumentFailed': {
    type:'warning',
    title:'Не удалось загрузить документ',
    msg:'Сбой загрузки, попробуйте еще раз',
    ontop:true,
    killtarget:'body'
  },
  'uploadDocumentNotAllowedByFilesize': {
    type:'warning',
    title:'Не удалось загрузить документ',
    msg:'Размер файла превышает допустимое значение',
    ontop:true,
    killtarget:'body'
  },
  'pricesChanged': {
    type:'warning',
    title:'Изменились цены предложения',
    msg:'Для продолжения операции требуется подтверждение согласия с новыми ценами',
    ontop:true,
    killtarget:'body'
  },
  'waitTOSLoading': {
    type:'warning',
    title:'Документ еще загружается',
    msg:'Попробуйте открыть снова через несколько секунд',
    ontop:true,
    killtarget:'body'
  },
  'noManualEditForm': {
    type:'warning',
    title:'Нет формы для редактирования услуги',
    msg:'Для данной услуги не поддерживается ручное редактирование',
    ontop:true,
    killtarget:'body'
  },
  'reservationNumberNotSet': {
    type:'warning',
    title:'Не указан номер брони',
    msg:'Для создания брони необходимо указать номер',
    ontop:true,
    killtarget:'body'
  },
  'ticketNumberNotEntered': {
    type:'warning',
    title:'Не указан номер билета',
    msg:'Введите номер билета',
    ontop:true,
    killtarget:'body'
  },
  'ticketTouristNotSet': {
    type:'warning',
    title:'Не указан турист для создания билета',
    msg:'Укажите туриста',
    ontop:true,
    killtarget:'body'
  },
  'changingTicketNotSelected': {
    type:'warning',
    title:'Не выбран билет для редактирования',
    msg:'Выберите билет',
    ontop:true,
    killtarget:'body'
  },
  'notAllCustomFieldsSet': {
    type:'warning',
    title:'Не все обязательные поля заполнены',
    msg:'',
    ontop:true,
    killtarget:'body'
  },
  'customFieldsRevealed': {
    type:'warning',
    title:'Проверьте данные',
    msg:'У туриста есть незаполненные дополнительные поля',
    ontop:true,
    killtarget:'body'
  }
});

/* Фабрика обработчиков дополнительных полей */
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
    return this.$control.val();
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
    cfunc.__super__ = this.prototype;
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
    this.calendarTpl = tpl['clndr'];
  });

  DateCF.prototype.initialize = function() {
    if (this.value !== null) {
      this.$control.val(moment(this.value,'YYYY-MM-DD').format('DD.MM.YYYY'));
    }

    this.$control
      .clndrize({
        'template': this.calendarTpl,
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
(function(global, extendApiClient) {

    extendApiClient(KT.apiClient);

}(this,function(ApiClient) {

  /**
  * Саджест сотрудников компании клиента
  * @param {Object} options - опции
  * @param {Object} options.data - параметры саджеста
  * @param {Function} options.error - коллбек при ошибке
  * @param {Function} options.success - коллбэк при успехе
  */
  ApiClient.getClientUserSuggest = function(options) {
    var _instance = this;
    
    KT.rest({
        caller:"orderEdit - getClientUserSuggest",
        data: options.data,
        url: _instance.urls.getClientUserSuggest
      })
      .done(options.success)
      .fail(options.error);
  };

  /**
  * Создание/обновление сотрудника компании 
  * @param {Object} userData - данные пользователя 
  * @param {String|Integer} touristId - ID сохраняемого туриста
  */
  ApiClient.setUser = function(userData, touristId) {
    var _instance = this;
    var request = $.Deferred();

    KT.rest({
        caller:'orderEdit#tourists - setUser',
        url: _instance.urls.setUser,
        data: userData
      })
      .done(function(response) {
        response.touristId = touristId;
        request.resolve(response);
      })
      .fail(request.reject);

    return request;
  };

  /**
  * Получение данных сотрудника компании
  * @param {Integer} documentId - ID документа пользователя
  */
  ApiClient.getClientUser = function(documentId) {
    var _instance = this;

    return KT.rest({
        caller:'orderEdit#tourists - getClientUser',
        url: _instance.urls.getClientUser,
        data: {'docId': documentId}
      });
      /*
      .done(function(response) {
        _instance.dispatch('gotClientUser', response);
      });
      */
  };

}));
(function(global, extendApiClient) {

    extendApiClient(KT.apiClient);

}(this,function(ApiClient) {

  /**
  * Получение полной информации по отелю
  * @param {Integer} hotelId - ID отеля
  */
  ApiClient.getHotelInfo = function(hotelId) {
    var _instance = this;

    var params = {
      'hotelId': hotelId,
      'lang':'ru'
    };

    return KT.rest({
        caller: "orderEdit - getHotelInfo",
        data: params,
        url: _instance.urls.getHotelInfo
      });
      /*
      .done(function(data) {
        _instance.dispatch('gotFullHotelInfo',data);
      }); */

  };

}));
(function(global, extendApiClient) {

    extendApiClient(KT.apiClient);

}(this,function(ApiClient) {

  /** Установка скидки
  * @param {Integer} orderId - ID заявки
  * @param {Number} discount - сумма скидки в рублях
  * @fires ApiClient.setDiscount
  */
  ApiClient.setDiscount = function(orderId, discount) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      'orderId': orderId,
      'agentOrderDiscount': discount
    };

    KT.rest({
        caller: 'orderEdit#payment - setDiscount',
        data: params,
        url: _instance.urls.setDiscount
      })
      .done(function (data) {
        data.discount = discount;
        request.resolve(data);
        //_instance.dispatch('setDiscount',data);
      })
      .fail(request.reject);

    return request;
  };

  /**
  * Удаление туриста из заявки
  * @param {Integer} touristId - id туриста для удаления
  * @fires ApiClient.removedTourist
  */
  ApiClient.removeOrderTourist = function(orderId, touristId) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      'orderId':orderId,
      'touristId':touristId
    };

    KT.rest({
        caller:'orderEdit#tourists - removeOrderTourist',
        data: params,
        url: _instance.urls.removeOrderTourist
      })
      .done(function (response) {
        response.touristId = touristId;
        request.resolve(response);
        //_instance.dispatch('removedTourist', response);
      })
      .fail(request.reject);

    return request;
  };

  /**
  * Добавление/обновление туриста в заявк(у|е)
  * @param {Object} touristData - информация по туристу
  * @fires ApiClient.setOrderTourist
  */
  ApiClient.setOrderTourist = function(OrderStorage, touristData) {
    var _instance = this;
    var request = $.Deferred();

    if (
      String(touristData.touristId).indexOf('tmp') === 0 ||
      String(touristData.touristId).indexOf('doc') === 0 
    ) {
      // если добавляем в заявку нового туриста
      delete touristData.touristId;
    }

    var params = {
      'orderId': (OrderStorage.orderId !== 'new') ? OrderStorage.orderId : null,
      'action':'AddTourist',
      'actionParams':touristData
    };

    KT.rest({
        caller:'orderEdit#tourists - setOrderTourist',
        data: params,
        url: _instance.urls.orderWorkflowManager
      })
      .done(function (response) {
        request.resolve(response);
        /*
        _instance.dispatch('setOrderTourist', {
          'touristData': touristData, //данные отправленной формы туриста
          'currentTouristId': currentTouristId, // текущий ID туриста: если турист добавляется, здесь будет его временный ID
          'response': response
        }); */
      })
      .fail(request.reject);

    return request;
  };

  /**
  * Загрузка документов в заявку
  * @param {Object} $docform - форма отправки документов
  * @fires Apiclient.documentUploadProgress
  * @fires Apiclient.uploadedDocument
  * @todo убрать проброс формы
  */
  ApiClient.uploadDocument = function(orderId, $docform) {
    var _instance = this;

    $docform.ajaxSubmit({
      url: _instance.urls.uploadDocument,
      data: {
        'orderId': orderId,
        'usertoken': KT.profile.userToken
      },
      uploadProgress: function(e, position, total, percent) {
        _instance.dispatch('documentUploadProgress', {'percent': percent});
      },
      success: function(data) {
        try {
          var loadedData = JSON.parse(data);
          _instance.dispatch('uploadedDocument', loadedData);
        } catch(e) {
          _instance.dispatch('uploadedDocument', {'error':'uploadDocument: not json'});
        }
      },
      error: function(xhr,text,err) {
        _instance.dispatch('uploadedDocument', {'error':text + ' ' + err});
      }
    });
  };

  /**
  * Выставление счета
  * @param {OrderStorage} orderStorage - данные заявки
  * @param {Array} invoiceData - массив номеров услуг и счетов по ним
  * @param {mwmodal} loader - модальное окно с загрузчиком
  * @fires Apiclient.setInvoice
  * @todo вот про модальное окно тут вообще ничего знать не надо, переделать
  */
  ApiClient.setInvoice = function(orderStorage, invoiceData) {
    var _instance = this;

    var invoiceServices = [];
    invoiceData.forEach(function(invoice) {
      invoiceServices.push({
        'serviceId': invoice.id,
        'invoicePrice': invoice.sum
      });
    });

    /** @todo грязнейший хак, по сути же одна клиентская валюта? */
    var params = {
      'userId': KT.profile.userId,
      'orderId': orderStorage.orderId,
      'paymentType': '2',
      'currency': orderStorage.getServices()[0].prices.inClient.currencyCode,
      'Services': invoiceServices
    };

    return KT.rest({
        caller: 'orderEdit - setInvoice',
        data: params,
        url: _instance.urls.setInvoice
      });
      /*
      .done(function(response) {
        _instance.dispatch('setInvoice', response);
      }); */
  };

  /**
  * Отмена счета
  * @param {Integer} invoiceId  - ID отменяемого счета 
  */
  ApiClient.cancelInvoice = function(invoiceId) {
    var _instance = this;

    /** @todo грязнейший хак, по сути же одна клиентская валюта? */
    var params = {
      'invoiceId': invoiceId
    };

    return KT.rest({
        caller: 'orderEdit - setInvoice',
        data: params,
        url: _instance.urls.cancelInvoice
      });
      /*
      .done(function(response) {
        _instance.dispatch('cancelledInvoice', response);
      }); */
  };

}));
(function(global, extendApiClient) {

    extendApiClient(KT.apiClient);

}(this,function(ApiClient) {

  /**
  * Запрос информации о заявке
  * @fires Apiclient.gotOrderInfo
  */
  ApiClient.getOrderInfo = function(orderId) {
    var _instance = this;
    var params = {
      'orderId':orderId,
      'getInCurrency':KT.profile.viewCurrency
    };

    return KT.rest({
        caller:"orderEdit - getOrderInfo",
        data: params,
        url: _instance.urls.getOrder
      });
      /*
      .done(function (response) {
        //_instance.dispatch('gotOrderInfo', response);
      });
      */
  };

  /**
  * Получение информации по услуге перелета
  * @param {Array} serviceIds - массив ID'шников сервисов
  * @fires Apiclient.gotOrderOffers
  */
  ApiClient.getOrderOffers = function(orderId, serviceIds) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'servicesIds':serviceIds,
      'lang':'ru',
      'getInCurrency':KT.profile.viewCurrency
    };

    return KT.rest({
        caller:'orderEdit#services - getOrderOffers',
        data: params,
        url: _instance.urls.getOrderOffers
      });
      /*
      .done(function (response) {
        _instance.dispatch('gotOrderOffers', response);
      });
      */
  };

  /**
  * Получение информации по туристам заявки
  * @fires ApiClient.gotOrderTourists
  */
  ApiClient.getOrderTourists = function(orderId) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      'orderId':orderId
    };

    KT.rest({
        caller:'orderEdit#tourists - getOrderTourists',
        data: params,
        url: _instance.urls.getOrderTourists
      })
      .done(function (response) {
        /** @todo API patch; deprecated with KT-1351 */
        if (response.status === 0 && Array.isArray(response.body.tourists)) {
          response.body.tourists.forEach(function(tourist) {
            if (tourist.surName !== undefined) {
              tourist.lastName = tourist.surName;
              delete tourist.surName;
            }
            if (tourist.document.surName !== undefined) {
              tourist.document.lastName = tourist.document.surName;
              delete tourist.document.surName;
            }
            if (tourist.document.serialNum !== undefined) {
              tourist.document.serialNumber = tourist.document.serialNum;
              delete tourist.document.serialNum;
            }
          });
        }
        /** @todo end API patch */
        request.resolve(response);
        //_instance.dispatch('gotOrderTourists', response);
      })
      .fail(request.reject);

    return request;
  };

  /**
  * Запрос информации о счетах
  * @fires Apiclient.gotOrderInvoices
  */
  ApiClient.getOrderInvoices = function(orderId) {
    var _instance = this;

    var params = {
      'orderId':orderId
    };

    return KT.rest({
        caller:'orderEdit - getOrderInvoices',
        data: params,
        url: _instance.urls.getOrderInvoices
      });
      /*
      .done(function (response) {
        _instance.dispatch('gotOrderInvoices', response);
      }); */
  };

  /**
  * Получение списка документов
  * @fires Apiclient.gotOrderDocuments
  */
  ApiClient.getOrderDocuments = function(orderId) {
    var _instance = this;

    var params = {
      'orderId':orderId
    };

    return KT.rest({
        caller:'orderEdit - getOrderDocuments',
        data: params,
        url: _instance.urls.getOrderDocuments
      });
      /*
      .done(function (response) {
        _instance.dispatch('gotOrderDocuments', response);
      }); */
  };

  /**
  * Получение истории заявки
  * @fires Apiclient.gotOrderHistory
  */
  ApiClient.getOrderHistory = function(orderId) {
    var _instance = this;

    var params = {
      'orderId': orderId,
      'lang': 'ru'
    };

    return KT.rest({
        caller:'orderEdit - getOrderHistory',
        data: params,
        url: _instance.urls.getOrderHistory
      });
      /*
      .done(function (response) {
        _instance.dispatch('gotOrderHistory', response);
      }); */
  };


}));
(function(global, extendApiClient) {

    extendApiClient(KT.apiClient);

}(this,function(ApiClient) {

  /**
  * Получение доступных действий с услугами
  * @param {Integer} orderId - ID заявки
  */
  ApiClient.getAllowedTransitions = function(orderId) {
    var _instance = this;

    var params = {
        'operation': 'checkTransition',
        'orderId': orderId
    };

    return KT.rest({
        caller: 'orderEdit - getAllowedTransitions',
        data: params,
        url: _instance.urls.checkWorkflow
      });
  };

  /**
  * Проверка доступности совершения операции с услугами с определенным набором параметров
  * @param {Integer} orderId - ID заявки
  * @param {String} action - валидируемое действие
  * @param {Object[]} actionParams - массив структур actionParams для услуг
  */
  ApiClient.validateAction = function(orderId, action, actionParams) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
        'operation': 'validate',
        'orderId': orderId,
        'action': action,
        'actionParams': actionParams
    };

    KT.rest({
        caller: 'orderEdit - validateAction',
        data: params,
        url: _instance.urls.checkWorkflow
      })
      .done(function(response) {
        response.action = action;
        if (response.status === 0) {
          actionParams.forEach(function(service, i) {
            response.body[i].serviceId = service.serviceId;
          });
        }

        request.resolve(response);
      })
      .fail(request.reject);

    return request;
  };

  /**
  * Запрос на старт процесса бронирования
  * @param {Integer} orderId - ID заявки
  * @param {Object} actionParams - параметры actionParams команды
  */
  ApiClient.startBooking = function(orderId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'BookStart',
      'actionParams': actionParams
    };

    return KT.rest({
        caller: 'orderEdit#services - startBooking',
        url: _instance.urls.orderWorkflowManager,
        data: params
      });
  };

  /**
  * Запрос на выписку билетов
  * @param {Integer} orderId - ID заявки
  * @param {Object} serviceId - ID сервиса для выписки
  * @fires Apiclient.issuedTickets
  */
  ApiClient.issueTickets = function(orderId, serviceId) {
    var _instance = this;

    var params = {
      'orderId': orderId,
      'action': 'IssueTickets',
      'actionParams': {
        'serviceId': serviceId
      }
    };

    return KT.rest({
        caller:'orderEdit#services - issueTickets',
        url: _instance.urls.orderWorkflowManager,
        data:params
      });
  };

  /**
  * Запрос на изменение данных брони
  * @param {Integer} orderId - ID заявки
  * @param {Object} actionParams - параметры actionParams команды
  */
  ApiClient.bookChange = function(orderId, actionParams) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      'orderId': orderId,
      'action': 'BookChange',
      'actionParams': actionParams
    };

    KT.rest({
        caller:'orderEdit#services - bookChange',
        url: _instance.urls.orderWorkflowManager,
        data:params
      })
      .done(function(response) {
        response.body.serviceId = actionParams.serviceId;
        request.resolve(response);
      })
      .fail(request.reject);
    
    return request;
  };

  /**
  * Запрос на отмену бронирования услуги
  * @param {Integer} orderId - ID заявки
  * @param {Object} actionParams - параметры actionParams команды
  */
  ApiClient.bookCancel = function(orderId, actionParams) {
    var _instance = this;

    var params = {
      'orderId': orderId,
      'action': 'BookCancel',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - bookCancel',
        url: _instance.urls.orderWorkflowManager,
        data:params
      });
  };

  /**
  * Запрос на отмену бронирования услуги
  * @param {Integer} orderId - ID заявки
  * @param {Object} actionParams - параметры actionParams команды
  */
  ApiClient.setServiceToManual = function(orderId, actionParams) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      'orderId': orderId,
      'action': 'Manual',
      'actionParams': actionParams
    };

    KT.rest({
        caller: 'orderEdit#services - Manual',
        url: _instance.urls.orderWorkflowManager,
        data: params
      })
      .done(function(response) {
        response.serviceId = actionParams.serviceId;
        request.resolve(response);
      })
      .fail(request.reject);

    return request;
  };

  /**
  * Установка привязки туристов к услуге
  * @param {Integer} orderId - ID заявки
  * @param {Integer} serviceId - ID сохраняемого сервиса
  * @param {Object[]} linkageInfo - данные по туристам
  */
  ApiClient.setTouristsLinkage = function(orderId, serviceId, linkageInfo) {
    var _instance = this;
    var request = $.Deferred();

    var params = {
      'orderId': orderId,
      'action':'TouristToService',
      'actionParams':{
        'serviceId': serviceId,
        'touristData': linkageInfo
      }
    };

    KT.rest({
        caller:'orderEdit#services - setTouristsLinkage',
        data: params,
        url: _instance.urls.orderWorkflowManager
      })
      .done(function (response) {
        response.serviceId = serviceId;
        response.linkageInfo = linkageInfo;

        request.resolve(response);
      })
      .fail(request.reject);

    return request;
  };

  /**
  * Изменение параметров услуги в ручном режиме (даты, цены)
  * @param {Integer} orderId - ID заявки
  * @param {Integer} serviceId - ID сохраняемого сервиса
  * @param {Object} actionParams - параметры команды 
  */
  ApiClient.setServiceData = function(orderId, serviceId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'SetServiceData',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - setServiceData',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Изменение статуса услуги в ручном режиме
  * @param {Integer} orderId - ID заявки
  * @param {Integer} serviceId - ID сохраняемого сервиса
  * @param {Object} actionParams - параметры команды 
  */
  ApiClient.manualSetStatus = function(orderId, serviceId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'ManualSetStatus',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - manualSetStatus',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Изменение параметров брони услуги в ручном режиме
  * @param {Integer} orderId - ID заявки
  * @param {Integer} serviceId - ID сохраняемого сервиса
  * @param {Object} actionParams - параметры команды 
  */
  ApiClient.setReservationData = function(orderId, serviceId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'SetReservationData',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - SetReservationData',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Добавление/изменение билетов
  * @param {Integer} orderId - ID заявки
  * @param {Integer} serviceId - ID сохраняемого сервиса
  * @param {Object} actionParams - параметры команды 
  */
  ApiClient.setTicketsData = function(orderId, serviceId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'SetTicketsData',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - SetTicketsData',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Сохранение значений дополнительных полей услуги 
  * @param {Integer} orderId - ID заявки
  * @param {Array} customFieldsValues - список значений дополнительных полей
  */
  ApiClient.setServiceAdditionalData = function(orderId, customFieldsValues) {
    var _instance = this;

    var params = {
      'orderId': orderId,
      'action': 'SetAdditionalData',
      'actionParams': {
        'additionalFields': customFieldsValues
      }
    };

    return KT.rest({
        caller:'orderEdit#services - SetAdditionalData',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Добавление дополнительной услуги
  * @param {Integer} orderId - ID заявки
  * @param {Object} axctionParams - параметры команды 
  */
  ApiClient.addExtraService = function(orderId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'AddExtraService',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - AddExtraService',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

  /**
  * Добавление дополнительной услуги
  * @param {Integer} orderId - ID заявки
  * @param {Object} avtionParams - параметры команды 
  */
  ApiClient.removeExtraService = function(orderId, actionParams) {
    var _instance = this;

    var params = {
      'orderId':orderId,
      'action':'RemoveExtraService',
      'actionParams': actionParams
    };

    return KT.rest({
        caller:'orderEdit#services - RemoveExtraService',
        data: params,
        url: _instance.urls.orderWorkflowManager
      });
  };

}));
/* global ktStorage */
(function(global,factory){

    KT.storage.ServiceStorage = factory();

}(this,function() {
  /**
  * Прототип хранилища данных услуги
  * @module ServiceStorage
  * @constructor
  * @param {Integer} serviceId - ID услуги
  */
  var ServiceStorage = ktStorage.extend(function(serviceId) {
    this.namespace = 'ServiceStorage';

    this.serviceId = serviceId;

    // флаг, обозначающий отсутствие оффера в услуге
    this.isPartial = false;

    // флаг согласия с условиями бронирования
    this.isTOSAgreementSet = false;

    // название документа для условий бронирования:
    // нужно для идентификации документа для вывода со страниц услуг и оформления
    this.tosDocumentName = false;

    // структура дл записи цен
    this.prices = {
      inLocal: {
        currencyCode: 'RUB', /** @todo default currency constant */
        client: {
          net: null,
          gross: null,
          commission: {
            amount: null, /** @todo клиентская комиссия? */
            percent: null
          }
        },
        supplier: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        }
      },
      inView: {
        currencyCode: null,
        client: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        },
        supplier: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        }
      },
      inSupplier: {
        currencyCode: null,
        client: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        },
        supplier: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        }
      },
      inClient: {
        currencyCode: null,
        client: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        },
        supplier: {
          net: null,
          gross: null,
          commission: {
            amount: null,
            percent: null
          }
        }
      }
    };

    // данные оффера
    this.offerInfo = null;

    // флаг невозможности возврата средств при отмене бронирования
    this.isNonRefundable = false;

    // возможные штрафы за отмену
    this.clientCancelPenalties = null;
    this.supplierCancelPenalties = null;

    // начисленные штрафы
    this.penalties = null;
    this.penaltySums = null;

    //  данные запроса по предложению
    this.requestData = null;

    // данные по привязке туристов
    this.tourists = {};

    // доступные действия с услугой
    this.allowedTransitions = [];

    // сумма выставленных на услугу счетов
    // NOTE: валюта должна быть та же, что и у clientCurrency
    this.invoiceSum = 0;
    // сумма незаплаченных денег
    this.unpaidSum = 0;

    // наличие данных по TP
    this.hasTravelPolicy = false;
    // наличие нарушений TP
    this.hasTPViolations = false;

    // дополнительные услуги (добавленные)
    this.additionalServices = [];
    
    // структура дополнительных полей
    this.customFields = null;
  });

  KT.addMixin(ServiceStorage,'Dispatcher');

  /**
  * Инициализация хранилища
  * @param {Object} serviceData - данные услуги (/getOrder)
  */
  ServiceStorage.prototype.initialize = function(serviceData) {
    if (serviceData.serviceID !== this.serviceId) {
        KT.error(
          this.namespace+': ' +
          'попытка инициализации данными другой услуги,' +
          ' текущая: ' . this.serviceId +
          ' данные от: '. serviceData.serviceID
        );
    }

    var isNotEmpty = function(v) {
      return !(v === undefined || v === null || v === '');
    };

    // название услуги
    this.name = serviceData.serviceName;

    // описание услуги
    this.description = serviceData.serviceDescription;

    // статусная информация
    this.status = serviceData.status;
    this.isOffline = serviceData.offline;

    // код типа услуги
    this.typeCode = +serviceData.serviceType;

    // установка названия документа с условиями бронирования
    // cancellationDocumentName - в каком документе содержатся правила отмены, для вкладки "оформление"
    /** @todo создать отдельные классы для каждого типа услуг */
    switch(this.typeCode) {
      case 1:
        this.tosDocumentName = 'hotelBookTerms-'+this.serviceId;
        this.cancellationDocumentName = this.tosDocumentName;
        break;
      case 2:
        this.tosDocumentName = 'aviaBookTerms';
        // название документа для правил тарифа авиа
        this.fareRuleDocumentName = 'aviaFareRule-'+this.serviceId;
        this.cancellationDocumentName = this.fareRuleDocumentName;
        break;
    }

    // ID шлюза, связанного с услугой
    this.gatewayId = serviceData.supplierId; /** @todo атавизм? */

    // даты начала и окончания услуги
    this.startDate = moment(serviceData.startDateTime,'YYYY-MM-DD HH:mm:ss');
    this.endDate = moment(serviceData.endDateTime,'YYYY-MM-DD HH:mm:ss');

    // дата заказа услуги
    this.creationDate = moment(serviceData.dateOrdered,'YYYY-MM-DD HH:mm:ss');

    // дата оплаты (оплатить до)
    this.dateAmend = isNotEmpty(serviceData.dateAmend) ?
      moment(serviceData.dateAmend,'YYYY-MM-DD HH:mm:ss') : null;

    // ценовая информация
    this.prices.inLocal.client.gross = Number(serviceData.localSum);
    this.prices.inLocal.client.commission.amount = Number(serviceData.localCommission);
    this.prices.inLocal.supplier.gross = Number(serviceData.localNetSum);
    this.prices.inView.currencyCode = KT.profile.viewCurrency;
    this.prices.inView.client.gross = Number(serviceData.requestedSum);
    this.prices.inView.supplier.gross = Number(serviceData.requestedNetSum);
    this.prices.inSupplier.currencyCode = serviceData.supplierCurrencyCode;
    this.prices.inSupplier.client.gross = Number(serviceData.supplierPrice);
    this.prices.inSupplier.supplier.gross = Number(serviceData.supplierNetPrice);
    this.prices.inClient.currencyCode = serviceData.paymentCurrencyCode;
    this.prices.inClient.client.gross = Number(serviceData.paymentSum);

    this.paymentCurrency = this.prices.inClient.currency;

    /** @todo как-то странно тут скидка, не пришей кобыле хвост... */
    this.discount = isNotEmpty(serviceData.discount) ?
      Number(serviceData.discount) : 0;

    /** структура дополнительных полей */
    this.customFields = Array.isArray(serviceData.additionalData) ? 
      serviceData.additionalData : null;

    // разрешенные операции
    /** @todo checkworkflow */
    this.isActionAvailable = {
      'setInvoice': (
        [2,3,4,8].indexOf(this.status) !== -1 ||
        (KT.profile.userType === 'op' && this.status === 9)
      )
    };
  };

  /*
  * Обновление полной информации по услуге (getOrderOffer)
  */
  ServiceStorage.prototype.updateFullInfo = function(serviceData) {
    var self = this;

    this.status = +serviceData.serviceStatus;
    this.offerInfo = serviceData.offerInfo;
    this.requestData = serviceData.requestData;

    // сохранение ценовых компонентов
    var salesTerms = serviceData.serviceSalesTermsInfo;
    this.prices = {
      inLocal: {
        currencyCode: 'RUB',
        client: {
          net: Number(salesTerms.localCurrency.client.amountNetto),
          gross: Number(salesTerms.localCurrency.client.amountBrutto),
          commission: {
            amount: Number(salesTerms.localCurrency.client.commission.amount),
            percent: Number(salesTerms.localCurrency.client.commission.percent)
          }
        },
        supplier: {
          net: Number(salesTerms.localCurrency.supplier.amountNetto),
          gross: Number(salesTerms.localCurrency.supplier.amountBrutto),
          commission: {
            amount: Number(salesTerms.localCurrency.supplier.commission.amount),
            percent: Number(salesTerms.localCurrency.supplier.commission.percent)
          }
        }
      },
      inView: {
        currencyCode: salesTerms.viewCurrency.client.currency,
        client: {
          net: Number(salesTerms.viewCurrency.client.amountNetto),
          gross: Number(salesTerms.viewCurrency.client.amountBrutto),
          commission: {
            amount: Number(salesTerms.viewCurrency.client.commission.amount),
            percent: Number(salesTerms.viewCurrency.client.commission.percent)
          }
        },
        supplier: {
          net: Number(salesTerms.viewCurrency.supplier.amountNetto),
          gross: Number(salesTerms.viewCurrency.supplier.amountBrutto),
          commission: {
            amount: Number(salesTerms.viewCurrency.supplier.commission.amount),
            percent: Number(salesTerms.viewCurrency.supplier.commission.percent)
          }
        }
      },
      inSupplier: {
        currencyCode: salesTerms.supplierCurrency.supplier.currency,
        client: {
          net: Number(salesTerms.supplierCurrency.client.amountNetto),
          gross: Number(salesTerms.supplierCurrency.client.amountBrutto),
          commission: {
            amount: Number(salesTerms.supplierCurrency.client.commission.amount),
            percent: Number(salesTerms.supplierCurrency.client.commission.percent)
          }
        },
        supplier: {
          net: Number(salesTerms.supplierCurrency.supplier.amountNetto),
          gross: Number(salesTerms.supplierCurrency.supplier.amountBrutto),
          commission: {
            amount: Number(salesTerms.supplierCurrency.supplier.commission.amount),
            percent: Number(salesTerms.supplierCurrency.supplier.commission.percent)
          }
        }
      },
      inClient: {
        currencyCode: salesTerms.clientCurrency.client.currency,
        client: {
          net: Number(salesTerms.clientCurrency.client.amountNetto),
          gross: Number(salesTerms.clientCurrency.client.amountBrutto),
          commission: {
            amount: Number(salesTerms.clientCurrency.client.commission.amount),
            percent: Number(salesTerms.clientCurrency.client.commission.percent)
          }
        },
        supplier: {
          net: Number(salesTerms.clientCurrency.supplier.amountNetto),
          gross: Number(salesTerms.clientCurrency.supplier.amountBrutto),
          commission: {
            amount: Number(salesTerms.clientCurrency.supplier.commission.amount),
            percent: Number(salesTerms.clientCurrency.supplier.commission.percent)
          }
        }
      }
    };
    
    // сохранение суммы для оплаты
    this.unpaidSum = Number(serviceData.restPaymentAmount);
    this.paymentCurrency = serviceData.restPaymentAmountCurrency;

    // сохранение данных по штрафам
    this.penalties = serviceData.penalties;
    if (this.penalties !== null) {
      this.penaltySums = {
        inLocal: {
          currencyCode: this.penalties.client.localCurrency.currency,
          client: Number(this.penalties.client.localCurrency.amount),
          supplier: Number(this.penalties.supplier.localCurrency.amount)
        },
        inView: {
          currencyCode: this.penalties.client.viewCurrency.currency,
          client: Number(this.penalties.client.viewCurrency.amount),
          supplier: Number(this.penalties.supplier.viewCurrency.amount)
        },
        inClient: {
          currencyCode: this.penalties.client.clientCurrency.currency,
          client: Number(this.penalties.client.clientCurrency.amount),
          supplier: Number(this.penalties.supplier.clientCurrency.amount)
        }
      };
    }

    // определение возможных действий с услугой
    this.isActionAvailable.setInvoice = (
        this.isActionAvailable.setInvoice &&
        this.unpaidSum > 0
      );

    // обработка специфичная для онлайн и оффлайн услуг
    if (this.offerInfo === null) {
      this.isPartial = true;

      /** @todo хак, т.к. из УТК такой информации не достается */

      switch (this.typeCode) {
        case 1:
          this.touristAges = {
            adults: serviceData.serviceTourists.length,
            children: 0
          };

          this.declaredTouristAges = {
            adults: serviceData.serviceTourists.length,
            children: 0
          };
          break;
        case 2:
          this.touristAges = {
            adults: serviceData.serviceTourists.length,
            children: 0,
            infants: 0
          };

          this.declaredTouristAges = {
            adults: serviceData.serviceTourists.length,
            children: 0,
            infants: 0
          };
          break;
      }
    } else {
      // данные по возрастному составу: текущий привязанный и согласно заказу
      /** @todo для каждого типа услуги нужен свой класс хранилища */
      switch (this.typeCode) {
        case 1:
          this.touristAges = {
            adults: null,
            children: null
          };

          this.declaredTouristAges = {
            adults: this.offerInfo.adult,
            children: this.offerInfo.child
          };
          break;
        case 2:
          this.touristAges = {
            adults: null,
            children: null,
            infants: null
          };

          this.declaredTouristAges = {
            adults: this.offerInfo.requestData.adult,
            children: this.offerInfo.requestData.child,
            infants: this.offerInfo.requestData.infant
          };

          if (Array.isArray(this.offerInfo.fareRules)) {
            this.offerInfo.fareRules.forEach(function(fareRule) {
              if (fareRule.aviaFareRule.shortRules.refund_before_rule === false) {
                self.isNonRefundable = true;
              }
            });
          }
          break;
      }

      // услугонезависимая обработка

      if (this.offerInfo.cancelPenalties !== null) {
        var clientPenaltiesLocal = this.offerInfo.cancelPenalties.localCurrency.client;
        var clientPenaltiesView = this.offerInfo.cancelPenalties.viewCurrency.client;
        var clientPenaltiesClient = this.offerInfo.cancelPenalties.clientCurrency.client;

        if (Array.isArray(clientPenaltiesLocal) && clientPenaltiesLocal.length > 0) {
          this.clientCancelPenalties = processCancelPenalties(
            clientPenaltiesLocal, clientPenaltiesView, clientPenaltiesClient
          );
        }

        var supplierPenaltiesLocal = this.offerInfo.cancelPenalties.localCurrency.supplier;
        var supplierPenaltiesView = this.offerInfo.cancelPenalties.viewCurrency.supplier;
        var supplierPenaltiesClient = this.offerInfo.cancelPenalties.clientCurrency.supplier;

        if (Array.isArray(supplierPenaltiesLocal) && supplierPenaltiesLocal.length > 0) {
          this.supplierCancelPenalties = processCancelPenalties(
            supplierPenaltiesLocal, supplierPenaltiesView, supplierPenaltiesClient
          );
        }

        if (Array.isArray(this.supplierCancelPenalties)) {
          this.supplierCancelPenalties.forEach(function(penalty) {
            if (!self.isNonRefundable && penalty.dateFrom.valueOf() < moment().endOf('day').valueOf()) {
              self.isNonRefundable = true;
            }
          });
        }
      }

      this.declaredTouristAmount = 0;
      for (var agegroup in this.declaredTouristAges) {
        if (this.declaredTouristAges.hasOwnProperty(agegroup)) {
          this.declaredTouristAmount += this.declaredTouristAges[agegroup];
        }
      }

      // определение наличия трэвел-политик
      this.hasTravelPolicy = (typeof this.offerInfo.travelPolicy === 'object' && this.offerInfo.travelPolicy !== null);
      this.hasTPViolations = (
        this.hasTravelPolicy && 
        Array.isArray(this.offerInfo.travelPolicy.travelPolicyFailCodes) &&
        this.offerInfo.travelPolicy.travelPolicyFailCodes.length > 0
      );

      // дополнительные услуги
      if (Array.isArray(serviceData.addServices)) {
        this.additionalServices = serviceData.addServices;
      }
    }
  };

  /**
  * Обработка плановых штрафов
  * @param {Object[]} penaltiesInLocal - штрафы в локальной валюте
  * @param {Object[]} penaltiesInView - штрафы в валюте просмотра
  * @param {Object[]} penaltiesInClient - штрафы в валюте оплаты
  * @return {Object[]} - скомпонованные  штрафы
  */
  function processCancelPenalties(penaltiesInLocal, penaltiesInView, penaltiesInClient) {
      if (penaltiesInLocal.length !== penaltiesInView.length) {
        console.error('amount of penalties in local currency and in view currency not equal!');
        return null;
      } else {
        var penalties = [];

        penaltiesInLocal.forEach(function(localPenalty, i) {
          penalties.push({
            'dateFrom': moment(localPenalty.dateFrom, 'YYYY-MM-DD HH:mm:ss'),
            'dateTo': moment(localPenalty.dateTo, 'YYYY-MM-DD HH:mm:ss'),
            'description': localPenalty.description,
            'penaltySum': {
              'inLocal': {
                'currency': localPenalty.penalty.currency,
                'amount': localPenalty.penalty.amount
              },
              'inView': {
                'currency': penaltiesInView[i].penalty.currency,
                'amount': penaltiesInView[i].penalty.amount
              },
              'inClient': {
                'currency': penaltiesInClient[i].penalty.currency,
                'amount': penaltiesInClient[i].penalty.amount
              }
            }
          });
        });

        return penalties;
      }
  }

  /**
  * Установка списка доступных действий с услугой
  */
  ServiceStorage.prototype.setAllowedTransitions = function(controls) {
    this.allowedTransitions = controls;
  };

  /**
  * Проверка доступности действия
  * @param {String} transition - название действия
  * @return {Boolean} - результат проверки
  */
  ServiceStorage.prototype.checkTransition = function(transition) {
    return (this.allowedTransitions.indexOf(transition) !== -1);
  };

  /**
  * Возращает список туристов с информацией о привязке к услуге в виже массива
  * @return {Array} - информация о туристах
  */
  ServiceStorage.prototype.getServiceTourists = function() {
    var tourists = [];

    for (var touristId in this.tourists) {
      if (this.tourists.hasOwnProperty(touristId)) {
        tourists.push(this.tourists[touristId]);
      }
    }

    return tourists;
  };

  /**
  * Возвращает возраст туриста на момент окончания услуги
  * @param {Object} birthdate - дата рождения туриста в структуре moment.js
  * @return {Integer} - возраст туриста
  */
  ServiceStorage.prototype.getAgeByServiceEnding = function(birthdate) {
    return moment.duration(this.endDate.valueOf() - birthdate.valueOf()).asYears();
  };

  /**
  * Возвращает возрастную группу туриста для данной услуги
  * @param {Integer} age - возраст туриста
  * @return {String} - возрастная группа (infants, adults, children)
  * @todo сделать классы для каждой услуги
  */
  ServiceStorage.prototype.getAgeGroup = function(age) {
    var agegroup = 'adults';
    switch (this.typeCode) {
      case 1:
        if (age < 12) { agegroup = 'children'; }
        else { agegroup = 'adults'; }
        break;
      case 2:
        if (age < 3) { agegroup = 'infants'; }
        else if (age < 12) { agegroup = 'children'; }
        else { agegroup = 'adults'; }
        break;
    }
    return agegroup;
  };

  /**
  * Проверяет, привязаны ли все необходимые туристы согласно заявленному
  * возрастному составу
  * @return {Boolean} результат проверки
  */
  ServiceStorage.prototype.checkAllTouristsLinked = function() {
    var allLinked = true;

    for (var agegroup in this.touristAges) {
      if (this.touristAges.hasOwnProperty(agegroup)) {
        if (this.touristAges[agegroup] !== this.declaredTouristAges[agegroup]) {
          allLinked = false;
        }
      }
    }

    return allLinked;
  };

  /**
  * Обновление информации по привязанным туристам (возрастной состав)
  * @param {Object]} tourists - список данных туристов [TouristStorage]
  * @todo для каждого типа услуги нужен свой класс хранилища
  */
  ServiceStorage.prototype.setTouristAges = function(tourists) {
    if (this.isPartial) { return false; }

    var touristId, age;
    switch (this.typeCode) {
      case 1:
        this.touristAges = {
          adults: 0,
          children: 0
        };

        for (touristId in tourists) {
          if (this.tourists.hasOwnProperty(touristId)) {
            age = tourists[touristId].age;
            if (age < 12) { this.touristAges.children += 1; }
            else { this.touristAges.adults += 1; }
          }
        }
        break;
      case 2:
        this.touristAges = {
          adults: 0,
          children: 0,
          infants: 0
        };

        for (touristId in this.tourists) {
          if (this.tourists.hasOwnProperty(touristId)) {
            age = tourists[touristId].age;
            if (age < 3) { this.touristAges.infants += 1; }
            else if (age < 12) { this.touristAges.children += 1; }
            else { this.touristAges.adults += 1; }
          }
        }
        break;
    }
  };

  /**
  * Подсчет суммы клиентских штрафов при отмене брони
  * @return {Object|false} данные штрафа (сумма, валюта) или false в случае отсутствия
  */
  ServiceStorage.prototype.countClientCancelPenalty = function() {
    if (this.isPartial) { return null; }
    if (this.clientCancelPenalties === null) { return null; }

    return this.clientCancelPenalties.reduce(function(total, penalty) {
      console.log('penalty:');
      console.log(penalty);
      if (moment().isBetween(penalty.dateFrom, penalty.dateTo, null, '[]')) {
        total.inLocal += penalty.penaltySum.inLocal.amount;
        total.inView += penalty.penaltySum.inView.amount;
      }
      return total;
    }, {'inLocal': 0, 'inView': 0});
  };

  /**
  * Сравнение цен услуги с переданными
  * согласно задаче, достаточно сравнить брутто-цены
  * @param {Object} salesTerms - ценообразователи в стуктуре КТ
  */
  ServiceStorage.prototype.compareSalesTerms = function(salesTerms) {
    return (this.prices.inClient.client.gross === Number(salesTerms.clientCurrency.client.amountBrutto));
  };

  /**
  * Добавление дополнительной услуги
  * @param {Object} addService - дополнительная услуга
  */
  ServiceStorage.prototype.addAdditionalService = function(addService) {
    this.additionalServices.push(addService);
  };

  /**
  * Удаление дополнительной услуги
  * @param {Integer} addServiceId - ID дополнительной услуги
  */
  ServiceStorage.prototype.removeAdditionalService = function(addServiceId) {
    this.additionalServices = this.additionalServices.filter(function(addService) {
      return addService.idAddService !== addServiceId;
    });
  };

  return ServiceStorage;
}));

/* global ktStorage */
(function(global,factory){

    KT.storage.TouristStorage = factory();

}(this,function() {
  /**
  * Прототип хранилища данных туриста
  * @module TouristStorage
  * @constructor
  * @param {Integer} serviceId - ID услуги
  */
  var TouristStorage = ktStorage.extend(function(touristId) {
    this.namespace = 'TouristStorage';

    this.touristId = touristId;

    // услуги, с которыми связан турист
    this.linkedServices = [];

    // карты программ лояльности
    this.bonusCards = [];

    // структура дополнительных полей
    this.customFields = null;
  });

  KT.addMixin(TouristStorage,'Dispatcher');

  /**
  * Инициализация хранилища
  * @param {Object} touristData - данные туриста (/getOrderTourist)
  */
  TouristStorage.prototype.initialize = function(touristData) {
    if (touristData.touristId !== this.touristId) {
        KT.error(
          this.namespace+': ' +
          'попытка инициализации данными другого туриста,' +
          ' текущий: ' . this.touristId +
          ' данные от: '. serviceData.touristId
        );
    }

    var isNotEmpty = function(v) {
      return !(v === undefined || v === null || v === '');
    };

    // ФИО туриста
    this.firstname = htmlDecode(touristData.firstName);
    this.lastname = htmlDecode(touristData.lastName);
    this.middlename = isNotEmpty(touristData.middleName) ?
      htmlDecode(touristData.middleName) : null;

    // пол
    this.sex = (touristData.sex === 1) ? 'male' : 'female';

    // контакты
    this.email = isNotEmpty(touristData.email) ? touristData.email : null;
    this.phone = {
      countryCode: null,
      cityCode: null,
      number: null
    };

    if (isNotEmpty(touristData.phone)) {
      var phone = String(touristData.phone).replace(/\s/g,'');
      var phoneparsed = phone.match(/^\+?(\d+)\((\d+)\)(\d+)$/);

      if (phoneparsed === null) {
        this.phone.number = phone;
      } else {
        this.phone.countryCode = phoneparsed[1];
        this.phone.cityCode = phoneparsed[2];
        this.phone.number = phoneparsed[3];
      }
    }

    // признак турлидера
    this.isTourleader = touristData.isTourLeader;

    // дата рождения и возраст
    this.birthdate = (touristData.birthdate !== null) ? moment(touristData.birthdate,'YYYY-MM-DD') : null;
    this.age = (this.birthdate !== null ) ? 
      moment.duration(moment().valueOf() - this.birthdate.valueOf()).asYears() : 
      null;

    // документы
    var documentData = touristData.document;
    this.document = {
      series: documentData.serialNumber,
      number: documentData.number,
      type: documentData.documentType,
      firstname: htmlDecode(documentData.firstName),
      lastname: htmlDecode(documentData.lastName),
      middlename: isNotEmpty(documentData.middleName) ?
        htmlDecode(documentData.middleName) : null,
      //issueDate: moment(documentData.issueDate,'YYYY-MM-DD'),
      expiryDate: (documentData.expiryDate !== null) ? 
        moment(documentData.expiryDate,'YYYY-MM-DD') : null,
      //issueDepartment: documentData.issueDepartment,
      citizenship: documentData.citizenship
    };

    var self = this;

    // привязка к сервисам
    if (Array.isArray(touristData.services)) {
      touristData.services.forEach(function(service) {
        self.linkedServices.push({
          'serviceId': service.serviceId,
          'loyalityProviderId': service.aviaLoyalityProgrammId,
          'loyalityCardNumber': service.bonuscardNumber
        });
      });
    }

    // карты программ лояльности
    this.bonusCards = Array.isArray(touristData.bonusCards) ? touristData.bonusCards : [];

    // дополнительные поля
    this.customFields = (
        Array.isArray(touristData.touristAdditionalData) && 
        touristData.touristAdditionalData.length > 0
      ) ? touristData.touristAdditionalData : null;
  };

  /**
  * Инициализация хранилища по данным пользователя
  * @param {Object} userData - данные пользователя (ль getClientUser) 
  */
  TouristStorage.prototype.initializeFromUser = function(userData) {
    var isNotEmpty = function(v) {
      return !(v === undefined || v === null || v === '');
    };

    // ID связанного пользователя
    this.userId = userData.user.userId;

    // ФИО туриста
    this.firstname = htmlDecode(userData.user.name);
    this.lastname = htmlDecode(userData.user.surname);
    this.middlename = isNotEmpty(userData.user.secondName) ?
      htmlDecode(userData.user.secondName) : null;

    // пол
    this.sex = (userData.user.prefix === 1) ? 'male' : 'female';

    // контакты
    this.email = isNotEmpty(userData.user.email) ? userData.user.email : null;
    this.phone = {
      countryCode: null,
      cityCode: null,
      number: null
    };

    if (isNotEmpty(userData.user.contactPhone)) {
      var phone = String(userData.user.contactPhone).replace(/\s/g,'');
      var phoneparsed = phone.match(/^\+?(\d+)\((\d+)\)(\d+)$/);

      if (phoneparsed === null) {
        this.phone.number = phone;
      } else {
        this.phone.countryCode = phoneparsed[1];
        this.phone.cityCode = phoneparsed[2];
        this.phone.number = phoneparsed[3];
      }
    }

    // признак турлидера
    this.isTourleader = false;

    // дата рождения и возраст
    this.birthdate = (userData.user.birthDate !== null) ? moment(userData.user.birthDate,'YYYY-MM-DD') : null;
    this.age = (userData.user.birthDate !== null ) ? 
      moment.duration(moment().valueOf() - this.birthdate.valueOf()).asYears() : 
      null;

    // документы
    this.document = {
      documentId: userData.document.docId,
      series: userData.document.docSerial,
      number: userData.document.docNumber,
      type: userData.document.docType,
      firstname: htmlDecode(userData.document.firstName),
      lastname: htmlDecode(userData.document.lastName),
      middlename: isNotEmpty(userData.document.middleName) ?
        htmlDecode(userData.document.middleName) : null,
      //issueDate: moment(documentData.issueDate,'YYYY-MM-DD'),
      expiryDate: (userData.document.docExpiryDate !== null) ? 
        moment(userData.document.docExpiryDate,'YYYY-MM-DD') : null,
      //issueDepartment: documentData.issueDepartment,
      citizenship: userData.document.citizenship
    };

    // карты программ лояльности
    this.bonusCards = Array.isArray(userData.user.bonusCards) ? userData.user.bonusCards : [];

    // дополнительные поля
    this.customFields = Array.isArray(userData.addData) ? 
      userData.addData : null;
  };

  /**
  * Возвращает строку с номером телефона
  * @return {String|null} - номер телефона или null если его нет
  */
  TouristStorage.prototype.getPhoneNumber = function() {
    if (this.phone.number !== null) {
      if (this.phone.countryCode !== null) {
        return '+' + this.phone.countryCode +
          '(' + this.phone.cityCode + ')' +
          this.phone.number;
      } else {
        return this.phone.number;
      }
    } else {
      return null;
    }
  };

  return TouristStorage;
}));

/* global ktStorage */
(function(global,factory){

    KT.storage.InvoiceStorage = factory();

}(this,function() {
  /**
  * Прототип хранилища данных счета
  * @module InvoiceStorage
  * @constructor
  * @param {Integer} invoiceId - ID услуги
  */
  var InvoiceStorage = ktStorage.extend(function(invoiceId) {
    this.namespace = 'InvoiceStorage';

    this.invoiceId = invoiceId;
  });

  KT.addMixin(InvoiceStorage,'Dispatcher');

  // статусы счета
  InvoiceStorage.prototype.statuses = {
    'WAIT': 1,
    'INVOICED': 2,
    'PARTIAL_PAID': 3,
    'PAID': 4,
    'CANCELLED': 5
  };

  /**
  * Инициализация хранилища
  * @param {Object} invoiceData - данные счета (/getOrder)
  */
  InvoiceStorage.prototype.initialize = function(invoiceData) {
    if (invoiceData.invoiceId !== this.invoiceId) {
        KT.error(
          this.namespace+': ' +
          'попытка инициализации данными другого инвойса,' +
          ' текущая: ' . this.invoiceId +
          ' данные от: '. invoiceData.invoiceId
        );
    }

    // номер счета
    this.number = invoiceData.invoiceNum;

    // статус счета
    this.status = +invoiceData.status;

    // описание счета
    this.description = invoiceData.description;

    // дата выставления
    this.creationDate = moment(invoiceData.creationDate,'YYYY-MM-DD HH:mm:ss');

    // сумма счета и валюта
    this.sum = Number(invoiceData.invoiceSum);
    this.currency = invoiceData.invoiceCur;

    // детализация по услугам
    this.serviceDetails = {};

    var self = this;

    invoiceData.InvoiceServices.forEach(function(service) {
      self.serviceDetails[service.serviceId] = {
        'sum': Number(service.serviceSum),
        'name': service.serviceName
      };
    });
  };

  /**
  * Возвращает детализацию счета по услугам в виде массива
  * @return {Array} - информация по услуге в составе счета
  */
  InvoiceStorage.prototype.getServiceDetails = function() {
    var serviceDetails = [], srv;

    for (var serviceId in this.serviceDetails) {
      if (this.serviceDetails.hasOwnProperty(serviceId)) {
        srv = $.extend(true,{},this.serviceDetails[serviceId]);
        srv.serviceId = serviceId;
        serviceDetails.push(srv);
      }
    }

    return serviceDetails;
  };

  return InvoiceStorage;
}));

/* global moment */
/* global KT */
/* global ktStorage */

(function(global,factory){

    KT.storage.OrderStorage = factory();

}(this,function() {
  /**
  * Прототип хранилища данных заявки
  * @module OrderStorage
  * @constructor
  * @param {Integer} orderId - ID заявки
  */
  var OrderStorage = ktStorage.extend(function(orderId) {
    this.namespace = 'OrderStorage';

    // состояние загрузки данных
    this.loadStates = {
      'orderdata': null,
      'servicedata': null,
      'invoicedata': null,
      'touristdata': null,
      'transitionsdata': null
    };

    // Идентификатор заявки (KT)
    this.orderId = orderId;
    // список услуг (ServiceStorage)
    this.services = {};
    // список счетов
    this.invoices = {};
    // список туристов (TouristStorage)
    this.tourists = {};
    // документы заявки
    this.documents = [];
    // история заявки
    this.history = [];

    // счетчик оставшихся активнх запросов по валидации действий над услугами
    // см. checkWorkflow - validate
    this.pendingValidations = 0;

    // результат валидации для группы услуг: {действие: вердикт}
    this.validatedActions = {};
  });

  KT.addMixin(OrderStorage,'Dispatcher');

  /**
  * Инициализация хранилища данными из getOrder
  * @param {Object} orderData - данные заявки
  */
  OrderStorage.prototype.initialize = function(orderData) {
    if (orderData.orderId !== this.orderId) {
        KT.error(
          this.namespace+': ' +
          'попытка инициализации данными другой заявки,' +
          ' текущая: ' . this.orderId +
          ' данные от: '. orderData.orderId
        );
    }

    var isNotEmpty = function(v) {
      return !(v === undefined || v === null || v === '');
    };

    // Идентификаторы заявки в шлюзах
    this.orderIdGp = isNotEmpty(orderData.orderIdGp) ? orderData.orderIdGp : null;
    this.orderIdUtk = isNotEmpty(orderData.orderIdUtk) ? orderData.orderIdUtk : null;

    // статусная информация
    this.status = +orderData.status;
    this.isVip = orderData.VIP;
    this.isArchived = orderData.archive;
    this.isOffline = false;
    this.isAddTouristAllowed = false;

    // ID контракта заявки
    this.contractId = orderData.contractID;

    // даты создания/начала/окончания заявки
    /** @todo дата создания пока не приходит */
    this.creationDate = (orderData.orderDate !== null) ?
      moment(orderData.orderDate,'YYYY-MM-DD HH:mm:ss') : null;
    this.startDate = moment(orderData.startDate,'YYYY-MM-DD HH:mm:ss');
    this.endDate = moment(orderData.endDate,'YYYY-MM-DD HH:mm:ss');

    // данные создателя заявки
    this.creator = (orderData.creator.id === null) ? null : {
      id: orderData.creator.id,
      firstname: orderData.creator.firstName,
      lastname: orderData.creator.lastName,
      middlename: isNotEmpty(orderData.creator.middleName) ?
        orderData.creator.middleName : null
    };

    // тип компании, для которой создается заявка (агентство, корпоратор)
    this.companyRoleType = (function(roleType) {
      switch (roleType) {
        case 1: return 'op';
        case 2: return 'agent';
        case 3: return 'corp';
        default: return KT.profile.userType;
      }
    }(orderData.companyRoleType));

    // данные ответственного менеджера КМП заявки
    this.kmpManager = (orderData.managerKMP.id === null) ? null : {
      id: orderData.managerKMP.id,
      firstname: orderData.managerKMP.firstName,
      lastname: orderData.managerKMP.lastName,
      middlename: isNotEmpty(orderData.managerKMP.middleName) ?
        orderData.managerKMP.middleName : null
    };

    // данные ответственного менеджера клиента заявки
    this.clientManager = (orderData.clientManager.id === null) ? null : {
      id: orderData.clientManager.id,
      firstname: orderData.clientManager.firstName,
      lastname: orderData.clientManager.lastName,
      middlename: isNotEmpty(orderData.clientManager.middleName) ?
        orderData.clientManager.middleName : null
    };

    // данные клиента заявки
    this.client = {
      id: orderData.agentId,
      name: orderData.agencyName
    };

    // данные турлидера заявки
    this.tourleader = (
        orderData.touristFirstName !== null &&
        orderData.touristLastName !== null
      ) ? {
      firstname: orderData.touristFirstName,
      lastname: orderData.touristLastName,
      middlename: null, /** @todo отчество турлидера не приходит */
      phone: isNotEmpty(orderData.liderPhone) ? orderData.liderPhone : null,
      email: isNotEmpty(orderData.liderEmail) ? orderData.liderEmail : null
    } : null;

    // количество туристов
    this.touristsAmount = orderData.touristsNums;

    // данные привязки туристов к услугам
    this.servicesTouristLinkage = {};

    var self = this;

    // заполнение услуг
    if (Array.isArray(orderData.services)) {
      orderData.services.forEach(function(service) {
        if (self.services[service.serviceID] === undefined) {
          self.services[service.serviceID] = new KT.storage.ServiceStorage(service.serviceID);
        }

        self.services[service.serviceID].initialize(service);
        if (self.services[service.serviceID].isOffline) {
          self.isOffline = true;
        }
      });
    }

    /** @todo это все должно уйти в checkworkflow, наверное? */
    var allowedStatuses = [ 0,1,2,5,9,10 ];
    if (allowedStatuses.indexOf(this.status) !== -1) {
      this.isAddTouristAllowed = true;
    }

    this.loadStates.orderdata = 'loaded';
    this.dispatch('initialized', this);
  };

  /**
  * Инициализация хранилища для новой заявки (установка настроек по умолчанию)
  * @param {Object} bareData - начальные данные заявки (клиент, создатель, ...)
  */
  OrderStorage.prototype.initializeBare = function(bareData) {
    // Идентификаторы заявки в шлюзах
    this.orderIdGp = null;
    this.orderIdUtk = null;

    // статусная информация
    this.status = 0;
    this.isVip = false;
    this.isArchived = false;
    this.isOffline = false;
    this.isAddTouristAllowed = true;

    // ID контракта заявки
    this.contractId = bareData.contractId;

    // даты создания/начала/окончания заявки
    /** @todo дата создания пока не приходит */
    this.creationDate = moment();
    this.startDate = this.creationDate;
    this.endDate = this.creationDate;

    // данные создателя заявки
    this.creator = {
      'id': KT.profile.user.id,
      'firstname': KT.profile.user.firstName,
      'lastname': KT.profile.user.lastName,
      'middlename': KT.profile.user.middleName
    };

    // тип компании, для которой создается заявка (агентство, корпоратор)
    this.companyRoleType = (function(roleType) {
      switch (roleType) {
        case 1: return 'op';
        case 2: return 'agent';
        case 3: return 'corp';
        default: return KT.profile.userType;
      }
    }(bareData.clientType));

    // данные ответственного менеджера КМП заявки
    this.kmpManager = null;

    // данные ответственного менеджера клиента заявки
    this.clientManager = null;

    // данные клиента заявки
    this.client = {
      id: bareData.clientId,
      name: bareData.clientName
    };

    // данные турлидера заявки
    this.tourleader = null;

    // количество туристов
    this.touristsAmount = 0;

    this.dispatch('initialized', this);
  };

  /**
  * Сохранение информации по документам заявки 
  * @param {Array} documents - документы
  */
  OrderStorage.prototype.setDocuments = function(documents) {
    this.documents = documents;
    this.dispatch('setDocuments', {'items': documents});
  };

  /**
  * Сохранение истории заявки
  * @param {Array} history - история заявки 
  */
  OrderStorage.prototype.setHistory = function(history) {
    this.history = history;
    this.dispatch('setHistory', {'records': history});
  };

  /**
  * Сохранение информации по услугам
  * @param {Array} services - информация по услугам (/getOrderOffer)
  */
  OrderStorage.prototype.setServices = function(services) {
    var self = this;

    services.forEach(function(service) {
      if (self.services[service.serviceId] === undefined) {
        KT.error(this.namespace + ': ' + 'неизвестная услуга:' + service.serviceId);
        return;
      }

      self.services[service.serviceId].updateFullInfo(service);
    });

    // если туристы уже загружены, обновить для услуг информацию по возрастному составу
    if (this.loadStates.touristdata !== null) {
      this.updateServiceTourists();
    }

    this.loadStates.servicedata = 'loaded';
    this.dispatch('setServices',this);
  };

  /**
  * Возвращает список услуг как массив
  * @return {ServiceStorage[]} массив услуг
  */
  OrderStorage.prototype.getServices = function() {
    var services = [];

    for(var serviceId in this.services) {
      if (this.services.hasOwnProperty(serviceId)) {
        services.push(this.services[serviceId]);
      }
    }

    return services;
  };

  /**
  * Возвращает список идентификаторов услуг в заявке
  * @return {Integer[]} массив идентификаторов
  */
  OrderStorage.prototype.getServiceIds = function() {
    var serviceIds = [];

    for(var serviceId in this.services) {
      if (this.services.hasOwnProperty(serviceId)) {
        serviceIds.push(serviceId);
      }
    }

    return serviceIds;
  };

  /**
  * Сохраняет информацию по счетам
  * @param {Array} invoices - массив счетов (/getOrderInvoices)
  */
  OrderStorage.prototype.setInvoices = function(invoices) {
    var self = this;

    for (var serviceId in this.services) {
      if (this.services.hasOwnProperty(serviceId)) {
        this.services[serviceId].invoiceSum = 0;
      }
    }

    invoices.forEach(function(invoice) {
      if (self.invoices[invoice.invoiceId] === undefined) {
        self.invoices[invoice.invoiceId] = new KT.storage.InvoiceStorage(invoice.invoiceId);
      }
      self.invoices[invoice.invoiceId].initialize(invoice);
    });

    this.loadStates.invoicedata = 'loaded';
    this.dispatch('setInvoices',this);
  };

  /**
  * Возвращает список счетов как массив
  * @return {InvoiceStorage[]} массив счетов
  */
  OrderStorage.prototype.getInvoices = function() {
    var invoices = [];

    for(var invoiceId in this.invoices) {
      if (this.invoices.hasOwnProperty(invoiceId)) {
        invoices.push(this.invoices[invoiceId]);
      }
    }

    return invoices;
  };

  /**
  * Сохранение информации по туристам
  * @param {Array} tourists - информация по туристам (/getOrderTourists)
  */
  OrderStorage.prototype.setTourists = function(tourists) {
    var self = this;

    this.servicesTouristLinkage = {};

    tourists.forEach(function(tourist) {
      if (self.tourists[tourist.touristId] === undefined) {
        self.tourists[tourist.touristId] = new KT.storage.TouristStorage(tourist.touristId);
      }
      self.tourists[tourist.touristId].initialize(tourist);
      var TouristStorage = self.tourists[tourist.touristId];

      TouristStorage.linkedServices.forEach(function(linkedService) {
        if (!self.servicesTouristLinkage.hasOwnProperty(linkedService.serviceId)) {
          self.servicesTouristLinkage[linkedService.serviceId] = {};
        }
        self.servicesTouristLinkage[linkedService.serviceId][TouristStorage.touristId] = {
          'touristId': TouristStorage.touristId,
          'isAttached': true, /** @deprecated?  */
          'firstname': TouristStorage.firstname,
          'lastname': TouristStorage.lastname,
          'middlename': TouristStorage.middlename,
          'loyalityProviderId': linkedService.loyalityProviderId,
          'loyalityCardNumber': linkedService.loyalityCardNumber
        };
      });
    });

    // если услуги уже загружены, обновить для услуг информацию по возрастному составу
    if (this.loadStates.servicedata !== null) {
      this.updateServiceTourists();
    }

    this.loadStates.touristdata = 'loaded';
    this.dispatch('setTourists', this);
  };

  /**
  * Возвращает список туристов как массив
  * @return {TouristStorage[]} массив туристов
  */
  OrderStorage.prototype.getTourists = function() {
    var tourists = [];

    for (var touristId in this.tourists) {
      if (this.tourists.hasOwnProperty(touristId)) {
        tourists.push(this.tourists[touristId]);
      }
    }

    return tourists;
  };

  /**
  * Обновление информации о туристах в составе услуг (возрастной состав)
  * @param {Integer} [serviceId] - если указано, обновить данные только по этой услуге 
  */
  OrderStorage.prototype.updateServiceTourists = function(updateServiceId) {
    var self = this;

    var updateServiceTourists = function(serviceId) {
        if (self.servicesTouristLinkage.hasOwnProperty(serviceId)) {
          self.services[serviceId].tourists = self.servicesTouristLinkage[serviceId];
        }
        self.services[serviceId].setTouristAges(self.tourists);
    };

    if (updateServiceId !== undefined) {
      updateServiceTourists(updateServiceId);
    } else {
      for (var serviceId in this.services) {
        if (this.services.hasOwnProperty(serviceId)) {
          updateServiceTourists(serviceId);
        }
      }
    }
  };

  /**
  * Сохранение туриста в структуре заявки
  * @param {TouristStorage} Tourist - турист
  */
  OrderStorage.prototype.saveTourist = function(Tourist) {
    this.tourists[Tourist.touristId] = Tourist;

    if (Tourist.isTourleader || this.tourleader === null) {

      this.tourleader = {
        firstname: Tourist.firstname,
        lastname: Tourist.lastname,
        middlename: null, /** @todo отчество турлидера не приходит */
        phone: Tourist.getPhoneNumber(),
        email: Tourist.email
      };
    }

    // количество туристов
    this.touristsAmount = this.getTourists().length;

    this.dispatch('savedTourist', {'touristId': Tourist.touristId});
  };

  /**
  * Сохранение статусов привязки туристов к услуге
  * @param {Integer} serviceId - ID услуги
  * @param {Object} linkageInfo - данные привязки туристов
  */
  OrderStorage.prototype.saveServiceLinkage = function(serviceId, linkageInfo) {
    var self = this;
    var service = this.services[serviceId];

    linkageInfo.forEach(function(touristLinkage) {
      var tourist = self.tourists[touristLinkage.touristId];
      if (touristLinkage.link === true) {
        tourist.linkedServices.push({
          'serviceId': service.serviceId,
          'loyalityProviderId': touristLinkage.aviaLoyalityProgrammId,
          'loyalityCardNumber': touristLinkage.bonuscardNumber
        });
        service.tourists[tourist.touristId] = {
          'touristId': tourist.touristId,
          'isAttached': true, /** @deprecated?  */
          'firstname': tourist.firstname,
          'lastname': tourist.lastname,
          'middlename': tourist.middlename,
          'loyalityProviderId': touristLinkage.aviaLoyalityProgrammId,
          'loyalityCardNumber': touristLinkage.bonuscardNumber
        };
      } else {
        tourist.linkedServices = tourist.linkedServices.filter(function(linkedService) {
          return (linkedService.serviceId !== serviceId);
        });
        delete service.tourists[tourist.touristId];
      }
    });
    
    service.setTouristAges(this.tourists);

    this.dispatch('savedServiceLinkage', {
      'serviceId' : serviceId
    });
  };

  /**
  * Удаление туриста
  * @param {Integer} touristId - ID удаляемого туриста
  */
  OrderStorage.prototype.removeTourist = function(touristId) {
    if (this.tourists[touristId].isTourleader) {
      this.tourleader = null;
    }
    delete this.tourists[touristId];
    this.dispatch('touristRemoved', {'touristId': touristId});
  };

  /**
  * Сохранение данные о доступных действиях
  * @param {Array} transitions - списов доступных действий (/checkWorkflow)
  */
  OrderStorage.prototype.setAllowedTransitions = function(transitions) {
    var self = this;

    transitions.forEach(function(service) {
      self.services[service.serviceId].setAllowedTransitions(service.controls);
    });

    this.loadStates.transitionsdata = 'loaded';
    this.dispatch('setAllowedTransitions',this);
  };
  
  /**
  * Формирование структуры привязки туристов к услуге
  * @param {Integer} serviceId - ID редактируемой услуги
  * @param {Object} newTouristLinkage - список туристов со статусами привязки и дополнительной информацией
  * @return {Array} - параметры привязки туристов для передачи в BE
  */
  OrderStorage.prototype.createLinkageStructure = function(serviceId, newTouristLinkage) {
    var linkageInfo = [];
    var linkedTourists = {};
    var service = this.services[serviceId];

    /* Сохранить уже привязанных туристов */
    service.getServiceTourists().forEach(function(tourist) {
      linkedTourists[tourist.touristId] = true;
    });

    for (var touristId in newTouristLinkage) {
      if (newTouristLinkage.hasOwnProperty(touristId)) {
        var isAttached = newTouristLinkage[touristId].state;
        if (isAttached) {
          var tourist = this.tourists[touristId];

          if (tourist.document.expiryDate !== null) {
            var docExpiry = tourist.document.expiryDate.valueOf();
            if (docExpiry <= service.endDate.valueOf()) {
              KT.notify('touristLinkageNotAllowedByDocument');
              return false;
            }
          }

          linkageInfo.push({
            'touristId': +touristId,
            'bonuscardNumber': newTouristLinkage[touristId].loyalityCardNumber,
            'aviaLoyalityProgrammId': newTouristLinkage[touristId].loyalityProviderId,
            'link': true
          });
        } else if (linkedTourists[touristId] === true && !isAttached) {
          linkageInfo.push({
            'touristId': +touristId,
            'bonuscardNumber': null,
            'aviaLoyalityProgrammId': null,
            'link': false
          });
        }
      }
    }

    return linkageInfo;
  };

  /**
  * Формирование параметров для вызова команд для работы с услугой
  * @param {String} command - название команды
  * @param {Integer} serviceId - ID услуги для проверки
  * @return {Object|false} - набор параметров (actionParams) или false в случае ошибки формирования
  */
  OrderStorage.prototype.getServiceCommandParams = function(command, serviceId) {
    switch(command) {
      // Команда запуска бронирования
      case 'BookStart':
        if (this.services[serviceId] === undefined) {
          console.error('BookStart: услуга ' + serviceId + ' не найдена');
          return false;
        }
        var service = this.services[serviceId];

        return {
          'serviceId':serviceId,
          'agreementSet':service.isTOSAgreementSet
        };
      // Команда отмены брони
      case 'BookCancel':
        return {
          'serviceId': serviceId,
          'createPenaltyInvoice': true
        };
      // Команда выписки билетов
      case 'IssueTickets':
        return {
          'serviceId': serviceId
        };
      // Команда выставления счетов
      case 'PayStart':
        return {
          'serviceId': serviceId
        };
      // Команда выставления статуса ручного режима
      case 'Manual':
        return {
          'serviceId': serviceId
        };
      // Команда запроса на изменение / изменения услуги
      case 'ServiceChange':
        return {
          'serviceId': serviceId
        };
      // Команда отмены услуги
      case 'ServiceCancel':
        return {
          'serviceId': serviceId
        };

      default:
        return false;
    }
  };

  /**
  * Сохранение результата валидации действий над услугами
  * @param {String} action - валидируемое действие
  * @param {Object|false} validationResult - данные процедуры валидации или false в случае ошибки
  */
  OrderStorage.prototype.setValidatedAction = function(action, validationResults) {
    var self = this;

    self.pendingValidations--;

    if (validationResults === false) {
      self.validatedActions[action] = {'validated': false};
    } else {
      self.validatedActions[action] = {'validated': true};

      validationResults.forEach(function(service) {
        if (service.validationResult === false) {
          self.validatedActions[action].validated = false;
        }
      });
    }

    if (this.pendingValidations === 0) {
      this.dispatch('setValidatedActions', this);
    }
  };

  return OrderStorage;
}));

/* global transliterate */
(function(global,factory) {

    KT.crates.OrderEdit.tourists.view = factory(KT.crates.OrderEdit);

}(this, function(crate) {
  /**
  * Редактирование заявки: туристы
  * @constructor
  * @param {Object} module - хранилище родительского модуля
  * @param {Object} [options] - Объект конфигурации класса
  */
  var modView = function(module,options) {
    this.mds = module;
    if (options === undefined) { options = {}; }
    this.config = $.extend(true,{
      'templateUrl':'/cabinetUI/orders/getTemplates',
      'templates':{
        touristForm:'orderEdit/tourists/touristForm',
        touristFormActions:'orderEdit/tourists/touristFormActions',
        touristFormBonusCard: 'orderEdit/tourists/touristFormBonusCard',
        touristFormNoBonusCards: 'orderEdit/tourists/touristFormNoBonusCards',
        touristFormBonusCardActions: 'orderEdit/tourists/touristFormBonusCardActions',
        touristListControls:'orderEdit/tourists/touristListControls',
        touristListEmpty:'orderEdit/tourists/touristListEmpty',
        clndr:'clndrTemplate',
        // custom fields
        TextCF: 'orderEdit/customFields/textCF',
        TextAreaCF: 'orderEdit/customFields/textAreaCF',
        NumberCF: 'orderEdit/customFields/numberCF',
        DateCF: 'orderEdit/customFields/dateCF'
      }
    },options);

    this.$tabLabel = $('#tab-headers').find('.tab-headers__link[data-tab="tourists"]');

    this.$touristList = $('#order-edit-tourists');
    this.$touristListControls = $('#order-edit-tourists--controls');

    // ссылки на формы туристов по ID туриста
    this.touristForms = {};

    // флаг наличия турлидера в заявке
    this.tlFlag = false; 

    // дополнительные поля туристов
    this.customFields = {};

    // список объектов документов (тип, валидация, ...)
    this.doctypes = [];
    for (var doctype in KT.config.touristDocuments) {
      if (KT.config.touristDocuments.hasOwnProperty(doctype)) {
        this.doctypes.push({
          value: doctype,
          text: KT.config.touristDocuments[doctype].docname
        });
      }
    }

    // список стран для выбора в поле "Гражданство"
    this.countries = [];
    for(var country in KT.countryCodes) {
      if (KT.countryCodes.hasOwnProperty(country)) {
        this.countries.push({
          value:country,
          text:KT.countryCodes[country]
        });
      }
    }
  };

  /** Отображение списка туристов
  * @param {OrderStorage} OrderStorage - данные заявки
  */
  modView.prototype.renderTouristList = function(OrderStorage) {
    var _instance = this;

    _instance.$touristList.empty();
    _instance.touristForms = {};

    var actions = {
      'allowAdd': OrderStorage.isAddTouristAllowed
    };

    _instance.$touristListControls.html(
      Mustache.render(_instance.mds.tpl.touristListControls, actions)
    );

    var Tourists = OrderStorage.getTourists();

    if (Tourists.length === 0) {
      _instance.$tabLabel.addClass('empty');
      _instance.$touristList.html(Mustache.render(_instance.mds.tpl.touristListEmpty, {}));
      return;
    } else {
      _instance.$tabLabel.removeClass('empty');
    }

    // турлидер(-ы) должен быть в начале списка
    Tourists.sort(function(a,b) {
      if (a.isTourleader) {
        return (b.isTourleader) ? 0 : -1;
      } else {
        return (b.isTourleader) ? 1 : 0;
      }
    });

    var isTourleaderSet = (OrderStorage.tourleader === null) ? false : true;
    var isOffline = (OrderStorage.isOffline !== true) ? false : true;
    var orderClientType = OrderStorage.companyRoleType;

    Tourists.forEach(function(Tourist) {
      var touristInfo = _instance.mapTouristInfo(Tourist, isTourleaderSet, OrderStorage.isOffline);

      touristInfo.actions = Mustache.render(_instance.mds.tpl.touristFormActions, isOffline ? {} : {
        'allowedSave': true,
        'allowedUserCreation': (orderClientType === 'corp'),
        'allowedReset': true,
        'allowedDelete': true,
      });

      _instance.touristForms[Tourist.touristId] = $(Mustache.render(_instance.mds.tpl.touristForm, touristInfo))
        .appendTo(_instance.$touristList);

      _instance.initControls(Tourist.touristId);
      _instance.initCustomFields(Tourist);
    });

  };

  /**
  * Добавление новой формы туриста
  * @param {OrderStorage} OrderStorage - хранилище данных заявки
  */
  modView.prototype.addTouristForm = function(OrderStorage) {
    var _instance = this;
    var isTourleaderSet = (OrderStorage.tourleader === null) ? false : true;
    var orderClientType = OrderStorage.companyRoleType;
    var isNewOrder = (OrderStorage.orderId === 'new');
    
    var tempTouristId = 'tmp' + moment().valueOf();

    if (this.$touristList.children('.js-ore-tourist').length === 0) {
      this.$touristList.empty();
    }

    var formActions = Mustache.render(this.mds.tpl.touristFormActions, {
        'allowedSave': true,
        'allowedUserCreation': (orderClientType === 'corp'),
        'allowedReset': !isNewOrder,
        'allowedDelete': !isNewOrder
    });

    this.touristForms[tempTouristId] = $(Mustache.render(this.mds.tpl.touristForm, {
        'touristId': tempTouristId,
        'isNewTourist': true,
        'isEditable': true,
        'allowedTouristSelect': (orderClientType === 'corp'),
        'isTourleaderSet':isTourleaderSet,
        'bonusCards': Mustache.render(_instance.mds.tpl.touristFormNoBonusCards, {}),
        'bonusCardsActions': Mustache.render(_instance.mds.tpl.touristFormBonusCardActions, {
            'add': true
          }),
        'actions': formActions
      }))
      .addClass('active')
      .appendTo(this.$touristList);

    if (orderClientType === 'corp') {
      this.touristForms[tempTouristId].find('.js-ore-tourist--suggest')
        .selectize({
          plugins: {'key_down': { start: 2 }},
          openOnFocus: true,
          create: false,
          selectOnTab: true,
          highlight: false,
          loadThrottle: 300,
          valueField: 'docId',
          labelField: 'name',
          sortField:'seqid',
          options:[],
          render: {
            item: function(user) {
              return '<div class="ore-tourist-suggest__item">' +
                user.lastName + ' ' + user.firstName + 
                ' (' + user.docSerial + ' ' + user.docNumber + ')' +
                '</div>';
            },
            option: function(user) {
              return '<div class="ore-tourist-suggest__option">' +
                user.lastName + ' ' + user.firstName + 
                ' (' + user.docSerial + ' ' + user.docNumber + ')' +
                '</div>';
            }
          },
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

            KT.apiClient.getClientUserSuggest({
              data: {
                'companyId': _instance.mds.OrderStorage.client.id,
                'substringFIO': query
              },
              error: function() {
                callback();
              },
              success: function(response) {
                var $selinp = self.$control;

                if (+response.status === 0 && Array.isArray(response.body)) {
                  response.body.forEach(function(item, i) {
                    item.seqid = i + 1;
                  });
                  callback(response.body);

                  if (response.body.length === 0) {
                    $selinp.addClass('warning');
                    setTimeout(function() {
                      $selinp.removeClass('warning');
                    }, 2000);
                  }
                } else {
                  callback();
                  self.refreshOptions(true);
                  $selinp.addClass('warning');
                  setTimeout(function() {
                    $selinp.removeClass('warning');
                  }, 2000);
                }
              }
            });
          },
          onType:function(str) {
            if (str.length < 2) {
              this.close();
              this.clearOptions();
            }
          },
          onItemRemove: function() {
            this.clearOptions();
          },
          onChange: function(val) {
            if (val === '') {
              this.trigger('item_remove');
            }
          }
        });
    }

    var yh = this.touristForms[tempTouristId].offset().top;

    /** @todo this is for baron scroller, redefine in KT core? */
    $('#main-scroller').stop().animate({
        scrollTop: $('#main-scroller').scrollTop() + (yh-20)
      }, 300, 'swing');

    this.initControls(tempTouristId);
  };

  /**
  * Отображение лоадера в процессе обработки туриста
  * @todo перенести в центральный класс вьюх?
  * @param {Object} $touristForm - форма редактирования туриста
  */
  modView.prototype.renderPendingProcess = function($touristForm) {
    $touristForm.find('.js-ore-tourist--actions')
      .html(Mustache.render(KT.tpl.spinner, {'type':'medium'}));
  };

  /**
  * Обновление информации по определенному туристу
  * @param {TouristStorage} Tourist - турист
  * @param {Boolean} isOffline - признак оффлайновости заявки
  */
  modView.prototype.refreshTouristForm = function(Tourist, isOffline) {
    if (isOffline !== true) { isOffline = false; }
    var isNewOrder = (this.mds.OrderStorage.orderId === 'new');
    var orderClientType = this.mds.OrderStorage.companyRoleType;

    this.$tabLabel.removeClass('empty');
    console.log('tourist: ');
    console.log(Tourist);
    var $target = this.touristForms[Tourist.touristId];
    
    var touristInfo = this.mapTouristInfo(Tourist, false, isOffline);
    touristInfo.actions = Mustache.render(this.mds.tpl.touristFormActions, isOffline ? {} : {
      'allowedSave': true,
      'allowedUserCreation': (orderClientType === 'corp'),
      'allowedReset': true,
      'allowedDelete': !isNewOrder,
    });

    var $newForm = $(Mustache.render(this.mds.tpl.touristForm, touristInfo));
    $target.off().replaceWith($newForm);
    $newForm.addClass('active');

    this.touristForms[Tourist.touristId] = $newForm;

    this.initControls(Tourist.touristId);
    this.initCustomFields(Tourist);
  };

  /**
  * Обновление действий с формой туриста
  * @param {Obejct} $tourist - форма турисиа
  * @param {Boolean} isOffline - признак оффлайновости заявки
  */
  modView.prototype.refreshTouristFormActions = function($tourist, isOffline) {
    var isNewOrder = (this.mds.OrderStorage.orderId === 'new');
    var orderClientType = this.mds.OrderStorage.companyRoleType;

    $tourist.find('.js-ore-tourist--actions')
      .html(Mustache.render(this.mds.tpl.touristFormActions, isOffline ? {} : {
        'allowedSave': true,
        'allowedUserCreation': (orderClientType === 'corp'),
        'allowedReset': true,
        'allowedDelete': !isNewOrder,
      }));
  };

  /**
  * Удаление формы туриста
  * @param {Integer|String} touristId - ID туриста (или временный ID для новой формы)
  */
  modView.prototype.removeTouristForm = function(touristId) {
    var $tourists = this.$touristList.find('.js-ore-tourist');
    var $touristForm = this.touristForms[touristId];
    var touristsAmount = $tourists.length;
    var adjustScroll = $('#main-scroller').scrollTop();
    var formOffset = $touristForm.offset().top;

    if (formOffset < 15) {
      adjustScroll -= (15 - formOffset);
      if (adjustScroll < 0) { adjustScroll = 0; }
      $('#main-scroller').animate({'scrollTop': adjustScroll}, 400);
    }
    $touristForm.slideUp(400, function() { $(this).remove(); });

    if (touristsAmount === 1) {
      this.$tabLabel.addClass('empty');
    }
  };

  /**
  * Инициализация элементов управления (календари, селекты, ...)
  * @param {String|Integer} touristId - идентификатор туриста
  */
  modView.prototype.initControls = function(touristId) {
    var _instance = this;
    var $touristForm = _instance.touristForms[touristId];

    $touristForm.find('.simpletoggler input').trigger('change');
    
    $touristForm.find('input[name="birthdate"]')
      .each(function() {
          $(this).clndrize({
            'template':_instance.mds.tpl.clndr,
            'eventName':'Дата рождения',
            'showDate':moment().subtract(20,'years'),
            'clndr': {
              'constraints': {
                'startDate':'1915-01-01',
                'endDate':moment().format('YYYY-MM-DD')
              }
            }
          });
      });

    $touristForm.find('select[name="citizenship"]')
      .selectize({
        openOnFocus: true,
        create: false,
        options: _instance.countries,
        selectOnTab:true,
        sortField:'text',
        searchField: 'text',
        render: {
          item: function(item) {
            return '<div data-value="'+item.value+'" class="item" title="'+item.text+'">'+item.text+'</div>';
          },
          option: function(item) {
            return '<div data-value="'+item.value+'" data-selectable class="option">'+item.text+'</div>';
          }
        }
      });
    
    $touristForm.find('input[name="docenddate"]')
      .each(function() {
        $(this).clndrize({
          'template':_instance.mds.tpl.clndr,
          'eventName':'Дата окончания',
          'clndr': {
            'constraints': {
              'startDate':moment().add(2,'days').format('YYYY-MM-DD'),
              'endDate':moment().add(10,'years').format('YYYY-MM-DD')
            }
          }
        });
      });

    $touristForm.find('select[name="doctype"]')
      .selectize({
        openOnFocus: true,
        create: false,
        selectOnTab:true,
        options: _instance.doctypes,
        searchField: 'text',
        onItemAdd: function(v) {
          v = +v;
          if (v === 1 || v === 2) {
            var $cz = this.$input.closest('.js-ore-tourist').find('select[name="citizenship"]');
            if ($cz.val() === '') {
              this.$input.closest('.js-ore-tourist').find('select[name="citizenship"]')[0].selectize.addItem('RU');
            }
          }
        }
      });

    $touristForm.find('input[name="country-prefix"],input[name="city-prefix"],input[name="phone"]')
      .on('keypress',function(e) {
          var char = String.fromCharCode(e.which);
          var check = /[0-9]/;
          if (!check.test(char)) {
            return false;
          }
        });

    KT.Dictionary.getAsList('loyalityPrograms')
      .then(function(loyalityPrograms) {
        $touristForm.find('.js-ore-tourist--bonus-card-program')
          .each(function() {
            var currentValue = $(this).val();

            initLoyalityProgramSelect($(this), {
              options: loyalityPrograms
            });

            if (currentValue !== '') {
              $(this)[0].selectize.addItem(+currentValue);
            }
          });
      });

    _instance.livecheckDocument($touristForm);
  };

  /** 
  * Добавляет форму ввода мильной карты к форме редактирования туриста 
  * @param {Object} $touristForm - форма туриста
  */
  modView.prototype.addBonusCardForm = function($touristForm) {
    var _instance = this;
    var $cardsList = $touristForm.find('.js-ore-tourist--bonus-cards');

    KT.Dictionary.getAsList('loyalityPrograms')
      .then(function(loyalityPrograms) {
        if ($cardsList.children('.js-ore-tourist--bonus-card').length === 0) {
          $cardsList.empty();
        }

        var $loyalityProgramSelect = $(Mustache.render(_instance.mds.tpl.touristFormBonusCard, {}))
          .appendTo($cardsList)
          .find('.js-ore-tourist--bonus-card-program');

        initLoyalityProgramSelect($loyalityProgramSelect, {
          options: loyalityPrograms
        });
      });
  };

  /** 
  * Удаление формы ввода мильной карты
  * @param {Object} $bonusCard - форма мильной карты
  */
  modView.prototype.removeBonusCardForm = function($bonusCard) {
    var $cardsList = $bonusCard.closest('.js-ore-tourist--bonus-cards');
    $bonusCard.remove();

    if ($cardsList.children('.js-ore-tourist--bonus-card').length === 0) {
      $cardsList.html(Mustache.render(this.mds.tpl.touristFormNoBonusCards, {}));
    }
  };

  /**
  * Инициализация дополнительных полей туриста 
  * @param {TouristStorage} Tourist - данные туриста
  */
  modView.prototype.initCustomFields = function(Tourist) {
    if (Tourist.customFields === null || Tourist.customFields.length === 0) {
      return;
    }

    var CustomFieldsFactory = new crate.CustomFieldsFactory(this.mds.tpl);
    var touristId = Tourist.touristId;

    // хак - поля типа textArea отсортировать в конец для нормального отображения
    Tourist.customFields.sort(function(a,b) {
      var typeSorting = (a.typeTemplate === 2) ?
        ((b.typeTemplate === 2) ? 0 : 1) :
        ((b.typeTemplate === 2) ? -1 : 0);
      
      if (typeSorting !== 0) {
        return typeSorting;
      } else {
        return (a.require) ?
          (b.require ? 0 : -1) :
          (b.require ? 1 : 0);
      }
    });

    var self = this;
    self.customFields[touristId] = [];

    Tourist.customFields.forEach(function(fieldData) {
      var customField = CustomFieldsFactory.create(fieldData);
      if (customField !== null) {
        self.customFields[touristId].push(customField);
      }
    });

    var $customFields = $();

    self.customFields[touristId].forEach(function(customField) {
      $customFields = $customFields.add(customField.render());
    });

    this.touristForms[touristId].find('.js-ore-tourist-custom-fields').html($customFields);

    self.customFields[touristId].forEach(function(customField) {
      customField.initialize();
    });
  };

  /**
  * Блокировка формы туриста в процессе получения данных пользователя
  * @param {Object} $touristForm - объект формы туриста
  * @param {Function} callback - хак для красивости интерфейса
  * @todo некрасиво, подумать над вариантами
  */
  modView.prototype.lockClientSelect = function($touristForm, callback) {
    var $dataForm = $touristForm.find('.js-ore-tourist--form-wrapper');
    $touristForm.addClass('is-locked').removeClass('active');
    
    var $clientSuggest = $touristForm.find('.js-ore-tourist--suggest');
    if ($clientSuggest.length !== 0) { 
      $clientSuggest[0].selectize.disable();
    }

    $dataForm.css({'display':'block'}).slideUp(500, function() {
      $touristForm.find('.js-ore-tourist--header-lock').css({'display': 'block'});
      if (typeof callback === 'function') { callback(); }
    });
  };

  /**
  * Разблокировка формы туриста в процессе получения данных пользователя
  * @param {Object} $touristForm - объект формы туриста
  */
  modView.prototype.unlockClientSelect = function($touristForm) {
    var $dataForm = $touristForm.find('.js-ore-tourist--form-wrapper');
    $touristForm.removeClass('is-locked').addClass('active');

    var $clientSuggest = $touristForm.find('.js-ore-tourist--suggest');
    if ($clientSuggest.length !== 0) { 
      $clientSuggest[0].selectize.enable();
    }

    $dataForm.css({'display':'none'}).slideDown(500);
  };

  /**
  * Сбор и валидация данных по туристу из формы
  * @param {Object} $form - форма туриста
  * @return {Object|false} - данные туриста или false в случае ошибки
  * @todo fix hack with dynamic serialnum
  */
  modView.prototype.getTouristFormData = function($form) {  
    var $phoneblock = $form.find('.js-ore-tourist--phone-input');
    var $c = {
      'sex': $form.find('input[name="sex"]'),
      'firstName': $form.find('input[name="firstname"]').livetrim(),
      'lastName': $form.find('input[name="lastname"]').livetrim(),
      'middleName': $form.find('input[name="middlename"]').livetrim(),
      'birthdate': $form.find('input[name="birthdate"]'),
      'email': $form.find('input[name="email"]').livetrim(),
      'phone': {
        'countryPrefix': $phoneblock.find('input[name="country-prefix"]'),
        'cityPrefix': $phoneblock.find('input[name="city-prefix"]'),
        'number': $phoneblock.find('input[name="phone"]')
      },
      'document': {
        'type': $form.find('select[name="doctype"]'),
        'firstName': $form.find('input[name="docfirstname"]').livetrim().livecapitalize(),
        'lastName': $form.find('input[name="doclastname"]').livetrim().livecapitalize(),
        'middleName': $form.find('input[name="docmiddlename"]').livetrim().livecapitalize(),
        'serial': $form.find('input[name="docseries"]').livetrim(),
        'number': $form.find('input[name="docnumber"]').livetrim(),
        'citizenship': $form.find('select[name="citizenship"]'),
        'expiryDate': $form.find('input[name="docenddate"]')
      },
      'bonusCards': $form.find('.js-ore-tourist--bonus-card')
    };

    // валидация email
    function vemail(v) {
      if (v !== '' && (/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z0-9.-]+$/ig).test(v)) { return true; } 
      else { return false; }
    }
    // валидация имени/фамилии туриста
    function vname(v) {
      if (v !== '' && (/^[^0-9\.,\s\t\nъЪ]*$/g).test(v) && (/^([A-z-]+|[А-яёЁ-]+)$/g).test(v)) { return true; } 
      else { return false; }
    }
    // проверка на число
    function digit(v) {
      if (v !== '' && (/^[0-9]+$/g).test(v)) { return true; } 
      else { return false; }
    }

    var errorflag = {val: false};
    var touristId = $form.data('touristid');
    if (
      String($form.data('touristid')).indexOf('tmp') === -1 &&
      String($form.data('touristid')).indexOf('doc') === -1
    ) { touristId = +touristId; }

    var userId = $form.data('userid');

    var formdata = {
      'userId': (userId !== undefined && userId !== '') ? +userId : null,
      'contractId': this.mds.OrderStorage.contractId,
      'touristId': touristId,
      'isTourLeader': ($form.data('leader') === 'true') ? true : false,
      'sex': $c.sex.prop('checked') ? 1 : 0,
      'firstName': validateControl($c.firstName, vname, errorflag, 'Введите имя'),
      'lastName': validateControl($c.lastName, vname, errorflag, 'Введите фамилию'),
      'middleName': $c.middleName.val(),
      'birthdate': validateDate($c.birthdate, errorflag, 'YYYY-MM-DD'),
      'email': validateControl($c.email, vemail, errorflag, 'Введите email'),
      'phone': ($c.phone.number.val() === '') ? null :
          (
            '+' +
            validateControl($c.phone.countryPrefix, digit, errorflag) + '(' +
            validateControl($c.phone.cityPrefix, digit, errorflag) + ')' +
            validateControl($c.phone.number, digit, errorflag)
          ),
      'bonusCards': []
    };

    var docType = parseInt($c.document.type.val());
    var documentConfig = KT.config.touristDocuments[docType];

    if (documentConfig !== undefined) {
      var docTouristNameTest = documentConfig.getTouristNameFullValidation();
      var docSerialTest = documentConfig.getDocumentSerialFullValidation();
      var docNumberTest = documentConfig.getDocumentNumberFullValidation();
      var documentId = $form.find('.js-ore-tourist--document').data('documentid');

      formdata.document = {
        'user_documentID': (documentId !== undefined && documentId !== '') ? +documentId : null,
        'documentType': docType,
        'firstName': validateControl($c.document.firstName, function (v) {
              if (v !== '' && docTouristNameTest.test(v)) { return true; } 
              else { return false; }
            }, errorflag, 'Введите имя'),
        'lastName': validateControl($c.document.lastName, function (v) {
              if (v !== '' && docTouristNameTest.test(v)) { return true; } 
              else { return false; }
            }, errorflag, 'Введите фамилию'),
        'middleName': $c.document.middleName.val(),
        'serialNumber': ($c.document.serial.val() === '') ? null :
            String(validateControl($c.document.serial, function (v) {
              if (docSerialTest.test(v)) { return true; } else { return false; }
            },errorflag)).toUpperCase(),
        'number': ($c.document.number.val() === '') ? null :
            String(validateControl($c.document.number, function (v) {
              if (docNumberTest.test(v)) { return true; } else { return false; }
            },errorflag)).toUpperCase(),
        'expiryDate': (!documentConfig.hasExpiryDate ||
              ($c.document.number.val() === '' && $c.document.serial.val() === '')
            ) ? null : 
            validateDate($c.document.expiryDate, errorflag, 'YYYY-MM-DD'),
        'citizenship': ($c.document.citizenship.val() !== '') ? 
            $c.document.citizenship[0].selectize.getValue() : 
            (function($c, errorflag) {
                errorflag.val = true;
                $c.document.citizenship[0].selectize.$control
                  .addClass('error')
                  .one('click',function() { $(this).removeClass('error'); });
                return false;
            }($c, errorflag))
      };
    } else {
      formdata.document = {};
      errorflag.val = true;
      $c.document.type[0].selectize.$control.addClass('error');
      $c.document.type[0].selectize.$control_input.one('focus', function() {
        $c.document.type[0].selectize.$control.removeClass('error');
      });
      makeInvalid($c.document.firstName);
      makeInvalid($c.document.lastName);
      makeInvalid($c.document.serial);
      makeInvalid($c.document.number);
      console.error('Не определены правила валидации для документа: ' + $form.find('select[name="doctype"]').val());
    }

    /** Доп. правила валидации ФИО туриста */
    if ([
          formdata.document['firstName'],
          formdata.document['middleName'],
          formdata.document['lastName']
        ].join('').length > 55
    ) {
      errorflag.val = true;
      makeInvalid($c.document.firstName);
      makeInvalid($c.document.lastName);
      makeInvalid($c.document.middleName);
    }

    if (
      formdata.document['firstName'] === null ||
      (/[ьЬ]/).test(String(formdata.document['firstName']).charAt(0))
    ) {
      errorflag.val = true;
      makeInvalid($c.document.firstName);
    }

    if (
      formdata.document['lastName'] === null ||
      (/[ьЬ]/).test(String(formdata.document['lastName']).charAt(0))
    ) {
      errorflag.val = true;
      makeInvalid($c.document.lastName);
    }

    if (
      formdata.document['middleName'] !== '' &&
      (/[ьЬ]/).test(String(formdata.document['middleName']).charAt(0))
    ) {
      errorflag.val = true;
      makeInvalid($c.document.middleName);
    }

    // бонусные карты
    if ($c.bonusCards.length !== 0) {
      $c.bonusCards.each(function() {
        var $programId = $(this).find('.js-ore-tourist--bonus-card-program');
        if ($programId.val() !== '') {
          var $cardNumber = $(this).find('.js-ore-tourist--bonus-card-number');
          if ($cardNumber.val() === '') {
            errorflag.val = true;
            $cardNumber
              .addClass('error')
              .one('focus', function() {
                $cardNumber.removeClass('error');
              });
          } else {
            var cardId = $(this).data('cardid');

            formdata.bonusCards.push({
              'id': (cardId !== undefined) ? cardId : null,
              'bonuscardNumber': $cardNumber.val(),
              'aviaLoyaltyProgramId': $programId.val()
            });
          }
        }
      });
    }

    // дополнительные поля
    if (Array.isArray(this.customFields[touristId])) {
      formdata.userAdditionalFields = [];

      this.customFields[touristId].forEach(function(field) {
        var fieldValue = field.getValue();
        if (field.modifiable && field.validate(fieldValue)) {
          formdata.userAdditionalFields.push({
            'fieldTypeId': field.fieldTypeId,
            'value': (fieldValue !== '') ? fieldValue : null,
          });
        } else { errorflag.val = true; }
      });
    }

    return (errorflag.val) ? false : formdata;
  };

  /**
  * Сбор и валидация данных из формы для создания пользователя
  * @param {Object} $form - форма туриста
  * @return {Object|false} - данные туриста или false в случае ошибки
  */
  modView.prototype.getUserFormData = function($form) {
    var _instance = this;

    var formdata = this.getTouristFormData($form);
    if (formdata === false) { return false; }

    var userData = {
      'user': {
        'userId': formdata.userId,
        'clientId': _instance.mds.OrderStorage.client.id,
        'sex': formdata.sex,
        'firstName': formdata.firstName,
        'middleName': formdata.middleName,
        'lastName': formdata.lastName,
        'birthdate': formdata.birthdate,
        'citizenshipId': null, /** @deprecated */
        'сontactPhone': formdata.phone,
        'email': formdata.email,
        'bonusCards': Array.isArray(formdata.bonusCards) ?
          formdata.bonusCards.map(function(item) {
            delete item.id;
            return item;
          }) : formdata.bonusCards
      },
      'document': {
        'userDocId': formdata.document.user_documentID,
        'docType': formdata.document.documentType,
        'firstName': formdata.document.firstName,
        'middleName': formdata.document.middleName,
        'lastName': formdata.document.lastName,
        'docSerial': formdata.document.serialNumber,
        'docNumber': formdata.document.number,
        'docExpiryDate': formdata.document.expiryDate,
        'citizenship': formdata.document.citizenship,
      }
    };

    return userData;
  };

  /**
  * Контроль ввода серии и номера документа на лету
  * @param {Object} $form - (jQuery DOM) форма туриста
  * @todo parse rules should be here?
  */
  modView.prototype.livecheckDocument = function($form) {
    var docType = parseInt( $form.find('select[name="doctype"]').val() );
    var $fioFields = $form.find('input').filter('[name="docfirstname"],[name="docmiddlename"],[name="doclastname"]');
    var $series = $form.find('input[name="docseries"]');
    var $number = $form.find('input[name="docnumber"]');
    var $expiryDate = $form.find('input[name="docenddate"]');

    var documentConfig = KT.config.touristDocuments[docType];

    if (documentConfig !== undefined) {
      if (documentConfig.hasExpiryDate) {
        $expiryDate.prop('disabled', false).trigger('change');

        if (documentConfig.lifetime !== undefined) {
          var calendar = $expiryDate.data('plugin_clndrize').config.clndr;
          calendar.constraints.endDate = moment().add(documentConfig.lifetime).format('YYYY-MM-DD');
        }
      } else {
        $expiryDate.prop('disabled', true).val('').trigger('change');
      }

      $expiryDate.closest('.clndr-datepicker-wrap').removeClass('error');

      $fioFields
        .off('.livecheck')
        .removeClass('error')
        .on('keypress.livecheck',function(e) {
          var char = String.fromCharCode(e.which);
          var check = documentConfig.touristNameValidation[0];
          if (!check.test(char)) {
            return false;
          }
        });

      $series
        .off('.livecheck')
        .removeClass('error')
        .on('keypress.livecheck',function(e) {
          var char = String.fromCharCode(e.which);
          var check = documentConfig.numberValidation[0];
          if (!check.test(char)) {
            return false;
          }
        })
        .attr('maxlength', documentConfig.numberValidation[2].replace(/[{}]/g, '').split(',').pop());

      $number
        .off('.livecheck')
        .removeClass('error')
        .on('keypress.livecheck',function(e) {
          var char = String.fromCharCode(e.which);
          var check = documentConfig.numberValidation[1];
          if (!check.test(char)) {
            return false;
          }
        })
        .attr('maxlength',documentConfig.numberValidation[3].replace(/[{}]/g, '').split(',').pop());
    }
  };

  /** Убрать возможность установки заказчика */
  modView.prototype.removeTLSetter = function() {
    this.$touristList.find('.js-ore-tourist--tourleader-block').remove();
    return this;
  };

  /** Сместить заказчика наверх списка туристов */
  modView.prototype.rearrange = function() {
    this.$touristList.find('.js-ore-tourist').filter('[data-leader="true"]').detach().prependTo(this.$touristList);
    return this;
  };

  /**
  * Маппинг данных туриста для вывода формы
  * @param {TouristStorage} tourist - Данные туриста
  * @param {Boolean} isTourleaderSet - Установлен ли турлидер в заявке
  * @param {Boolean} isOffline - оффлайновая ли заявка
  */
  modView.prototype.mapTouristInfo = function(Tourist, isTourleaderSet, isOffline) {
    var _instance = this;
    if (isOffline !== true) { isOffline = false; }

    var touristInfo = {
      'isEditable': !isOffline,
      'touristId': Tourist.touristId,
      'userId': (Tourist.userId !== undefined) ? Tourist.userId : null,
      'isTourleader': Tourist.isTourleader,
      'firstname': Tourist.firstname,
      'lastname': Tourist.lastname,
      'middlename': (Tourist.middlename !== null) ? Tourist.middlename : '',
      'email': (Tourist.email !== null) ? Tourist.email : '',
      'phoneCountryCode': (Tourist.phone.countryCode !== null) ?
        Tourist.phone.countryCode : '',
      'phoneCityCode': (Tourist.phone.cityCode !== null) ?
        Tourist.phone.cityCode : '',
      'phoneNumber': (Tourist.phone.number !== null) ?
        Tourist.phone.number : '',
      'phone': (Tourist.phone.countryCode !== null) ?
        (Tourist.phone.countryCode + '(' + Tourist.phone.cityCode + ')' + Tourist.phone.number) :
        Tourist.phone.number,
      'isTourleaderSet': isTourleaderSet,
      'isMale': (Tourist.sex === 'male') ? true : false,
      'birthdate': (Tourist.birthdate !== null) ? Tourist.birthdate.format('DD.MM.YYYY') : '',
      'document': {
        'documentId': (Tourist.document.documentId !== undefined) ? 
          Tourist.document.documentId : null,
        'type': Tourist.document.type,
        'series': Tourist.document.series,
        'number': Tourist.document.number,
        'firstname': Tourist.document.firstname,
        'lastname': Tourist.document.lastname,
        'middlename': (Tourist.document.middlename !== null) ?
          Tourist.document.middlename : '',
        'citizenship': Tourist.document.citizenship,
        //'issueDate': tourist.document.issueDate.format('DD.MM.YYYY'),
        'expiryDate': (Tourist.document.expiryDate !== null) ? 
          Tourist.document.expiryDate.format('DD.MM.YYYY') : ''
      },
      'bonusCards': (Tourist.bonusCards.length === 0) ? 
        Mustache.render(_instance.mds.tpl.touristFormNoBonusCards, {}) :
        Tourist.bonusCards.map(function(card) {
          return Mustache.render(_instance.mds.tpl.touristFormBonusCard, card);
        }).join(''),
      'bonusCardsActions': Mustache.render(_instance.mds.tpl.touristFormBonusCardActions, {
          'add': true
        }),
      'hasCustomFields': (Tourist.customFields !== null && Tourist.customFields.length > 0)
    };

    return touristInfo;
  };

  /**
  * Скопировать значения полей ФИО из данных туриста в документ
  * @param {Object} $el - (jQuery DOM) кнопка формы
  */
  modView.prototype.copyNamesToDoc = function($el) {
    var $form = $el.closest('.js-ore-tourist');
    $form.find('input[name="doclastname"]')
      .removeClass('error')
      .val( transliterate($form.find('input[name="lastname"]').val()) );
    $form.find('input[name="docfirstname"]')
      .removeClass('error')
      .val( transliterate($form.find('input[name="firstname"]').val()) );
    $form.find('input[name="docmiddlename"]')
      .removeClass('error')
      .val( transliterate($form.find('input[name="middlename"]').val()) );
  };

  /** 
  * Вывод модального окна отмены добавления туриста 
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showRevertAddTouristModal = function(submitAction) {
    KT.Modal.notify({
      type: 'warning',
      title: 'Удаление туриста из заявки',
      msg: '<p>Вы действительно хотите отменить добавление туриста?</p>',
      buttons:[{
          type: 'warning',
          title: 'нет'
        },
        {
          type: 'error',
          title: 'да',
          callback: submitAction
      }]
    });
  };

  /** 
  * Вывод модального окна удаления туриста 
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showRemoveTouristModal = function(submitAction) {
    KT.Modal.notify({
      type: 'warning',
      title: 'Удаление туриста из заявки',
      msg: '<p>Вы действительно хотите удалить туриста из заявки?</p>',
      buttons:[{
          type: 'warning',
          title: 'нет'
        },
        {
          type: 'error',
          title: 'да',
          callback: submitAction
      }]
    });
  };

  /**
  * Отобразить ошибку значения в поле
  * @param {Object} $el - [jQuery DOM] поле формы
  * @param {String} [msg] - текст для плейсхолдера контрола
  */
  function makeInvalid($el, msg) {
    if (msg === undefined || msg === null) { msg = ''; }
    $el
      .addClass('error')
      .attr('data-pl', $el.attr('placeholder'))
      .attr('placeholder', msg)
      .one('focus', function() {
        $(this)
          .removeClass('error')
          .attr('placeholder', $(this).attr('data-pl'))
          .removeAttr('data-pl');
      });
  }

  /**
  * Проверка значений форм
  * @param {Object} $el - (jQuery DOM) элемент для проверки
  * @param {Function} rule - функция проверки значения, принимает значение и возвращает true/false
  * @param {Object} flag - объект флага для сохранения состояния ошибки
  * @param {String} [msg] - текст для плейсхолдера контрола
  * @return {*|Boolean} - возращает значение элемента или false в случае непрошедшей валидации
  */
  function validateControl($el, rule, flag, msg) {
    var cv = $el.val();
    if (msg === undefined || msg === null) { msg = ''; }

    if (rule(cv)) {
      return cv;
    } else {
      makeInvalid($el, msg);
      flag.val = true;
      return null;
    }
  }

  /**
  * Проверка даты в контролах Clndrize
  * @param {Object} $el - (jQuery DOM) элемент для проверки (input)
  * @param {Object} flag - объект флага для сохранения состояния ошибки
  * @param {String} frmt - формат возвращаемой даты
  * @return {*|Boolean} - возращает дату в заданном формате или false в случае непрошедшей валидации
  */
  function validateDate($el,flag, frmt) {
    var cv = $el.val();

    if (cv !== '' && (/^\d{2}\.\d{2}\.\d{4}$/ig).test(cv)) {
      var constraints = $el.data('plugin_clndrize').config.clndr.constraints;
      var cvdate = moment(cv,'DD.MM.YYYY');
      if (
        cvdate >= moment(constraints.startDate,'YYYY-MM-DD') &&
        cvdate <= moment(constraints.endDate,'YYYY-MM-DD')
      ) {
        $el.attr('title','');
        return moment(cv,'DD.MM.YYYY').format(frmt);
      } else {
        $el.attr('title','Введенная дата выходит за допустимые границы');
      }
    }

    $el
      .closest('.clndr-datepicker-wrap')
        .addClass('error')
      .end()
      .one('focus click',function() {
        $(this).closest('.clndr-datepicker-wrap').removeClass('error');
      });

    flag.val = true;
    return null;
  }

  /**
  * Инициализация элемента выбора программы лояльности
  * @param {Object} $control - элемент выбора 
  * @param {Object} [params] - дополнительные параметры
  */
  function initLoyalityProgramSelect($control, params) {
    var controlOptions = {
      plugins: {'jirafize':{}},
      openOnFocus: true,
      create: false,
      options: [],
      selectOnTab: true,
      valueField: 'programId',
      searchField: ['IATAcode','loyalityProgramName', 'aircompanyName'],
      render: {
        item: function(item) {
          return '<div class="item" ' +
            'data-tooltip="авиакомпания: ' + item.aircompanyName + '<br>' +
            ' альянс: ' + item.allianceName + '">' + 
            '<b>[' + item.IATAcode + ']</b> ' + item.loyalityProgramName + 
            '</div>';
        },
        option: function(item) {
          return '<div class="option" ' +
            'data-tooltip="авиакомпания: ' + item.aircompanyName + '<br>' +
            ' альянс: ' + item.allianceName + '">' + 
            '<b>[' + item.IATAcode + ']</b> ' + item.loyalityProgramName + 
            '</div>';
        }
      }
    };

    if (typeof params === 'object') {
      $.extend(controlOptions, params);
    }

    $control.selectize(controlOptions);
  }

  return modView;
}));

(function(global,factory) {

    KT.crates.OrderEdit.tourists.controller = factory(KT.crates.OrderEdit.tourists);

}(this,function(crate) {
  /**
  * Редактирование заявки: туристы
  * submodule
  * @constructor
  * @param {Object} module - хранилище модуля (родительского)
  * @param {Object} orderId - ID заявки
  */
  var oetController = function(module, orderId) {
    this.mds = module;
    this.orderId = orderId;
    this.mds.tourists.view = new crate.view(this.mds);
  };

  oetController.prototype.init = function() {
    var _instance = this;
    var modView = this.mds.tourists.view;

    /** ID сервиса, из которого вызвано добавление туриста; при перезагрузке неактуально */
    window.sessionStorage.removeItem('serviceToAddTourist');

    /**
    * Запрос на добавление туриста к заявке
    * @todo rethink mechanism
    */
    KT.on('OrderEdit.createAddTouristForm', function() {
      modView.addTouristForm(_instance.mds.OrderStorage);
    });

    /*==========Обработчики событий модели============================*/
    /** Рендер пустого списка если в заявке нет услуг */
    KT.on('OrderStorage.initialized', function(e, OrderStorage) {
      if (OrderStorage.orderId === 'new') {
        modView.$touristList.empty();
        modView.addTouristForm(_instance.mds.OrderStorage);
      }
    });

    /** Обработка обновления информации по туристам в заявке */
    KT.on('OrderStorage.setTourists', function(e, OrderStorage) {
      modView.renderTouristList(OrderStorage);
    });

    /** Добавление/обновление туриста */
    KT.on('OrderStorage.savedTourist', function(e, data) {
      var touristId = data.touristId;
      var Tourist = _instance.mds.OrderStorage.tourists[touristId];
      var isOffline = _instance.mds.OrderStorage.isOffline;
      modView.refreshTouristForm(Tourist, isOffline);

      if (_instance.mds.OrderStorage.touleader !== null) {
        modView.removeTLSetter().rearrange();
      }

      KT.dispatch('OrderEdit.reloadHeader');
    });

    /*==========Обработчики событий представления============================*/

    /** Обработка выбора сотрудника в саджесте */
    modView.$touristList.on('change', '.js-ore-tourist--suggest', function() {
      var documentId = $(this).val();
      if (documentId !== '') {
        var userId = $(this)[0].selectize.options[+documentId].userId;
        var tempTouristId = $(this).closest('.js-ore-tourist').attr('data-touristid');
        var $touristForm = modView.touristForms[tempTouristId];

        modView.lockClientSelect($touristForm, function() {
          KT.apiClient.getClientUser(documentId)
            .done(function(response) {
              if (response.status === 0) {
                var documentId = response.body.document.docId;
                var newTouristId = 'doc' + documentId;
                var Tourist = new KT.storage.TouristStorage(newTouristId);
        
                $touristForm.attr('data-touristid', newTouristId);
                $touristForm.data('userid', userId);

                modView.touristForms[newTouristId] = modView.touristForms[tempTouristId];
                delete modView.touristForms[tempTouristId];

                Tourist.initializeFromUser(response.body);
                modView.refreshTouristForm(Tourist, false);
              }
            });
        });
      }
    });

    /** Скрыть/показать подробную информацию по туристу */
    modView.$touristList.on('click','.js-ore-tourist--header',function(e) {
      var $touristForm = $(this).closest('.js-ore-tourist');

      if (!$touristForm.hasClass('is-locked')) {
        var $dataForm = $touristForm.find('.js-ore-tourist--form-wrapper');

        if ($(e.target).closest('.js-ore-tourist--suggest-field', $touristForm).length === 0) {
          if ($touristForm.hasClass('active')) {
            $touristForm.removeClass('active');
            $dataForm.css({'display':'block'}).slideUp(500);
          } else {
            $touristForm.addClass('active');
            $dataForm.css({'display':'none'}).slideDown(500);
          }
        }
      }
    });

    /** Обработка нажатия на кнопку "Удалить туриста" */
    modView.$touristList.on('click','.js-ore-tourist--remove',function(e) {
      e.stopPropagation();

      var $touristForm = $(this).closest('.js-ore-tourist');
      var touristId = $touristForm.data('touristid');

      if (String(touristId).indexOf('tmp') === 0 || String(touristId).indexOf('doc') === 0) {
        // отмена добавления туриста
        modView.showRevertAddTouristModal(function() {
          KT.Modal.closeModal();
          $touristForm.remove();
          window.sessionStorage.removeItem('serviceToAddTourist');
        });

      } else {
        // удаление туриста
        modView.showRemoveTouristModal(function() {
          KT.Modal.showLoader();
          modView.renderPendingProcess($touristForm);

          touristId = +touristId;

          KT.apiClient.removeOrderTourist(_instance.orderId, touristId)
            .done(function(response) {
              KT.Modal.closeModal();
              if (response.status === 0) {
                var tourist = _instance.mds.OrderStorage.tourists[touristId];

                _instance.mds.OrderStorage.removeTourist(touristId);
                modView.removeTouristForm(touristId);

                KT.dispatch('OrderEdit.reloadHeader');
                KT.notify('touristRemoved',{name: tourist.firstname + ' ' + tourist.lastname});
              } else {
                modView.refreshTouristFormActions($touristForm,  _instance.mds.OrderStorage.isOffline);
                KT.notify('removingTouristFailed', response.errors);
              }
            });
        });
      }
    });

    /** Обработка нажатия кнопки отмены ввода данных формы туриста
    * @todo для свежесозданного туриста надо полностью очищать форму
    */
    modView.$touristList.on('click','.js-ore-tourist--reset',function() {
      var $touristForm = $(this).closest('.js-ore-tourist');
      var touristId = $touristForm.data('touristid');

      if (String(touristId).indexOf('tmp') !== -1) {
          modView.removeTouristForm(touristId);

      } else if (String(touristId).indexOf('doc') !== -1) {
        var documentId = +touristId.substr(3);
        modView.renderPendingProcess($touristForm);

        KT.apiClient.getClientUser(documentId)
          .done(function(response) {
            if (response.status === 0) {
              var Tourist = new KT.storage.TouristStorage(touristId);
              Tourist.initializeFromUser(response.body);
              modView.refreshTouristForm(Tourist, false);
            }
          });

      } else {
        touristId = +touristId;
        var Tourist = _instance.mds.OrderStorage.tourists[touristId];
        var isOffline = _instance.mds.OrderStorage.isOffline;
        modView.refreshTouristForm(Tourist, isOffline);
      }
    });

    /** Обработка подтверждения формы данных туриста */
    modView.$touristList.on('submit','.js-ore-tourist',function(e) {
      e.preventDefault();
      var $touristForm = $(this);
      var formdata = modView.getTouristFormData($touristForm);

      if (formdata !== false && typeof formdata === 'object') {
        modView.renderPendingProcess($touristForm);

        var currentTouristId = formdata.touristId;
        var newTouristId;

        KT.apiClient.setOrderTourist(_instance.mds.OrderStorage, formdata)
          .then(function(response) {
            if (response.status === 0) {
              /* если заявка создана через туриста */
              if (_instance.mds.OrderStorage.orderId === 'new') {
                window.sessionStorage.removeItem('clientId');
                window.sessionStorage.removeItem('contractId');
                window.location.assign('/cabinetUI/orders/order/' + response.body.orderId);
              } else {
                newTouristId = response.body.touristId;
                _instance.updateTouristForm(currentTouristId, newTouristId);
              }

            } else {
              if (_instance.mds.OrderStorage.tourleader === null) {
                $touristForm.removeAttr('data-leader')
                  .find('.js-ore-tourist--tourleader-toggler')
                    .prop('checked',false).change();
              }
              modView.refreshTouristFormActions($touristForm, _instance.mds.OrderStorage.isOffline);
              KT.notify('saveTouristFailed', response.errors);
            }
          });
      } else {
        KT.notify('incorrectTouristData');
      }
    });

    /** Обработка нажатия на кнопку "Сохранить как сотрудника" */
    modView.$touristList.on('click', '.js-ore-tourist--save-user', function() {
      var $touristForm = $(this).closest('.js-ore-tourist');
      var tempTouristId = $touristForm.data('touristid');
      var touristData = modView.getTouristFormData($touristForm);
      var userData = modView.getUserFormData($touristForm);

      if (touristData !== false && userData !== false) {
        userData.user.clientId = _instance.mds.OrderStorage.client.id;
        var currentTouristId = touristData.touristId;
        var newTouristId;

        modView.renderPendingProcess($touristForm);

        var savingTourist = KT.apiClient.setOrderTourist(_instance.mds.OrderStorage, touristData);
        var savingUser = KT.apiClient.setUser(userData, tempTouristId);

        $.when(savingTourist, savingUser)
          .then(function(touristResponse, userResponse) {
            if (touristResponse.status === 0) {
              /* если заявка создана через туриста */
              if (_instance.mds.OrderStorage.orderId === 'new') {
                window.sessionStorage.removeItem('clientId');
                window.sessionStorage.removeItem('contractId');
                window.location.assign('/cabinetUI/orders/order/' + touristResponse.body.orderId);
              } else {
                newTouristId = touristResponse.body.touristId;
                _instance.updateTouristForm(currentTouristId, newTouristId);

                if (userResponse.status === 0) {
                  KT.notify('userSaved');
                } else {
                  KT.notify('savingUserFailed', userResponse.errors);
                }
              }
            } else {
              // обработка ошибкуи сохранения туриста
              if (_instance.mds.OrderStorage.tourleader === null) {
                $touristForm.removeAttr('data-leader')
                  .find('.js-ore-tourist--tourleader-toggler')
                    .prop('checked',false).change();
              }
              modView.refreshTouristFormActions($touristForm, _instance.mds.OrderStorage.isOffline);
              KT.notify('saveTouristFailed', touristResponse.errors);

              // обработка результата сохранения сотрудника
              if (userResponse.status === 0) {
                // несохраненные туристы имеют Id со строковым префиксом
                var isExistingTourist = !isNaN(parseInt(tempTouristId));

                if (!isExistingTourist) {
                  var documentId = userResponse.body.userDocId;
                  var userId = userResponse.body.userId;
                  newTouristId = 'doc' + documentId;

                  if (newTouristId !== tempTouristId) {
                    $touristForm.attr('data-touristid', newTouristId);
                    $touristForm.attr('data-userid', userId);
                    $touristForm.data('userid', userId);

                    modView.touristForms[newTouristId] = modView.touristForms[tempTouristId];
                    delete modView.touristForms[tempTouristId];
                  }

                  modView.lockClientSelect($touristForm, function() {
                    KT.apiClient.getClientUser(documentId)
                      .done(function(response) {
                        if (response.status === 0) {
                          var Tourist = new KT.storage.TouristStorage(newTouristId);
                          Tourist.initializeFromUser(response.body);
                          modView.refreshTouristForm(Tourist, false);
                        }
                      });
                  });
                }
                KT.notify('userSaved');
              } else {
                KT.notify('savingUserFailed', userResponse.errors);
              }
            }
          });
      } else {
        console.error('Ошибка ввода данных туриста');
      }
    });

    /** Обработка изменения переключателя "Заказчик" */
    modView.$touristList.on('change','.js-ore-tourist--tourleader-toggler',function() {
      var $leaderToggler = $(this);
      var $touristForm = $leaderToggler.closest('.js-ore-tourist');

      var declineAction = function() {
        $leaderToggler.prop('checked',false).change();
        KT.Modal.closeModal();
      };

      var submitAction = function() {
        KT.Modal.closeModal();
        $touristForm.attr('data-leader','true');
        $touristForm.submit();
      };

      if ($leaderToggler.prop('checked')) {
        KT.Modal.notify({
          type:'info',
          title:'Определить заказчика',
          msg:'<p>Вы действительно хотите сделать этого туриста заказчиком?</p>',
          buttons:[{
              type:'common',
              title:'нет',
              callback:declineAction
            },
            {
              type:'success',
              title:'да',
              callback:submitAction
          }]
        });
      } else {
        $leaderToggler.closest('.simpletoggler').removeClass('active');
        $touristForm.removeAttr('data-leader');
      }
    });

    /** Обработка изменения типа документа */
    modView.$touristList.on('change','select[name="doctype"]',function() {
      modView.livecheckDocument($(this).closest('.js-ore-tourist'));
    });

    /** Добавление новой формы ввода мильной карты */
    modView.$touristList.on('click', '.js-ore-tourist--bonus-card-add', function() {
      modView.addBonusCardForm($(this).closest('.js-ore-tourist'));
    });

    /** Удаление формы ввода мильной карты */
    modView.$touristList.on('click', '.js-ore-tourist--bonus-card-delete', function() {
      modView.removeBonusCardForm($(this).closest('.js-ore-tourist--bonus-card'));
    });

    /** Изменение тогглера
    * @todo move to library
    */
    modView.$touristList.on('change','.simpletoggler input',function() {
      if ($(this).prop('checked')) {
        $(this).closest('.simpletoggler').addClass('active');
      } else {
        $(this).closest('.simpletoggler').removeClass('active');
      }
    });

    /** Обработка нажатия на кнопку "скопировать из данных ФИО туриста" */
    modView.$touristList.on('click','.js-ore-tourist--copy-name',function() {
      modView.copyNamesToDoc($(this));
    });

    /** @deprecated
    * Обработка нажатия на переключатель "виза"
    */
    modView.$touristList.on('change','input[name="getvisa"]',function() {
      var text = $(this).prop('checked') ? 'Требуется' : 'Не требуется';
      $(this).closest('.js-ore-tourist').find('.js-ore-tourist--require-visa').text(text);
    });

    /** @deprecated
    * Обработка нажатия на переключатель "страховка"
    */
    modView.$touristList.on('change','input[name="getinsurance"]',function() {
      var text = $(this).prop('checked') ? 'Требуется' : 'Не требуется';
      $(this).closest('.js-ore-tourist').find('.js-ore-tourist--require-insurance').text(text);
    });

    /** Обработка нажатия на кнопку "Добавить туриста" */
    modView.$touristListControls.on('click','.js-ore-tourists--add-new',function() {
      modView.addTouristForm(_instance.mds.OrderStorage);
    });

    
  };

  /** 
  * Обновление данных формы туриста 
  */
  oetController.prototype.updateTouristForm = function(currentTouristId, newTouristId) {
    var _instance = this;
    var modView = _instance.mds.tourists.view;
    var $touristForm = modView.touristForms[currentTouristId];

    KT.apiClient.getOrderTourists(_instance.orderId)
      .then(function(response) {
        if (response.status !== 0) {
          window.location.assign('/cabinetUI/orders/order/' + _instance.orderId);
        } else {
          var touristData = response.body.tourists.filter(function(item) {
            return newTouristId === item.touristId;
          })[0];

          var Tourist = new KT.storage.TouristStorage(touristData.touristId);
          Tourist.initialize(touristData);

          if (newTouristId !== currentTouristId) {
            // изменение данных формы туриста и сброс структур отображения
            $touristForm
              .attr('data-touristid', newTouristId)
              .data('touristid', newTouristId);
            modView.touristForms[newTouristId] = $touristForm;
            delete modView.touristForms[currentTouristId];
            delete modView.customFields[currentTouristId];

            /*
            * Проверка наличия у туриста доп. полей: 
            * если есть, не совершать переход к услуге
            */
            if (Tourist.customFields !== null) {
              window.sessionStorage.removeItem('serviceToAddTourist');
              KT.notify('customFieldsRevealed');
            }
          }

          _instance.mds.OrderStorage.saveTourist(Tourist);

          if (newTouristId === currentTouristId) {
            KT.notify('touristUpdated', {name: [Tourist.firstname, Tourist.lastname].join(' ')});
          } else {
            KT.notify('touristAdded', {name: [Tourist.firstname, Tourist.lastname].join(' ')});
          }
        }
      });
  };

  return oetController;
}));

(function(global,factory){

    KT.crates.OrderEdit.services.ServiceViewModel = factory(KT.crates.OrderEdit);

}(this,function(crate) {

  /**
  * Базовый класс view-модели услуги
  * @constructor
  * @param {ServiceStorage} ServiceStorage - данные услуги
  * @param {Object} templates - ссылка на коллекцию шаблонов модуля
  */
  
  var ServiceViewModel = function(ServiceStorage, templates) {
    this.ServiceStorage = ServiceStorage;
    this.tpl = templates;
    this.customFields = [];
    
    this.headerView = '';
    this.mainView = '';
    this.additionalServicesView = '';
    this.minimalPriceView = '';
    this.actionsView = '';
  };

  /** Механизм наследования */
  ServiceViewModel.extend = function (cfunc) {
    cfunc.prototype = Object.create(this.prototype);
    cfunc.__super__ = this.prototype;
    cfunc.constructor = cfunc;
    return cfunc;
  };

  /** Подготовка шаблонов для вывода услуги */
  ServiceViewModel.prototype.prepareViews = function() {
    throw new Error('ServiceViewModel:prepareViews not implemented');
  };

  /** Подготовка шаблона шапки */
  ServiceViewModel.prototype.prepareHeaderView = function() {
    throw new Error('ServiceViewModel:prepareHeaderView not implemented');
  };

  /** Подготовка основного шаблона */
  ServiceViewModel.prototype.prepareMainView = function() {
    throw new Error('ServiceViewModel:prepareMainView not implemented');
  };

  /** Подготовка шаблона действий с услугой */
  ServiceViewModel.prototype.prepareActionsView = function() {
    throw new Error('ServiceViewModel:prepareActionsView not implemented');
  };

  /** 
  * Обработка дополнительных полей. 
  * Метод возвращает массив необработанных доп. полей, соответственно в дочернем классе
  * можно определить собственную обработку для уникальных/специализированных доп. полей
  * @return {Array|null} - массив доп. полей или null если доп. полей нет
  */
  ServiceViewModel.prototype.processCustomFields = function() {
    var self = this;
    var ServiceStorage = this.ServiceStorage;
    var CustomFieldsFactory = new crate.CustomFieldsFactory(this.tpl);
    
    this.customFields = [];
    var unprocessedFields = [];

    if (Array.isArray(ServiceStorage.customFields)) {
      // хак - поля типа textArea отсортировать в конец для нормального отображения
      ServiceStorage.customFields.sort(function(a,b) {
        var typeSorting = (a.typeTemplate === 2) ?
          ((b.typeTemplate === 2) ? 0 : 1) :
          ((b.typeTemplate === 2) ? -1 : 0);
        
        if (typeSorting !== 0) {
          return typeSorting;
        } else {
          return (a.require) ?
            (b.require ? 0 : -1) :
            (b.require ? 1 : 0);
        }
      });

      ServiceStorage.customFields.forEach(function(fieldData) {
        var customField = CustomFieldsFactory.create(fieldData);
        if (customField !== null) {
          self.customFields.push(customField);
        } else {
          unprocessedFields.push(fieldData);
        }
      });

      return unprocessedFields;
    } else { return null; }
  };

  /** Инициализация элементов управления после рендера формы */
  ServiceViewModel.prototype.initControls = function() {
    throw new Error('ServiceViewModel:initControls not implemented');
  };

  /**
  * Сбор значений дополнительных полей
  * @return {Array|null} - массив значений доп. полей или null в случае ошибки
  */
  ServiceViewModel.prototype.getCustomFieldsValues = function() {
    var ServiceStorage = this.ServiceStorage;
    var customFieldsValues = [];
    /* 
    * Флаг отсутствия ошибок при обработке полей. 
    * Флаг специально, чтобы отметить все ошибочные поля, а не первое папавшееся
    */
    var noErrors = true;

    this.customFields.forEach(function(field) {
      var fieldValue = field.getValue();
      if (field.modifiable && field.validate(fieldValue)) {
        customFieldsValues.push({
          'fieldTypeId': field.fieldTypeId,
          'value': (fieldValue !== '') ? fieldValue : null,
          'serviceId': ServiceStorage.serviceId
        });
      } else { noErrors = false; }
    });

    return noErrors ? customFieldsValues : null;
  };

  /**
  * Связывание информации о туристах из заявки (getOrderTourists)
  * с информацией из услуги и формирование данных для блока туристов
  * @param {TouristStorage[]} tourists - массив информации по туристам
  * @param {Boolean} [overrideSave] - явное указание возможности сохранения привязки
  */
  ServiceViewModel.prototype.mapTouristsInfo = function() {
    throw new Error('ServiceViewModel:mapTouristsInfo not implemented');
  };

  /**
  * Получение структуры данных привязок туристов
  * @return {Object[]} структура данных привязок
  */
  ServiceViewModel.prototype.getTouristsMap = function() {
    if (this.touristsMap !== undefined) {
      return this.touristsMap;
    } else {
      console.error('Ошибка получения данных формы туристов: сначала необходимо вызвать mapTouristInfo()');
      return false;
    }
  };

  /**
  * Получение статуса возможности добавления туриста к услуге
  * (на основании текущей привязки туристов)
  * @return {Boolean} возможность добавления туриста
  */
  ServiceViewModel.prototype.checkTouristAddAllowance = function() {
    var ServiceStorage = this.ServiceStorage;

    var isSavingAllowed = (
        !ServiceStorage.isPartial &&
        ((ServiceStorage.status === 9 && KT.profile.userType === 'op') || ServiceStorage.status === 0)
      );

    if (!isSavingAllowed) { return false; }

    var isAddingAllowed = false;

    for (var ag in this.touristsAges) {
      if (this.touristsAges.hasOwnProperty(ag)) {
        if (this.touristsAges[ag].ordered > this.touristsAges[ag].current) {
          isAddingAllowed = true;
        }
      }
    }
    
    return isAddingAllowed;
  };

  /**
  * Установка общих для всех услуг параметров формы ручного редактирования
  * @return {Object} - общие параметры
  */
  ServiceViewModel.prototype.setCommonManualFormParams = function() {
    var ServiceStorage = this.ServiceStorage;

    return {
      'serviceId': ServiceStorage.serviceId,
      'clientCurrency': ServiceStorage.prices.inClient.currencyCode,
      'clientGrossPrice': ServiceStorage.prices.inClient.client.gross.toMoney(2,',',' '),
      'agentCommission': ServiceStorage.prices.inClient.client.commission.amount.toMoney(2,',',' '),
      'supplierGrossPrice': ServiceStorage.prices.inSupplier.supplier.gross.toMoney(2,',',' '),
      'supplierCurrency': ServiceStorage.prices.inSupplier.currencyCode,
      'clientCancelPenalties': (ServiceStorage.clientCancelPenalties === null) ? null : 
        ServiceStorage.clientCancelPenalties.map(function(penalty, i) {
          return {
            'penaltyIndex': i,
            'dateFrom': penalty.dateFrom.format(KT.config.dateFormat),
            'dateTo': penalty.dateTo.format(KT.config.dateFormat),
            'amount': Number(penalty.penaltySum.inClient.amount).toMoney(2,',',' '),
            'currency': penalty.penaltySum.inClient.currency,
            'currencyIcon': KT.getCatalogInfo('lcurrency', penalty.penaltySum.inClient.currency, 'icon')
          };
        })
    };
  };

  /**
  * Инициализация общих для всех услуг элементов управления окна ручного редактирования услуги
  * @param {Object} $wnd - [jQueryDom] объект окна
  * @param {Object} mds - объект модуля
  */
  ServiceViewModel.prototype.initManualFormControls = function($wnd, mds) {
    var ServiceStorage = this.ServiceStorage;

    // настройка переключения между вкладками
    $wnd.on('click','.js-ore-service-manualedit--tab-link',function() {
      if (!$(this).hasClass('active')) {
        var $root = $(this).closest('.js-ore-service-manualedit');

        $root.children('.js-ore-service-manualedit--tab-header')
          .children('.js-ore-service-manualedit--tab-link')
            .filter('.active').removeClass('active');
        $(this).addClass('active');

        var $tabs = $root.children('.js-ore-service-manualedit--tab');
        $tabs.removeClass('active')
          .filter('[data-tab="'+$(this).data('tab')+'"]')
            .addClass('active');
      }
    });

    // даты начала - окончания услуги
    $wnd.find('.js-ore-service-manualedit--start-date')
      .val(this.ServiceStorage.startDate.format('DD.MM.YYYY'))
      .clndrize({
        'template':mds.tpl.clndr,
        'eventName':'Дата начала услуги',
        'showDate':this.ServiceStorage.startDate,
        'clndr': {
          'constraints': {
            'startDate':moment().format('YYYY-MM-DD'),
            'endDate':moment().add(1,'years').format('YYYY-MM-DD')
          }
        }
      });

    $wnd.find('.js-ore-service-manualedit--end-date')
      .val(this.ServiceStorage.endDate.format('DD.MM.YYYY'))
      .clndrize({
        'template':mds.tpl.clndr,
        'eventName':'Дата окончания услуги',
        'showDate':this.ServiceStorage.endDate,
        'clndr': {
          'constraints': {
            'startDate':moment().format('YYYY-MM-DD'),
            'endDate':moment().add(1,'years').format('YYYY-MM-DD')
          }
        }
      });

    // цены, комиссии, штрафы
    var $currencyLabels = $wnd.find('.js-ore-service-manualedit--selected-currency');
    
    $wnd.find('.js-ore-service-manualedit--currency')
      .selectize({
        openOnFocus: true,
        create: false,
        allowEmptyOption: false,
        valueField: 'value',
        labelField: 'text',
        options: [
          {
            'value': ServiceStorage.prices.inClient.currencyCode,
            'text': 'Валюта продажи'
          },
          {
            'value': ServiceStorage.prices.inSupplier.currencyCode,
            'text': 'Валюта поставщика'
          },
          {
            'value': ServiceStorage.prices.inView.currencyCode,
            'text': 'Валюта просмотра'
          },
          {
            'value': ServiceStorage.prices.inLocal.currencyCode,
            'text': 'Локальная валюта'
          }
        ],
        items: [ServiceStorage.prices.inClient.currencyCode],
        render: {
          item: function(item) {
            return '<div class="item">' + item.value + ' (' + item.text + ')</div>';
          },
          option: function(item) {
            return '<div class="option">' + item.value + ' (' + item.text + ')</div>';
          }
        },
        onItemAdd: function(value) {
          $currencyLabels.text(value);
        }
      });

    $wnd.find('.js-ore-service-manualedit--client-gross-price')
      .setValidation('price', null, true)
      .on('change, focusout', function() {
        var price = parseFloat($(this).val().replace(/\s+/g,'').replace(',','.'));
        if (isNaN(price) || $(this).val() === '') {
          $(this).val(ServiceStorage.prices.inClient.client.gross.toMoney(2,',',' '));
          $(this)
            .addClass('error')
            .one('click, focusin', function() { $(this).removeClass('error'); });
        }
      });

    $wnd.find('.js-ore-service-manualedit--agent-commission')
      .setValidation('price', null, true)
      .on('change, focusout', function() {
        var commission = parseFloat($(this).val().replace(/\s+/g,'').replace(',','.'));
        if (isNaN(commission) || $(this).val() === '') {
          $(this).val(ServiceStorage.prices.inClient.client.commission.amount.toMoney(2,',',' '));
          $(this)
            .addClass('error')
            .one('click, focusin', function() { $(this).removeClass('error'); });
        }
      });

    $wnd.find('.js-ore-service-manualedit--client-cancel-penalty-amount')
      .setValidation('price', null, true)
      .on('change, focusout', function() {
        var price = parseFloat($(this).val().replace(/\s+/g,'').replace(',','.'));
        if (isNaN(price) || $(this).val() === '') {
          $(this).val($(this).data('penalty'));
        }
      });

    /* Установка статуса */
    var statuses = [];
    for (var status in KT.getCatalogInfo.servicestatuses) {
      status = +status;
      if ([0,2,5,7,8].indexOf(status) !== -1) { // см. app/__devel/js/core/config/catalogs.js
        statuses.push({
          'value': status,
          'name': KT.getCatalogInfo.servicestatuses[status][1],
          'icon': KT.getCatalogInfo.servicestatuses[status][0]
        });
      }
    }

    $wnd.find('.js-ore-service-manualedit--change-status')
      .selectize({
        openOnFocus: true,
        create: false,
        valueField: 'value',
        labelField: 'name',
        options: statuses,
        render: {
          item: function(item) {
            return '<div data-value="' + item.value + '" class="item" title="' + item.name + '">' + 
              '<i class="service-status-small service-sm-status-' + item.icon + '"></i> ' + item.name + 
              '</div>';
          },
          option: function(item) {
            return '<div data-value="'+item.value+'" data-selectable class="option">' +
              '<i class="service-status-small service-sm-status-' + item.icon + '"></i> ' + item.name + 
              '</div>';
          }
        }
      });
  };

  return ServiceViewModel;

}));
(function(global,factory) {

    KT.crates.OrderEdit.services.AviaServiceViewModel = factory(KT.crates.OrderEdit);

}(this, function(crate) {
  var ServiceViewModel = crate.services.ServiceViewModel;

  var ticketStatusMap = {
    1: 'ISSUED',
    2: 'VOIDED',
    3: 'RETURNED',
    4: 'CHANGED'
  };

  /**
  * View-объект для отображения оффера перелета
  * @constructor
  * @param {ServiceStorage} ServiceStorage - данные услуги
  * @param {Object} templates - ссылка на коллекцию шаблонов модуля
  * @param {Object} suppliersMap - список поставщиков
  */
  var AviaServiceViewModel = ServiceViewModel.extend(function(ServiceStorage, templates, suppliersMap) {
    ServiceViewModel.call(this, ServiceStorage, templates);

    this.serviceTypeName = 'avia';

    this.touristsAges = {
      'adults': {
        'ordered':ServiceStorage.declaredTouristAges.adults,
        'current':0
      },
      'children': {
        'ordered':ServiceStorage.declaredTouristAges.children,
        'current':0
      },
      'infants': {
        'ordered':ServiceStorage.declaredTouristAges.infants,
        'current':0
      }
    };

    this.fareRulesView = '';

    this.tripData = [];
    this.tripDates = []; //dates
    this.tripPoints = []; //flights

    this.hasPnr = false;
    this.pnrNumber = false;
    this.tickets = false;
    this.receipts = false;
    this.ticketLink = false;

    this.touristsMap = [];

    if (!ServiceStorage.isPartial) {
      this.supplierCode = ServiceStorage.offerInfo.supplierCode;

      this.supplierName = (suppliersMap[this.supplierCode] !== undefined) ?
        suppliersMap[this.supplierCode].name :
        this.supplierCode;
    }

    this.manualFormParams = null;
  });

  /* Шаблоны для авиа */
  AviaServiceViewModel.templates = {
    aviaFormHeader: 'orderEdit/services/avia/aviaFormHeader',
    aviaFormMain: 'orderEdit/services/avia/aviaFormMain',
    aviaServiceActions: 'orderEdit/services/avia/aviaServiceActions',
    aviaTrip: 'orderEdit/services/avia/aviaTrip',
    aviaTripRoute: 'orderEdit/services/avia/aviaTripRoute',
    aviaLoyaltyCard: 'orderEdit/services/avia/aviaLoyaltyCard',
    aviaNoLoyaltyCards: 'orderEdit/services/avia/aviaNoLoyaltyCards',
    aviaFareRule: 'orderEdit/services/avia/aviaFareRule',
    aviaFareRuleUnavailable: 'orderEdit/services/avia/aviaFareRuleUnavailable',
    aviaMinimalPrice: 'orderEdit/services/avia/aviaMinimalPrice',
    aviaManualForm: 'orderEdit/services/avia/aviaManualForm'
  };

  /**  Подготовка шаблонов */
  AviaServiceViewModel.prototype.prepareViews = function() {
    var ServiceStorage = this.ServiceStorage;

    if (!ServiceStorage.isPartial) {
      this.processTrips();
      this.processPnr();
      this.processCustomFields();
    } else {
      this.tripDates.push({
        'wd': ServiceStorage.startDate.format('dd'),
        'dm': ServiceStorage.startDate.format('DD.MM')
      });
    }

    this.prepareHeaderView();
    this.prepareMainView();
    this.prepareActionsView();
  };

  /** Обработка маршрута перелета и формирование данных */
  AviaServiceViewModel.prototype.processTrips = function() {
    var self = this;

    this.tripData = [];
    this.tripDates = [];
    this.tripPoints = [];

    var hasPnr = (
      !this.ServiceStorage.isPartial && 
      this.ServiceStorage.offerInfo.pnr !== undefined && 
      this.ServiceStorage.offerInfo.pnr !== null
    );

    this.ServiceStorage.offerInfo.itinerary.forEach(function(trip) {
      var firstSegment = trip.segments[0];
      var lastSegment = trip.segments[(+trip.segments.length - 1)];

      var startdate = moment(firstSegment.departureDate,'YYYY-MM-DD HH:mm:ss');
      var marketingAirlineCode = firstSegment.marketingAirline;

      self.tripDates.push({
        'wd': startdate.format('dd'),
        'dm': startdate.format('DD.MM')
      });

      self.tripPoints.push({
        'dep':{
          'city': firstSegment.departureCityName,
          'iata': firstSegment.departureAirportCode
        },
        'arr':{
          'city': lastSegment.arrivalCityName,
          'iata': lastSegment.arrivalAirportCode
        }
      });

      var tripRoute = {
        'alName': (KT.airlineCodes[marketingAirlineCode] !== undefined) ?
          KT.airlineCodes[marketingAirlineCode].name : marketingAirlineCode,
        'alLogo': (
            KT.airlineCodes[marketingAirlineCode] !== undefined &&
            KT.airlineCodes[marketingAirlineCode].hasLogo === true
          ) ? marketingAirlineCode : false,
        'segments':[]
      };

      trip.segments.forEach(function(seg, segIndex) {
        var waitingTime = false;

        if (segIndex + 1 < trip.segments.length) {
          var nextseg = trip.segments[segIndex + 1];
          var fhm = moment(seg.arrivalDate,'YYYY-MM-DD HH:mm:ss');
          var thm = moment(nextseg.departureDate,'YYYY-MM-DD HH:mm:ss');
          waitingTime = moment.duration(thm.valueOf() - fhm.valueOf()).asMinutes();
        }

        tripRoute.segments.push({
          'flightNum':seg.marketingAirline + ' ' + seg.flightNumber,
          'transporter': (seg.operatingAirline === seg.marketingAirline) ? false :
            {
              'code': seg.operatingAirline,
              'name': (KT.airlineCodes[seg.operatingAirline] !== undefined) ?
                KT.airlineCodes[seg.operatingAirline].name : seg.operatingAirline
            },
          'aircraft': seg.aircraft,
          'class': (seg.categoryClassType!==null) ? seg.categoryClassType.substr(0,1) : 'A',
          'className': (seg.categoryClassType!==null) ? seg.categoryClassType : false,
          'dhours': parseInt(seg.duration/60),
          'dminutes': seg.duration%60,
          'startpoint': {
            'city': seg.departureCityName,
            'airport': seg.departureAirportName,
            'terminal': seg.departureTerminal,
            'iata': seg.departureAirportCode,
            'date': seg.departureDate.split(' ')[0],
            'time': seg.departureDate.split(' ')[1].substr(0,5)
          },
          'endpoint': {
            'city': seg.arrivalCityName,
            'airport': seg.arrivalAirportName,
            'terminal': seg.arrivalTerminal,
            'iata': seg.arrivalAirportCode,
            'date': seg.arrivalDate.split(' ')[0],
            'time': seg.arrivalDate.split(' ')[1].substr(0,5)
          },
          'waiting': (waitingTime === false) ? false : {
            'hours': Math.floor(waitingTime / 60),
            'minutes': waitingTime % 60,
          },
          'stops': []
        });
      });

      var baggageInfo = [];
      if (trip.segments.length === 1) {
        if (Array.isArray(trip.segments[0].baggage)) {
          baggageInfo.push(
            trip.segments[0].baggage.map(function(baggage) {
              return [
                baggage.measureQuantity,
                (baggage.measureCode === 'PC' ? 
                  declOfNum(baggage.measureQuantity, ['место', 'места', 'мест']) : 
                  baggage.measureCode
                )
              ].join(' ');
            }).join(', ')
          );
        }
      } else {
        trip.segments.forEach(function(segment) {
          if (Array.isArray(segment.baggage)) {
            baggageInfo.push(
              segment.departureAirportCode + ' - ' + segment.arrivalAirportCode + ': ' +
              segment.baggage.map(function(baggage) {
                return [
                  baggage.measureQuantity,
                  (baggage.measureCode === 'PC' ? 
                    declOfNum(baggage.measureQuantity, ['место', 'места', 'мест']) : 
                    baggage.measureCode
                  )
                ].join(' ');
              }).join(', ')
            );
          }
        });
      }

      var transfersAmount = trip.segments.length - 1;
      self.tripData.push(Mustache.render(self.tpl.aviaTrip, {
        'route': Mustache.render(self.tpl.aviaTripRoute, tripRoute),
        'hasPnr': hasPnr,
        'baggageInfo': baggageInfo,
        'transfersNum': (transfersAmount === 0) ? 'без пересадок' :
          transfersAmount + ' ' + declOfNum(transfersAmount, ['пересадка','пересадки','пересадок']),
      }));
    });
  };

  /** Обработка данных брони и билетов */
  AviaServiceViewModel.prototype.processPnr = function() {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    if (ServiceStorage.offerInfo.pnr !== undefined && ServiceStorage.offerInfo.pnr !== null) {
      self.hasPnr = true;
      self.pnrNumber = ServiceStorage.offerInfo.pnr.pnrNumber;
      self.pnrBaggageInfo = {
        'info': null
      };

      if (
          ServiceStorage.offerInfo.pnr.receipts !== undefined &&
          Array.isArray(ServiceStorage.offerInfo.pnr.receipts)
        ) {
          self.receipts = ServiceStorage.offerInfo.pnr.receipts;

        self.ticketLink = (self.receipts[0] !== undefined) ?
          self.receipts[0].receiptUrl : false;
      }

      if (
        ServiceStorage.offerInfo.pnr.tickets !== undefined &&
        Array.isArray(ServiceStorage.offerInfo.pnr.tickets) &&
        ServiceStorage.offerInfo.pnr.tickets.length !== 0
      ) {
        self.tickets = {};
        ServiceStorage.offerInfo.pnr.tickets.forEach(function(ticket) {
          if (!self.tickets.hasOwnProperty(ticket.touristId)) {
            self.tickets[ticket.touristId] = [];
          }
          self.tickets[ticket.touristId].push({
            'status': ticketStatusMap[ticket.ticketStatus],
            'number': ticket.ticketNumber
          });
        });
      }

      if (Array.isArray(ServiceStorage.offerInfo.pnr.baggage)) {
        ServiceStorage.offerInfo.pnr.baggage.map(function(baggage) {
          if (baggage.measureCode === 'PC') { 
            baggage.measureCode = declOfNum(baggage.measureQuantity, ['место', 'места', 'мест']);
          }
        });

        self.pnrBaggageInfo.info = ServiceStorage.offerInfo.pnr.baggage;
      }
    }
  };

  /** Обработка дополнительных полей  */
  AviaServiceViewModel.prototype.processCustomFields = function() {
    var self = this;
    var unprocessedFields = ServiceViewModel.prototype.processCustomFields.call(this);
    
    // Обработка специализированных доп. полей
    if (Array.isArray(unprocessedFields)) {
      unprocessedFields.forEach(function(fieldData) {
        switch (fieldData.typeTemplate) {
          case 5: // minimal price
            if (fieldData.value === null) { return; }
            var minimalPriceData = JSON.parse(fieldData.value);

            self.minimalPriceView = Mustache.render(self.tpl.aviaMinimalPrice, {
              'from': minimalPriceData.from,
              'to': minimalPriceData.to,
              'dateStart': moment(minimalPriceData.dateStart, 'YYYY-MM-DD HH:mm:ss')
                .format('HH:mm YYYY-MM-DD'),
              'dateFinish': moment(minimalPriceData.dateFinish, 'YYYY-MM-DD HH:mm:ss')
                .format('HH:mm YYYY-MM-DD'),
              'duration': {
                'hours': Math.floor(minimalPriceData.duration / 60),
                'minutes': minimalPriceData.duration % 60,
              },
              'changes': [
                  minimalPriceData.changes,
                  declOfNum(minimalPriceData.changes, ['пересадка','пересадки','пересадок'])
                ].join(' '),
              'price': Number(minimalPriceData.price).toMoney(0,',',' '),
              'currency': KT.getCatalogInfo('lcurrency', minimalPriceData.currency, 'icon')
            });
            break;
        }
      });
    }
  };

  /** Подготовка интерфейса шапки */
  AviaServiceViewModel.prototype.prepareHeaderView = function() {
    var ServiceStorage = this.ServiceStorage;

    var firstFlightNumber = '';
    if (!ServiceStorage.isPartial) {
      if (
        ServiceStorage.offerInfo.itinerary[0] !== undefined &&
        ServiceStorage.offerInfo.itinerary[0].segments[0] !== undefined
      ) {
        var firstSegment = ServiceStorage.offerInfo.itinerary[0].segments[0];
        firstFlightNumber = firstSegment.marketingAirline+'-'+firstSegment.flightNumber;
      }
    } else {
      firstFlightNumber = ServiceStorage.serviceName;
    }

    this.headerView = Mustache.render(this.tpl.aviaFormHeader, {
      'firstFlightNum': firstFlightNumber,
      'amendDate':(ServiceStorage.dateAmend === null) ? false :
        ServiceStorage.dateAmend.format('DD.MM.YYYY'),
      'priceLocal': Number(ServiceStorage.prices.inLocal.client.gross).toMoney(0,',',' '),
      'priceInView': Number(ServiceStorage.prices.inView.client.gross).toMoney(0,',',' '),
      'viewCurrencyIcon': KT.getCatalogInfo(
          'lcurrency', ServiceStorage.prices.inView.currencyCode, 'icon'
        ),
      'statusIcon':KT.getCatalogInfo('servicestatuses',ServiceStorage.status,'icon'),
      'statusTitle': (KT.profile.userType === 'op' && ServiceStorage.status === 9) ?
        'Ручной режим' :
        KT.getCatalogInfo('servicestatuses',ServiceStorage.status,'title'),
      'dates': this.tripDates,
      'flights': this.tripPoints,
      'passengers': {
        'adult': ServiceStorage.declaredTouristAges.adults,
        'child': ServiceStorage.declaredTouristAges.children,
        'infant': ServiceStorage.declaredTouristAges.infants,
      },
      'pnrNumber': this.pnrNumber,
      'ticketLink': this.ticketLink
    });
  };

  /** Подготовка интерфейса главного блока */
  AviaServiceViewModel.prototype.prepareMainView = function() {
    var ServiceStorage = this.ServiceStorage;

    if (!ServiceStorage.isPartial) {
      var lastTicketingDate = (ServiceStorage.offerInfo.lastTicketingDate !== null) ?
            moment(ServiceStorage.offerInfo.lastTicketingDate,'YYYY-MM-DD HH:mm:ss').format('HH:mm DD-MM-YYYY') :
            null;

      this.mainView = Mustache.render(this.tpl.aviaFormMain, {
        //'minimalPrice': this.minimalPriceView,
        'offerSupplier': (KT.profile.userType === 'op') ? this.supplierName : false,
        'lastTicketingDate': lastTicketingDate,
        'flightTrips': this.tripData,
        'overNightFlight': !ServiceStorage.hasTravelPolicy ? false :
          ServiceStorage.offerInfo.travelPolicy.overNightFlight,
        'nightTransfer': !ServiceStorage.hasTravelPolicy ? false :
          ServiceStorage.offerInfo.travelPolicy.nightTransfer,
        'pnrBaggageInfo': this.pnrBaggageInfo,
        'ticketLink': this.ticketLink
      });
    } else { this.mainView = ''; }
  };

  /** Подготовка интерфейса действий с услугой */
  AviaServiceViewModel.prototype.prepareActionsView = function() {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    if (!ServiceStorage.isPartial) {
      var serviceActions = this.getServiceActions();

      this.actionsView = Mustache.render(self.tpl.aviaServiceActions, serviceActions);
      this.prepareFareRulesView();
    } else { this.actionsView = ''; }
  };

  /** Подготовка интерфейса правил тарифов перелета */
  AviaServiceViewModel.prototype.prepareFareRulesView = function() {
    var ServiceStorage = this.ServiceStorage;

    if (
      !Array.isArray(ServiceStorage.offerInfo.fareRules) ||
      ServiceStorage.offerInfo.fareRules.length === 0
    ) {
      this.fareRulesView = Mustache.render(KT.tpl.spinner, {});
      return;
    }

    var fareRule = {'rules': []};
    var isActiveTab = true;

    var locationMap = {};
    ServiceStorage.offerInfo.itinerary.forEach(function(trip) {
      trip.segments.forEach(function(segment) {
        locationMap[segment.arrivalAirportCode] = segment.arrivalCityName;
        locationMap[segment.departureAirportCode] = segment.departureCityName;
      });
    });

    ServiceStorage.offerInfo.fareRules.forEach(function(rule) {
      var shortRules = {};

      var iataCodes = rule.segment.flightSegmentName.split(/\s*-\s*/);
      var fullSegmentName = locationMap[iataCodes[0]] + ' - ' + locationMap[iataCodes[1]];

      for (var flag in rule.aviaFareRule.shortRules) {
        if (rule.aviaFareRule.shortRules.hasOwnProperty(flag)) {
          switch (rule.aviaFareRule.shortRules[flag]) {
            case true:
              shortRules[flag] = {'allowed':true};
              break;
            case false:
              shortRules[flag] = {'forbidden':true};
              break;
            default:
              shortRules[flag] = {'undefined':true};
              break;
          }
        }
      }
      
      fareRule.rules.push({
        'active': isActiveTab,
        'segment' : rule.segment.flightSegmentName,
        'fullSegmentName': fullSegmentName,
        'shortRules': shortRules,
        'rulesText': rule.aviaFareRule.rules
      });

      isActiveTab = false;
    });

    this.fareRulesView = Mustache.render(this.tpl.aviaFareRule, fareRule);
  };

  /** Подготовка пустой формы правил тарифов (не найдены) */
  AviaServiceViewModel.prototype.prepareEmptyFareRulesView = function() {
    this.fareRulesView = Mustache.render(this.tpl.aviaFareRuleUnavailable, {});
  };

  /** 
  * Инициализация элементов управления после рендера формы
  * @param {Object} $container - форма услуги
  * @todo в принципе передать управление контейнером услуги в класс viewModel?
  */
  AviaServiceViewModel.prototype.initControls = function($container) {
    if (this.customFields.length > 0) {
      var $customFields = $();

      this.customFields.forEach(function(customField) {
        $customFields = $customFields.add(customField.render());
      });

      $container.find('.js-service-form-custom-fields').html($customFields);

      this.customFields.forEach(function(customField) {
        customField.initialize();
      });
    }
  };

  /**
  * Связывание информации о туристах из заявки (getOrderTourists)
  * с информацией из услуги и формирование данных для блока туристов
  * @param {TouristStorage[]} tourists - массив информации по туристам
  * @param {Boolean} [overrideSave] - явное указание возможности сохранения привязки
  */
  AviaServiceViewModel.prototype.mapTouristsInfo = function(tourists, overrideSave) {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    this.touristsAges = {
      'adults': {
        'ordered':ServiceStorage.declaredTouristAges.adults,
        'current':0
      },
      'children': {
        'ordered':ServiceStorage.declaredTouristAges.children,
        'current':0
      },
      'infants': {
        'ordered':ServiceStorage.declaredTouristAges.infants,
        'current':0
      }
    };
    this.touristsMap = [];

    if (tourists.length === 0) { return; }
    if (overrideSave === undefined) { overrideSave = false; }
    var isSavingAllowed = (
        !ServiceStorage.isPartial &&
        ((ServiceStorage.status === 9 && KT.profile.userType === 'op') || ServiceStorage.status === 0)
      );

    tourists.forEach(function(TouristStorage) {
      var isLinked = ServiceStorage.tourists.hasOwnProperty(TouristStorage.touristId);
      var linkedTourist = isLinked ? ServiceStorage.tourists[TouristStorage.touristId] : null;
      var touristExtra = '';

      if (!ServiceStorage.isPartial) {

        // сохранение числа привязанных туристов по возрастным группам
        if (isLinked) {
          var age = moment.duration(
              moment().valueOf() - TouristStorage.birthdate.valueOf()
            ).asYears();
          if (age < 3) { self.touristsAges.infants.current += 1; }
          else if (age < 12) { self.touristsAges.children.current += 1; }
          else { self.touristsAges.adults.current += 1; }
        }

        if (self.tickets !== undefined && self.tickets[TouristStorage.touristId] !== undefined) {
          var issuedTickets = self.tickets[TouristStorage.touristId].filter(function(ticket) {
              return (ticket.status === 'ISSUED');
            }).map(function(ticket) {
              return ticket.number;
            });
            
          touristExtra = '<div class="service-form-tourist__ticket-number">№ билет' +
            ((issuedTickets.length > 1) ? 'ов' : 'а') + ': ' + 
            issuedTickets.join(', ') + 
            '</div>';

        } else {
          if (
            (isLinked && linkedTourist.loyalityProviderId  !== null) ||
            TouristStorage.bonusCards.length > 0
          ) {
            touristExtra = Mustache.render(self.tpl.aviaLoyaltyCard, {
              'isSavingAllowed': isSavingAllowed,
              'loyalityProgram': isLinked ? linkedTourist.loyalityProviderId : null,
              'loyalityCardNumber': isLinked ? linkedTourist.loyalityCardNumber : null,
            });
          } else {
            touristExtra = Mustache.render(self.tpl.aviaNoLoyaltyCards, {});
          }
        }
      }

      if (overrideSave || isSavingAllowed || isLinked) {
        self.touristsMap.push({
          'allowSave': isSavingAllowed,
          'touristId': TouristStorage.touristId,
          'firstName': TouristStorage.firstname,
          'bonusCards': TouristStorage.bonusCards,
          'surName': TouristStorage.lastname,
          'attached': isLinked,
          'touristExtra': touristExtra
        });
      }
    });
  };

  /**
  * Рендер элементов управления туриста 
  * @param {Object} $tourist - блок туриста
  * @param {Object} tourist - данные туриста (touristsMap)
  */
  AviaServiceViewModel.prototype.renderTouristControls = function($tourist, tourist) {
    KT.Dictionary.getAsMap('loyalityPrograms', 'programId')
      .then(function(loyalityProgramsMap) {
        var bonusCardsOptions = tourist.bonusCards.map(function(card) {
          if (loyalityProgramsMap.hasOwnProperty(card.aviaLoyaltyProgramId)) {
            return $.extend(true, {
              'cardNumber': card.bonuscardNumber
            }, loyalityProgramsMap[card.aviaLoyaltyProgramId]);
          }
        });

        if (bonusCardsOptions.length === 0) { return; }

        var $bonusCard = $tourist.find('.js-service-avia-loyalty-program');
        // карт не будет, если услуга оформлена
        if ($bonusCard.length !== 0) {
          var $providerSelect = $bonusCard.find('.js-service-avia-loyalty-program--provider');
          var $cardNumber = $bonusCard.find('.js-service-avia-loyalty-program--number');
          var currentProvider = $providerSelect.val();

          $providerSelect.selectize({
            plugins: {'jirafize':{}},
            openOnFocus: true,
            create: false,
            options: bonusCardsOptions,
            selectOnTab: true,
            valueField: 'programId',
            searchField: ['IATAcode','loyalityProgramName', 'aircompanyName'],
            render:{
              item: function(item) {
                return '<div class="item" ' +
                  'data-tooltip="авиакомпания: ' + item.aircompanyName + '<br>' +
                  ' альянс: ' + item.allianceName + '">' + 
                  '<b>[' + item.IATAcode + ']</b> ' + item.loyalityProgramName + 
                  '</div>';
              },
              option: function(item) {
                return '<div class="option" ' +
                  'data-tooltip="авиакомпания: ' + item.aircompanyName + '<br>' +
                  ' альянс: ' + item.allianceName + '">' + 
                  '<b>[' + item.IATAcode + ']</b> ' + item.loyalityProgramName + 
                  '</div>';
              }
            },
            onItemAdd: function(value) {
              $cardNumber.val(this.options[value].cardNumber);
            },
            onItemRemove: function() {
              $cardNumber.val('');
            },
            onClear: function() {
              $cardNumber.val('');
            }
          });

          if (currentProvider !== '' && currentProvider !== null) {
            $providerSelect[0].selectize.addItem(+currentProvider);
          } else if (bonusCardsOptions.length > 0 && tourist.allowSave && !tourist.attached) {
            /*
            * выбор первого элемента только в том случае ,если турист не привязан:
            * нужно для того, чтобы при бронировании не было "мелькания" мильной карты на услуге
            */
            $providerSelect[0].selectize.addItem(bonusCardsOptions[0].programId);
          }
        }
      });
  };

  /**
  * Получение структуры доступных действий над услугой
  * @return {Object} идентификатор услуги и разрешения на действия
  */
  AviaServiceViewModel.prototype.getServiceActions = function() {
    var ServiceStorage = this.ServiceStorage;

    var serviceActions = {
      'serviceId': ServiceStorage.serviceId,
      'ManualEdit': (KT.profile.userType === 'op' && ServiceStorage.status === 9) ? true : false,
      'save': (this.customFields.length > 0)
    };

    ServiceStorage.allowedTransitions.forEach(function(transition) {
      serviceActions[transition] = true;
    });

    // хак для разного стиля кнопок, переделать?
    if (KT.profile.userType !== 'op' && serviceActions['Manual']) {
      serviceActions['Manual'] = false;
      serviceActions['ToManager'] = true;
    }

    serviceActions.agreementSet = ServiceStorage.isTOSAgreementSet;

    if (!serviceActions.BookStart) {
      serviceActions.agreementDisabled = true;
      serviceActions.agreementSet = true;
    }

    return serviceActions;
  };

  /**
  * Создает список документов с условиями работы с услугой с методами их получения
  * @return {Object} объект с документами и методами их получения
  */
  AviaServiceViewModel.prototype.defineTermsDocuments = function() {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    /*
    * renderer - функция, отрисовывающая контент. 
    *  Принимает название документа и контент, Возвращает ссылку на DOM-объект окна
    * loader - функция загрузки документа.
    *  Принимает название документа и коллбэк
    */

    var termsDocuments = {
      'companyTOS': {
        'load':function(renderer, loader) {
          loader('companyTOS', renderer);
        }
      }
    };
    termsDocuments[ServiceStorage.tosDocumentName] = {
      'load':function(renderer, loader) {
        loader('aviaBookTerms', renderer);
      }
    };

    termsDocuments[ServiceStorage.fareRuleDocumentName] = {
      'load':function(renderer) {
        var $fareRule = renderer(ServiceStorage.fareRuleDocumentName, self.fareRulesView);
        $fareRule.on('click','.js-avs-fare-rule--tab-link', function() {
          if (!$(this).hasClass('active')) {
            var $root = $(this).closest('.js-avs-fare-rule');
            
            $root.children('.js-avs-fare-rule--tab-header')
              .children('.js-avs-fare-rule--tab-link')
                .filter('.active')
                  .removeClass('active');
            $(this).addClass('active');

            var $tabs = $root.children('.js-avs-fare-rule--tab');
            $tabs.removeClass('active')
              .filter('[data-tab="' + $(this).data('tab') + '"]')
                .addClass('active');
          }
        });
      }
    };

    return termsDocuments;
  };

  /**
  * Рендер формы ручного редактирования улуги
  * @param {Object} mds - объект модуля
  * @return {String} - код формы редактирования услуги
  */
  AviaServiceViewModel.prototype.renderManualForm = function(mds) {
    var ServiceStorage = this.ServiceStorage;

    var commonParams = ServiceViewModel.prototype.setCommonManualFormParams.call(this);

    var manualFormParams = $.extend(commonParams, {
        'flightTariff': ServiceStorage.offerInfo.flightTariff,
        'pnr': (this.hasPnr) ?
          ServiceStorage.offerInfo.pnr.pnrNumber : null,
        'tickets': null
      });
    
    if (this.hasPnr) {
      var tickets = this.ServiceStorage.offerInfo.pnr.tickets;
      if (tickets !== undefined && tickets.length !== 0) {
        manualFormParams.tickets = [];

        tickets.forEach(function(ticket) {
          var tourist = mds.OrderStorage.tourists[ticket.touristId];

          manualFormParams.tickets.push({
            'pnr': ServiceStorage.offerInfo.pnr.pnrNumber,
            'number': ticket.ticketNumber,
            'tourist': {
              'id': tourist.touristId,
              'fullname': [
                  tourist.lastname,
                  tourist.firstname,
                  (tourist.middlename === null ? '' : tourist.middlename)
                ].join(' '),
              'document': {
                'docname': KT.config.touristDocuments[tourist.document.type].docname,
                'number': tourist.document.series + ' ' + tourist.document.number
              }
            },
            'status': ticketStatusMap[+ticket.ticketStatus],
            'disabled': (ticketStatusMap[+ticket.ticketStatus] !== 'ISSUED'),
            'newNumber': (ticket.newTicket !== undefined) ? ticket.newTicket: null
          });
        });
      }
    }

    this.manualFormParams = manualFormParams;
    return Mustache.render(this.tpl.aviaManualForm, manualFormParams);
  };

  /**
  * Инициализация элементов управления окна ручного редактирования услуги
  * @param {Object} $wnd - [jQueryDom] объект окна
  * @param {Object} mds - объект модуля
  * @todo Убрать зависимость от модуля
  */
  AviaServiceViewModel.prototype.initManualFormControls = function($wnd, mds) {
    var self = this;

    ServiceViewModel.prototype.initManualFormControls.call(this, $wnd, mds);

    var ServiceStorage = this.ServiceStorage;

    /* Изменение параметров брони */
    KT.Dictionary.getAsList('suppliers', {'serviceId': ServiceStorage.typeCode, 'active': 1})
      .then(function(aviaSuppliers) {
        /*
        var suppliers = [];
        for (var supplierCode in aviaSuppliers) {
          if (aviaSuppliers.hasOwnProperty(supplierCode)) {
            suppliers.push({
              'value': supplierCode,
              'name': aviaSuppliers[supplierCode].name
            });
          }
        } */

        $wnd.find('.js-ore-service-manualedit--supplier')
          .selectize({
            openOnFocus: true,
            create: false,
            valueField: 'supplierCode',
            labelField: 'name',
            options: aviaSuppliers,
            items: [self.supplierCode] //!!!
          });
      });

    var lastTicketingDate = (ServiceStorage.offerInfo.lastTicketingDate !== null) ?
      moment(ServiceStorage.offerInfo.lastTicketingDate,'YYYY-MM-DD HH:mm:ss') : null;

    $wnd.find('.js-ore-service-manualedit--last-ticketing-date')
      .val((lastTicketingDate !== null) ? lastTicketingDate.format('DD.MM.YYYY') : '')
      .clndrize({
        'template': mds.tpl.clndr,
        'eventName': 'Выписать до',
        'showDate': (lastTicketingDate !== null) ? lastTicketingDate : '',
        'clndr': {
          'constraints': {
            'startDate': moment().format('YYYY-MM-DD'),
            'endDate': this.ServiceStorage.startDate.format('YYYY-MM-DD')
          }
        }
      });
    
    /* Редактирование авиабилетов */
    var ticketsList = (this.manualFormParams.tickets === null) ? [] : 
      this.manualFormParams.tickets.filter(function(ticket) {
        return (ticket.status === 'ISSUED');
      });

    var $ticketsList = $wnd.find('.js-ore-service-manualedit--avia-tickets-list');
    var $changeTicketForm = $wnd.find('.js-ore-service-manualedit--change-avia-ticket');
    var $addTicketForm = $wnd.find('.js-ore-service-manualedit--add-avia-ticket');
    var $changingTicketSelect = $wnd.find('.js-ore-service-manualedit--current-ticket-number');
    var $addNewTicketButton = $wnd.find('.js-ore-service-manualedit--add-avia-ticket-button');

    // редактирование билетов

    $changingTicketSelect.selectize({
        openOnFocus: true,
        create: false,
        valueField: 'number',
        labelField: 'number',
        options: ticketsList
      });

    $ticketsList.on('click', '.js-ore-service-manualedit--avia-ticket:not(.disabled)', function() {
        $addTicketForm.removeClass('active');
        /*
        $changeTicketForm.data('changingTicket', {
          'number': $(this).data('ticket'),
          'pnr': $(this).data('pnr'),
          'touristId': $(this).data('touristid')
        }); */
        $changeTicketForm.addClass('active');
        $changingTicketSelect[0].selectize.addItem($(this).data('ticket'));
      });

    // форма добавления билетов
    $addNewTicketButton
      .on('click', function() {
        $changeTicketForm.removeClass('active');
        $addTicketForm.addClass('active');
      });

    var tourists = [];
    mds.OrderStorage.getTourists().forEach(function(Tourist) {
      // билеты можно добавить/редактировать только привязанным туристам
      if (ServiceStorage.tourists.hasOwnProperty(Tourist.touristId)) {
        tourists.push({
          'value': Tourist.touristId,
          'name': [
              Tourist.lastname,
              Tourist.firstname
            ].join(' '),
          'document': [
              KT.config.touristDocuments[Tourist.document.type].docname,
              Tourist.document.series,
              Tourist.document.number
            ].join(' ')
        });
      }
    });

    $wnd.find('.js-ore-service-manualedit--new-ticket-tourist')
      .selectize({
        openOnFocus: true,
        create: false,
        valueField: 'value',
        labelField: 'name',
        options: tourists,
        render: {
          item: function(item) {
            return '<div data-value="' + item.value + '" class="item" data-tooltip="' + item.document + '">' +
            item.name + '</div>';
          },
          option: function(item) {
            return '<div data-value="' + item.value + '" data-selectable class="option">' +
            item.name + '<br>(' + item.document + ')</div>';
          }
        }
      });
  };

  /**
  * Получение параметров для команды изменения брони
  * @param {Object} $manualForm - [jQuery DOM] форма ручного редактирования услуги
  */
  AviaServiceViewModel.prototype.getChangeBookDataParams = function($manualForm) {
    var ServiceStorage = this.ServiceStorage;

    var pnr = $manualForm.find('.js-ore-service-manualedit--new-pnr-number').val();
    var supplier = $manualForm.find('.js-ore-service-manualedit--supplier').val();
    var tariff = $manualForm.find('.js-ore-service-manualedit--tariff').val();
    var lastTicketingDate = $manualForm.find('.js-ore-service-manualedit--last-ticketing-date').val();

    if (pnr === '') {
      if (ServiceStorage.offerInfo.pnr.pnrNumber !== undefined) {
        pnr = ServiceStorage.offerInfo.pnr.pnrNumber;
      } else {
        KT.notify('reservationNumberNotSet');
        return false;
      }
    }

    if (supplier === '') { supplier = this.supplierCode; } //!!!!

    lastTicketingDate = (lastTicketingDate !== '') ?
      moment(lastTicketingDate,'DD.MM.YYYY').format('YYYY-MM-DD HH:mm:ss') : null;

    return {
      'serviceId': ServiceStorage.serviceId,
      'reservationData': [{
        'reservationAction' : 'update',
        'PNR': pnr,
        'aviaReservation': {
          'PNR': pnr,
          'segments': null,
          'supplierCode': supplier,
          'status' : 1
        },
        'flightTariff': tariff,          
        'lastTicketingDate': lastTicketingDate
      }]
    };
  };

  /**
  * Получение параметров для команды изменения билетов
  * @param {Object} $manualForm - [jQuery DOM] форма ручного редактирования услуги
  */
  AviaServiceViewModel.prototype.getChangeTicketDataParams = function($manualForm) {
    var $changeTicketForm = $manualForm.find('.js-ore-service-manualedit--change-avia-ticket');
    var changingTicketSelect = $changeTicketForm.find('.js-ore-service-manualedit--current-ticket-number')[0].selectize;
    
    var changingTicketNumber = changingTicketSelect.getValue();
    var changingTicket;

    if (changingTicketNumber === '' || changingTicketNumber === null) {
      KT.notify('changingTicketNotSelected');
      return false;
    } else {
      changingTicket = changingTicketSelect.options[changingTicketNumber];
    }

    var newTicketNumber = $changeTicketForm.find('.js-ore-service-manualedit--new-ticket-number').val();
    if (newTicketNumber === '') {
      KT.notify('ticketNumberNotEntered');
      return false;
    }

    return {
      'serviceId': this.ServiceStorage.serviceId,
      'ticketData': {
        'ticketAction': 'update',
        'ticketNumber': changingTicket.number,
        'ticketData': {
          'pnr': changingTicket.pnr,
          'touristId': changingTicket.tourist.id,
          'ticketNumber': newTicketNumber,
          'ticketStatus': 1,
          'newTicket': null
        }
      }
    };
  };

  /**
  * Получение параметров для команды создания билета
  * @param {Object} $manualForm - [jQuery DOM] форма ручного редактирования услуги
  */
  AviaServiceViewModel.prototype.getAddTicketParams = function($manualForm) {
    var $addTicketForm = $manualForm.find('.js-ore-service-manualedit--add-avia-ticket');
    var newTicketNumber = $addTicketForm.find('.js-ore-service-manualedit--new-ticket-number').val();
    if (newTicketNumber === '') {
      KT.notify('ticketNumberNotEntered');
      return false;
    }
    var touristId = $addTicketForm.find('.js-ore-service-manualedit--new-ticket-tourist').val();
    if (touristId === '') {
      KT.notify('ticketTouristNotSet');
      return false;
    }

    return {
      'serviceId': this.ServiceStorage.serviceId,
      'ticketData': {
        'ticketAction': 'add',
        'ticketNumber': null,
        'ticketData': {
          'pnr': this.ServiceStorage.offerInfo.pnr.pnrNumber,
          'touristId': +touristId,
          'ticketNumber': newTicketNumber,
          'ticketStatus': 1,
          'newTicket': null
        }
      }
    };
  };

  return AviaServiceViewModel;

}));

(function(global, factory){

    KT.crates.OrderEdit.services.HotelServiceViewModel = factory(KT.crates.OrderEdit);

}(this, function(crate) {
  var ServiceViewModel = crate.services.ServiceViewModel;

  /** карта категорий отеля */
  var categoryMap = {
    'FIVE': 5,
    'FOUR': 4,
    'THREE': 3,
    'TWO': 2,
    'ONE': 1
  };

  /**
  * View-объект для отображения оффера проживания
  * @constructor
  * @param {ServiceStorage} ServiceStorage - данные услуги
  * @param {Object} templates - ссылка на коллекцию шаблонов модуля
  * @param {Object} suppliersMap - список поставщиков
  */
  var HotelServiceViewModel = ServiceViewModel.extend(function(ServiceStorage, templates, suppliersMap) {
    ServiceViewModel.call(this, ServiceStorage, templates);

    this.serviceTypeName = 'hotel';

    this.touristsAges = {
      'adults': {
        'ordered':ServiceStorage.declaredTouristAges.adults,
        'current':0
      },
      'children': {
        'ordered':ServiceStorage.declaredTouristAges.children,
        'current':0
      }
    };

    this.bookTermsView = '';

    this.hotelName = ServiceStorage.name;
    this.hotelAddress = '';
    this.hotelPhoto = '';
    this.category = null;
    this.roomType = '';

    this.reservationData = false;
    this.voucherLink = false;
    this.hasAdditionalServices = false;

    this.touristsMap = [];

    if (!ServiceStorage.isPartial) {      
      this.supplierCode = ServiceStorage.offerInfo.supplierCode;
      this.supplierName = (suppliersMap[this.supplierCode] !== undefined) ?
          suppliersMap[this.supplierCode].name :
          this.supplierCode;
          
      this.tosDocumentName = ServiceStorage.tosDocumentName;
    }
  });

  /* Шаблоны для отелей */
  HotelServiceViewModel.templates = {
    hotelFormHeader: 'orderEdit/services/hotel/hotelFormHeader',
    hotelFormMain: 'orderEdit/services/hotel/hotelFormMain',
    hotelServiceActions: 'orderEdit/services/hotel/hotelServiceActions',
    hotelBookTerms: 'orderEdit/services/hotel/hotelBookTerms',
    hotelFullInfo: 'orderEdit/services/hotel/hotelFullInfo',
    hotelAdditionalServices: 'orderEdit/services/hotel/hotelAdditionalServices',
    hotelMinimalPrice: 'orderEdit/services/hotel/hotelMinimalPrice',
    hotelManualForm: 'orderEdit/services/hotel/hotelManualForm'
  };

  /**  Подготовка шаблонов */
  HotelServiceViewModel.prototype.prepareViews = function() {
    if (!this.ServiceStorage.isPartial) {
      this.processHotelInfo();
      this.processReservation();
      this.processCustomFields();
    }

    this.prepareHeaderView();
    this.prepareMainView();
    this.prepareAdditionalServicesView();
    this.prepareActionsView();
    this.prepareBookTermsView();
  };

  /** Обработка информации об отеле */
  HotelServiceViewModel.prototype.processHotelInfo = function() {
    var ServiceStorage = this.ServiceStorage;

    this.hotelName = (ServiceStorage.offerInfo.hotelInfo !== null) ? 
        ServiceStorage.offerInfo.hotelInfo.name : 'нет названия';

    this.hotelAddress = (ServiceStorage.offerInfo.hotelInfo !== null) ? 
        ServiceStorage.offerInfo.hotelInfo.address : 'нет адреса';

    this.hotelPhoto = (ServiceStorage.offerInfo.hotelInfo !== null) ? 
        ServiceStorage.offerInfo.hotelInfo.mainImageUrl : '';

    this.category = (
        ServiceStorage.offerInfo.hotelInfo === null || 
        ServiceStorage.offerInfo.hotelInfo.category === null
      ) ? false : (function(c) {
        var stars = [];
        if (categoryMap[c] !== undefined) {
          for (var i = 0; i < categoryMap[c]; i++) {
            stars.push(true);
          }
        }
        return stars;
      }(ServiceStorage.offerInfo.hotelInfo.category));

    this.roomType = ServiceStorage.offerInfo.roomType;
  };

  /** Обработка данных брони и ваучеров */
  HotelServiceViewModel.prototype.processReservation = function() {
    var ServiceStorage = this.ServiceStorage;

    this.reservationData = (ServiceStorage.offerInfo.hotelReservations !== null) ?
          {'number': ServiceStorage.offerInfo.hotelReservations.reservationNumber} : 
          false;
    
    this.voucherLink = (
        ServiceStorage.offerInfo.hotelReservations !== null && 
        ServiceStorage.offerInfo.hotelReservations.hotelVouchers !== null && 
        Array.isArray(ServiceStorage.offerInfo.hotelReservations.hotelVouchers)
      ) ? ServiceStorage.offerInfo.hotelReservations.hotelVouchers[0].receiptUrl : false;
  };

  /** Обработка дополнительных полей  */
  HotelServiceViewModel.prototype.processCustomFields = function() {
    var self = this;
    var unprocessedFields = ServiceViewModel.prototype.processCustomFields.call(this);

    // Обработка специализированных доп. полей
    if (Array.isArray(unprocessedFields)) {
      unprocessedFields.forEach(function(fieldData) {
        switch (fieldData.typeTemplate) {
          case 5: // minimal price
            if (fieldData.value === null) { return; }
            var minimalPriceData = JSON.parse(fieldData.value);

            self.minimalPriceView = Mustache.render(self.tpl.hotelMinimalPrice, {
              'hotelName': minimalPriceData.hotelName,
              'room': minimalPriceData.roomType,
              'mealType': minimalPriceData.mealType,
              'price': Number(minimalPriceData.price).toMoney(0,',',' '),
              'currency': KT.getCatalogInfo('lcurrency', minimalPriceData.currency, 'icon')
            });
            break;
        }
      });
    }
  };

  /** Подготовка интерфейса шапки */
  HotelServiceViewModel.prototype.prepareHeaderView = function() {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    var dateFrom = ServiceStorage.startDate;
    var dateTo = ServiceStorage.endDate;
    var nightsCount = dateTo.startOf('day').diff(dateFrom.startOf('day'), 'days');
    var daysCount = nightsCount + 1;

    this.headerView = Mustache.render(this.tpl.hotelFormHeader, {
      'priorityOffer': !ServiceStorage.hasTravelPolicy ? false :
        ServiceStorage.offerInfo.travelPolicy.priorityOffer,
      'isPartial': ServiceStorage.isPartial,
      'roomType': (!ServiceStorage.isPartial) ? ServiceStorage.offerInfo.roomType : '',
      'hotelId': (!ServiceStorage.isPartial) ? ServiceStorage.offerInfo.hotelId : '',
      'hotelName': self.hotelName,
      'hotelAddress': self.hotelAddress,
      'hotelPhoto': self.hotelPhoto,
      'category': self.category,
      'priceLocal': Number(ServiceStorage.prices.inLocal.client.gross).toMoney(0,',',' '),
      'priceInView': Number(ServiceStorage.prices.inView.client.gross).toMoney(0,',',' '),
      'viewCurrencyIcon': KT.getCatalogInfo(
          'lcurrency', ServiceStorage.prices.inView.currencyCode,'icon'
        ),
      'statusIcon': KT.getCatalogInfo('servicestatuses', ServiceStorage.status, 'icon'),
      'statusTitle': (KT.profile.userType === 'op' && ServiceStorage.status === 9) ?
        'Ручной режим' :
        KT.getCatalogInfo('servicestatuses', ServiceStorage.status, 'title'),
      'dateFrom': {
        'wd': dateFrom.format('dd'),
        'dm': dateFrom.format('DD.MM')
      },
      'dateTo': {
        'wd': dateTo.format('dd'),
        'dm': dateTo.format('DD.MM')
      },
      'days': {
        'count': daysCount,
        'label': declOfNum(daysCount,['день','дня','дней'])
      },
      'residents': {
        'adults': ServiceStorage.declaredTouristAges.adults,
        'children': ServiceStorage.declaredTouristAges.children,
      },
      'reservationData': self.reservationData,
      'voucherLink': self.voucherLink
    });
  };

  /** Подготовка интерфейса главного блока */
  HotelServiceViewModel.prototype.prepareMainView = function() {
    var ServiceStorage = this.ServiceStorage;

    var dateFrom = ServiceStorage.startDate;
    var dateTo = ServiceStorage.endDate;
    var nightsCount = dateTo.startOf('day').diff(dateFrom.startOf('day'), 'days');
    var daysCount = nightsCount + 1;

    if (!ServiceStorage.isPartial) {
      this.mainView = Mustache.render(this.tpl.hotelFormMain, {
        'offerSupplier': (KT.profile.userType === 'op') ? this.supplierName : false,
        'roomType': this.roomType,
        'roomTypeDescription':(
            typeof ServiceStorage.offerInfo.roomTypeDescription === 'string' &&
            ServiceStorage.offerInfo.roomTypeDescription !== ''
          ) ? ServiceStorage.offerInfo.roomTypeDescription.replace(/<\/?[^>]+>/gi, '') : false,
        'mealType': ServiceStorage.offerInfo.mealType,
        'daysCount': daysCount + ' ' + declOfNum(daysCount,['день','дня','дней']),
        'nightsCount': nightsCount + ' ' + declOfNum(nightsCount,['ночь','ночи','ночей']),
        'fareName':(
            typeof ServiceStorage.offerInfo.fareName === 'string' &&
            ServiceStorage.offerInfo.fareName !== ''
          ) ? ServiceStorage.offerInfo.fareName.replace(/<\/?[^>]+>/gi, '') : false,
        'fareDescription':(
            typeof ServiceStorage.offerInfo.fareDescription === 'string' &&
            ServiceStorage.offerInfo.fareDescription !== ''
          ) ? ServiceStorage.offerInfo.fareDescription.replace(/<\/?[^>]+>/gi, '') : false,
        'voucherLink': this.voucherLink
      });
    }
  };

  /** Подготовка интерфеса дополнительных услуг */
  HotelServiceViewModel.prototype.prepareAdditionalServicesView = function() {
    if (this.ServiceStorage.isPartial) { return; }
    var availableAddServices = this.ServiceStorage.offerInfo.additionalServices;
    var issuedAddServices = this.ServiceStorage.additionalServices;

    if (!Array.isArray(availableAddServices) || availableAddServices.length === 0) {
      return;
    }

    var issuedServiceTypes = {};
    issuedAddServices.forEach(function(addService) {
      issuedServiceTypes[addService.serviceSubType] = {
        'oneOfTypeAllowed': !Boolean(addService.bookingSomeServices)
      };
    });

    this.hasAdditionalServices = true;

    var addServiceTypes = {
      1: {
        'icon': 'service-meal',
        'name': 'Дополнительное питание'
      }
    };

    var self = this;

    this.additionalServicesView = Mustache.render(this.tpl.hotelAdditionalServices, {
      'issued': issuedAddServices
        .map(function(addService) {
          var serviceType = addService.serviceSubType;
          if (!addServiceTypes.hasOwnProperty(serviceType)) {
            return false;
          }

          var localSalesTerms = addService.salesTermsInfo.localCurrency.client;
          var viewSalesTerms = addService.salesTermsInfo.viewCurrency.client;
          var addServiceStatus = +addService.status;
          
          return {
            'id': addService.idAddService,
            'name': addService.name,
            'typeName': addServiceTypes[serviceType].name,
            'icon': addServiceTypes[serviceType].icon,
            'localPrice': Number(localSalesTerms.amountBrutto).toMoney(0, ',', ' '),
            'localCurrency': KT.getCatalogInfo('lcurrency', localSalesTerms.currency, 'icon'),
            'viewPrice': Number(viewSalesTerms.amountBrutto).toMoney(2, ',', ' '),
            'viewCurrency': KT.getCatalogInfo('lcurrency', viewSalesTerms.currency, 'icon'),
            'isBooked': (addServiceStatus !== 0), //TODO: добавить 1, когда статусы будут приведены в соответствие
            'bookedWithService': (addServiceStatus === 0 && addService.bookedWithService),
            'bookAllowed': (addServiceStatus === 0 && !addService.bookedWithService),
            'removeAllowed': (
              self.ServiceStorage.checkTransition('RemoveExtraService') &&
              addServiceStatus === 0
            )
          };
        })
        .filter(function(addService) { return addService !== false; }),
      'available': availableAddServices
        .map(function(addService) {
          var serviceType = addService.serviceSubType;
          if (!addServiceTypes.hasOwnProperty(serviceType)) {
            return false;
          }

          var localSalesTerms = addService.salesTermsInfo.localCurrency.client;
          var viewSalesTerms = addService.salesTermsInfo.viewCurrency.client;
          
          return {
            'id': addService.idAddService,
            'name': addService.name,
            'typeName': addServiceTypes[serviceType].name,
            'icon': addServiceTypes[serviceType].icon,
            'addingAllowed': (
                !issuedServiceTypes.hasOwnProperty(serviceType) || 
                !issuedServiceTypes[serviceType].oneOfTypeAllowed
              ),
            'localPrice': Number(localSalesTerms.amountBrutto).toMoney(0, ',', ' '),
            'localCurrency': KT.getCatalogInfo('lcurrency', localSalesTerms.currency, 'icon'),
            'viewPrice': Number(viewSalesTerms.amountBrutto).toMoney(2, ',', ' '),
            'viewCurrency': KT.getCatalogInfo('lcurrency', viewSalesTerms.currency, 'icon')
          };
        })
        .filter(function(addService) { return addService !== false; })
    });
  };

  /** Подготовка интерфейса действий с услугой */
  HotelServiceViewModel.prototype.prepareActionsView = function() {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    if (!ServiceStorage.isPartial) {
      var serviceActions = this.getServiceActions();

      this.actionsView = Mustache.render(self.tpl.hotelServiceActions, serviceActions);
    } else { this.actionsView = ''; }
  };

  /** Подготовка интерфейса условий бронирования */
  HotelServiceViewModel.prototype.prepareBookTermsView = function() {
    var ServiceStorage = this.ServiceStorage;

    var penalties = null;

    if (Array.isArray(ServiceStorage.clientCancelPenalties)) {
      penalties = [];

      ServiceStorage.clientCancelPenalties.forEach(function(penalty) {
        penalties.push({
          'dateFrom': penalty.dateFrom.format(KT.config.dateFormat),
          'dateTo': penalty.dateTo.format(KT.config.dateFormat),
          'description': penalty.description,
          'localAmount': Number(penalty.penaltySum.inLocal.amount).toMoney(2, ',', ' '),
          'localCurrency': KT.getCatalogInfo('lcurrency', penalty.penaltySum.inLocal.currency, 'icon'),
          'viewAmount': Number(penalty.penaltySum.inView.amount).toMoney(2, ',', ' '),
          'viewCurrency': KT.getCatalogInfo('lcurrency', penalty.penaltySum.inView.currency, 'icon'),
        });
      });
    }

    this.bookTermsView = Mustache.render(this.tpl.hotelBookTerms, {
      'hotelName': this.hotelName,
      'roomType': this.roomType,
      'penalties': penalties
    });
  };

  /** 
  * Инициализация элементов управления после рендера формы
  * @param {Object} $container - форма услуги
  * @todo в принципе передать управление контейнером услуги в класс viewModel?
  */
  HotelServiceViewModel.prototype.initControls = function($container) {
    this.initAddServicesControls($container);

    if (this.customFields.length > 0) {
      var $customFields = $();

      this.customFields.forEach(function(customField) {
        $customFields = $customFields.add(customField.render());
      });

      $container.find('.js-service-form-custom-fields').html($customFields);

      this.customFields.forEach(function(customField) {
        customField.initialize();
      });
    }
  };

  /**
  * Инициализация элементов управления формы дополнительных услуг
  * @param {Object} $container - формв услуги
  */
  HotelServiceViewModel.prototype.initAddServicesControls = function($container) {
    if (this.hasAdditionalServices) {
      var $availableAddServicesList = $container.find('.js-service-form--available-add-services-list');
      var $showAddServiceList = $container.find('.js-service-form--show-add-services-list');
      var $hideAddServiceList = $container.find('.js-service-form--hide-add-services-list');

      $showAddServiceList.on('click', function() {
        $hideAddServiceList.addClass('active');
        $showAddServiceList.removeClass('active');
        $availableAddServicesList.slideDown(200).addClass('active');
      });

      $hideAddServiceList.on('click', function() {
        $hideAddServiceList.removeClass('active');
        $showAddServiceList.addClass('active');
        $availableAddServicesList.slideUp(200).removeClass('active');
      });
    }
  };

  /**
  * Связывание информации о туристах из заявки (getOrderTourists)
  * с информацией из услуги и формирование данных для блока туристов
  * @param {TouristStorage[]} tourists - массив информации по туристам
  * @param {Boolean} [overrideSave] - явное указание возможности сохранения привязки
  */
  HotelServiceViewModel.prototype.mapTouristsInfo = function(tourists, overrideSave) {
    var self = this;
    var ServiceStorage = this.ServiceStorage;

    this.touristsAges = {
      'adults': {
        'ordered': ServiceStorage.declaredTouristAges.adults,
        'current':0
      },
      'children': {
        'ordered': ServiceStorage.declaredTouristAges.children,
        'current':0
      }
    };
    this.touristsMap = [];

    if (tourists.length === 0) { return; }
    if (overrideSave === undefined) { overrideSave = false; }
    var isSavingAllowed = (
        !ServiceStorage.isPartial &&
        ((ServiceStorage.status === 9 && KT.profile.userType === 'op') || ServiceStorage.status === 0)
      );

    tourists.forEach(function(TouristStorage) {
      var isLinked = ServiceStorage.tourists.hasOwnProperty(TouristStorage.touristId);

      if (!ServiceStorage.isPartial) {

        // сохранение числа привязанных туристов по возрастным группам
        if (isLinked) {
          var age = moment.duration(
              moment().valueOf() - TouristStorage.birthdate.valueOf()
            ).asYears();
          if (age < 12) { self.touristsAges.children.current += 1; }
          else { self.touristsAges.adults.current += 1; }
        }
      }

      if (overrideSave || isSavingAllowed || isLinked) {
        self.touristsMap.push({
          'allowSave': isSavingAllowed,
          'touristId': TouristStorage.touristId,
          'firstName': TouristStorage.firstname,
          'surName': TouristStorage.lastname,
          'attached': isLinked,
          'touristExtra': ''
        });
      }
    });
  };

  /**
  * Рендер элементов управления блока туристоп
  * @param {Object} $tourist - блок туриста
  * @param {Object} tourist - данные туриста (touristsMap)
  */
  HotelServiceViewModel.prototype.renderTouristControls = function() {};

  /**
  * Получение структуры доступных действий над услугой
  * @return {Object} идентификатор услуги и разрешения на действия
  */
  HotelServiceViewModel.prototype.getServiceActions = function() {
    var ServiceStorage = this.ServiceStorage;

    var serviceActions = {
      'serviceId': ServiceStorage.serviceId,
      'ManualEdit': (KT.profile.userType === 'op' && ServiceStorage.status === 9) ? true : false,
      'save': (this.customFields.length > 0)
    };

    ServiceStorage.allowedTransitions.forEach(function(transition) {
      serviceActions[transition] = true;
    });

    // хак для разного стиля кнопок, переделать?
    if (KT.profile.userType !== 'op' && serviceActions['Manual']) {
      serviceActions['Manual'] = false;
      serviceActions['ToManager'] = true;
    }

    serviceActions.agreementSet = ServiceStorage.isTOSAgreementSet;

    if (!serviceActions.BookStart) {
      serviceActions.agreementDisabled = true;
      serviceActions.agreementSet = true;
    }

    return serviceActions;
  };

  /**
  * Создает список документов с условиями работы с услугой с методами их получения
  * @return {Object} объект с документами и методами их получения
  */
  HotelServiceViewModel.prototype.defineTermsDocuments = function() {
    var _instance = this;

    var termsDocuments = {
      'companyTOS': {
        'load':function(renderer,loader) {
          loader('companyTOS',renderer);
        }
      }
    };
    termsDocuments[_instance.tosDocumentName] = {
      'load':function(renderer) {
        renderer(_instance.tosDocumentName, _instance.bookTermsView);
      }
    };

    return termsDocuments;
  };

  /**
  * Рендер формы ручного редактирования улуги
  * @return {String} - код формы редактирования услуги
  */
  HotelServiceViewModel.prototype.renderManualForm = function() {
    var commonParams = ServiceViewModel.prototype.setCommonManualFormParams.call(this);

    var manualFormParams = $.extend(commonParams, {
          'reservation': (
            this.ServiceStorage.offerInfo.hotelReservations !== null &&
            this.ServiceStorage.offerInfo.hotelReservations.reservationNumber !== null
          ) ? this.ServiceStorage.offerInfo.hotelReservations.reservationNumber : null
      });

    return Mustache.render(this.tpl.hotelManualForm, manualFormParams);
  };

  /**
  * Инициализация элементов управления окна ручного редактирования услуги
  * @param {Object} $wnd - [jQueryDom] объект окна
  * @param {Object} mds - объект модуля
  * @todo Убрать зависимость от модуля
  */
  HotelServiceViewModel.prototype.initManualFormControls = function($wnd, mds) {
    var self = this;
    ServiceViewModel.prototype.initManualFormControls.call(this, $wnd, mds);

    var ServiceStorage = this.ServiceStorage;

    /* Изменение параметров брони */
    KT.Dictionary.getAsList('suppliers', {'serviceId': ServiceStorage.typeCode, 'active': 1})
      .then(function(hotelSuppliers) {
        /*
        var suppliers = [];
        for (var supplierCode in aviaSuppliers) {
          if (aviaSuppliers.hasOwnProperty(supplierCode)) {
            suppliers.push({
              'value': supplierCode,
              'name': aviaSuppliers[supplierCode].name
            });
          }
        } */

        $wnd.find('.js-ore-service-manualedit--supplier')
          .selectize({
            openOnFocus: true,
            create: false,
            valueField: 'supplierCode',
            labelField: 'name',
            options: hotelSuppliers,
            items: [self.supplierCode] //!!!
          });
      });
  };

  /**
  * Получение параметров для команды изменения брони
  * @param {Object} $manualForm - [jQuery DOM] форма ручного редактирования услуги
  */
  HotelServiceViewModel.prototype.getChangeBookDataParams = function($manualForm) {
    var newReservationNumber = $manualForm.find('.js-ore-service-manualedit--new-reservation-number').val();
    var supplier = $manualForm.find('.js-ore-service-manualedit--supplier').val();

    if (newReservationNumber === '') {
      if (this.ServiceStorage.offerInfo.hotelReservations !== null) {
        newReservationNumber = this.ServiceStorage.offerInfo.hotelReservations.reservationNumber;
      } else {
        return false;
      }
    }

    if (supplier === '') { supplier = this.supplierCode; }

    return  {
      'serviceId': this.serviceId,
      'reservationData': [{
        'reservationNumber': newReservationNumber,
        'supplierCode': supplier
      }]
    };
  };

  return HotelServiceViewModel;
  
}));

(function(global,factory) {

    KT.crates.OrderEdit.services.view = factory(KT.crates.OrderEdit.services);

}(this,function(crate) {
  var AviaServiceViewModel = crate.AviaServiceViewModel;
  var HotelServiceViewModel = crate.HotelServiceViewModel;

  /**
  * Редактирование заявки: услуги
  * @constructor
  * @param {Object} module - хранилище родительского модуля
  * @param {Object} [options] - Объект конфигурации класса
  */
  var modView = function(module,options) {
    this.mds = module;
    if (options === undefined) {options = {};}
    this.config = $.extend(true, {
      'templateUrl': '/cabinetUI/orders/getTemplates',
      'staticDocumentUrl': '/cabinetUI/orders/getStaticDocument',
      'templates': {
        serviceForm: 'orderEdit/services/serviceForm',
        serviceTourist: 'orderEdit/services/serviceTourist',
        serviceListEmpty: 'orderEdit/services/serviceListEmpty',
        // staticDoc: 'orderEdit/staticDoc',
        pricesChangedModal: 'orderEdit/modals/pricesChanged',
        bookChangeModal: 'orderEdit/modals/bookChange',
        bookCancelModal: 'orderEdit/modals/bookCancel',
        sendToManagerModal: 'orderEdit/modals/sendToManager',
        // custom fields
        TextCF: 'orderEdit/customFields/textCF',
        TextAreaCF: 'orderEdit/customFields/textAreaCF',
        NumberCF: 'orderEdit/customFields/numberCF',
        DateCF: 'orderEdit/customFields/dateCF'
      }
    },options);

    // добавить шаблоны view-моделей услуг
    $.extend(this.config.templates, AviaServiceViewModel.templates);
    $.extend(this.config.templates, HotelServiceViewModel.templates);

    this.mds.servicesTermsDocuments = {};

    this.$serviceList = $('#cab-services');
    this.$manualFormsRoot = $('body');

    // ссылка на модальное окно изменения брони
    this.$bookChangeModal = null;

    // view-модели услуг
    this.serviceViewModels = {};
    // формы услуг
    this.serviceForms = {};

    // управление окном изменения брони
    this.BookChangeModal = this.setBookChangeModal();
    // управление окном отмены брони
    this.BookCancelModal = this.setBookCancelModal();

    // управление окном просмотра полной информации об отеле
    this.HotelInfoPages = this.setHotelInfoPages();
    // управление окнами редактирования услуг в ручном режиме
    this.ManualEditForms = this.setManualEditForms();
  };

  /**
  * Рендер услуг - общий метод
  * @param {OrderStorage} OrderStorage - хранилище данных заявки
  * @return {Promise} - процесс рендера
  */
  modView.prototype.renderServices = function(OrderStorage) {
    var _instance = this;
    var renderProcess = $.Deferred();

    var getAviaSuppliersMap = KT.Dictionary.getAsMap('suppliers', 'supplierCode', {'serviceId': 2, 'active': 1});
    var getHotelSuppliersMap = KT.Dictionary.getAsMap('suppliers', 'supplierCode', {'serviceId': 1, 'active': 1});

    $.when(getAviaSuppliersMap, getHotelSuppliersMap)
      .then(function(aviaSuppliersMap, hotelSuppliersMap) {
        var serviceIds = [];
        var termsDocuments = {};

        /*
        * сохраняем текущую позицию окна для возврата после перезагрузки форм,
        * если есть открытые услуги, сохраняем состояние,
        * очищаем список услуг
        */
        var savescroll = $('#main-scroller').scrollTop();
        var srvFormStatus = {};

        _instance.$serviceList.find('.js-service-form')
          .each(function() {
            srvFormStatus[$(this).attr('data-sid')] = $(this).hasClass('active');
          })
          .end()
          .empty();

        // обработка массива услуг
        OrderStorage.getServices().forEach(function(ServiceStorage) {
          var ServiceViewModel;
          var serviceId = ServiceStorage.serviceId;

          if (_instance.serviceViewModels.hasOwnProperty(serviceId)) {
            ServiceViewModel = _instance.serviceViewModels[serviceId];
          } else {
            switch(ServiceStorage.typeCode) {
              case 1:
                ServiceViewModel = new HotelServiceViewModel(ServiceStorage, _instance.mds.tpl, hotelSuppliersMap);
                break;
              case 2:
                ServiceViewModel = new AviaServiceViewModel(ServiceStorage, _instance.mds.tpl, aviaSuppliersMap);
                break;
              default:
                return;
            }
            _instance.serviceViewModels[serviceId] = ServiceViewModel;
          }

          ServiceViewModel.prepareViews();
          ServiceViewModel.mapTouristsInfo(OrderStorage.getTourists());
          $.extend(termsDocuments, ServiceViewModel.defineTermsDocuments());
          _instance.ManualEditForms.prepare(ServiceViewModel);

          var $serviceForm = $(
              Mustache.render(_instance.mds.tpl.serviceForm, {
                'serviceId':ServiceStorage.serviceId,
                'isCancelled': (ServiceStorage.status === 7 || ServiceStorage.penaltySums !== null),
                'serviceName': KT.getCatalogInfo('servicetypes', ServiceStorage.typeCode, 'title'),
                'serviceType': ServiceStorage.typeCode,
                'serviceTypeName': ServiceViewModel.serviceTypeName,
                'serviceIcon': KT.getCatalogInfo('servicetypes',ServiceStorage.typeCode,'icon'),
                'penalty': (ServiceStorage.penaltySums === null) ? null : {
                    'client': {
                      'inLocal': ServiceStorage.penaltySums.inLocal.client.toMoney(0,',',' '),
                      'inView': ServiceStorage.penaltySums.inView.client.toMoney(2,',',' '),
                      'viewCurrencyIcon': KT.getCatalogInfo(
                          'lcurrency', ServiceStorage.penaltySums.inView.currencyCode, 'icon'
                        )
                    },
                    'supplier': (KT.profile.userType !== 'op') ? null : {
                      'inLocal': ServiceStorage.penaltySums.inLocal.supplier.toMoney(0,',',' '),
                      'inView': ServiceStorage.penaltySums.inView.supplier.toMoney(2,',',' '),
                      'viewCurrencyIcon': KT.getCatalogInfo(
                          'lcurrency', ServiceStorage.penaltySums.inView.currencyCode, 'icon'
                        )
                    }
                  },
                'header': ServiceViewModel.headerView,
                'main': ServiceViewModel.mainView,
                'additionalServices': (!ServiceViewModel.hasAdditionalServices) ? null :
                  ServiceViewModel.additionalServicesView,
                'minimalPrice': ServiceViewModel.minimalPriceView,
                'travelPolicyViolations': (!ServiceStorage.hasTPViolations) ? null : 
                  {'list': ServiceStorage.offerInfo.travelPolicy.travelPolicyFailCodes},
                'allowTouristAdd': (
                    OrderStorage.isAddTouristAllowed &&
                    ServiceViewModel.checkTouristAddAllowance()
                  ) ? true : false,
                'tourists': '', //touristsList /** @deprecated */ */
                'hasCustomFields': (ServiceViewModel.customFields.length > 0)
              })
            )
            .appendTo(_instance.$serviceList)
            .data('unsaved', false);

          ServiceViewModel.initControls($serviceForm);

          _instance.serviceForms[serviceId] = $serviceForm;

          if (srvFormStatus[ServiceStorage.serviceId] === true) {
            $serviceForm.addClass('active');
          }

          _instance.serviceForms[ServiceStorage.serviceId] = $serviceForm;
          serviceIds.push(serviceId);
        });

        $('#main-scroller').scrollTop(savescroll);

        _instance.prepareServiceTermsDocuments(termsDocuments);

        serviceIds.forEach(function(serviceId) {
          _instance.serviceForms[serviceId]
            .find('.js-service-form-actions')
              .html(_instance.serviceViewModels[serviceId].actionsView)
              .end()
            .find('.js-service-form-tos-agreement')
              .on('click','.js-service-form-tos-agreement--tos-link',function() {
                var doc = $(this).attr('data-link');
                _instance.mds.servicesTermsDocuments[doc].open();
              });
          
          _instance.renderServiceTourists(serviceId);
        });

        _instance.getServiceTermsDocuments(termsDocuments);
        renderProcess.resolve();
      });

      return renderProcess.promise();
  };

  /** Вывод информации об отсутствии услуг для вывода */
  modView.prototype.renderEmptyServiceList = function() {
    this.$serviceList.html(Mustache.render(this.mds.tpl.serviceListEmpty, {}));
  };

  /**
  * Отображение лоадера в процессе обработки услуги
  * @todo перенести в центральный класс вьюх?
  * @param {Integer} serviceId - ID редактируемой услуги
  */
  modView.prototype.renderPendingServiceProcess = function(serviceId) {
    this.serviceForms[serviceId].find('.js-service-form-actions')
      .html(Mustache.render(KT.tpl.spinner, {'type':'medium'}));
  };

  /**
  * Раскрыть полную информацию об услуге и *дослайдить* до нее 
  * @param {Integer} serviceId - ID услуги
  */
  modView.prototype.navigateToService = function(serviceId) {
    var $service = this.serviceForms[serviceId];
    console.log('navigating to service: ' + serviceId + ', offset: ' + $service.offset().top);

    if ($service !== undefined) {
      $('#main-scroller').animate({'scrollTop': $service.offset().top}, 400, function() {
        $service.addClass('active');
      });
    }
  };

  /**
  * Рендер блока туристов конкретной услуги
  * @param {Integer} serviceId - ID услуги
  * @todo переделать view-модели и этот метод
  */
  modView.prototype.renderServiceTourists = function(serviceId) {
    var _instance = this;

    var $serviceForm = _instance.serviceForms[serviceId];
    var ServiceViewModel = _instance.serviceViewModels[serviceId];
    var ServiceStorage = ServiceViewModel.ServiceStorage;

    ServiceViewModel.mapTouristsInfo(_instance.mds.OrderStorage.getTourists());

    var $serviceTourists = $serviceForm.find('.js-service-form-tourists');
    $serviceTourists.data('unsaved', false).empty();

    ServiceViewModel.touristsMap.forEach(function(tourist) {
      var $tourist = $(Mustache.render(_instance.mds.tpl.serviceTourist, tourist))
        .appendTo($serviceTourists);
      ServiceViewModel.renderTouristControls($tourist, tourist);
    });    

    if (ServiceStorage.checkAllTouristsLinked()) {
      $serviceForm.find('.js-service-form--add-tourist').remove();
    }
  };

  /**
  * Рендер доступных действий с услугой 
  * @param {Integer} serviceId - ID услуги
  */
  modView.prototype.renderServiceActions = function(serviceId) {
    var ServiceViewModel = this.serviceViewModels[serviceId];
    var $serviceForm = this.serviceForms[serviceId];

    ServiceViewModel.prepareActionsView();

    $serviceForm.find('.js-service-form-actions')
      .html(ServiceViewModel.actionsView);
  };

  /**
  * Сбор данных из блока туристов услуги 
  * @param {Integer} serviceId - ID услуги
  * @return {Object} данные по привязке и дополнительные данные туристов
  */
  modView.prototype.getServiceTouristsData = function(serviceId) {
    var touristData = {};
    var errors = false;

    this.serviceForms[serviceId].find('.js-service-form-tourist')
      .each(function() {
        var $bindControl = $(this).find('.js-service-form-tourist--service-bound');
        var touristId = +$bindControl.attr('data-touristid');

        touristData[touristId] = {
          'state': $bindControl.prop('checked'),
          'loyalityProviderId': null,
          'loyalityCardNumber': null
        };

        var $loyalityProgramSelect = $(this).find('.js-service-avia-loyalty-program');
        if ($loyalityProgramSelect.length !== 0) {
          var $loyalityProvider = $loyalityProgramSelect.find('.js-service-avia-loyalty-program--provider');
          var $loyalityNumber = $loyalityProgramSelect.find('.js-service-avia-loyalty-program--number');

          if ($loyalityNumber.val() !== '') {
            if ($loyalityProvider.val() === '') {
              errors = true;
              KT.notify('saveServiceFailedIncorrectLoyality');
              $loyalityNumber
                .addClass('error')
                .one('focus', function() {
                  $loyalityNumber.removeClass('error');
                });
            } else {
              touristData[touristId].loyalityProviderId = $loyalityProvider.val();
              touristData[touristId].loyalityCardNumber = $loyalityNumber.val();
            }
          }
        }
      });

    return (!errors) ? touristData : false;
  };

  /**
  * Подготовка окон для документов с условиями работы с услугой
  * @param {Object} termsDocuments - список документов, определенных для услуги
  */
  modView.prototype.prepareServiceTermsDocuments = function(termsDocuments) {
    for (var doc in termsDocuments) {
        this.mds.servicesTermsDocuments[doc] = $.featherlight(
          //$(Mustache.render(this.mds.tpl.staticDoc,{docName:doc})),
          Mustache.render(KT.tpl.lightbox, {
              classes: 'js-service-form--staticdoc', 
              attributes: 'data-doc="doc"'
            }
          ),
          {
            persist:true,
            closeIcon:'',
            openSpeed:0,
            closeSpeed:0
        });
        this.mds.servicesTermsDocuments[doc].close();
        this.mds.servicesTermsDocuments[doc].openSpeed = 250;
        this.mds.servicesTermsDocuments[doc].closeSpeed = 250;
    }
  };

  /**
  * Загрузка и отображение документов с условиями работы с услугой
  * @param {Object} termsDocuments - список документов
  */
  modView.prototype.getServiceTermsDocuments = function(termsDocuments) {
    var _instance = this;

    var renderer = function(document,data) {
      _instance.mds.servicesTermsDocuments[document].$content.html(data);
      return _instance.mds.servicesTermsDocuments[document].$content;
    };

    var loader = function(document,callback) {
      $.ajax({
        type: 'POST',
        data: JSON.stringify({'document':document}),
        contentType: 'application/json; charset=utf-8',
        url: _instance.config.staticDocumentUrl,
        success: function (data) {
          var loadedData;
          try {
            loadedData = JSON.parse(data);
            if (loadedData.status === 0) {
              callback(document,loadedData.document);
            } else {
              callback('Document not found');
            }
          } catch (e) {
            console.error("Не получилось загрузить статические документы =(");
            callback('Document not found');
          }
        }
      });
    };

    for (var doc in termsDocuments) {
      termsDocuments[doc].load(renderer,loader);
    }
  };

  /**
  * Только для авиа - обновление правил тарифа
  * @param {Integer} serviceId - ID услуги
  * @param {Object} [data] - данные правил тарифа, если они есть
  */
  modView.prototype.updateFareRules = function(serviceId, data) {
    var serviceView = this.serviceViewModels[serviceId];
    var fareRuleDocument = serviceView.ServiceStorage.fareRuleDocumentName;

    if (data === undefined) {
      // пустые правила тарифа
      serviceView.prepareEmptyFareRulesView();
    } else {
      serviceView.prepareFareRulesView();
    }
    this.mds.servicesTermsDocuments[fareRuleDocument].$content.html(serviceView.fareRulesView);
  };

  /**
  * Обновление формы дополнительных услуг 
  */
  modView.prototype.updateAdditionalServices = function(serviceId) {
    var ServiceViewModel = this.serviceViewModels[serviceId];
    var $serviceForm = this.serviceForms[serviceId];

    ServiceViewModel.prepareAdditionalServicesView();
    $serviceForm.find('.js-service-form--add-services')
      .html(ServiceViewModel.additionalServicesView);
    ServiceViewModel.initAddServicesControls($serviceForm);
  };

  /**
  * Сбор значений дополнительных полей услуги
  * @param {Integer} serviceId - ID услуги
  * @return {Array|null} - массив доп. полей или null в случае ошибки
  */
  modView.prototype.getCustomFieldsValues = function(serviceId) {
    var ServiceViewModel = this.serviceViewModels[serviceId];
    return ServiceViewModel.getCustomFieldsValues();
  };

  /**
  * Управление выводом окон редактироваия услуг в ручном режиме
  */
  modView.prototype.setManualEditForms = function() {
    var _instance = this;

    var ManualEditForms = {
      elem: {
        $root: $('body')
      },
      forms: {},
      prepare: function(ServiceViewModel) {
        if (KT.profile.userType !== 'op') { return; }

        var ServiceStorage = ServiceViewModel.ServiceStorage;
        var serviceId = ServiceStorage.serviceId;

        if (ServiceStorage.status === 9) {
          // подготовка или обнуление окна формы
          if (this.forms[serviceId] === undefined) {
            this.forms[serviceId] = $.featherlight(Mustache.render(KT.tpl.lightbox, {}), {
              persist:true,
              variant:'featherlight--fix-scroll',
              closeIcon:'',
              openSpeed:0,
              closeSpeed:0
            });
            this.forms[serviceId].close();
            this.forms[serviceId].openSpeed = 250;
            this.forms[serviceId].closeSpeed = 250;
          } else {
            this.forms[serviceId].$content.html(Mustache.render(KT.tpl.spinner, {}));
            this.forms[serviceId].$content.off();
          }
          this.forms[serviceId].manualFormRendered = false;
        } else {
          // очистка формы 
          if (this.forms[serviceId] !== undefined) {
            /** @todo find out where persisted featherlight is stored & kill it */
            delete this.forms[serviceId];
          }
        }
      },
      open: function(serviceId) {
        if (KT.profile.userType !== 'op') { return; }

        var ServiceViewModel = _instance.serviceViewModels[serviceId];
        var manualForm = this.forms[serviceId];

        if (manualForm === undefined) {
          console.warn('для услуги ' + serviceId + ' не создана форма работы в ручном режиме!');
          KT.notify('noManualEditForm');
          return;
        }

        manualForm.open();

        if (!manualForm.manualFormRendered) {
          manualForm.$content.html(ServiceViewModel.renderManualForm(_instance.mds));
          ServiceViewModel.initManualFormControls(manualForm.$content, _instance.mds);
          manualForm.manualFormRendered = true;
          // нормализация высоты табов
          var $tabs = manualForm.$content.find('.js-ore-service-manualedit--tab');
          var maxHeight = 0;
          $tabs.each(function() {
            if ($(this).height() > maxHeight) {
              maxHeight = $(this).height();
            }
          });
          $tabs.height(maxHeight);
        }
      },
      getSaveServiceDataParams: function(serviceId) {
        var ServiceStorage = _instance.serviceViewModels[serviceId].ServiceStorage;

        var $manualForm = this.forms[serviceId].$content.find('.js-ore-service-manualedit');

        var startDate = $manualForm.find('.js-ore-service-manualedit--start-date').val();
        var endDate = $manualForm.find('.js-ore-service-manualedit--end-date').val();

        var agencyProfit = $manualForm.find('.js-ore-service-manualedit--agent-commission').val();
        agencyProfit = (agencyProfit !== '') ? parseFloat(agencyProfit.replace(/\s+/g,'').replace(',','.')) : null;

        var clientPrice = $manualForm.find('.js-ore-service-manualedit--client-gross-price').val();
        clientPrice = (clientPrice !== '') ? parseFloat(clientPrice.replace(/\s+/g,'').replace(',','.')) : null;

        var currency = $manualForm.find('.js-ore-service-manualedit--currency').val();

        var clientCancelPenalties = [];
        $manualForm.find('.js-ore-service-manualedit--client-cancel-penalty').each(function() {
          var penaltyIndex = +$(this).data('id');
          var penaltyCurrency = $(this).data('currency');
          var penaltyAmount = $(this).find('.js-ore-service-manualedit--client-cancel-penalty-amount').val();
          var cancelPenalty = ServiceStorage.clientCancelPenalties[penaltyIndex];

          clientCancelPenalties.push({
            'dateFrom': cancelPenalty.dateFrom.format('YYYY-MM-DD HH:mm'),
            'dateTo': cancelPenalty.dateTo.format('YYYY-MM-DD HH:mm'),
            'description': cancelPenalty.description,
            'penalty': {
              'amount': (penaltyAmount !== '') ? parseFloat(penaltyAmount.replace(/\s+/g,'').replace(',','.')) : 0,
              'currency': penaltyCurrency
            }
          });
        });

        return {
          'serviceId': serviceId,
          'orderServiceData': {
            'dateStart': (startDate !== '') ? moment(startDate,'DD.MM.YYYY').format('YYYY-MM-DD') : null,
            'dateFinish': (endDate !== '') ? moment(endDate,'DD.MM.YYYY').format('YYYY-MM-DD') : null,
            'agencyProfit': agencyProfit,
            'cancelPenalties': {
              'client': clientCancelPenalties
            },
            'salesTerms': {
              'client':{
                'amountBrutto': clientPrice,
                'currency': currency
              }
            }
          }
        };
      },
      getSaveServiceStatusParams: function(serviceId) {
        var service = _instance.mds.OrderStorage.services[serviceId];
        var $manualForm = this.forms[serviceId].$content.find('.js-ore-service-manualedit');

        var newStatus = $manualForm.find('.js-ore-service-manualedit--change-status').val();
        var setOffline = $manualForm.find('.js-ore-service-manualedit--set-offline').prop('checked');
        var comment = $manualForm.find('.js-ore-service-manualedit--status-comment').val();

        var params = {
          'serviceId': serviceId,
          'serviceStatus': (newStatus !== '' && +newStatus !== service.status) ?
            +newStatus : null,
          'online': setOffline ? false : null,
          'comment': comment
        };

        return params;
      },
      getChangeBookDataParams: function(serviceId) {
        var $manualForm = this.forms[serviceId].$content.find('.js-ore-service-manualedit');
        if (_instance.serviceViewModels[serviceId].getChangeBookDataParams !== undefined) {
          return _instance.serviceViewModels[serviceId].getChangeBookDataParams($manualForm);
        } else { return false; }
      },
      getChangeTicketDataParams: function(serviceId) {
        var $manualForm = this.forms[serviceId].$content.find('.js-ore-service-manualedit');
        if (_instance.serviceViewModels[serviceId].getChangeTicketDataParams !== undefined) {
          return _instance.serviceViewModels[serviceId].getChangeTicketDataParams($manualForm);
        } else { return false; }
      },
      getAddTicketParams: function(serviceId) {
        var $manualForm = this.forms[serviceId].$content.find('.js-ore-service-manualedit');
        if (_instance.serviceViewModels[serviceId].getAddTicketParams !== undefined) {
          return _instance.serviceViewModels[serviceId].getAddTicketParams($manualForm);
        } else { return false; }
      }
    };

    return ManualEditForms;
  };

  /**
  * Управление выводом полной информации по отелям
  * @return {Object} - модуль управления выводом полной информации об отеле
  */
  modView.prototype.setHotelInfoPages = function() {
    var _instance = this;

    var HotelInfoPages = {
      loaded: {},
      dummyPhoto: '/resources/hotels/dummy.jpg',
      photoLinkTest: /^(http|[^\/]{2,}\.[^\/.]{2,}\/).*/,
      categoryMap: {
        'ONE':1,
        'TWO':2,
        'THREE':3,
        'FOUR':4,
        'FIVE':5
      },
      serviceGroupMap: {
        'Продленная регистрация': 'prolonged-regtime',
        'В номере': 'room-service',
        'Общие': 'general',
        'Стойка регистрации': 'reception',
        'Дети': 'children',
        'НА свежем воздухе': 'fresh-air',
        'Бизнес': 'business',
        'Развлечения': 'entertainment',
        'Питание и напитки': 'food-n-drink',
        'Спорт/Здоровье': 'sports',
        'Трансфер': 'transfer',
        'Парковка': 'parking',
        'Сейф': 'safe',
        'Персонал говорит': 'staff-lang',
        'Интернет': 'internet',
        'Домашние животные': 'pet'
      },
      init: function(hotelId) {
        if (!this.loaded.hasOwnProperty(hotelId)) {
          this.loaded[hotelId] = $.featherlight(Mustache.render(KT.tpl.lightbox, {}), {
            persist:true,
            closeIcon:'',
            openSpeed:250,
            closeSpeed:250
          });
          return true;
        } else {
          return false;
        }
      },
      render: function(hotel) {
        var self = this;

        /* вывести краткое описание наверх списка */
        if (Array.isArray(hotel.descriptions)) {
          var descriptionTypes = {};

          // убрать дубли
          hotel.descriptions.forEach(function(description) {
            descriptionTypes[description.descriptionType] = description;
          });

          hotel.descriptions = [];

          for (var type in descriptionTypes) {
            descriptionTypes[type].description = htmlDecode(descriptionTypes[type].description);
            // краткое описание в начало списка
            if (type !== 'short') {
              hotel.descriptions.push(descriptionTypes[type]);
            } else {
              hotel.descriptions.unshift(descriptionTypes[type]);
            }
          }
        }
        
        var serviceMap = {};
        hotel.services.forEach(function(service) {
          var icon = self.serviceGroupMap[service.serviceGroupCode];
          if (icon !== undefined) {
            if (serviceMap[icon] === undefined) {
              serviceMap[icon] = {
                'group': service.serviceGroupCode,
                'icon': icon,
                'list': []
              };
            }

            serviceMap[icon].list.push({'name':service.name});
          }
        });

        var services = [];
        for (var group in serviceMap) {
          if (serviceMap.hasOwnProperty(group)) {
            services.push(serviceMap[group]);
          }
        }
        services.sort(function(a,b) {
          if (a.list.length > b.list.length) { return 1; }
          else if (a.list.length < b.list.length) { return -1; }
          else { return 0; }
        });

        var hotelInfo = {
          'name': hotel.name,
          'icon': hotel,
          'hotelChain': (hotel.hotelChain === '') ? null : hotel.hotelChain,
          'mainImage': (
              hotel.mainImageUrl !== undefined &&
              hotel.mainImageUrl !== null &&
              this.photoLinkTest.test(String(hotel.mainImageUrl))
            ) ?
              hotel.mainImageUrl :
              this.dummyPhoto,
          'category': (hotel.category === null || this.categoryMap[hotel.category] === undefined) ? false :
            (function(category){
              var stars = [];
              for (var i = 0; i < category; i++) {
                stars.push(true);
              }
              return stars;
            }(this.categoryMap[hotel.category])),
          'city': hotel.cityName,
          'country': hotel.countryName, 
          'checkIn': (hotel.checkInTime !== null) ? 
            moment(hotel.checkInTime,'HH:mm:ss').format('HH:mm') : null, 
          'checkOut': (hotel.checkOutTime !== null) ? 
            moment(hotel.checkOutTime,'HH:mm:ss').format('HH:mm') : null, 
          'address': hotel.address,
          'distance': hotel.distance,
          'phone': hotel.phone,
          'fax': hotel.fax,
          'email': hotel.email,
          'siteurl': (KT.profile.userType === 'op') ? hotel.url : null,
          'photos': hotel.images,
          'services': services,
          'descriptions': hotel.descriptions
        };

        var $hotelInfo = this.loaded[hotel.hotelId].$content;

        $hotelInfo
          .html(Mustache.render(_instance.mds.tpl.hotelFullInfo, hotelInfo))
          .find('.js-hotel-info__gallery')
            .on('click','.js-hotel-info__gallery-item', function() {
              $(this).parent().children('.active').removeClass('active');
              $(this).addClass('active');
              var url = $(this).data('url');
              $hotelInfo.find('.js-hotel-info__main-photo').css({'background-image': 'url('+url+')'});
            })
            .addClass('baron baron__root baron__clipper _simple')
            .wrapInner('<div class="js-hotel-info__gallery-wrap"></div>')
            .wrapInner('<div class="js-hotel-info__gallery-scroller baron__scroller"></div>')
            .append('<div class="js-hotel-info__gallery-track baron__track">'+
                '<div class="baron__control baron__up">▲</div>'+
                '<div class="baron__free">'+
                '<div class="js-hotel-info__gallery-bar baron__bar"></div>'+
                '</div>'+
                '<div class="baron__control baron__down">▼</div>'+
                '</div>')
            .baron({
              root: $('.js-hotel-info__gallery'),
              scroller: '.js-hotel-info__gallery-scroller',
              bar: '.js-hotel-info__gallery-bar',
              track: '.js-hotel-info__gallery-track',
              scrollingCls: '_scrolling',
              draggingCls: '_dragging'
            });
      },
      open: function(hotelId) {
        this.loaded[hotelId].open();
      }
    };

    return HotelInfoPages;
  };

  /**
  * Рендер сообщения диалога согласия с изменением цены
  * @param {Object} newSalesTerms - новые ценовые данные предложения
  * @param {ServiceStorage} Service - изменившаяся услуга
  * @param {Function} [submitAction] - действие при подтверждении
  * @param {Function} [cancelAction] - действие при отмене
  * @return {String} - сообщение диалога
  */
  modView.prototype.showPricesChangedModal = function(newSalesTerms, Service, submitAction, cancelAction) {
    var prices = {};
    prices.client = {
      'oldPrice': Service.prices.inClient.client.gross.toMoney(2,',',' '),
      'newPrice': Number(newSalesTerms.clientCurrency.client.amountBrutto).toMoney(2,',',' '),
      'currency': KT.getCatalogInfo('lcurrency', newSalesTerms.clientCurrency.client.currency, 'icon')
    };
    if (KT.profile.userType === 'op') {
      prices.supplier = {
        'oldPrice': Service.prices.inSupplier.supplier.gross.toMoney(2,',',' '),
        'newPrice': Number(newSalesTerms.supplierCurrency.supplier.amountBrutto).toMoney(2,',',' '),
        'currency': KT.getCatalogInfo('lcurrency', newSalesTerms.supplierCurrency.supplier.currency, 'icon')
      };
    }

    var buttons = [];
    if (typeof submitAction !== 'function') {
      buttons.push({
          type:'warning',
          title:'ok'
        });
    } else {
      buttons.push({
        type:'warning',
        title:'принять',
        callback: submitAction
      });

      if (typeof cancelAction === 'function') {
        buttons.push({
            type:'warning',
            title:'отклонить',
            callback: cancelAction
          });
      }
    }

    KT.Modal.notify({
      type:'warning',
      title:'Изменение данных брони',
      msg: Mustache.render(this.mds.tpl.pricesChangedModal, prices),
      buttons: buttons
    });
  };

  /**
  * Рендер сообщения о невозвратности услуги
  */
  modView.prototype.showNonrefundableModal = function(submitAction, cancelAction) {
    KT.Modal.notify({
      type:'warning',
      title:'Невозвратная услуга',
      msg: '<p style="text-align:center">Услуга, которую Вы собираетесь забронировать, является невозвратной</p>',
      buttons:[{
          type:'warning',
          title:'Продолжить',
          callback: submitAction
        },
        {
          type:'warning',
          title:'Отмена',
          callback: cancelAction
      }]
    });
  };

  /**
  * Подготовка объекта управления окном изменения брони
  * @return {Object} - объект управления
  */
  modView.prototype.setBookChangeModal = function() {
    var _instance = this;

    var BookChangeModal = {
      $modal: null,
      serviceId: null,
      touristAges: {},
      /** 
      * рендер окна
      * @param {Integer} serviceId - ID рдактируемой услуги
      * @param {Function} submitAction - действие при подтверждении
      * @param {Function} cancelAction - действие при отмене
      * @param {Function} toManagerAction - действие при отправке услуги на редактирование менеджеру
      */
      showModal: function(serviceId, submitAction, cancelAction, toManagerAction) {
        var self = this;

        var ServiceViewModel = _instance.serviceViewModels[serviceId];
        var Service = ServiceViewModel.ServiceStorage;

        self.serviceId = serviceId;
        self.touristAges = $.extend(true, {}, Service.touristAges);

        ServiceViewModel.mapTouristsInfo(_instance.mds.OrderStorage.getTourists(), true);

        var modalParams = {
          'serviceId': serviceId,
          'serviceIcon': KT.getCatalogInfo('servicetypes', Service.typeCode, 'icon'),
          'serviceName': Service.name,
          'dateIn': Service.startDate.format('DD.MM.YYYY'),
          'dateOut': Service.endDate.format('DD.MM.YYYY'),
          'tourists': ServiceViewModel.touristsMap
        };

        var buttons = [
          {title:'да', callback: submitAction },
          {title:'нет', callback: function($modal) { self.clearData(); cancelAction($modal); }}
        ];

        if (KT.profile.userType !== 'op') {
          buttons.push({
            title:'Передать менеджеру', 
            callback: function() { 
              self.showSendToManagerModal(modalParams, toManagerAction, cancelAction); 
            }
          });
        }

        self.$modal = KT.Modal.notify({
          type: 'info',
          title: 'Изменение данных брони',
          msg: Mustache.render(_instance.mds.tpl.bookChangeModal, modalParams),
          buttons: buttons
        }).$content;

        self.$modal.find('.js-modal-book-change--date-in').clndrize({
          'template': _instance.mds.tpl.clndr,
          'eventName': 'Дата заезда',
          'showDate': moment(),
          'clndr': {
            'constraints': {
              'startDate': moment().format('YYYY-MM-DD'),
              'endDate': moment().add(1,'years').format('YYYY-MM-DD')
            }
          }
        });

        self.$modal.find('.js-modal-book-change--date-out').clndrize({
          'template': _instance.mds.tpl.clndr,
          'eventName': 'Дата выезда',
          'showDate': moment(),
          'clndr': {
            'constraints': {
              'startDate': moment().format('YYYY-MM-DD'),
              'endDate': moment().add(1,'years').format('YYYY-MM-DD')
            }
          }
        });

        self.$modal.on('change', '.js-modal-book-change--tourist-bound' , function() {
          var touristId = +$(this).attr('data-touristid');
          var tourist = _instance.mds.OrderStorage.tourists[touristId];

          var agegroup = Service.getAgeGroup(Service.getAgeByServiceEnding(tourist.birthdate));

          if ($(this).prop('checked')) {
            if ((self.touristAges[agegroup] + 1) <= Service.declaredTouristAges[agegroup]) {
              self.touristAges[agegroup] += 1;
            } else {
              $(this).prop('checked',false).closest('.simpletoggler').removeClass('active');
              KT.notify('touristLinkageNotAllowedByAge');
            }
          } else {
            self.touristAges[agegroup] -= 1;
          }
        });
      },
      /**
      * рендер модального окна отправки запроса менеджеру на изменение брони
      */
      showSendToManagerModal: function(modalParams, toManagerAction, cancelAction) {
        var self = this;

        self.$modal = KT.Modal.notify({
          type: 'info',
          title: 'Передача услуги менеджеру для исправления других параметров',
          msg: Mustache.render(_instance.mds.tpl.sendToManagerModal, modalParams),
          buttons: [
            {title:'передать менеджеру', callback: toManagerAction},
            {title:'отмена', callback: function($modal) { self.clearData(); cancelAction($modal); }}
          ]
        }).$content;
      },
      /**
      * Сбор параметров с формы
      * @return {Object|null} - параметры формы или null случае ошибки
      */
      getParams: function() {
        var self = this;
        
        var newTouristLinkage = {};
        var linkedAmount = 0;

        self.$modal.find('.js-modal-book-change--tourist-bound')
          .each(function() {
            var touristId = +$(this).attr('data-touristid');
            newTouristLinkage[touristId] = {
              'state': $(this).prop('checked'),
              'loyalityProviderId': null,
              'loyalityCardNumber': null
            };
            if (newTouristLinkage[touristId].state) { linkedAmount++; }
          });

        var linkageInfo = _instance.mds.OrderStorage.createLinkageStructure(self.serviceId, newTouristLinkage);

        var service = _instance.mds.OrderStorage.services[self.serviceId];
        if (linkedAmount !== service.declaredTouristAmount) {
          KT.notify('notAllTouristsLinked');
          return null;
        }

        var $dateIn = self.$modal.find('.js-modal-book-change--date-in');
        var $dateOut = self.$modal.find('.js-modal-book-change--date-out');
        var dateIn = $dateIn.val();
        var dateOut = $dateOut.val();
        dateIn = moment(
            (dateIn === '' || dateIn === null) ? $dateIn.data('default') : dateIn,
            'DD.MM.YYYY'
          ).format('YYYY-MM-DD');
        dateOut = moment(
            (dateOut === '' || dateOut === null) ? $dateOut.data('default') : dateOut,
            'DD.MM.YYYY'
          ).format('YYYY-MM-DD');

        return {
          'serviceId': self.serviceId,
          'serviceData': {
            'dateStart': dateIn,
            'dateFinish': dateOut,
            'touristData': linkageInfo
          }
        };
      },
      /**
      * Сбор параметров с формы отправки запроса менеджеру 
      * @return {Object|null} - параметры формы или null случае ошибки
      */
      getToManagerParams: function() {
        var self = this;
        var $comment = self.$modal.find('.js-modal-send-to-manager--comment');

        if ($comment.val() === '') {
          $comment.addClass('error')
            .attr('placeholder', 'Оставьте комментарий')
            .one('focus', function() { $(this).removeClass('error'); });
          return null;
        } else {
          return {
            'serviceId': self.serviceId,
            'comment': $comment.val()
          };
        }
      },
      /**
      * Очистка сохраненных данных по услуге
      */
      clearData: function() {
        this.$modal = null;
        this.serviceId = null;
        this.touristAges = {};
      }
    };

    return BookChangeModal;
  };

  /**
  * Подготовка объекта управления окном отмены брони
  * @return {Object} - объект управления
  */
  modView.prototype.setBookCancelModal = function() {
    var _instance = this;

    var BookCancelModal = {
      $modal: null,
      serviceId: null,
      /** 
      * рендер окна
      * @param {Integer} serviceId - ID рдактируемой услуги
      * @param {Function} submitAction - действие при подтверждении
      * @param {Function} cancelAction - действие при отмене
      */
      showModal: function(serviceId, submitAction, cancelAction) {
        var self = this;

      self.serviceId = serviceId;

        var ServiceViewModel = _instance.serviceViewModels[serviceId];
        var Service = ServiceViewModel.ServiceStorage;

        var cancelPenaltySum = Service.countClientCancelPenalty();

        var modalParams = {
          'serviceName': Service.name,
          'penalty': (cancelPenaltySum !== null && cancelPenaltySum.inLocal !== 0) ? {
              'localAmount': Number(cancelPenaltySum.inLocal).toMoney(0,',',' '),
              'localCurrency': KT.getCatalogInfo('lcurrency', KT.profile.localCurrency, 'icon'),
              'viewAmout': Number(cancelPenaltySum.inView).toMoney(0,',',' '),
              'viewCurrency': KT.getCatalogInfo('lcurrency', KT.profile.viewCurrency, 'icon'),
            } : null,
          'isInvoiceOptional': false
        };

        self.$modal = KT.Modal.notify({
          type: 'info',
          title: 'Отмена бронирования',
          msg: Mustache.render(_instance.mds.tpl.bookCancelModal, modalParams),
          buttons: [{
              type: 'common',
              title: 'да',
              callback: submitAction
            },
            {
              type: 'common',
              title: 'нет',
              callback: function() {
                self.clearData();
                cancelAction();
              }
          }]
        }).$content;
      },
      /**
      * Сбор параметров с формы
      * @return {Object|null} - параметры формы или null случае ошибки
      */
      getParams: function() {
        var self = this;

        var $isSetInvoiceSelected = self.$modal.find('.js-modal-book-cancel--set-invoice');
        var commandParams = _instance.mds.OrderStorage.getServiceCommandParams('BookCancel', self.serviceId);
        if ($isSetInvoiceSelected.length !== 0) {
          commandParams['createPenaltyInvoice'] = $isSetInvoiceSelected.prop('checked');
        } else {
          commandParams['createPenaltyInvoice'] = true;
        }

        return commandParams;
      },
      /**
      * Очистка сохраненных данных по услуге
      */
      clearData: function() {
        this.$modal = null;
        this.serviceId = null;
        this.touristAges = {};
      }
    };

    return BookCancelModal;
  };

  return modView;

}));

(function(global,factory) {

    KT.crates.OrderEdit.services.controller = factory(KT.crates.OrderEdit);

}(this,function(crate) {
  /**
  * Редактирование заявки: услуги
  * @constructor
  * @param {Object} module - хранилище родительского модуля
  * @param {Integer} orderId - ID заявки
  */
  var oesController = function(module,orderId) {
    this.mds = module;
    this.orderId = orderId;

    this.mds.services.view = new crate.services.view(this.mds);

    /** @deprecated */
    //this.mds.orderInfo = {};
  };

  /** Инициализация событий модуля */
  oesController.prototype.init = function() {
    var _instance = this;
    var modView = this.mds.services.view;

    /*==========Обработчики событий модели============================*/

    /** @todo временное решение, надо бы переделать с отдельным рендером
    * самой услуги и блока привязки туристов
    * @param {OrderStorage} OrderStorage - хранилище данных заявки
    */
    var callServicesRendering = function(OrderStorage) {
      modView.renderServices(OrderStorage)
        .then(function() {
          var showService = window.sessionStorage.getItem('showService');
          if (showService !== null) {
            window.sessionStorage.removeItem('showService');
            modView.navigateToService(+showService);
          }
          
          // исключительно для авиации: проверка на наличие правил тарифа
          OrderStorage.getServices().forEach(function(Service) {
            if (Service.typeCode === 2 && !Service.isPartial) {
              var fareRules = Service.offerInfo.fareRules;
              if (!Array.isArray(fareRules) || fareRules.length === 0) {

                var fareRulesLoader = function(serviceId, retry) {
                  console.log('load rules');
                  retry--;
                  if (retry === 0) { 
                    modView.updateFareRules(serviceId);
                    return;
                  }

                  KT.apiClient.getOrderOffers(OrderStorage.orderId, [serviceId])
                    .then(function(response) {
                      if (response.status !== 0) {
                        setTimeout(function() { fareRulesLoader(Service.serviceId, retry); }, 5000);
                      } else {
                        var fareRules = response.body[0].offerInfo.fareRules;
                        if (!Array.isArray(fareRules) || fareRules.length === 0) {
                          setTimeout(function() { fareRulesLoader(Service.serviceId, retry); }, 5000);
                        } else {
                          OrderStorage.services[serviceId].offerInfo.fareRules = fareRules;
                          modView.updateFareRules(serviceId, fareRules);
                        }
                      }
                    });
                };

                setTimeout(function() { fareRulesLoader(Service.serviceId, 3); }, 5000);
              }
            }
          });
        });
    };

    /** Обработка смены валюты просмотра */
    KT.on('KT.changedViewCurrency', function() {
      modView.$serviceList.html(Mustache.render(KT.tpl.spinner, {}));
      var OrderStorage = _instance.mds.OrderStorage;
      var serviceIds = _instance.mds.OrderStorage.getServiceIds();

      if (serviceIds.length > 0) {
        KT.apiClient.getOrderOffers(_instance.orderId, serviceIds)
          .then(function(offersData) {
            if (offersData.status === 0) {
              OrderStorage.setServices(offersData.body);
              if (OrderStorage.loadStates.transitionsdata === 'loaded') {
                callServicesRendering(_instance.mds.OrderStorage);
              }
            } else {
              return $.Deferred().reject();
            }
          });
      }
    });

    /** Рендер пустого списка если в заявке нет услуг */
    KT.on('OrderStorage.initialized', function(e, OrderStorage) {
      if (OrderStorage.getServices().length === 0) {
        modView.renderEmptyServiceList();
      }
    });

    /** Обработка инициализации доступных действий с услугами */
    KT.on('OrderStorage.setAllowedTransitions', function(e, OrderStorage) {
      if (OrderStorage.loadStates.touristdata === 'loaded') {
        callServicesRendering(_instance.mds.OrderStorage);
      }
    });

    /** Обработка обновления данных туристов в хранилище */
    KT.on('OrderStorage.setTourists', function(e, OrderStorage) {
      if (OrderStorage.loadStates.transitionsdata === 'loaded') {
        callServicesRendering(OrderStorage);
      }
    });

    /** Обработка привязки туриста к услуге */
    KT.on('OrderStorage.savedServiceLinkage', function(e, data) {
      modView.renderServiceTourists(data.serviceId);
    });

    /** Обработка добавления/создания туриста (перерисовка блока услуг) */
    KT.on('OrderStorage.savedTourist', function(e, data) {
      _instance.mds.OrderStorage.getServiceIds().forEach(function(serviceId) {
          _instance.mds.OrderStorage.updateServiceTourists(serviceId);
          modView.renderServiceTourists(serviceId);
      });

      // проверка на добавление туриста из услуги для привязки
      var linkingServiceId = window.sessionStorage.getItem('serviceToAddTourist');

      if (linkingServiceId !== null) {
        window.sessionStorage.removeItem('serviceToAddTourist');

        var touristId = +data.touristId;
        linkingServiceId = +linkingServiceId;

        KT.dispatch('OrderEdit.setActiveTab', {activeTab:'services', callback: function() {
          modView.navigateToService(linkingServiceId);
        }});
        KT.notify('linkingTourists');

        var touristLinkage = {};
        touristLinkage[touristId] = {
          'state': true,
          'loyalityCardNumber': null,
          'loyalityProviderId': null
        };
        var linkageInfo = _instance.mds.OrderStorage.createLinkageStructure(linkingServiceId, touristLinkage);

        KT.apiClient.setTouristsLinkage(_instance.orderId, linkingServiceId, linkageInfo)
          .done(function(response) {
            if (+response.status === 0) {
              var link = response.body.result[0];

              if (link === undefined) {
                KT.notify('linkingTouristFailed', ['Турист', tourist.lastname, tourist.firstname].join(' '));
                _instance.mds.OrderStorage.updateServiceTourists(response.serviceid);
                modView.renderServiceTourists(response.serviceId);

              } else if (!Boolean(link.success)) {
                var tourist = _instance.mds.OrderStorage.tourists[link.touristId];
                KT.notify('linkingTouristFailed', [
                    'Турист', tourist.lastname, tourist.firstname,
                    ':', link.error
                  ].join(' '));
                _instance.mds.OrderStorage.updateServiceTourists(response.serviceid);
                modView.renderServiceTourists(response.serviceId);

              } else {
                _instance.mds.OrderStorage.saveServiceLinkage(response.serviceId, response.linkageInfo);
                KT.notify('touristsLinked');
                
              }
            } else {
              KT.notify('linkingTouristFailed', response.errors);
            }
          });
      }
    });

    /** Обработка удаления туриста (перерисовка блока услуг) */
    KT.on('OrderStorage.touristRemoved', function() {
      modView.renderServices(_instance.mds.OrderStorage);
    });

    /*==========Обработчики событий представления============================*/

    /** Скрыть/показать подробную информацию по услуге */
    modView.$serviceList.on('click','.js-service-form-header', function() {
      var $sf = $(this).closest('.js-service-form');
      var $dataform = $sf.find('.js-service-form-content');

      if ($sf.hasClass('active')) {
        $sf.removeClass('active');
        $dataform.css({'display':'block'}).slideUp(500);
      } else {
        $sf.addClass('active');
        $dataform.css({'display':'none'}).slideDown(500);
      }
    });

    /** Обработка изменения значения привязки туриста */
    modView.$serviceList.on('change','.js-service-form-tourist--service-bound', function() {
      var touristId = +$(this).attr('data-touristid');
      var tourist = _instance.mds.OrderStorage.tourists[touristId];
      var $serviceForm = $(this).closest('.js-service-form');
      var serviceId = +$serviceForm.attr('data-sid');
      var service = _instance.mds.OrderStorage.services[serviceId];

      var agegroup = service.getAgeGroup(service.getAgeByServiceEnding(tourist.birthdate));

      if ($(this).prop('checked')) {
        if ((service.touristAges[agegroup] + 1) <= service.declaredTouristAges[agegroup]) {
          service.touristAges[agegroup] += 1;
          $serviceForm.data('unsaved',true);
        } else {
          $(this).prop('checked',false).closest('.simpletoggler').removeClass('active');
          KT.notify('touristLinkageNotAllowedByAge');
        }
      } else {
        service.touristAges[agegroup] -= 1;
        $serviceForm.data('unsaved',true);
      }
    });

    /** Обработка изменения данных программы лояльности */
    modView.$serviceList.on('change', [
        '.js-service-avia-loyalty-program--provider',
        '.js-service-avia-loyalty-program--number'
      ].join(','), 
      function() {
        $(this).closest('.js-service-form').data('unsaved', true);
      }
    );

    /** Обработка нажатия на кнопку "Забронировать" услуги */
    modView.$serviceList.on('click','.js-service-form-actions--book', function() {
      var $serviceForm = $(this).closest('.js-service-form');
      var serviceId = +$serviceForm.data('sid');
      var Service = _instance.mds.OrderStorage.services[serviceId];

      var startBooking = function() {
        if ($serviceForm.data('unsaved') === false) {
          _instance.saveCustomFields(serviceId)
            .then(function() {
              return _instance.bookService(serviceId);
            });
        } else {
          _instance.saveServiceLinkage(serviceId)
            .then(function() {
              return _instance.saveCustomFields(serviceId);
            })
            .then(function() {
              return _instance.bookService(serviceId);
            });
        }
      };

      var submitAction = function() {
        KT.Modal.closeModal();
        startBooking();
      };

      var cancelAction = function () {
        KT.Modal.closeModal();
      };

      if (Service.isNonRefundable) {
        modView.showNonrefundableModal(submitAction, cancelAction);
      } else {
        startBooking();
      }
    });

    /** Обработка изменения переключателя согласия с условиями бронирования */
    modView.$serviceList.on('change','.js-service-form-tos-agreement input', function() {
      var serviceId = +$(this).closest('.js-service-form').data('sid');
      var service = _instance.mds.OrderStorage.services[serviceId];
      service.isTOSAgreementSet = $(this).prop('checked');
    });

    /** Обработка нажатия на кнопку сохранения полей услуги */
    modView.$serviceList.on('click','.js-service-form-actions--save', function() {
      var serviceId = +$(this).closest('.js-service-form').data('sid');

      modView.renderPendingServiceProcess(serviceId);

      _instance.saveCustomFields(serviceId)
        .always(function() {
          modView.renderServiceActions(serviceId);
        });
    });

    /** Обработка нажатия на кнопку "Выписать билеты" */
    modView.$serviceList.on('click','.js-service-form-actions--issue', function() {
      var serviceId = +$(this).closest('.js-service-form').attr('data-sid');

      modView.renderPendingServiceProcess(serviceId);

      KT.apiClient.issueTickets(_instance.orderId, serviceId)
        .done(function(response) {
          if (response.status === 0) {
            KT.notify('ticketsIssued');
          } else {
            if (response.errors !== undefined) {
              KT.notify('issuingTicketsFailed', response.errors);
            }
          }

          KT.dispatch('OrderEdit.reloadInfo');
        });
    });

    /** Обработка нажатия на кнопку "Изменить бронь" */
    modView.$serviceList.on('click','.js-service-form-actions--book-change', function() {
      var serviceId = +$(this).closest('.js-service-form').data('sid');

      // действие при подтверждении изменений брони
      var submitAction = function() {
        var bookChangeParams = modView.BookChangeModal.getParams();

        if (bookChangeParams === null) { return; }

        modView.renderPendingServiceProcess(serviceId);
        KT.Modal.showLoader();
        
        KT.apiClient.bookChange(_instance.orderId, bookChangeParams)
          .done(function(response) {
            if (response.status === 0) {
              KT.notify('bookingChanged');
              var Service = _instance.mds.OrderStorage.services[serviceId];

              console.warn('data changed:');
              console.log(Service.compareSalesTerms(response.body.newSalesTerms));

              if (!Service.compareSalesTerms(response.body.newSalesTerms)) {
                modView.showPricesChangedModal(response.body.newSalesTerms, Service);
              } else {
                KT.Modal.closeModal();
              }
            } else {
              KT.Modal.closeModal();

              switch(+response.errorCode) {
                case 165:
                  KT.notify('bookingChangeNotSupported');
                  break;
                case 170:
                  KT.notify('bookingChangeProhibited');
                  break;
                default:
                  KT.notify('bookingChangeFailed');
                  break;
              }
            }

            /** @todo убрать это и заменить */
            KT.dispatch('OrderEdit.reloadInfo');
          });
      };

      // действие при отмене изменений брони
      var cancelAction = function() {
        KT.Modal.closeModal();
      };

      // действие при передаче услуги менеджеру
      var toManagerAction = function() {
        var toManagerParams = modView.BookChangeModal.getToManagerParams();
        
        if (toManagerParams === null) { return; }

        modView.renderPendingServiceProcess(serviceId);
        KT.Modal.showLoader();
        
        KT.apiClient.setServiceToManual(_instance.orderId, toManagerParams)
          .then(function(response) {
            KT.Modal.closeModal();

            if (response.status === 0) {
              KT.notify('serviceSetToManual');
            } else {
              KT.notify('settingServiceToManualFailed');
            }
            
            KT.dispatch('OrderEdit.reloadInfo');
          }, function(err) {
            modView.renderServiceActions(serviceId);
            
            if (err.error !== 'denied') {
              KT.Modal.closeModal(); 
            }
          });
      };

      modView.BookChangeModal.showModal(serviceId, submitAction, cancelAction, toManagerAction);
    });

    /** Обработка нажатия на кнопку "Отменить бронь" */
    modView.$serviceList.on('click','.js-service-form-actions--book-cancel', function() {
      var serviceId = +$(this).closest('.js-service-form').data('sid');

      var submitAction = function() {
        var bookCancelParams = modView.BookCancelModal.getParams();
        if (bookCancelParams === null) { return; }

        modView.renderPendingServiceProcess(serviceId);
        KT.Modal.showLoader();
  
        KT.apiClient.bookCancel(_instance.orderId, bookCancelParams)
          .done(function(response) {
            KT.Modal.closeModal();

            if (response.status === 0) {
              KT.notify('bookingCancelled');
            } else {
              KT.notify('bookingCancelFailed');
            }
            KT.dispatch('OrderEdit.reloadInfo');
          });
      };

      var cancelAction = function() {
        KT.Modal.closeModal();
      };

      modView.BookCancelModal.showModal(serviceId, submitAction, cancelAction);
    });

    /** Обработка нажатия на кнопку "Выставить счет" */
    modView.$serviceList.on('click','.js-service-form-actions--set-invoice', function() {
      KT.dispatch('OrderEdit.openSetInvoiceForm');
    });

    /** Обработка нажатия на кнопку "Перевести в ручной режим" */
    modView.$serviceList.on('click','.js-service-form-actions--to-manual', function() {
      var serviceId = +$(this).closest('.js-service-form').data('sid');

      modView.renderPendingServiceProcess(serviceId);

      KT.apiClient.setServiceToManual(_instance.orderId, {'serviceId': serviceId})
        .done(function(response) {
          if (response.status === 0) {
            console.log('Услуга переведена в ручной режим');
          } else {
            console.error('Не удалось перевести услугу в ручной режим');
          }
          
          KT.dispatch('OrderEdit.reloadInfo');
        });
    });

    /** Обработка нажатия на кнопку "Добавить туриста" */
    modView.$serviceList.on('click','.js-service-form--add-tourist', function() {
      var $serviceForm = $(this).closest('.js-service-form');
      var serviceId = +$serviceForm.attr('data-sid');

      /** ID сервиса, из которого вызвано добавление туриста */
      window.sessionStorage.setItem('serviceToAddTourist', serviceId);

      KT.dispatch('OrderEdit.setActiveTab', {activeTab: 'tourists'});
      KT.dispatch('OrderEdit.createAddTouristForm');
    });

    /** Обработка нажатия на кнопку добавления доп. услуги */
    modView.$serviceList.on('click','.js-service-form-add-service--action-add', function() {
      var $addService = $(this).closest('.js-service-form-add-service--available-service');
      var $serviceForm = $addService.closest('.js-service-form');

      var OrderStorage = _instance.mds.OrderStorage;
      var serviceId = +$serviceForm.attr('data-sid');
      var addServiceId = +$addService.data('add-service-id');

      $addService.closest('.js-service-form--add-services')
        .html(Mustache.render(KT.tpl.spinner, {}));

      KT.apiClient.addExtraService(OrderStorage.orderId, {
        'serviceId': serviceId,
        'touristIds': OrderStorage.services[serviceId].getServiceTourists()
          .map(function(tourist) { return tourist.touristId; }),
        'addServiceOfferId': addServiceId,
        'viewCurrency': KT.profile.viewCurrency
      }).then(function(response) {
        if (response.status !== 0) {
          KT.notify('AddingAdditionalServiceFailed', response.errors);
        } else {
          OrderStorage.services[serviceId].addAdditionalService(response.body.addService);
        }

        modView.updateAdditionalServices(serviceId);
      });
    });

    /** Обработка нажатия на кнопку удаления доп. услуги */
    modView.$serviceList.on('click','.js-service-form-add-service--action-remove', function() {
      var $addService = $(this).closest('.js-service-form-add-service--issued-service');
      var $serviceForm = $addService.closest('.js-service-form');

      var OrderStorage = _instance.mds.OrderStorage;
      var serviceId = +$serviceForm.attr('data-sid');
      var addServiceId = +$addService.data('add-service-id');

      $addService.closest('.js-service-form--add-services')
        .html(Mustache.render(KT.tpl.spinner, {}));

      KT.apiClient.removeExtraService(OrderStorage.orderId, {
        'serviceId': serviceId,
        'addServiceId': addServiceId
      }).then(function(response) {
        if (response.status !== 0) {
          KT.notify('RemovingAdditionalServiceFailed', response.errors);
        } else {
          OrderStorage.services[serviceId].removeAdditionalService(addServiceId);
        }

        modView.updateAdditionalServices(serviceId);
      });
    });

    /** Отобразить полную информацию по отелю  */
    modView.$serviceList.on('click', '.js-service-hotel--hotel-link', function(e) {
      e.stopPropagation();
      var hotelId = +$(this).data('hotelid');
      
      if (modView.HotelInfoPages.init(hotelId)) {
        KT.apiClient.getHotelInfo(hotelId)
          .done(function(response) {
            if (response.status !== 0) {
              console.error(response.errors);
            } else {
              modView.HotelInfoPages.render(response.body);
            }
          });
      } else {
        modView.HotelInfoPages.open(hotelId);
      }
    });

    /** Открыть окно редактирования услуги в ручном режиме */
    modView.$serviceList.on('click', '.js-service-form-actions--manual-edit', function() {
      var $serviceForm = $(this).closest('.js-service-form');
      var serviceId = +$serviceForm.attr('data-sid');

      modView.ManualEditForms.open(serviceId);
    });

    /*==========Обработчики ручного режима============================*/
    /** Изменение параметров услуги */
    modView.$manualFormsRoot.on('click', '.js-ore-service-manualedit--save-common-data', function() {
      var orderId = _instance.mds.OrderStorage.orderId;
      var $manualForm = $(this).closest('.js-ore-service-manualedit');
      var serviceId = +$manualForm.data('serviceid');

      var actionParams = modView.ManualEditForms.getSaveServiceDataParams(serviceId);
      if (actionParams === false) {
        console.error('Некорректные параметры для изменения услуги');
        return;
      }

      modView.ManualEditForms.forms[serviceId].close();
      modView.renderPendingServiceProcess(serviceId);

      KT.Modal.showLoader();

      KT.apiClient.setServiceData(orderId, serviceId, actionParams)
        .done(function(response) {
          KT.Modal.closeModal();
          if (response.status === 0) {
            KT.notify('serviceDataChanged');
          } else {
            KT.notify('changingServiceDataFailed', response.errors);
          }
          KT.dispatch('OrderEdit.reloadInfo');
        });
    });

    /** Изменение статуса услуги */
    modView.$manualFormsRoot.on('click', '.js-ore-service-manualedit--save-service-status', function() {
      var orderId = _instance.mds.OrderStorage.orderId;
      var $manualForm = $(this).closest('.js-ore-service-manualedit');
      var serviceId = +$manualForm.data('serviceid');

      var actionParams = modView.ManualEditForms.getSaveServiceStatusParams(serviceId);
      if (actionParams === false) {
        console.error('Некорректные параметры для изменения статуса');
        return;
      }

      modView.ManualEditForms.forms[serviceId].close();
      modView.renderPendingServiceProcess(serviceId);

      KT.Modal.showLoader();

      KT.apiClient.manualSetStatus(orderId, serviceId, actionParams)
        .done(function(response) {
          KT.Modal.closeModal();
          if (response.status === 0) {
            KT.notify('reservationDataChanged');
          } else {
            KT.notify('changingReservationDataFailed');
          }
          KT.dispatch('OrderEdit.reloadInfo');
        });
    });

    /** Изменение параметров брони */
    modView.$manualFormsRoot.on('click', '.js-ore-service-manualedit--save-book-info', function() {
      var orderId = _instance.mds.OrderStorage.orderId;
      var $manualForm = $(this).closest('.js-ore-service-manualedit');
      var serviceId = +$manualForm.data('serviceid');

      var actionParams = modView.ManualEditForms.getChangeBookDataParams(serviceId);
      if (actionParams === false) {
        console.error('Некорректные параметры для изменения брони');
        return;
      }

      modView.ManualEditForms.forms[serviceId].close();
      modView.renderPendingServiceProcess(serviceId);

      KT.Modal.showLoader();

      KT.apiClient.setReservationData(orderId, serviceId, actionParams)
        .done(function(response) {
          KT.Modal.closeModal();
          if (response.status === 0) {
            KT.notify('reservationDataChanged');
          } else {
            KT.notify('changingReservationDataFailed');
          }
          KT.dispatch('OrderEdit.reloadInfo');
        });
    });

    /** Изменение авиабилета */
    modView.$manualFormsRoot.on('click', '.js-ore-service-manualedit--save-changed-avia-ticket', function() {
      var orderId = _instance.mds.OrderStorage.orderId;
      var $manualForm = $(this).closest('.js-ore-service-manualedit');
      var serviceId = +$manualForm.data('serviceid');

      var actionParams = modView.ManualEditForms.getChangeTicketDataParams(serviceId);
      if (actionParams === false) {
        console.error('Некорректные параметры для изменения билета');
        return;
      }

      modView.ManualEditForms.forms[serviceId].close();
      modView.renderPendingServiceProcess(serviceId);

      KT.Modal.showLoader();

      KT.apiClient.setTicketsData(orderId, serviceId, actionParams)
        .done(function(response) {
          KT.Modal.closeModal();
          if (response.status === 0) {
            KT.notify('ticketsDataChanged');
          } else {
            KT.notify('changingTicketsDataFailed');
          }
          KT.dispatch('OrderEdit.reloadInfo');
        });
    });

    /** Добавление авиабилета */
    modView.$manualFormsRoot.on('click', '.js-ore-service-manualedit--save-new-avia-ticket', function() {
      var orderId = _instance.mds.OrderStorage.orderId;
      var $manualForm = $(this).closest('.js-ore-service-manualedit');
      var serviceId = +$manualForm.data('serviceid');

      var actionParams = modView.ManualEditForms.getAddTicketParams(serviceId);
      if (actionParams === false) {
        console.error('Некорректные параметры для изменения билета');
        return;
      }

      modView.ManualEditForms.forms[serviceId].close();
      modView.renderPendingServiceProcess(serviceId);

      KT.Modal.showLoader();

      KT.apiClient.setTicketsData(orderId, serviceId, actionParams)
        .done(function(response) {
          KT.Modal.closeModal();
          if (response.status === 0) {
            KT.notify('ticketsDataChanged');
          } else {
            KT.notify('changingTicketsDataFailed');
          }
          KT.dispatch('OrderEdit.reloadInfo');
        });
    });
  };

  /** 
  * Передача данных призязки туристов к услуге
  * @param {Integer} serviceId - ID услуги
  */
  oesController.prototype.saveServiceLinkage = function(serviceId) {
    var _instance = this;
    var modView = _instance.mds.services.view;
    var request = $.Deferred();

    if (_instance.mds.OrderStorage.getTourists().length > 0) {
      var newTouristLinkage = modView.getServiceTouristsData(serviceId);
      if (newTouristLinkage === false) {
        return request.reject();
      }

      var linkageInfo = _instance.mds.OrderStorage.createLinkageStructure(serviceId, newTouristLinkage);
      if (linkageInfo === false) {
        return request.reject();
      } else if (linkageInfo.length === 0) {
        return request.resolve(serviceId);
      } else {
        KT.apiClient.setTouristsLinkage(_instance.orderId, serviceId, linkageInfo)
          .done(function(response) {
            if (+response.status === 0) {
              var errors = [];
              
              response.body.result.forEach(function(link) {
                if (!Boolean(link.success)) {
                  var tourist = _instance.mds.OrderStorage.tourists[link.touristId];
                  errors.push([
                    'Турист', tourist.lastname, tourist.firstname,
                    ':',link.error
                  ].join(' '));
                }
              });

              /** 
               * @todo по идее, это должно вызываться только после сохранения, 
               * но тогда не сохраняются данные мильных карт. пересмотреть.
               */
              _instance.mds.OrderStorage.saveServiceLinkage(serviceId, linkageInfo);

              if (errors.length !== 0) {
                KT.notify('linkingTouristFailed', errors.join('<br>'));
                _instance.mds.OrderStorage.updateServiceTourists(serviceId);
                modView.renderServiceTourists(serviceId);

                request.reject();
              } else {
                KT.notify('touristsLinked');
                request.resolve(serviceId);
              }
            } else {
              KT.notify('linkingTouristFailed', response.errors);
              request.reject();
            }
          });
      }
    } else {
      KT.notify('saveServiceFailedNoTourists');
      return request.reject();
    }

    return request.promise();
  };

  /** 
  * Передача значений дополнительных полей
  * @param {Integer} serviceId - ID услуги
  */
  oesController.prototype.saveCustomFields = function(serviceId) {
    var _instance = this;
    var modView = _instance.mds.services.view;
    var customFieldsValues = modView.getCustomFieldsValues(serviceId);
    var request = $.Deferred();

    if (customFieldsValues === null) {
      KT.notify('notAllCustomFieldsSet');
      request.reject();
    } else {
      if (customFieldsValues.length === 0) {
        request.resolve(serviceId);
      } else {
        KT.apiClient.setServiceAdditionalData(_instance.orderId, customFieldsValues)
          .done(function(response) {
            if (response.status === 0) {
              KT.notify('customFieldsSaved');
              request.resolve(serviceId);
            } else {
              KT.notify('savingCustomFieldsFailed', response.errors);
              request.reject();
            }
          });
      }
    }

    return request.promise();
  };

  /** 
  * Бронирование услуги
  * @param {Integer} serviceId - ID услуги
  */
  oesController.prototype.bookService = function(serviceId) {
    var _instance = this;
    var modView = _instance.mds.services.view;
    var $serviceForm = modView.serviceForms[serviceId];
    var orderTourists = _instance.mds.OrderStorage.getTourists();
    var request = $.Deferred();

    if (orderTourists.length > 0) {
      var service = _instance.mds.OrderStorage.services[serviceId];

      var isTOSAgreementSet = $serviceForm
        .find('.js-service-form-tos-agreement')
        .find('input[type="checkbox"]')
        .prop('checked');

      var hasAllTouristsLinked = service.checkAllTouristsLinked();

      if (!hasAllTouristsLinked) {
        KT.notify('notAllTouristsLinked');
        request.reject();
      } else if (!isTOSAgreementSet) {
        KT.notify('bookingTermsNotAccepted');
        request.reject();
      } else {
        modView.renderPendingServiceProcess(serviceId);

        var actionParams = _instance.mds.OrderStorage.getServiceCommandParams('BookStart', serviceId);
        
        if (actionParams !== false) {
          KT.apiClient.startBooking(_instance.orderId, actionParams)
            .then(function(response) {
              //в этом блоке выполняется проверка на изменение цен, обработка результата бронирования в следующием 
              var bookrequest = $.Deferred();

              if (
                response.status === 0 && 
                response.body.newOfferData !== undefined && 
                typeof response.body.newOfferData === 'object'
              ) {
                KT.notify('pricesChanged');
                var Service = _instance.mds.OrderStorage.services[serviceId];

                var submitAction = function() {
                  KT.Modal.closeModal();
                  KT.apiClient.startBooking(_instance.orderId, actionParams)
                    .done(function(rebookResponse) {
                      bookrequest.resolve(rebookResponse);
                    });
                };

                var cancelAction = function() {
                  KT.Modal.closeModal();
                  KT.dispatch('OrderEdit.reloadInfo');
                  bookrequest.reject();
                };

                modView.showPricesChangedModal(response.body.newOfferData, Service, submitAction, cancelAction);
              } else {
                bookrequest.resolve(response);
              }

              return bookrequest.promise();
            })
            .then(function(response) {
              if (response.status === 0) {
                if (response.body.serviceStatus === 1) {
                  KT.notify('bookingStarted');
                } else if (response.body.serviceStatus === 2) {
                  KT.notify('bookingFinished');
                } else {
                  KT.notify('bookingFailed');
                }
                request.resolve(serviceId);
              } else {
                KT.notify('bookingFailed', response.errors);
                request.reject(serviceId);
              }

              KT.dispatch('OrderEdit.reloadInfo');
            }, function() {
              // отказ от принятия измененной цены
              request.resolve(serviceId);
            });
        } else {
          request.reject();
        }
      }
    } else {
      KT.notify('bookingFailedNoTourists');
      request.reject();
    }

    return request.promise();
  };

  return oesController;

}));

(function(global,factory) {

    KT.crates.OrderEdit.payment.view = factory();

}(this, function() {
  /**
  * Редактирование заявки: оформление
  * @constructor
  * @param {Object} module - сслыка на радительский модуль
  * @param {Object} [options] - Объект конфигурации класса
  */
  var modView = function(module,options) {
    this.mds = module;
    if (options === undefined) { options = {}; }
    this.config = $.extend(true,{
      'templateUrl':'/cabinetUI/orders/getTemplates',
      'templates':{
        serviceList:'orderEdit/payment/serviceList',
        serviceListRow:'orderEdit/payment/serviceListRow',
        serviceActions:'orderEdit/payment/serviceActions',
        serviceNoActions: 'orderEdit/payment/serviceNoActions',
        invoiceListItem:'orderEdit/payment/invoiceListItem',
        invoiceListItemService:'orderEdit/payment/invoiceListItemService',
        invoiceListEmpty:'orderEdit/payment/invoiceListEmpty',
        TOSAgreementModal:'orderEdit/modals/acceptBookingTerms',
        multiBookCancelModal: 'orderEdit/modals/multiBookCancel'
      }
    },options);

    this.$serviceList = $('#order-edit-payment--services');
    this.$invoiceList = $('#order-edit-payment--invoices');
    this.$invoiceActions = $('#order-edit-payment--invoice-actions');

    /** @todo this should be in model? */
    this.discountSum = 0;
  };

  /**
  * Отображение списка услуг заявки
  * @param {ServiceStorage[]} services - сервисы заявки
  * @todo add param for setdiscount event or rework?
  */
  modView.prototype.renderServiceList = function(services) {
    var _instance = this;

    var serviceList = '';
    var totalNet = 0,
        totalGross = 0,
        totalCommission = 0,
        totalDiscount = 0;
    var showNetPrice = (KT.profile.userType === 'op');

    services.forEach(function(service) {
      var serviceInfo = {
        'serviceId': service.serviceId,
        'serviceIcon': KT.getCatalogInfo('servicetypes', service.typeCode, 'icon'),
        'serviceTypeName': KT.getCatalogInfo('servicetypes',service.typeCode, 'title'),
        'statusIcon': KT.getCatalogInfo('servicestatuses', service.status, 'icon'),
        'statusTitle': KT.getCatalogInfo('servicestatuses', service.status, 'title'),
        'serviceName': service.name,
        'net': showNetPrice ? service.prices.inLocal.supplier.gross.toMoney(2,',',' ') : null,
        'gross': service.prices.inLocal.client.gross.toMoney(2,',',' '),
        'offline': service.isOffline,
        'travelPolicyViolations': (!service.hasTPViolations) ? null : 
          {'list': service.offerInfo.travelPolicy.travelPolicyFailCodes}
      };

      if (KT.profile.userType === 'op' && service.status === 9) {
        serviceInfo.statusTitle = 'Ручной режим';
      }

      totalDiscount += service.discount;
      totalNet += service.prices.inLocal.supplier.gross;
      totalGross += service.prices.inLocal.client.gross;
      totalCommission += service.prices.inLocal.client.commission.amount;

      serviceList += Mustache.render(_instance.mds.tpl.serviceListRow, serviceInfo);
    });

    var serviceActions = Mustache.render(_instance.mds.tpl.serviceNoActions,{});

    var serviceTable = {
      'serviceList': serviceList,
      'showNetPrice': showNetPrice,
      'totalNet': showNetPrice ? totalNet.toMoney(2,',',' ') : null,
      'totalGross': totalGross.toMoney(2,',',' '),
      'totalCommission': totalCommission.toMoney(2,',',' '),
      'serviceActions': serviceActions,
      'discount': (KT.profile.userType === 'agent') ? totalDiscount.toMoney(2,',','') : null,
      'profit': (KT.profile.userType === 'agent') ? (totalCommission - totalDiscount).toMoney(2,',',' ') : null
    };

    _instance.$serviceList.html(Mustache.render(_instance.mds.tpl.serviceList, serviceTable));
    /** 
    * @todo не очень красиво, наверное, стоит подумать, как лучше блокировать чекбоксы услуг 
    * до загрузки доступных переходов
    */
    if (_instance.mds.OrderStorage.loadStates.transitionsdata === null) {
      _instance.$serviceList
        .find('.js-ore-payment-services--check-service')
          .prop('disabled', true);
    }

    if(KT.profile.userType === 'agent') {
      var $discountField = _instance.$serviceList.find('.js-ore-payment-services--discount');
      
      $discountField.jirafize({
        position: 'right',
        margin: '20px',
        buttons:{
          name: 'submit',
          type: 'submit',
          callback: function() {
            $discountField.prop('disabled',true);
            KT.dispatch('PaymentView.setDiscount', {
              'discount':parseFloat($discountField.val().replace(/ /g,'').replace(',','.')).toFixed(2)
            });
          }
        }
      });
    }
  };

  /**
  * Вывод элементов для управления услугами
  * @param {Object|false} actions - список действия или false если действия недоступны
  */
  modView.prototype.renderServiceActions = function(actions) {
    this.$serviceList
      .find('.js-ore-payment-services--check-service')
        .not('[data-offline="true"]')
          .prop('disabled', false);

    if (actions === false) {
      this.$serviceList.find('.js-ore-payment-services--service-actions')
        .html(Mustache.render(this.mds.tpl.serviceNoActions, {}));
    } else {
      this.$serviceList.find('.js-ore-payment-services--service-actions')
        .html(Mustache.render(this.mds.tpl.serviceActions, actions));
    }
  };

  /** Очистка списка доступных действий с услугами */
  modView.prototype.clearServiceActions = function() {
    this.$serviceList
      .find('.js-ore-payment-services--check-service')
        .empty();
  };

  /**
  * Возвращает идентификаторы выбранных для совершения действия услуг
  * @return {Integer[]} - массив ID услуг
  */
  modView.prototype.getSelectedServices = function() {
      var serviceIds = [];
      var $serviceControls = this.$serviceList.find('.js-ore-payment-services--check-service');

      // получим ID всех выбранных услуг
      $serviceControls
        .filter(':checked')
          .each(function() {
            serviceIds.push(+$(this).data('serviceid'));
      });

      return serviceIds;
  };

  /**
  * Отображение списка счетов заявки
  * @param {InvoiceStorage[]} invoices - массив счетов
  */
  modView.prototype.renderInvoiceList = function(Invoices) {
    var invoiceList = '';
    var _instance = this;

    Invoices.forEach(function(Invoice) {
      var serviceDetails = '';
      var currencySign = KT.getCatalogInfo('lcurrency',Invoice.currency,'icon');
      if (currencySign === 'unknown') {
        currencySign = Invoice.currency;
      }

      Invoice.getServiceDetails().forEach(function(service) {
        serviceDetails += Mustache.render(_instance.mds.tpl.invoiceListItemService, {
          'serviceId': service.serviceId,
          'name': service.name,
          'sum': service.sum.toMoney(2,',',' '),
          'currencySign': currencySign
        });
      });

      var invoiceInfo = {
        'invoiceId': Invoice.invoiceId,
        'statusIcon': KT.getCatalogInfo('invoicestatuses', Invoice.status, 'icon'),
        'statusTitle': KT.getCatalogInfo('invoicestatuses', Invoice.status, 'title'),
        'number': Invoice.number,
        'creationDate': Invoice.creationDate.format('DD.MM.YY'),
        'description': Invoice.description !== null ?
          Invoice.description : '[Без названия]',
        'sum': Invoice.sum.toMoney(2,',',' '),
        'currencySign': currencySign,
        'serviceDetails': serviceDetails,
        'actions': {
          /** Возможность удаления счетов убрана (возможно, временно) */
          'cancel': false // (Invoice.status !== 5) ? true : false
        }
      };

      invoiceList += Mustache.render(_instance.mds.tpl.invoiceListItem, invoiceInfo);
    });

    if (invoiceList === '') {
      invoiceList = Mustache.render(_instance.mds.tpl.invoiceListEmpty,{});
    }

    _instance.$invoiceList.html(invoiceList);
  };

  /** Очистка данных списка счетов */
  modView.prototype.flushInvoiceList = function() {
    this.$invoiceList.html(Mustache.render(KT.tpl.spinner,{}));
  };

  /** Очистка данных списка услуг */
  modView.prototype.flushServiceList = function() {
    this.$serviceList.html(Mustache.render(KT.tpl.spinner,{}));
  };

  /**
  * Рендер диалога бронирования нескольких услуг
  * @param {Object} tosData - данные по условиям юронирования
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showChainBookingModal = function(tosData, submitAction) {
    var _instance = this;

    KT.Modal.notify({
      type:'info',
      title:'Бронирование услуг',
      msg: Mustache.render(this.mds.tpl.TOSAgreementModal, tosData),
      buttons:[{
          type:'common',
          title:'да',
          callback: submitAction
        },
        {
          type:'common',
          title:'нет',
          callback: function() {
            KT.Modal.closeModal();
          }
      }]
    }).$container
      .find('#order-edit-payment--tos-agreement')
        .on('click', '.js-modal-accept-book-terms--tos-link', function() {
          var tosDocName = $(this).data('tosdoc');
          _instance.mds.servicesTermsDocuments[tosDocName].open();
        });
  };

  /**
  * Рендер диалога множественной отмены бронирования
  * @param {ServiceStorage[]} Services - массив услуг
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showChainBookCancelModal = function(Services, submitAction) {
    var modalParams = {
      'hasPenalty': false,
      'services': []
    };

    Services.forEach(function(Service) {
      var cancelPenaltySum = Service.countClientCancelPenalty();

      var servicePenalty = {
        'serviceId': Service.serviceId,
        'name': Service.name,
        'penalty': (cancelPenaltySum !== null && cancelPenaltySum.inLocal !== 0) ? {
            'localAmount': Number(cancelPenaltySum.inLocal).toMoney(0,',',' '),
            'localCurrency': KT.getCatalogInfo('lcurrency', KT.profile.localCurrency, 'icon'),
            'viewAmout': Number(cancelPenaltySum.inView).toMoney(0,',',' '),
            'viewCurrency': KT.getCatalogInfo('lcurrency', KT.profile.viewCurrency, 'icon'),
          } : null,
        'isInvoiceOptional': false // (KT.profile.userType === 'op' && servicePenalty.penalty !== false) ? true : false;
      };

      modalParams.services.push(servicePenalty);

      if (servicePenalty.penalty !== null) {
        modalParams.hasPenalty = true;
      }
    });
    
    KT.Modal.notify({
      type: 'info',
      title:'Отмена бронирования',
      msg: Mustache.render(this.mds.tpl.multiBookCancelModal, modalParams),
      buttons:[{
          type:'common',
          title:'да',
          callback: submitAction
        },
        {
          type:'common',
          title:'нет',
          callback: function() {
            KT.Modal.closeModal();
          }
      }]
    });

    return ;
  };

  /**
  * Рендер диалога множественной выписки билетов/ваучеров
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showChainIssueModal = function(submitAction) {
    KT.Modal.notify({
      type:'info',
      title:'Выписка ваучеров',
      msg: '<p>Выписать ваучеры для всех выбранных услуг?</p>',
      buttons:[{
          type:'common',
          title:'да',
          callback: submitAction
        },
        {
          type:'common',
          title:'нет',
          callback: function() {
            KT.Modal.closeModal();
          }
      }]
    });
  };

  /**
  * Рендер диалога множественного перевода услуг в ручной режим
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showChainSetToManualModal = function(submitAction) {
    KT.Modal.notify({
      type:'info',
      title:'Перевод в ручной режим',
      msg: '<p>Перевести выбранные услуги в ручной режим?</p>',
      buttons:[{
          type:'common',
          title:'да',
          callback: submitAction
        },
        {
          type:'common',
          title:'нет',
          callback: function() {
            KT.Modal.closeModal();
          }
      }]
    });
  };

  /**
  * Рендер диалога отмены счета
  * @param {InvoiceStorage} InvoiceStorage - данные счета
  * @param {Function} submitAction - действие при подтверждении
  */
  modView.prototype.showCancelInvoiceModal = function(InvoiceStorage, submitAction) {
    KT.Modal.notify({
      type:'info',
      title:'Отмена счета',
      msg: 'Вы действительно хотите отменить счет № ' + InvoiceStorage.number,
      buttons:[{
          type:'common',
          title:'да',
          callback: submitAction
        },
        {
          type:'common',
          title:'нет',
          callback: function() {
            KT.Modal.closeModal();
          }
      }]
    });
  };

  return modView;
}));

(function(global,factory) {

    KT.crates.OrderEdit.payment.controller = factory(KT.crates.OrderEdit.payment);

}(this, function(crate) {
  /**
  * Редактирование заявки: оформление
  * @constructor
  * @param {Object} module - хранилище родительского модуля
  * @param {Object} orderId - ID заявки
  */
  var oepController = function(module, orderId) {
    this.mds = module;
    this.orderId = orderId;
    this.mds.payment.view = new crate.view(this.mds);
  };

  oepController.prototype.init = function() {
    var _instance = this;
    var modView = this.mds.payment.view;

    /** Рендер пустого списка если в заявке нет услуг */
    KT.on('OrderStorage.initialized', function(e, OrderStorage) {
      if (OrderStorage.getServices().length === 0) {
        modView.renderServiceList([]);
      }
    });

    /** Рендер списка услуг */
    KT.on('OrderStorage.setServices', function(e, OrderStorage) {
      modView.renderServiceList(OrderStorage.getServices());
    });

    /** Обработка загрузки данных по счетам */
    KT.on('OrderStorage.setInvoices', function(e, OrderStorage) {
      modView.renderInvoiceList(OrderStorage.getInvoices());
    });

    /**
    * При загрузке доступных переходов разблокировать выбор услуг для совершения действий
    */
    KT.on('OrderStorage.setAllowedTransitions', function() {
      modView.renderServiceActions(false);
    });

    /** Обработка получения всех запрашиваемых валидаций действий над услугами */
    KT.on('OrderStorage.setValidatedActions', function(e, OrderStorage) {
      console.warn('service validations:');
      console.log(OrderStorage.validatedActions);

      var actions = $.extend(true, {}, OrderStorage.validatedActions);

      // хак для разного стиля кнопок, переделать?
      if (KT.profile.userType !== 'op' && actions['Manual']) {
        actions['ToManager'] = actions['Manual'];
        delete actions['Manual'];
      }

      modView.renderServiceActions(actions);
    });

    /*===============================================
    * Обработчики событий представления
    **===============================================*/
    
    /** Обработка нажатия кнопки "Сохранить" на вкладке оформления заявки */
    KT.on('PaymentView.setDiscount', function(e, data) {
      KT.apiClient.setDiscount(_instance.orderId, data.discount)
        .done(function(response) {
          if (response.status === 0) {
            KT.notify('discountSet',{'discount': response.discount});
            modView.flushSetInvoiceForm();
            modView.flushServiceList();
          } else {
            $(modView.$serviceList).find('input[name="discount"]')
              .prop('disabled',false)
              .val(modView.discountSum);
            KT.notify('settingDicountFailed', response.errors);
          }
        });
    });

    /** Раскрыть/свернуть информацию по счету */
    modView.$invoiceList.on('click','.js-ore-invoice--header', function() {
      var $invoice = $(this).closest('.js-ore-invoice');
      if ($invoice.hasClass('active')) { $invoice.removeClass('active'); }
      else { $invoice.addClass('active'); }
      $invoice.find('.js-ore-invoice--description').toggle(500);
    });

    /**
    * Обработка выбора услуги на вкладке "Оформление": 
    * вычисление доступных действий с услугами
    */
    modView.$serviceList.on('change','.js-ore-payment-services--check-service', function() {
      modView.clearServiceActions();
      _instance.mds.OrderStorage.validatedActions = {};
      
      var checkedServices = modView.getSelectedServices();
      modView.$serviceList
        .find('.js-ore-payment-services--check-service')
          .prop('disabled', true);
      
      if (checkedServices.length === 0) {
        modView.renderServiceActions(false);
      } else {
        var servicesTransitions = [];
        // для каждой услуги получим набор доступных переходов по checkTransitions ...
        checkedServices.forEach(function(serviceId) {
          servicesTransitions.push(
            $.extend(true, [], _instance.mds.OrderStorage.services[serviceId].allowedTransitions)
          );
        });
        // ... и отфильтруем переходы, общие для всех выбранных услуг
        var commonTransitions;
        if (servicesTransitions.length === 1) {
          commonTransitions = servicesTransitions[0];
        } else {
          commonTransitions = servicesTransitions.shift().filter(function(v) {
            return servicesTransitions.every(function(a) {
              return a.indexOf(v) !== -1;
            });
          });
        }

        var validationParams = {};
        var transitionsAmount = 0;

        // для каждого перехода получим масив парвметров по всем услугам для передачи на validate
        commonTransitions.forEach(function(transition) {
          validationParams[transition] = [];
          var paramsMergeError = false;

          checkedServices.forEach(function(serviceId) {
            var serviceValidations = _instance.mds.OrderStorage.getServiceCommandParams(transition, serviceId);
            if (serviceValidations === false) {
              paramsMergeError = true;
            } else {
              /** @todo хак для галочки bookStart */
              if (transition === 'BookStart') {
                serviceValidations.agreementSet = true;
              }

              validationParams[transition].push(serviceValidations);
            }
          });

          if (paramsMergeError) {
            delete validationParams[transition];
          } else {
            transitionsAmount++;
          }
        });
        
        if (transitionsAmount === 0) {
          modView.renderServiceActions(false);
        } else {
          _instance.mds.OrderStorage.pendingValidations = transitionsAmount;
          for (var action in validationParams) {
            if (validationParams.hasOwnProperty(action)) {
              KT.apiClient.validateAction(_instance.orderId, action, validationParams[action])
                .done(function(response) {
                  if (response.status === 0) {
                    _instance.mds.OrderStorage.setValidatedAction(response.action, response.body);
                  } else {
                    _instance.mds.OrderStorage.setValidatedAction(response.action, false);
                  }  
                });
            }
          }
        }
      }
    });

    /** Обработка нажатия на кнопку "Бронировать" */
    modView.$serviceList.on('click','.js-ore-payment-services--book', function() {
      var serviceIds = modView.getSelectedServices();
      var tosData = {
        'services': [],
        'hasNonRefundableServices': false
      };

      serviceIds.forEach(function(serviceId) {
        var Service = _instance.mds.OrderStorage.services[serviceId];

        if (Service.isNonRefundable) {
          tosData.hasNonRefundableServices = true;
        }

        tosData.services.push({
          'icon': KT.getCatalogInfo('servicetypes', Service.typeCode, 'icon'),
          'type': KT.getCatalogInfo('servicetypes', Service.typeCode, 'title'),
          'name': Service.name,
          'tosdoc': Service.tosDocumentName
        });
      });

      var chainBooking = function() {
        KT.Modal.showLoader();

        var bookProcesses = [];
        
        serviceIds.forEach(function(serviceId) {
          var commandParams = _instance.mds.OrderStorage.getServiceCommandParams('BookStart', serviceId);
          commandParams['agreementSet'] = true;
          bookProcesses.push(KT.apiClient.startBooking(_instance.orderId, commandParams));
        });

        $.when.apply($, bookProcesses).always(function() {
          KT.Modal.closeModal();
          KT.dispatch('OrderEdit.reloadInfo');
        });
      };

      modView.showChainBookingModal(tosData, chainBooking);
    });

    /** Обработка нажатия на кнопку "Отменить бронь" */
    modView.$serviceList.on('click','.js-ore-payment-services--book-cancel', function() {
      var serviceIds = modView.getSelectedServices();
      var services = [];

      serviceIds.forEach(function(serviceId) {
        services.push(_instance.mds.OrderStorage.services[serviceId]);
      });

      var chainBookCancel = function($modal) {
        var $isSetInvoiceSelected = $modal.find('.js-modal-book-cancel--set-invoice');
        KT.Modal.showLoader();

        var bookCancelProcesses = [];

        serviceIds.forEach(function(serviceId) {
          var commandParams = _instance.mds.OrderStorage.getServiceCommandParams('BookCancel', serviceId);
          var $serviceInvoiceCheckbox = $isSetInvoiceSelected.filter('[data-serviceid="' + serviceId + '"]');
          if ($serviceInvoiceCheckbox.length !== 0) {
            commandParams['createPenaltyInvoice'] = $serviceInvoiceCheckbox.prop('checked');
          } else {
            commandParams['createPenaltyInvoice'] = true;
          }

          bookCancelProcesses.push(KT.apiClient.bookCancel(_instance.orderId, commandParams));
        });

        $.when.apply($, bookCancelProcesses).always(function() {
          KT.Modal.closeModal();
          KT.dispatch('OrderEdit.reloadInfo');
        });
      };

      modView.showChainBookCancelModal(services, chainBookCancel);
    });

    /** Обработка нажатия на кнопку "Выставить счет" */
    modView.$serviceList.on('click','.js-ore-payment-services--set-invoice', function() {
      KT.dispatch('OrderEdit.openSetInvoiceForm');
    });

    /** Обработка нажатия кнопки "Аннулировать услугу" */
    /** @todo template, не реализовано */
    modView.$serviceList.on('click','.js-ore-payment-services--cancel', function() {
      var serviceIds = modView.getSelectedServices();

      KT.Modal.notify({
        type:'info',
        title:'Аннулирование услуг',
        msg:'<p>Вы действительно хотите аннулировать выбранные услуги: ' +
          serviceIds.join(',') + ' ?</p>',
        buttons:[
          {title:'Да'},
          {title:'Нет'}
        ]
      });
    });

    /** Обработка нажатия кнопки "Выписать ваучер" */
    modView.$serviceList.on('click','.js-ore-payment-services--issue', function() {
      var serviceIds = modView.getSelectedServices();
      var services = [];

      serviceIds.forEach(function(serviceId) {
        services.push(_instance.mds.OrderStorage.services[serviceId]);
      });

      var chainIssueTickets = function() {
        KT.Modal.showLoader();

        var issueProcesses = [];

        serviceIds.forEach(function(serviceId) {
          var commandParams = _instance.mds.OrderStorage.getServiceCommandParams('IssueTickets', serviceId);
          issueProcesses.push(KT.apiClient.issueTickets(_instance.orderId, commandParams));
        });

        $.when.apply($, issueProcesses).always(function() {
          KT.Modal.closeModal();
          KT.dispatch('OrderEdit.reloadInfo');
        });
      };

      modView.showChainIssueModal(chainIssueTickets);
    });

    /** Обработка нажатия кнопки "Перевести в ручной режим" */
    modView.$serviceList.on('click','.js-ore-payment-services--to-manual', function() {
      var serviceIds = modView.getSelectedServices();
      var services = [];

      serviceIds.forEach(function(serviceId) {
        services.push(_instance.mds.OrderStorage.services[serviceId]);
      });

      var chainSetServiceToManual = function() {
        KT.Modal.showLoader();

        var chainProcesses = [];

        serviceIds.forEach(function(serviceId) {
          var commandParams = _instance.mds.OrderStorage.getServiceCommandParams('Manual', serviceId);
          chainProcesses.push(KT.apiClient.setServiceToManual(_instance.orderId, commandParams));
        });

        $.when.apply($, chainProcesses).always(function() {
          KT.Modal.closeModal();
          KT.dispatch('OrderEdit.reloadInfo');
        });
      };

      modView.showChainSetToManualModal(chainSetServiceToManual);
    });

    /** Обработка нажатия на ссылку на условия бронирования */
    modView.$serviceList.on('click','.js-ore-payment-services--tos', function() {
      var serviceId = +$(this).data('serviceid');
      var service = _instance.mds.OrderStorage.services[serviceId];

      if (service.cancellationDocumentName !== false) {
        if (_instance.mds.servicesTermsDocuments[service.cancellationDocumentName] !== undefined) {
          _instance.mds.servicesTermsDocuments[service.cancellationDocumentName].open();
        } else {
          KT.notify('waitTOSLoading');
        }
      }
    });

    /** Обработка нажатия на кнопку отмены счета */
    /** @todo добавить обновление модели счета? */
    modView.$invoiceList.on('click', '.js-ore-invoice-actions--cancel', function() {
      var invoiceId = +$(this).closest('.js-ore-invoice').data('invoiceid');
      var InvoiceStorage = _instance.mds.OrderStorage.invoices[invoiceId];

      var cancelInvoce = function() {
        KT.Modal.showLoader();

        KT.apiClient.cancelInvoice(invoiceId)
          .then(function(response) {
            if (response.status === 0) {
              //InvoiceStorage.status = InvoiceStorage.statuses.CANCELLED;
              modView.flushInvoiceList();

              var getServices = KT.apiClient.getOrderOffers(_instance.orderId, _instance.mds.OrderStorage.getServiceIds());
              var getInvoices = KT.apiClient.getOrderInvoices(_instance.orderId);

              getServices.done(function(offersData) {
                if (offersData.status !== 0) {
                  console.error('offer load error: ' + offersData.error);
                } else {
                  _instance.mds.OrderStorage.setServices(offersData.body);
                }
              });

              getInvoices.done(function(invoiceData) {
                var invoices = (invoiceData.body.Invoices !== undefined && Array.isArray(invoiceData.body.Invoices)) ?
                    invoiceData.body.Invoices : [];
                _instance.mds.OrderStorage.setInvoices(invoices);
              });

              $.when.apply($, [getServices, getInvoices]).always(function() {
                KT.Modal.closeModal();
              });
            }
          });
      };

      modView.showCancelInvoiceModal(InvoiceStorage, cancelInvoce);
    });
  };

  return oepController;
}));

(function(global,factory){

    KT.crates.OrderEdit.view = factory();

}(this,function() {
  /**
  * Редактирование заявки
  * @constructor
  * @param {Object} module - хранилище родительского модуля
  * @param {Number} orderId - Id заявки
  * @param {Object} [options] - Объект конфигурации класса
  */
  var modView = function(module, orderId, options) {
    this.mds = module;
    if (options === undefined) { options = {}; }
    this.config = $.extend(true, {
      'templateUrl':'/cabinetUI/orders/getTemplates',
      'templates':{
        headerOrderInfo:'orderEdit/headerInfo',
        headerSpinner:'orderEdit/headerSpinner',
        invoices:'orderEdit/invoices',
        invoicesRow:'orderEdit/invoicesRow',
        documents:'orderEdit/documents',
        documentsRow:'orderEdit/documentsRow',
        documentsEmpty:'orderEdit/documentsEmpty',
        history:'orderEdit/history',
        historyRow:'orderEdit/historyRow',
      }
    },options);

    this.mds.tpl = {};

    this.orderId = orderId;

    this.$headerInfo = $('#order-edit-header__info');
    this.$headerControls = $('#order-edit-header__controls');
    this.$breadcrumb = $('#order-edit-header__ordernum');

    this.$btnInvoices = this.$headerControls.find('.js-ore-show-invoices');
    this.$btnDocuments = this.$headerControls.find('.js-ore-show-documents');

    /** @todo set forms loaded via ajax? */
    this.SetInvoiceForm = {};
    this.SetInvoiceForm.controls = {};
    this.SetInvoiceForm.$wrapper = $('.js-ore-set-invoice');
    this.SetInvoiceForm.wnd = $.featherlight(this.SetInvoiceForm.$wrapper, {
      persist:true,
      closeIcon:'',
      openSpeed:0,
      closeSpeed:0
    });
    this.SetInvoiceForm.wnd.close();
    this.SetInvoiceForm.wnd.openSpeed = 200;
    this.SetInvoiceForm.wnd.closeSpeed = 200;
    this.SetInvoiceForm.$content = this.SetInvoiceForm.wnd.$content.find('.js-ore-set-invoice--content');
    //this.Invoices.$content = this.invoices.$wrapper.find('.js-ore-invoices--content');

    this.documents = {};
    this.documents.$wrapper = $('.js-ore-documents');
    this.documents.$content = this.documents.$wrapper.find('.js-ore-documents--content');

    this.$history = $('.js-ore-history');
    this.History = this.setHistory(this.$history, this.$headerControls, '.js-ore-show-history');

    this.$addServiceBlock = $('.order-footer .add-service-block');

    /** Установка хлебной крошки */
    this.$breadcrumb.html(
      this.orderId === 'new' ?
      'Новая заявка' :
      'Заявка '+this.orderId
    );

    /** @todo move it */
    this.initTabs();
    this.History.init();

    /*
    this.$btnInvoices.featherlight(this.invoices.$wrapper, {
      persist:'shared',
      closeIcon:''
    }); */
    this.$btnDocuments.featherlight(this.documents.$wrapper, {
      persist:'shared',
      closeIcon:''
    });
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

  /** Инициализация табов */
  modView.prototype.initTabs = function() {
    var _instance = this;

    $('#tab-headers').on('click','.js-tab-header',function(e){
      e.preventDefault();
      var tab = $(this).attr('data-tab');
      _instance.setActiveTab(tab);
    });
  };

  /**
  * Отображение информации о заявке в шапке
  * @param {Object} orderInfo - информация о заявке
  */
  modView.prototype.renderHeaderInfo = function(OrderStorage) {
    var localSum = 0;
    var requestedSum = 0;
    var tourleaderName = '';
    var hasTourleader = false;
    var gatewayOrderIds = false;
    var kmpManager = null;
    var clientManager = null;
    var clientCompany = null;
    var creator = null;

    if (this.orderId !== 'new' && KT.profile.userType === 'op') {
      this.$breadcrumb.html(
        'Заявка ' + OrderStorage.orderId +
        (
          (OrderStorage.creationDate !== null) ?
           ' (от ' + OrderStorage.creationDate.format('DD.MM.YYYY') + ')' :
           ''
        )
      );

      if (OrderStorage.orderIdGp !== null || OrderStorage.orderIdUtk !== null) {
        gatewayOrderIds = {
          'gp':OrderStorage.orderIdGp,
          'utk':OrderStorage.orderIdUtk
        };
      }
    }

    if (OrderStorage.tourleader === null) {
      tourleaderName = 'Ф.И.О. туриста';
    } else {
      hasTourleader = true;
      tourleaderName = OrderStorage.tourleader.lastname +
        ((OrderStorage.tourleader.firstname !== null) ?
          ' ' + OrderStorage.tourleader.firstname : '');
    }

    kmpManager = (OrderStorage.kmpManager === null) ? null :
      OrderStorage.kmpManager.lastname + ' ' +
        ((OrderStorage.kmpManager.firstname !== null) ?
          (OrderStorage.kmpManager.firstname.substr(0,1) + '. ') : '') +
        ((OrderStorage.kmpManager.middlename !== null) ?
          (OrderStorage.kmpManager.middlename.substr(0,1) + '.') : '');

    clientManager = (OrderStorage.clientManager === null) ? null : 
      OrderStorage.clientManager.lastname + ' ' +
        ((OrderStorage.clientManager.firstname !== null) ?
          (OrderStorage.clientManager.firstname.substr(0,1) + '. ') : '') +
        ((OrderStorage.clientManager.middlename !== null) ?
          (OrderStorage.clientManager.middlename.substr(0,1) + '.') : '');

    creator = (OrderStorage.creator === null) ? null : 
      OrderStorage.creator.lastname + ' ' +
        ((OrderStorage.creator.firstname !== null) ?
          (OrderStorage.creator.firstname.substr(0,1) + '. ') : '') +
        ((OrderStorage.creator.middlename !== null) ?
          (OrderStorage.creator.middlename.substr(0,1) + '.') : '');
    
    clientCompany = (KT.profile.userType !== 'op') ? null :
      ('"' + OrderStorage.client.name + '"').replace('""','"');

    OrderStorage.getServices().forEach(function(Service) {
      switch (KT.profile.prices) {
        case 'gross':
          localSum += Service.prices.inLocal.client.gross;
          requestedSum += Service.prices.inView.client.gross;
          break;
        case 'net':
          switch (KT.profile.userType) {
            case 'op':
              localSum += Service.prices.inLocal.supplier.gross;
              requestedSum += Service.prices.inView.supplier.gross;
              break;
            case 'agent':
              localSum += Service.prices.inLocal.client.gross - Service.prices.inLocal.client.commission.amount;
              requestedSum += Service.prices.inView.client.gross - Service.prices.inView.client.commission.amount;
              break;
            default:
              localSum += Service.prices.inLocal.client.gross;
              requestedSum += Service.prices.inView.client.gross;
              break;
          }
          break;
      }
    });

    var order = {
      'vip': OrderStorage.isVip,
      'orderStatusIcon': KT.getCatalogInfo('orderstatuses', OrderStorage.status, 'icon'),
      'orderStatusTitle': KT.getCatalogInfo('orderstatuses', OrderStorage.status, 'title'),
      'orderId': (OrderStorage.orderId !== 'new') ? OrderStorage.orderId : null,
      'creationDate': (OrderStorage.creationDate === null) ? 'не указана' :
        OrderStorage.creationDate.format('YYYY-MM-DD HH:mm:ss'),
      'gatewayOrderIds': gatewayOrderIds,
      'kmpManager': kmpManager,
      'clientCompany': clientCompany,
      'clientManager': clientManager,
      'creator': creator,
      'touristSet': hasTourleader,
      'touristName': htmlDecode(tourleaderName),
      'touristCount': (OrderStorage.touristsAmount > 1) ? '+ ' + (OrderStorage.touristsAmount - 1) : '',
      'touristTel': hasTourleader ? OrderStorage.tourleader.phone : null,
      'touristEmail': hasTourleader ? OrderStorage.tourleader.email : null,
      'localSum': localSum.toMoney(0,',',' '),
      'requestedSum': requestedSum.toMoney(2,',',' '),
      'requestedCurrency': KT.getCatalogInfo('lcurrency',KT.profile.viewCurrency,'icon')
    };

    if (KT.profile.userType === 'op' && OrderStorage.status === 1) {
      order.orderStatusTitle = 'Ручной режим';
    }

    this.$headerInfo.html( Mustache.render(this.mds.tpl.headerOrderInfo, order) );
  };

  /**
  * Инициализация формы выставления счетов
  * @param {Object} $container контейнер формы
  * @param {Object} $bindcontainer элемент, на который вешается обработка клика для открытия окна
  * @param {String} bindelem селектор элемента-цели для открытия окна
  */
  modView.prototype.setHistory = function($container,$bindcontainer,bindelem) {
    var _instance = this;

    var History = {
      elem: {
        $container: $container,
        $bindcontainer: $bindcontainer,
        $history: $container.find('.js-ore-history--content')
      },
      toggler: bindelem,
      wnd: null,
      init: function() {
        var self = this;
        self.wnd = $.featherlight(this.elem.$container, {
          persist:'shared',
          closeIcon:'',
          openSpeed:0,
          closeSpeed:0
        });
        self.wnd.close();
        self.wnd.openSpeed = 250;
        self.wnd.closeSpeed = 250;

        self.elem.$bindcontainer.on('click',self.toggler+':not(:disabled)',function() {
          self.wnd.open();
        });

        self.elem.$container.on('click','button[data-action="cancel"]',function(){
          self.wnd.close();
        });
      },
      render: function(history) {
        if (Array.isArray(history)) {
          var historyList = {
            'history': ''
          };

          history.forEach(function(record) {
            record.eventTime = moment(record.eventTime,'YYYY-MM-DD HH:mm:ss').format('D MMMM YYYY, HH:mm');
            record.serviceStatusTitle = KT.getCatalogInfo('servicestatuses', record.serviceStatus,'title');
            record.serviceStatusIcon = KT.getCatalogInfo('servicestatuses', record.serviceStatus,'icon');
            record.orderStatusTitle = KT.getCatalogInfo('orderstatuses', record.orderStatus,'title');
            record.orderStatusIcon = KT.getCatalogInfo('orderstatuses', record.orderStatus,'icon');
            record.success = (+record.result === 0) ? true : false;

            historyList.history += Mustache.render(_instance.mds.tpl.historyRow, record);
          });

          this.elem.$history.html( Mustache.render(_instance.mds.tpl.history,historyList) );
          this.elem.$bindcontainer.find(this.toggler).prop('disabled', false);
        }
      }
    };

    return History;
  };

  /**
  * Отображение формы выставления счетов
  * @param {ServiceStorage[]} services - сервисы заявки (ServiceStorage)
  */
  modView.prototype.renderSetInvoiceForm = function(services) {
    var _instance = this;
    var invoiceRows = '';
    /** @todo надо бы понять, как выставлять счета на все услуги, если у них разные валюты */
    var currencySign = '';

    _instance.$btnInvoices.prop('disabled', false);

    services.forEach(function(service) {
      currencySign = KT.getCatalogInfo('lcurrency',service.paymentCurrency,'icon');
      if (currencySign === 'unknown') {
        currencySign = service.paymentCurrency;
      }

      var serviceData = {
        'serviceId': service.serviceId,
        'statusIcon': KT.getCatalogInfo('servicestatuses',service.status,'icon'),
        'statusTitle': KT.getCatalogInfo('servicestatuses',service.status,'title'),
        'serviceName': service.name,
        'price': service.prices.inClient.client.gross.toMoney(2,',',' '),
        'disabled': !service.isActionAvailable.setInvoice,
        'leftToPay': (!service.isActionAvailable.setInvoice) ? 0 :
          Number(service.unpaidSum).toMoney(2,',',' '),
        'currencyIcon': currencySign
      };

      if (KT.profile.userType === 'op' && service.status === 9) {
        serviceData.statusTitle = 'Ручной режим';
      }

      invoiceRows += Mustache.render(_instance.mds.tpl.invoicesRow, serviceData);
    });

    /** @todo rework currency icon for services..*/


    var invoiceTable = {
      'services': invoiceRows,
      'currencyCode': currencySign
    };

    _instance.SetInvoiceForm.$content.html(Mustache.render(_instance.mds.tpl.invoices, invoiceTable));

    _instance.SetInvoiceForm.controls.selectPayment = $('#SetInv-SelectPayment').selectize({
        openOnFocus: true,
        create: false,
        createOnBlur:true
    });
  };

  /**
  * Отображение формы управления документами заявки
  * @param {Object[]} documents - массив документов заявки
  */
  modView.prototype.renderDocumentsForm = function(documents) {
    var _instance = this;
    var documentsList = '';

    _instance.$btnDocuments.prop('disabled', false);

    if (Array.isArray(documents) && documents.length > 0) {
      documents.forEach(function(document) {
        if (document.fileName === '') { document.fileName = 'Документ'; }
        documentsList += Mustache.render(_instance.mds.tpl.documentsRow, document);
      });
    } else {
      documentsList = Mustache.render(_instance.mds.tpl.documentsEmpty, {});
    }

    _instance.documents.$content.html(Mustache.render(_instance.mds.tpl.documents, {
      'documents': documentsList
    }));
  };

  /**
  * Отображение прогресса загрузки документов
  * @param {Integer} percent - процент загрузки
  */
  modView.prototype.showDocUploadProgress = function(percent) {
    var $progresslabel = this.documents.$content.find('.js-ore-documents--upload-progress-text');
    var $progressbar = this.documents.$content.find('.js-ore-documents--upload-progress-bar');

    if (+percent !== 100) {
      $progresslabel.text(percent + ' %');
      $progressbar.css({'width':percent + '%'});
    } else {
      $progresslabel.text('обработка документа...');
      $progressbar.removeClass('active').css({'width':'100%'});
    }
  };

  /** Очистка блока документов */
  modView.prototype.flushDocumentsForm = function() {
    this.documents.$content.html(Mustache.render(KT.tpl.spinner, {}));
  };

  /**
  * Управление значениями полей ввода в форме выставления счета
  * @param {Object} $input - поле ввода
  * @param {Number} maxInvoiceSum - максимальная сумма счета для услуги
  */
  modView.prototype.checkInvoiceFormInput = function($serviceRow, maxInvoiceSum) {
    var $inputs = this.SetInvoiceForm.$content.find('.js-ore-set-invoice--service-sum');
    var $input = $serviceRow.find('.js-ore-set-invoice--service-sum');
    var $checkbox = $serviceRow.find('.js-ore-set-invoice--service-check');

    $input.prop('disabled',true);
    var inputValue = parseFloat($input.val().replace(/\s+/g,'').replace(',','.'));

    
    $checkbox.on('change.onhold', function(e) { e.stopPropagation(); });

    if (isNaN(inputValue)) {
      /** @todo fire error */
      console.log('NaN entered');
      $input.val('');
      $checkbox.prop('checked', false);
    } else {
      if (inputValue < 0) { inputValue = 0; }
      if (inputValue > maxInvoiceSum) {
        inputValue = maxInvoiceSum;
      }

      if (inputValue !== 0) {
        $input.val(inputValue.toMoney(2,',',' '));
        $checkbox.prop('checked', true);
      } else {
        $input.val('');
        $checkbox.prop('checked', false);
      }
    }

    $checkbox.off('change.onhold');

    var total = 0;

    $inputs.each(function() {
      var currentValue = parseFloat($(this).val().replace(/\s+/g,'').replace(',','.'));
      total += isNaN(currentValue) ? 0 : currentValue;
    });

    this.SetInvoiceForm.$content.find('.js-ore-set-invoice--total-invoice').text(total.toMoney(2,',',' '));
    $input.prop('disabled',false);
  };

  /** Отметить услугу для выставления счета */
  modView.prototype.markServiceForInvoice = function($serviceRow, defaultInvoiceSum) {
    var $inputs = this.SetInvoiceForm.$content.find('.js-ore-set-invoice--service-sum');
    var $input = $serviceRow.find('.js-ore-set-invoice--service-sum');

    $input.prop('disabled',true);

    if (defaultInvoiceSum !== 0) {
      $input.val(defaultInvoiceSum.toMoney(2,',',' '));
    } else {
      $input.val('');
    }

    var total = 0;
    $inputs.each(function() {
      var currentValue = parseFloat($(this).val().replace(/\s+/g,'').replace(',','.'));
      total += isNaN(currentValue) ? 0 : currentValue;
    });

    this.SetInvoiceForm.$content.find('.js-ore-set-invoice--total-invoice').text(total.toMoney(2,',',' '));
    $input.prop('disabled',false);
  };

  /** Сбор данных из формы выставления счета */
  modView.prototype.getInvoiceFormData = function() {
    var invServices = [];
    var hasZeroSum = false;

    this.SetInvoiceForm.$content
      .find('.js-ore-set-invoice--service-check:checked')
        .each(function() {
          var $row = $(this).closest('.js-ore-set-invoice--service');
          var sum = Number($row.find('.js-ore-set-invoice--service-sum').val().replace(/\s+/g,'').replace(',','.'));

          if (sum === 0) {
            hasZeroSum = true;
          } else if (sum > 0) {
            sum = sum.toFixed(2);
            invServices.push({
              'id':+$row.attr('data-serviceid'),
              'sum':sum
            });
          }
    });

    if (invServices.length === 0 && hasZeroSum) { return false; }
    else { return invServices; }
  };

  /** Очистка данных формы выставления счета */
  modView.prototype.flushSetInvoiceForm = function() {
    this.SetInvoiceForm.$content.html(Mustache.render(KT.tpl.spinner, {}));
  };

  /** Очистка шапки */
  modView.prototype.flushTopInfo = function() {
    /** @todo поменять на общий spinner */
    this.$headerInfo.html(Mustache.render(this.mds.tpl.headerSpinner));
  };

  return modView;
}));

(function(global,factory) {

    KT.crates.OrderEdit.controller = factory(KT.crates.OrderEdit);

}(this,function(crate) {
  /**
  * Редактирование заявки
  * @constructor
  * @param {Object} module - ссылка на модуль
  */
  var oeController = function(module) {
    /** Module storage - модуль со всеми его компонентами */
    this.mds = module;

    window.sessionStorage.removeItem('inOrderInfo');

    /** Получение номера запрашиваемой заявки из ссылки */
    try {
      this.orderId = window.location.pathname.match(/[^/]+$/)[0];
      if (this.orderId !== 'new') {
        this.orderId = +this.orderId;
        if (isNaN(this.orderId) || this.orderId <= 0) {
          this.orderId = null;
        }
      }
    } catch (e) {
      this.orderId = null;
    }

    this.mds.view = new crate.view(this.mds, this.orderId);
    this.mds.OrderStorage = new KT.storage.OrderStorage(this.orderId);

    if (this.orderId === 'new') {
      this.mds.view.setActiveTab('tourists');
      this.mds.tourists.controller = new crate.tourists.controller(this.mds, this.orderId);
      this.mds.view.$addServiceBlock.find('.iconed-link[data-srv]').addClass('disabled');
      
    } else if (this.orderId !== null) {
      this.mds.view.enableTab('tourists');
      this.mds.view.enableTab('payment');
      this.mds.view.setActiveTab('services');

      this.mds.payment.controller = new crate.payment.controller(this.mds, this.orderId);
      this.mds.tourists.controller = new crate.tourists.controller(this.mds, this.orderId);
      this.mds.services.controller = new crate.services.controller(this.mds, this.orderId);
    }
  };

  /** Инициализация событий */
  oeController.prototype.init = function() {
    var _instance = this;
    var modView = _instance.mds.view;

    /** Обработка смены валюты просмотра */
    KT.on('KT.changedViewCurrency', function() {
      //modView.flushSetInvoiceForm();
      if (_instance.orderId !== 'new') {
        modView.flushTopInfo();
        KT.apiClient.getOrderInfo(_instance.orderId)
          .done(function(response) {
            if (response.status !== 0) {
              window.location.assign('/cabinetUI/orders/order/' + _instance.orderId);
            } else {
              _instance.mds.OrderStorage.initialize(response.body);
            }
          });
      }
    });

    /** Обработка изменения значения переключателя нетто/брутто */
    KT.on('KT.changedViewPrice',function() {
      //modView.flushSetInvoiceForm();
      if (_instance.orderId !== 'new') {
        modView.flushTopInfo();
        KT.apiClient.getOrderInfo(_instance.orderId)
          .done(function(response) {
            if (response.status !== 0) {
              window.location.assign('/cabinetUI/orders/order/' + _instance.orderId);
            } else {
              _instance.mds.OrderStorage.initialize(response.body);
            }
          });
      }
    });

    /** Обработка запроса на переход по вкладке */
    KT.on('OrderEdit.setActiveTab', function(e, data) {
      if (data.activeTab !== undefined) {
        modView.setActiveTab(data.activeTab);
        if (typeof data.callback === 'function') {
          data.callback();
        }
      }
    });

    /** Обработка обновления информации по туристам */
    KT.on('OrderEdit.reloadInfo', function() {
      //modView.flushSetInvoiceForm();
      modView.flushTopInfo();
      _instance.mds.OrderStorage.loadStates.servicedata = 'pending';
      _instance.mds.OrderStorage.loadStates.transitionsdata = 'pending';
      console.warn('reload catched');

      KT.apiClient.getOrderInfo(_instance.orderId)
        .then(function(orderInfo) {
          if (orderInfo.status === 0) {
            _instance.mds.OrderStorage.initialize(orderInfo.body);
            var serviceIds = _instance.mds.OrderStorage.getServiceIds();
            return KT.apiClient.getOrderOffers(_instance.orderId, serviceIds);
          } else {
            return $.Deferred().reject();
          }
        })
        .then(function(offersData) {
          if (offersData.status === 0) {
            _instance.mds.OrderStorage.setServices(offersData.body);
            return KT.apiClient.getAllowedTransitions(_instance.orderId);
          } else {
            return $.Deferred().reject();
          }
        })
        .then(function(transitionsData) {
          console.warn('got transitions data');
          if (transitionsData.status === 0 ) {
            if (Array.isArray(transitionsData.body.services)) {
              _instance.mds.OrderStorage.setAllowedTransitions(transitionsData.body.services);
            } else {
              _instance.mds.OrderStorage.setAllowedTransitions([]);
            }
          } else {
            console.error('не удалось получить доступные действия');
          }
        });

      KT.apiClient.getOrderHistory(_instance.orderId)
        .done(function(response) {
          if (response.status !== 0) {
            if (response.errorCode === 8) {
              console.log('Для данной заявки нет истории');
            } else {
              KT.notify('loadingHistoryFailed', response.errorCode + ': ' + response.errors);
            }
          } else {
            _instance.mds.OrderStorage.setHistory(response.body);
          }
        });
    });

    /** Обработка запроса на обновление информации в шапке
    * @todo normalize event model
    */
    KT.on('OrderEdit.reloadHeader', function() {
      modView.flushTopInfo();
      modView.renderHeaderInfo(_instance.mds.OrderStorage);
    });

    /** Открытие формы выставления счетов */
    KT.on('OrderEdit.openSetInvoiceForm', function() {
      modView.SetInvoiceForm.wnd.open();
    });

    /*==========Обработчики событий модели============================*/

    /** Обработка инициализации хранилища данных заявки */
    KT.on('OrderStorage.initialized', function(e, OrderStorage) {
      modView.renderHeaderInfo(OrderStorage);
    });

    /** Обработка обновления документов заявки */
    KT.on('OrderStorage.setDocuments', function(e, documents) {
      modView.renderDocumentsForm(documents.items);
    });

    /** Обработка обновления истории заявки */
    KT.on('OrderStorage.setHistory', function(e, history) {
      modView.History.render(history.records);
    });

    /** Обработка обновления данных по услугам */
    KT.on('OrderStorage.setServices', function(e, OrderStorage) {
      modView.renderSetInvoiceForm(OrderStorage.getServices());
    });

    /** Обработка выписки билетов */
    /*
    KT.on('ApiClient.issuedTickets',function(e, data) {
      if (data.status === 0) {
        KT.apiClient.getOrderDocuments(_instance.mds.OrderStorage.orderId);
      }
    }); */

    /** Обработка отмены счета */
    KT.on('ApiClient.cancelledInvoice', function(e, response) {
      KT.Modal.closeModal();
      if (+response.status === 0) {
        modView.flushSetInvoiceForm();
        KT.notify('invoiceCancelled');
        KT.apiClient.getOrderOffers(_instance.orderId, _instance.mds.OrderStorage.getServiceIds());
        KT.apiClient.getOrderInvoices(_instance.orderId);
      } else {
        KT.notify('cancellingInvoiceFailed', response.errors);
      }
    });

    /** Обработка ответа сервера на установку скидки */
    /*
    KT.on('ApiClient.setDiscount', function(e, data) {
      if (data.status === 0) {
        KT.apiClient.getOrderInfo(_instance.orderId);
      }
    }); */

    /** Обработка получения информации о прогрессе загрузки документа */
    KT.on('ApiClient.documentUploadProgress',function(e, data) {
      modView.showDocUploadProgress( parseInt(data.percent) );
    });

    /** Обработка загрузки документа */
    KT.on('ApiClient.uploadedDocument',function(e, data) {
      if (data.error !== undefined) {
        KT.notify('uploadDocumentFailed');
      } else {
        if (data.status !== 0) {
          if (data.status === 2) {
            if (data.errorCode === 2) {
              KT.notify('uploadDocumentNotAllowedByFilesize');
            } else {
              KT.notify('uploadDocumentFailed', data.errors);
            }
          } else {
            KT.notify('uploadDocumentFailed', data.errors);
          }
        } else {
          KT.notify('documentUploaded');
          modView.flushDocumentsForm();
          KT.apiClient.getOrderDocuments(_instance.orderId);
        }
      }
    });

    /*==========Обработчики событий представления========================*/

    /** Изменение тогглера
    * @todo move to library
    */
    $('body').on('change','.simpletoggler input', function() {
      if ($(this).prop('checked')) {
        $(this).closest('.simpletoggler').addClass('active');
      } else {
        $(this).closest('.simpletoggler').removeClass('active');
      }
    });
    
    /** Открытие формы выставления счетов по клику на кнопку "Счета" в шапке */
    modView.$btnInvoices.on('click', function() {
      KT.dispatch('OrderEdit.openSetInvoiceForm');
    });

    /** Обработка изменения значения в поле ввода "К оплате" блока выставления счетов */
    modView.SetInvoiceForm.$content.on('change','.js-ore-set-invoice--service-sum', function() {
      var $serviceRow = $(this).closest('.js-ore-set-invoice--service');
      var serviceId = +$serviceRow .attr('data-serviceid');
      var Service = _instance.mds.OrderStorage.services[serviceId];
      modView.checkInvoiceFormInput($serviceRow, Service.unpaidSum);
    });

    /** Обработка выбора услуги (чекбокс) на форме выставления счетов */
    modView.SetInvoiceForm.$content.on('change','.js-ore-set-invoice--service-check', function() {
      var $serviceRow = $(this).closest('.js-ore-set-invoice--service');
      var serviceId = +$serviceRow.attr('data-serviceid');
      var Service = _instance.mds.OrderStorage.services[serviceId];
      if ($(this).prop('checked')) {
        modView.markServiceForInvoice($serviceRow, Service.unpaidSum);
      } else {
        modView.markServiceForInvoice($serviceRow, 0);
      }
    });

    /** Обработка нажатия кнопки "Выставить счет" в форме выставления счета */
    modView.SetInvoiceForm.$content.on('click','.js-ore-set-invoice--action-set', function() {
      var invoices = modView.getInvoiceFormData();

      if (invoices === false) {
        KT.Modal.notify({
          high: true,
          type:'info',
          title:'Выставление счета',
          msg:'<p>Нельзя выставить нулевой счет</p>',
          buttons:{
            title:'ok'
          }
        });
      } else if (invoices.length === 0) {
        KT.Modal.notify({
          high: true,
          type:'info',
          title:'Выставление счета',
          msg:'Вы не выбрали ни одной услуги для выставления счета!',
          buttons:{
            title:'ok'
          }
        });
      } else {
        KT.Modal.showLoader();

        KT.apiClient.setInvoice(_instance.mds.OrderStorage, invoices)
          .done(function(response) {
            if (response.status === 0) {
              modView.flushSetInvoiceForm();

              KT.apiClient.getOrderOffers(_instance.orderId, _instance.mds.OrderStorage.getServiceIds())
                .done(function(offersData) {
                  if (offersData.status !== 0) {
                    console.error('offer load error: ' + offersData.error);
                  } else {
                    _instance.mds.OrderStorage.setServices(offersData.body);
                  }
                });

              KT.apiClient.getOrderInvoices(_instance.orderId)
                .done(function(invoiceData) {
                  var invoices = (invoiceData.body.Invoices !== undefined && Array.isArray(invoiceData.body.Invoices)) ?
                      invoiceData.body.Invoices : [];
                  _instance.mds.OrderStorage.setInvoices(invoices);
                });

              KT.Modal.notify({
                high: true,
                type: 'success',
                title: 'Выставление счета',
                msg: 'Счет успешно выставлен!',
                buttons: {type:'success', title:'ok'}
              });
            } else {
              KT.Modal.notify({
                high: true,
                type: 'error',
                title: 'Выставление счета',
                msg: 'Не удалось выставить счет<br>Код ошибки: ' + response.errors,
                buttons: {type:'error', title:'ok'}
              });
            }
          })
          .fail(function(err) {
            if (err.error !== 'denied') {
              KT.Modal.closeModal();
            }
          });
      }
    });

    /** Обработка нажатия кнопки "Отмена" в форме выставления счета */
    modView.SetInvoiceForm.$content.on('click','.js-ore-set-invoice--action-cancel', function() {
      modView.SetInvoiceForm.wnd.close();
    });

    /** Обработка выбора документа для загрузки */
    modView.documents.$content.on('change','.js-ore-documents--upload-field',function() {
      var $lbl = modView.documents.$content.find('.js-ore-documents--upload-label');
      var file;

      if ($(this)[0].files.length > 0) {
        file = $(this)[0].files[0].name;
      }
      if (file !== undefined) {
        $lbl.text(file);
      } else {
        $lbl.text('Добавить документ');
      }
    });

    /** Обработка нажатия кнопки "отмена" формы загрузки документов */
    modView.documents.$content.on('click', '.js-ore-documents--upload-decline', function(){
      var $form = modView.documents.$content.find('.js-ore-documents--upload');
      $form[0].reset();
      $form
        .find('.js-ore-documents--upload-field').change()
        .end()
        .find('.js-ore-documents--upload-control').show()
        .end()
        .find('.js-ore-documents--upload-progress').hide()
          .find('js-ore-documents--upload-progress-text').text('0 %')
          .end()
          .find('.js-ore-documents--upload-progress-bar')
            .css({'width':'0%'})
            .removeClass('active')
        .end();
    });

    /** Блокировка подтверждения формы загрузки файла при сабмите формы */
    modView.documents.$content.on('submit', '.js-ore-documents--upload', function(e) {
      e.preventDefault();
    });

    /** Обработка подтвержения формы загрузки файла кликом на кнопку  */
    modView.documents.$content.on('click', '.js-ore-documents--upload-confirm', function() {
      var $docform = modView.documents.$content.find('.js-ore-documents--upload');
      if ($docform.find('.js-ore-documents--upload-field').val() !== '') {
        modView.documents.$content.find('.js-ore-documents--upload-control').hide();
        modView.documents.$content.find('.js-ore-documents--upload-progress')
          .find('.js-ore-documents--upload-progress-bar')
            .css({'width':'0%'})
            .addClass('active')
          .end()
          .show();
        KT.apiClient.uploadDocument(_instance.orderId, $docform)
          .done(function(response) {
            if (response.status !== 0) {
              KT.notify('loadingDocumentsFailed', response.errorCode + ': ' + response.errors);
            } else {
              _instance.mds.OrderStorage.setDocuments(response.body);
            }
          });
      }
    });

    /** Обработка нажатия на кнопки добавления услуг */
    modView.$addServiceBlock.on('click', '.iconed-link[data-srv]:not(.disabled)', function() {
      if (!_instance.mds.OrderStorage.isOffline) {
        var serviceType = $(this).attr('data-srv');
        window.sessionStorage.setItem('inOrderInfo', JSON.stringify(_instance.mds.OrderStorage));

        switch(serviceType) {
          case 'flight':
            window.location.assign('/UI/searchAvia/index');
            break;
          case 'accommodation':
            window.location.assign('/UI/searchHotel/index');
            break;
          default:
            window.sessionStorage.removeItem('inOrderInfo');
            break;
        }
      }
    });
  };

  /** Инициализация модуля управления заявкой */
  oeController.prototype.load = function() {
    var _instance = this;

    //==== сбор данных по требуемым шаблонам
    $.extend(_instance.mds.view.config.templates, _instance.mds.payment.view.config.templates);
    $.extend(_instance.mds.view.config.templates, _instance.mds.tourists.view.config.templates);
    $.extend(_instance.mds.view.config.templates, _instance.mds.services.view.config.templates);

    //==== инициализация контроллеров субмодулей
    _instance.mds.payment.controller.init();
    _instance.mds.tourists.controller.init();
    _instance.mds.services.controller.init();

    //==== загрузка шаблонов
    var templates = KT.getTemplates(
        _instance.mds.view.config.templateUrl,
        _instance.mds.view.config.templates
      );

    //==== обработка шаблонов
    templates
      .then(function(templates) {
        _instance.mds.tpl = templates;
        return KT.apiClient.getOrderInfo(_instance.orderId);
      })
      .then(function(orderInfo) {
        if (orderInfo.status !== 0 || orderInfo.body.orderId === undefined) {
          console.error('Заявка ' + _instance.orderId + ' не найдена');
          window.location.assign('/cabinetUI/orders/index');
        }

        _instance.mds.OrderStorage.initialize(orderInfo.body);
        if (_instance.orderId === 'new') { 
          _instance.mds.OrderStorage.setServices([]); // надо ли? это всего лишь для сброса крутилок
          return $.Deferred().reject(); 
        } else {
          return;
        }
      })
      .then(function() {
        var serviceIds = _instance.mds.OrderStorage.getServiceIds();
        if (serviceIds.length > 0) {
          KT.apiClient.getOrderOffers(_instance.orderId, serviceIds)
            .then(function(offersData) {
              if (offersData.status === 0) {
                _instance.mds.OrderStorage.setServices(offersData.body);
                return KT.apiClient.getAllowedTransitions(_instance.orderId);
              } else {
                return $.Deferred().reject();
              }
            })
            .then(function(transitionsData) {
              if (transitionsData.status === 0 ) {
                if (Array.isArray(transitionsData.body.services)) {
                  _instance.mds.OrderStorage.setAllowedTransitions(transitionsData.body.services);
                } else {
                  console.warn('список доступных действий пуст');
                  _instance.mds.OrderStorage.setAllowedTransitions([]);
                }
              } else {
                console.error('не удалось получить доступные действия');
              }
            });
        } else {
          _instance.mds.OrderStorage.setServices([]);
        }

        KT.apiClient.getOrderTourists(_instance.orderId)
          .done(function(response) {
            var tourists = (response.body.tourists !== undefined) ?
              response.body.tourists : [];
            _instance.mds.OrderStorage.setTourists(tourists);
          });
        
        KT.apiClient.getOrderInvoices(_instance.orderId)
          .done(function(response) {
            var invoices = (response.body.Invoices !== undefined && Array.isArray(response.body.Invoices)) ?
                response.body.Invoices : [];
            _instance.mds.OrderStorage.setInvoices(invoices);
          });

        KT.apiClient.getOrderHistory(_instance.orderId)
          .done(function(response) {
            if (response.status !== 0) {
              if (response.errorCode === 8) {
                console.log('Для данной заявки нет истории');
              } else {
                KT.notify('loadingHistoryFailed', response.errorCode + ': ' + response.errors);
              }
            } else {
              _instance.mds.OrderStorage.setHistory(response.body);
            }
          });
             
        KT.apiClient.getOrderDocuments(_instance.orderId)
          .done(function(response) {
            if (response.status !== 0) {
              KT.notify('loadingDocumentsFailed', response.errorCode + ': ' + response.errors);
            } else {
              _instance.mds.OrderStorage.setDocuments(response.body);
            }
          });
      });
  };

  /** Загрузка необходимых данных для интерфейса создания новой заявки */
  oeController.prototype.loadBare = function() {
    var _instance = this;

    $.extend(_instance.mds.view.config.templates, _instance.mds.tourists.view.config.templates);

    _instance.mds.tourists.controller.init();

    var clientId = window.sessionStorage.getItem('clientId');
    var contractId = window.sessionStorage.getItem('contractId');

    if (clientId === null) { 
      clientId = KT.getStoredSettings().companyId;
    }

    /** Обработка загрузки шаблонов модуля */
    var templates = KT.getTemplates(
        _instance.mds.view.config.templateUrl,
        _instance.mds.view.config.templates
      );
    
    var companyInfo = KT.Dictionary.getAsList('companies', {
        'companyId': +clientId,
        'fieldsFilter': [],
        'lang': 'ru'
      });

    templates.done(function(templates) {
      _instance.mds.tpl = templates;
    });

    companyInfo.fail(function() {
      console.error('Ошибка получения данных клиента');
    });

    $.when(templates, companyInfo)
      .done(function(tpl, companyInfo) {
        if (companyInfo.length !== 0) {
          var clientData = companyInfo[0];

          _instance.mds.OrderStorage.initializeBare({
            'clientId': +clientId,
            'clientType': clientData.companyRoleType,
            'clientName': clientData.name,
            'contractId': contractId
          });
        } else {
          console.error('Ошибка получения данных клиента');
        }
      });
  };

  return oeController;
}));

(function() {
  KT.on('KT.initializedCore', function() {
    /** Инициализация модуля */
    KT.mdx.OrderEdit.controller = new KT.crates.OrderEdit.controller(KT.mdx.OrderEdit);
    KT.mdx.OrderEdit.controller.init();

    if (KT.mdx.OrderEdit.controller.orderId === 'new') {
      KT.mdx.OrderEdit.controller.loadBare();
    } else if (KT.mdx.OrderEdit.controller.orderId !== null) {
      KT.mdx.OrderEdit.controller.load();
    } else {
      window.location.assign('/cabinetUI/orders/index');
    }
  });
}());

//# sourceMappingURL=cui-orderEdit.js.map