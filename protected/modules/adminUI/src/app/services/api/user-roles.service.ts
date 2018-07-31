import { Injectable } from '@angular/core';
import { Headers, Http, RequestOptions } from '@angular/http';
import { Observable } from 'rxjs/Observable';
import { Observer } from 'rxjs/Observer';
import 'rxjs/add/operator/catch';
import 'rxjs/add/operator/map';

import { ApiClientService } from '../../core/services/api-client.service';
import { UserRole, IUserRole } from '../../types/user-role';
import { Permission, IPermission } from '../../types/permission';

@Injectable()
export class UserRolesService {
  userRoles: UserRole[];
  permissions: Permission[];

  constructor(private apiClient: ApiClientService) {}

  loadRolesAndPermissions(): Observable<Boolean> {
    let payload = {
      'dictionaryType': 22
    };

    return this.apiClient.request('adminUI/admin/getRolesAndPermissions', payload)
      .map((response) => {
        if (response.status === 0) {
          this.userRoles = response.body.roles.map(
            (role: IUserRole): UserRole => new UserRole(role)
          );
          this.permissions = response.body.permissions.map(
            (permission: IPermission): Permission => new Permission(permission)
          );
          return true;
        } else { throw new Error(response.errors); }
      })
      .catch((err) => {
        console.error('Failed to get dictionary: ', err);
        return Observable.of(false);
      });
  }

  getRoles(): Observable<UserRole[]> {
    return (this.userRoles !== undefined) ?
      Observable.of(this.userRoles) :
      this.loadRolesAndPermissions()
        .flatMap((result: boolean): Observable<UserRole[]> => {
          return Observable.of(this.userRoles);
        });
  }

  getRole(roleId: number): Observable<UserRole> {
    return this.getRoles()
      .flatMap((userRoles: UserRole[]): Observable<UserRole> => {
        let targetRole = this.userRoles.find((userRole: UserRole) => userRole.id === roleId);
        return (targetRole !== undefined) ?
          Observable.of(targetRole) :
          Observable.throw('no such role');
      });
  }

  getUserPermissionsWithMask(hexmask: string): Permission[] {
    /* tslint:disable: no-bitwise */
    console.log('string mask: ' + String(hexmask));
    let maskHalfbytes = String(hexmask).split('').reverse().map(function(halfbyte) {
      return parseInt(halfbyte, 16);
    });

    return this.permissions.map((permission: Permission): Permission => {
      let userPermission = new Permission(permission.toStruct());

      let halfByteIndex = Math.floor(permission.bit / 4);
      let checker = 1 << (permission.bit % 4);
      if ((checker & maskHalfbytes[halfByteIndex]) === checker) {
        userPermission.allow();
      }

      return userPermission;
    });

    /* tslint:enable: no-bitwise */
  };
}
