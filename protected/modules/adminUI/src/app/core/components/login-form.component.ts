import { Component } from '@angular/core';

import { ApiClientService } from '../services/api-client.service';
import { ProfileService } from '../services/profile.service';
import { ApiErrors } from '../types';

interface Warning {
  text: string
};

@Component({
  selector: 'login-form',
  host: {
    '[class.login-form-overlay]': 'true',
    '[class.is-active]': 'authNeeded'
  },
  templateUrl: '../tpl/login-form.html'
})
export class LoginFormComponent {
  private authNeeded = false;
  private login = '';
  private password = '';
  private warnings: Warning[] = [];

  constructor(private apiClient: ApiClientService, private profileService: ProfileService) {
    this.apiClient.restErrors.subscribe((error) => {
      if (error === ApiErrors.TokenExpired) {
        console.warn('token expired');
        this.authNeeded = true;
      }
    });
  }

  ngOnInit(): void { }

  onSubmit() {
    this.warnings = [];

    if (this.login === '') {
      this.warnings.push({text: 'Пустой логин'});
    }
    if (this.password === '') {
      this.warnings.push({text: 'Пустой пароль'});
    }

    if (this.warnings.length !== 0) { return; }

    this.apiClient.request('adminUI/admin/userAuth', {
        'login': this.login, 'password': this.password
      })
      .subscribe((response) => {
        if (response.status === 0) {
          this.profileService.generateProfile(response.body);
          window.location.reload();
        } else {
          switch (+response.errorCode) {
            case 2:
              this.warnings.push({text: 'Неверный логин'});
              break;
            case 3:
              this.warnings.push({text: 'Неверный пароль'});
              break;
            default:
              this.warnings.push({text: response.errors});
              break;
          }
        }
      });
  }
}
