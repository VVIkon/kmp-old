import { Injectable } from '@angular/core';
import { Headers, Http, RequestOptions } from '@angular/http';
import { Observable } from 'rxjs/Observable';
import { Observer } from 'rxjs/Observer';
import 'rxjs/add/operator/catch';
import 'rxjs/add/operator/map';

import { ApiClientService } from '../../core/services/api-client.service';
import { Company, ICompany } from '../../types/company';

@Injectable()
export class CompaniesService {

  constructor(private apiClient: ApiClientService) {}

  getCompaniesByPattern(pattern: string): Observable<Company[]> {
    if (pattern.length < 2) {
      return Observable.of([]);
    }

    let payload = {
      'dictionaryType': 1,
      'dictionaryFilter': {
        'textFilter': pattern,
        'lang': 'ru'
      }
    };

    return this.apiClient.request('adminUI/admin/getDictionary', payload)
      .map((response) => {
        if (response.status === 0) {
          return response.body.items.map(
            (company: ICompany): Company => new Company(company)
          );
        } else { return Observable.throw(false); }
      })
      .catch((err) => {
        console.error('An error occurred', err);
        return Observable.throw(false);
      });
  }
}
