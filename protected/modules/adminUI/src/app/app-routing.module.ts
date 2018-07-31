import { NgModule }             from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { DashboardComponent } from './Dashboard/dashboard.component';
import { UsersComponent } from './Users/users.component';
import { UserDetailComponent } from './Users/user-detail.component';
import { UserRolesComponent } from './UserRoles/user-roles.component';
import { UserRoleDetailComponent } from './UserRoles/user-role-detail.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'users', component: UsersComponent },
  { path: 'user/:id', component: UserDetailComponent },
  { path: 'user-roles', component: UserRolesComponent },
  { path: 'user-role/:id', component: UserRoleDetailComponent }
];

@NgModule({
  imports: [ RouterModule.forRoot(routes) ],
  exports: [ RouterModule ]
})
export class AppRoutingModule {}
