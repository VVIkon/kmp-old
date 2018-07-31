import 'rxjs/add/operator/switchMap';

import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Params } from '@angular/router';
import { Location } from '@angular/common';

import { UserRole } from '../types/user-role';
import { Permission } from '../types/permission';
import { UserRolesService } from '../services/api/user-roles.service';

@Component({
  selector: 'user-role-detail',
  templateUrl: './tpl/user-role-detail.html',
  providers: [UserRolesService]
})
export class UserRoleDetailComponent implements OnInit {
  userRole: UserRole;
  permissions: Permission[];

  constructor(
    private userRolesService: UserRolesService,
    private route: ActivatedRoute,
    private location: Location
  ) { }

  ngOnInit(): void {
    this.route.params.subscribe((params: Params) => {
      this.userRolesService.getRole(+params['id'])
        .subscribe((userRole: UserRole) => {
          this.userRole = userRole;
          this.permissions = this.userRolesService.getUserPermissionsWithMask(this.userRole.hexmask);
        });
    });
  }

  goBack(): void {
    this.location.back();
  }
 }
