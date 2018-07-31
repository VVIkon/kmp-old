import * as moment from 'moment';
import 'moment/locale/ru';

moment.locale('ru');

export interface IUserFieldOption {
  name: string,
  field: string,
  format: null | string,
  formatter?: Function
};

/*================================
 * Функции форматирования данных
 *=================================*/
let dateFormatter = function(val: string) {
  if (!val) { return null; }
  return moment(val).format('YYYY-MM-DD');
};

let sexFormatter = function(val: string) {
  if (!val) { return 0; }
  if (/^(м|m).*$/i.test(val) || val === '1') {
    return 1;
  } else {
    return 0;
  }
};

let valuesMapFormatter = function(val: string) {
  if (!val) { return null; }
  let valuesMap = {};
  this.format.split('|').reduce(function(vmap: {}, item: string) {
    let kv = item.split('=>');
    vmap[kv[0]] = kv[1];
    return vmap;
  }, valuesMap);
  return valuesMap[val];
};

let noSpaceFormatter = function(val: string) {
  if (!val) { return null; }
  return String(val).replace(/\s/, '');
};


/**
 * Управление маппингом полей пользователя из массива (ex. при импорте из excel)
 */
export class UserFieldsMapper {
  // список полей структур пользователя и документа с базовыми настройками
  public fieldOptions: [IUserFieldOption] = [
    {name: 'Имя', field: 'user.firstName', format: null},
    {name: 'Фамилия', field: 'user.lastName', format: null},
    {name: 'Отчество', field: 'user.middleName', format: null},
    {name: 'Дата рождения', field: 'user.birthdate', format: null, formatter: dateFormatter},
    {name: 'E-mail', field: 'user.email', format: null},
    {name: 'Пол', field: 'user.sex', format: null, formatter: sexFormatter},
    {name: 'Гражданство', field: 'document.citizenship', format: null},
    {name: 'Тип документа', field: 'document.docType',
      format: 'Паспорт РФ=>1|Паспорт иностранного гражданина=>18',
      formatter: valuesMapFormatter
    },
    // {name: 'Дата выдачи', field: 'document.name', format: null},
    {name: 'Срок действия', field: 'document.docExpiryDate', format: null, formatter: dateFormatter},
    {name: 'Серия', field: 'document.docSerial', format: null, formatter: noSpaceFormatter},
    {name: 'Номер', field: 'document.docNumber', format: null, formatter: noSpaceFormatter},
    // {name: 'Место выдачи', field: 'document.name', format: null}
  ];

  private fieldOptionsMapper: {[k: string]: IUserFieldOption} = {};

  public fieldsMap: Array<{header: string, linkedField: string}>;

  constructor(headers: Array<string>) {
    this.fieldOptions.map((option: IUserFieldOption) => {
      this.fieldOptionsMapper[option.field] = option;
    });

    // соответствие полей по умолчанию
    let offset = 0;
    this.fieldsMap = headers.map((header, i) => {
      if (i === 0) {
        offset++;
        return {header: header, linkedField: null};
      } else if (i === 9) {
        offset++;
        return {header: header, linkedField: null};
      } else if (this.fieldOptions[i - offset] !== undefined) {
        return {header: header, linkedField: this.fieldOptions[i - offset].field};
      } else {
        return {header: header, linkedField: null}
      }
    });
  }

  processValue(val: string, field: string): any {
    let fieldOption = this.fieldOptionsMapper[field];
    return (typeof fieldOption.formatter === 'function') ? fieldOption.formatter(val) : val;
  }
}
