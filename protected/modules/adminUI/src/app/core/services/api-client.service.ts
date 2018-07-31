import { Injectable } from '@angular/core';
import { Headers, Http, RequestOptions } from '@angular/http';
import { Subject } from 'rxjs/Subject';
import { Observable } from 'rxjs/Observable';
import { Observer } from 'rxjs/Observer';
import 'rxjs/add/operator/catch';
import 'rxjs/add/operator/map';

import { ProfileService } from './profile.service';

import { ApiErrors } from '../types';

@Injectable()
export class ApiClientService {
  private _restErrors: Subject<number> = new Subject<number>();
  restErrors: Observable<number> = this._restErrors.asObservable();

  constructor(private http: Http, private profileService: ProfileService) {}

  request(url: string, payload: {[k: string]: any}): Observable<any> {
    payload.usertoken = this.profileService.profile.userToken;

    console.log('api request: ' + url);

    let headers = new Headers({ 'Content-Type': 'application/json' });
    let options = new RequestOptions({ headers: headers });

    return this.http.post(url, payload, options)
      .flatMap((response): Observable<any> => {
        console.log(url);
        let data = response.json();
        console.log(data);

        if (data.status === undefined) {
          return Observable.never();
          // throw new Error('bad response');
        }

        if (data.status === 0) {
          return Observable.of(data);
        } else {
          switch (data.errorCode) {
            case ApiErrors.TokenExpired:
              this._restErrors.next(ApiErrors.TokenExpired);
              return Observable.never();
            default:
              return Observable.of(data);
          }
        }
      })
      .catch((err: any) => {
        console.error('api client error: ');
        console.error(err);
        return Observable.never();
      });
  }
}
