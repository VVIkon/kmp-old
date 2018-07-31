import { RoleType } from '../core/types';

export interface IUserProfile {
    userId: number,
    userName: string,
    userLastName: string,
    userMName: string,
    userType: number,
    roleType: number;
    roleId: number;
    userEmail: string,
    companyID: number,
    companyType: number,
    companyName: string,
    companyTypeDesc: string,
    contractExpiry: string,
    subscribeChat: number,
    aviaaccess: boolean,
    hotelaccess: boolean,
    trainaccess: boolean,
    transferaccess: boolean
};

export class UserProfile {
  id: number;
  firstName: string;
  middleName: string;
  lastName: string;
  roleType: RoleType;
  roleId: number;
  email: string;
  companyId: number;
  companyName: string;
  contractExpiry: string;
  chatSubsription: number;
  searchAccess: {
    avia: boolean,
    hotel: boolean,
    train: boolean,
    transfer: boolean
  };

  constructor (user: IUserProfile) {
    this.id = user.userId;
    this.firstName = user.userName;
    this.middleName = user.userMName;
    this.lastName = user.userLastName;
    this.roleType = user.roleType;
    this.roleId = user.roleId;
    this.email = user.userEmail;
    this.companyId = user.companyID;
    this.companyName = user.companyName;
    this.contractExpiry = user.contractExpiry;
    this.chatSubsription = user.subscribeChat;
    this.searchAccess = {
      avia: user.aviaaccess,
      hotel: user.hotelaccess,
      train: user.trainaccess,
      transfer: user.transferaccess
    };
  }

  get fullName(): string {
    return [this.lastName, this.firstName, this.middleName].join(' ');
  }
}
