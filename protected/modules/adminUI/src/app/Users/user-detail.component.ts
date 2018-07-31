import 'rxjs/add/operator/switchMap';

import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Params } from '@angular/router';
import { Location } from '@angular/common';

import { UserProfile } from '../types/user-profile';
import { UserRole } from '../types/user-role';
import { Permission } from '../types/permission';
import { UserRolesService } from '../services/api/user-roles.service';
import { UsersService } from '../services/api/users.service';

@Component({
  selector: 'user-detail',
  templateUrl: './tpl/user-detail.html',
  providers: [UsersService, UserRolesService]
})
export class UserDetailComponent implements OnInit {
  user: UserProfile;
  userRoles: UserRole[];
  rolePermissions: Permission[];
  rolesSelectorState = 'loading';
  currentRole: UserRole;
  selectedRole: UserRole;

  constructor(
    private usersService: UsersService,
    private userRolesService: UserRolesService,
    private route: ActivatedRoute,
    private location: Location
  ) { }

  ngOnInit(): void {
    this.route.params.subscribe((params: Params) => {
      this.usersService.getUserProfile(+params['id'])
        .subscribe((user: UserProfile) => {
          this.user = user;
          this.userRolesService.getRole(user.roleId)
            .subscribe((role: UserRole) => {
              this.currentRole = role;
            });
        });
    });

    this.userRolesService.getRoles()
      .subscribe((userRoles: UserRole[]) => {
        this.userRoles = userRoles;
        this.rolesSelectorState = 'default';
      });
  }

  /**
   * Обработка выбора роли пользователя из списка
   * @param role - выбранная роль
   */
  onUserRoleSelect(role: UserRole): void {
    this.selectedRole = role;
  }

  /**
   * Обработка подтверждения смены роли пользователя
   */
  onSubmitRoleChange(): void {
    if (this.selectedRole.id !== this.user.roleId) {
      this.usersService.setUserRole(this.user, this.selectedRole.id)
        .subscribe((response: any) => {
          this.user.roleId = this.selectedRole.id;
          this.userRolesService.getRole(this.user.roleId)
            .subscribe((role: UserRole) => {
              this.currentRole = role;
            });
        });
    }
  }

  goBack(): void {
    this.location.back();
  }
 }
