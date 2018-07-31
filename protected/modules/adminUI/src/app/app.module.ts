// angular modules
import { NgModule, APP_INITIALIZER }      from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { HttpModule }    from '@angular/http';
import { FormsModule }   from '@angular/forms';

// core application files
import { AppComponent }  from './app.component';
import { AppRoutingModule } from './app-routing.module';
import { CoreModule } from './core/core.module';
import { StartupService } from './core/services/startup.service';

// directives
import { KtIconDirective } from './directives/KtIconDirective/kt-icon.directive';

// widgets
import { SelectWidget } from './widgets/SelectWidget/select.widget';
import { FileLoaderWidget } from './widgets/FileLoaderWidget/file-loader.widget';

// pipes
import { EvenOddPipe } from './pipes/even-odd.pipe';
import { ColumnSortPipe } from './pipes/column-sort.pipe';
import { SubstrPipe } from './pipes/substr.pipe';

// application components
import { HeaderPanelComponent } from './HeaderPanel/header-panel.component';
import { DashboardComponent } from './Dashboard/dashboard.component';

// application pages
import { UsersComponent } from './Users/users.component';
import { UserDetailComponent } from './Users/user-detail.component';
import { UserRolesComponent } from './UserRoles/user-roles.component';
import { UserRoleDetailComponent } from './UserRoles/user-role-detail.component';

export function checkAccess(startupService: StartupService): Function {
  return () => startupService.load();
}

@NgModule({
  imports: [
    BrowserModule,
    HttpModule,
    FormsModule,
    AppRoutingModule,
    CoreModule.forRoot()
  ],
  declarations: [
    AppComponent,
    // widgets
    SelectWidget,
    FileLoaderWidget,
    // directives
    KtIconDirective,
    // pipes
    EvenOddPipe,
    ColumnSortPipe,
    SubstrPipe,
    // main components
    HeaderPanelComponent,
    DashboardComponent,
    UsersComponent,
    UserDetailComponent,
    UserRolesComponent,
    UserRoleDetailComponent
  ],
  providers: [
    StartupService,
    {
      provide: APP_INITIALIZER,
      useFactory: checkAccess,
      deps: [StartupService],
      multi: true
    }
  ],
  bootstrap: [ AppComponent ]
})

export class AppModule { }
