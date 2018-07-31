import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import * as XLSX from 'xlsx';
import { Observable } from 'rxjs/Observable';
import 'rxjs/observable/forkJoin';

import { KtModalService } from '../core/services/kt-modal.service';

import { Company } from '../types/company';
import { User, ISetUser } from '../types/user';
import { ISetDocument } from '../types/document';
import { UserProfile } from '../types/user-profile';
import { CompaniesService } from '../services/api/companies.service';
import { UsersService } from '../services/api/users.service';

import { UserFieldsMapper, IUserFieldOption } from './models/user-fields-mapper';

type AOA = Array<Array<any>>;

@Component({
  selector: 'users',
  templateUrl: './tpl/users.html',
  providers: [CompaniesService, UsersService]
})
export class UsersComponent implements OnInit {
  private companies: Company[];
  private companiesSelectorState = 'default';
  private selectedCompany: Company;

  private users: User[];
  private usersLoadMessage: string;
  private usersSheet: AOA;
  private userFieldsMapper: UserFieldsMapper;

  private parsedUserColumns: Array<string>;
  private parsedUsers: Array<{[k: string]: any}>;
  private parsedUsersArray: Array<{status: number, error: string, row: Array<any>}>;
  private parsedUsersSaved = false;

  constructor(
    private router: Router,
    private companiesService: CompaniesService,
    private usersService: UsersService,
    private modalService: KtModalService
  ) { }

  ngOnInit(): void { }

  /**
   * Обработка ввода символов в саджесте компаний
   * @param pattern - введенные символы
   */
  onCompanyType(pattern: string) {
    if (pattern.length < 2) { return; }
    this.companiesSelectorState = 'loading';
    this.companiesService.getCompaniesByPattern(pattern)
      .subscribe((companies: Company[]) => {
        this.companiesSelectorState = 'default';
        this.companies = companies;
      });
  }

  /**
   * Обраотка выбора компании в саджесте
   * @param company - выбранная компания
   */
  onCompanySelect(company: Company) {
    if (!company) {
      this.selectedCompany = null;
      return;
    }

    this.selectedCompany = company;
    this.users = null;
    this.usersService.getCompanyUsers(this.selectedCompany)
      .subscribe((users: User[]) => {
        this.users = users;
      });
  }

  /**
   * Обработка выбора пользователя из списка
   * @param user - выбранный пользователя
   */
  onUserSelect(user: User) {
    if (!user) { return; }
    this.router.navigate(['/user', user.id]);
  }

  /**
   * Метод открытия модальных окон
   * @param id - ID модального окна
   */
  openModal(id: string) {
    this.modalService.open(id);
  }

  /**
   * Обработка выбора xls(-x) файла для загрузки пользователей
   * @param file - выбранный файл
   */
  onUsersFileSelect(file: File|null) {
    console.log('users file selected');
    this.usersLoadMessage = null;
    this.parsedUserColumns = null;
    this.parsedUsers = null;
    this.parsedUsersArray = null;
    this.parsedUsersSaved = false;

    if (file === null) {
      this.usersSheet = null;
      this.userFieldsMapper = null;
      return;
    }

    let usersSheet;

    const xlsReader = new FileReader();
    xlsReader.onload = (event: any) => {
      const bstr = event.target.result;

      try {
        const wb = XLSX.read(bstr, {type: 'binary', cellDates: true});

        /* grab first sheet */
        const wsname = wb.SheetNames[0];
        const ws = wb.Sheets[wsname];
        this.usersLoadMessage = null;

        /* save data */
        let sheet = <AOA>(XLSX.utils.sheet_to_json(ws, {
          raw: true,
          header: 1
        }));

        this.userFieldsMapper = new UserFieldsMapper(sheet.shift());
        this.usersSheet = sheet;
      } catch (e) {
        this.usersLoadMessage = 'Ошибка чтения файла!';
        return;
      }
    };
    xlsReader.readAsBinaryString(file);
  }

  /**
   * Обработка списка пользователей, загруженных из файла,
   * в соответствии с выбранной схемой маппинга полей
   */
  processUsersList() {
    let fieldMapper = this.userFieldsMapper;

    this.openModal('users__loaded-users');

    this.parsedUserColumns = fieldMapper.fieldsMap
      .filter((f: {linkedField: string}) => f.linkedField !== null)
      .map((f: {header: string}) => f.header);

    this.parsedUsersArray = [];
    this.parsedUsers = this.usersSheet
      .map((row: Array<any>) => {
        if (row.length === 0) {
          return null;
        }

        let user: {user: any, document: any} = {
          user: {},
          document: {}
        };

        let parsedRow: Array<string> = [];

        // не forEach, потому что row может быть с *дырками*, а forEach реиндексирует
        for (let i = 0; i < row.length; i++) {
          let linkedField = fieldMapper.fieldsMap[i].linkedField;
          if (linkedField === null) { continue; }
          let path = linkedField.split('.');
          let val = fieldMapper.processValue(row[i], linkedField);
          user[path[0]][path[1]] = val;
          parsedRow.push((!val) ? '' : val.toString());
        }

        this.parsedUsersArray.push({
          status: null,
          error: null,
          row: parsedRow
        });

        user.user.userId = null;
        user.user.clientId = this.selectedCompany.id;
        user.document.userDocId = null;
        user.document.firstName = user.user.firstName;
        user.document.middleName = user.user.middleName;
        user.document.lastName = user.user.lastName;

        return user;
      })
      .filter((user: any) => {
        return user !== null;
      });
  }

  /**
   * Оработка изменения схемы маппинга полей (xls -> структура пользователя/документа)
   * @see UserFieldsMapper
   * @param field - поле в xls-файле
   * @param option - выбранная опция соответствия структуре пользователя/документа
   */
  onUserFieldMapped(field: any, option: any) {
    field.linkedField = (option !== null) ? option.field : null;
  }

  /**
   * Сохранение списка пользователей из загруженного файла
   */
  saveUsersList() {
    console.log('parsed users:');
    console.log(this.parsedUsers);

    let usersLoading = Observable.forkJoin.apply(Observable, this.parsedUsers.map((user: any, i: number) => {
      return this.usersService.setUser(user)
        .map((result) => {
          this.parsedUsersArray[i].status = 0;
          return result;
        })
        .catch((err) => {
          this.parsedUsersArray[i].status = 1;
          this.parsedUsersArray[i].error = String(err);
          return Observable.of('error');
        });
    }));

    usersLoading.subscribe((result: any) => {
      console.log('users loaded');
      this.parsedUsersSaved = true;
      this.onCompanySelect(this.selectedCompany);
    })
  }
}
