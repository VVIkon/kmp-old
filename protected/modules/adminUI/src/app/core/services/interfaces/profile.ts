export interface Profile {
  // ID комании пользователя
  companyId: number;
  // навзвание компании пользователя
  companyName: string;
  // данные пользователя
  user: {
    id: number,
    firstName?: string,
    lastName: string,
    middleName?: string,
  };
  // тип пользователя
  userType: string;
  // токен пользовательской авторизации
  userToken: string;
  // процент комиссии пользователя
  userCommission?: number;
  // локальная валюта пользователя
  localCurrency: string;
  // признак подписки на чат
  subscribedForChat: boolean;
  // доступ к поиску
  searchAccess: {
    avia: boolean,
    hotel: boolean,
    train: boolean,
    transfer: boolean
  }
};
