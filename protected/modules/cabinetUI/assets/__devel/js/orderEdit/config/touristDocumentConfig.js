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