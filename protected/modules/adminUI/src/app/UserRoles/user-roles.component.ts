import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';

import { UserRole } from '../types/user-role';
import { Permission } from '../types/permission';
import { UserRolesService } from '../services/api/user-roles.service';

@Component({
  selector: 'user-roles',
  templateUrl: './tpl/user-roles.html',
  providers: [UserRolesService]
})
export class UserRolesComponent implements OnInit {
  userRoles: UserRole[];
  permissions: Permission[];

  constructor(
    private router: Router,
    private userRolesService: UserRolesService
  ) { }

  ngOnInit(): void {
    this.userRolesService.getRoles()
      .subscribe((userRoles: UserRole[]) => {
        this.userRoles = userRoles;
      });
  }

  onSelect(userRole: UserRole) {
    this.router.navigate(['/user-role', userRole.id])
  }
 }
