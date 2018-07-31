export interface ISetDocument {
  userDocId?: number,
  docType: number,              // Тип документа из справочника типов документов kt_toursts_doc_type
  firstName: string,       // Имя
  middleName?: string,          // Отчество
  lastName: string,     // Фамилия
  docSerial?: string,        // Серия документа
  docNumber: string,     // номер документа
  docExpiryDate?: string, // Дата окончания действия
  citizenship: string,
};
