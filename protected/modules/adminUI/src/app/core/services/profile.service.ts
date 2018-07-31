import { Injectable } from '@angular/core';
import { Observable } from 'rxjs/Observable';

import { Profile } from  './interfaces/profile';
import { RoleType } from '../types/role-type';

@Injectable()
export class ProfileService {
  profile: Profile;

  constructor() {
    let profileData = localStorage.getItem('KTUserSettings');

    if (profileData === null) {
      window.location.assign('http://' + window.location.host);
    } else {
      this.profile = JSON.parse(profileData);
    }
  }

  generateProfile(data: {[k: string]: any}) {
    this.profile = {
      companyId: +data.companyID,
      companyName: data.companyName,
      user: {
        id: +data.userId,
        firstName: data.userName,
        lastName: data.userLastName,
        middleName: data.userMName
      },
      userType: (function(type) {
        switch (type) {
          case RoleType.Operator: return 'op';
          case RoleType.Agent: return 'agent';
          case RoleType.Corporate: return 'corp';
          default: throw new Error('unknown user type');
        }
      }(+data.userType)),
      userCommission: (data.commission !== '') ? parseFloat(data.commission) : null,
      localCurrency: 'RUB',
      userToken: data.token,
      subscribedForChat: Boolean(data.subscribeChat),
      searchAccess: {
        avia: data.aviaaccess,
        hotel: data.hotelaccess,
        train: data.trainaccess,
        transfer: data.transferaccess
      }
    };
    localStorage.setItem('KTUserSettings', JSON.stringify(this.profile));
  }

}
