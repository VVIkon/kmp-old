import { Injectable } from '@angular/core';
import { Headers, Http, RequestOptions } from '@angular/http';
import { Observable } from 'rxjs/Observable';
import { Observer } from 'rxjs/Observer';
import 'rxjs/add/operator/catch';
import 'rxjs/add/operator/map';

import { ApiClientService } from '../../core/services/api-client.service';
import { Company } from '../../types/company';
import { User, IUser, ISetUser } from '../../types/user';
import { ISetDocument } from '../../types/document';
import { UserProfile, IUserProfile } from '../../types/user-profile';

@Injectable()
export class UsersService {

  constructor(private apiClient: ApiClientService) {}

  getCompanyUsers(company: Company): Observable<User[]> {
    if (!company) {
      return Observable.of([]);
    }

    let payload = {
      'companyId': company.id,
      'onlyChatSubscribers': false
    };

    return this.apiClient.request('adminUI/admin/getUserSuggest', payload)
      .map((response) => {
        if (response.status === 0) {
          return response.body.map(
            (user: IUser): User => new User(user)
          );
        } else { throw new Error(response.errors); }
      })
      .catch((err) => {
        console.error('An error occurred', err);
        return Observable.throw(false);
      });
  }

  getUserProfile(userId: number): Observable<UserProfile> {
    let payload = {
      'userId': userId
    };

    return this.apiClient.request('adminUI/admin/getUser', payload)
      .map((response) => {
        if (response.status === 0) {
          return new UserProfile(response.body);
        } else { throw new Error(response.errors); }
      })
      .catch((err) => {
        console.error('An error occurred: ', err);
        return Observable.throw(false);
      });
  }

  setUserRole(user: UserProfile, roleId: number): Observable<any> {
    let payload = {
      'userId': user.id,
      'roleType': user.roleType,
      'roleId': roleId
    };

    return this.apiClient.request('adminUI/admin/setUserRole', payload)
      .map((response) => {
        if (response.status === 0) {
          return response;
        } else { throw new Error(response.errors); }
      })
      .catch((err) => {
        console.error('An error occurred: ', err);
        return Observable.throw(false);
      });
  }

  setUser(user: {user: ISetUser, document: ISetDocument}): Observable<any> {
    let payload = user;

    return this.apiClient.request('adminUI/admin/setUser', payload)
      .map((response) => {
        if (response.status === 0) {
          return response;
        } else { throw new Error(response.errors); }
      })
      .catch((err) => {
        console.error('An error occurred: ', err);
        return Observable.throw(err);
      });
  }

}
