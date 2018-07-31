import { Injectable } from '@angular/core';
import { Http, Response } from '@angular/http';
import { Observable } from 'rxjs';

import 'rxjs/add/operator/map';
import 'rxjs/add/operator/toPromise';
import 'rxjs/add/operator/catch';

import { ApiClientService } from './api-client.service';

@Injectable()
export class StartupService {

    constructor(private apiClient: ApiClientService) { }

    // This is the method you want to call at bootstrap
    // Important: It should return a Promise
    load(): Promise<any> {
      return this.apiClient.request('adminUI/admin/checkUserAccess', {'permissions': [0]})
        .map((response) => {
          console.log('api client response: ');
          console.log(response);
          if (+response.status === 0 && response.body.hasAccess) {
            return true;
          } else {
            // если не очистить, ангуляр все равно продолжает грузить интерфейс =(
            document.body.innerHTML = '';
            window.location.assign(window.location.origin);
          }
        })
        .toPromise()
        .catch((err) => {
          window.location.assign(window.location.origin);
        });
    }
}
