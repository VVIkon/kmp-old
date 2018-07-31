import { NgModule, ModuleWithProviders } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule }   from '@angular/forms';
import { ApiClientService } from './services/api-client.service';
import { ProfileService } from './services/profile.service';
import { KtModalService } from './services/kt-modal.service';
import { LoginFormComponent } from './components/login-form.component';
import { KtModalComponent } from './components/kt-modal.component';

@NgModule({
  imports: [ CommonModule, FormsModule ],
  declarations: [ LoginFormComponent, KtModalComponent ],
  exports: [ LoginFormComponent, KtModalComponent ]
})
export class CoreModule {
  static forRoot(): ModuleWithProviders {
    return {
      ngModule: CoreModule,
      providers: [ ProfileService, ApiClientService, KtModalService ]
    };
  }
}
