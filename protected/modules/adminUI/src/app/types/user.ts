import * as moment from 'moment';
import 'moment/locale/ru';

moment.locale('ru');

export interface IUser {
  userId: number;
  clientId: number;
  name: string;
  surname: string;
  secondName: string;
  prefix?: string;
  citizenshipId?: number;
  birthDate?: string;
  contactPhone?: string;
  email?: string;
};

export interface ISetUser {
  userId?: number,
  clientId: number,
  sex: number,
  firstName: string,
  middleName?: string,
  lastName: string,
  birthdate?: string,
  сontactPhone?: string,
  email?: string,
};

export class User {
  id: number;
  companyId: number;
  firstName: string;
  middleName: string;
  lastName: string;
  sex: number;
  citizenshipId: number;
  birthdate: moment.Moment;
  phone: string;
  email: string;

  constructor (user: IUser) {
    this.id = user.userId;
    this.companyId = user.clientId;
    this.firstName = user.name;
    this.middleName = user.secondName;
    this.lastName = user.surname;
    this.sex = (user.prefix !== undefined) ? (user.prefix === 'Mr' ? 1 : 0) : null;
    this.citizenshipId = user.citizenshipId;
    this.birthdate = (user.birthDate) ? moment(user.birthDate, 'YYYY-MM-DD') : null;
    this.phone = user.contactPhone;
    this.email = user.email;
  }

  get fullName(): string {
    return [this.lastName, this.firstName, this.middleName].join(' ');
  }
}
