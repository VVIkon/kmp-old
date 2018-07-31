/* global ktStorage */
(function(global,factory){
  
      KT.storage.UsersStorage = factory();
  
  }(this,function() {
    'use strict';

    /**
    * Хранилище данных пользователей
    * @module UsersStorage
    * @constructor
    * @param {Object} CompanyStorage - хранилище данных компании, чьи пользователи обрабатываются
    */
    var UsersStorage = ktStorage.extend(function(CompanyStorage) {
      this.namespace = 'UsersStorage';  

      this.CompanyStorage = CompanyStorage;

      this.users = [];
    });
  
    KT.addMixin(UsersStorage, 'Dispatcher');
  
    /**
    * Инициализация хранилища
    * @param {Object[]} usersData - данные пользователей (сотрудников) компании
    */
    UsersStorage.prototype.initialize = function(usersData) {
      var userMap = {};

      this.users = usersData.filter(function(userdoc) {
        if (userMap.hasOwnProperty(userdoc.userId)) {
          return false;
        } else {
          userMap[userdoc.userId] = true;
          return true;
        }
      });

      this.dispatch('initialized', this);
    };

    /**
    * Возвращает список пользователей 
    * @param {String} [searchString] - если указано, будут отобраны пользователи по паттерну
    * @return {Array} - список пользователей
    */
    UsersStorage.prototype.getUsers = function(searchString) {
      if (searchString !== undefined) {
        searchString = searchString.trim().toLowerCase();
      }

      var users = (searchString !== undefined) ? 
        this.users.filter(function(user) {
          if (
            user.firstName !== null && 
            user.firstName.toLowerCase().indexOf(searchString) === 0
          ) {
            return true;
          } else if (
            user.lastName !== null && 
            user.lastName.toLowerCase().indexOf(searchString) === 0
          ) {
            return true;
          } else if (
            user.middleName !== null && 
            user.middleName.toLowerCase().indexOf(searchString) === 0
          ) {
            return true;
          } else {
            return false;
          }
        }) :
        this.users;

      return users.map(function(user) {
        return $.extend(true, {}, user);
      });
    };

    /**
    * Подготовка данных пользователя для формы редатирования дополнительных полей
    * @param {Object} userInfo - информация о пользователе (из getClientUser)
    * @return {Object} - подготовленные данные пользователя
    */
    UsersStorage.prototype.prepareUserData = function(userInfo) {
      if (!Array.isArray(userInfo.addData)) {
        userInfo.addData = [];
      }

      var userData = {
        'user': $.extend(true, {}, userInfo.user),
        'document': $.extend(true, {}, userInfo.document),
        'customFields': userInfo.addData.map(function(customField) {
          return $.extend(true, {}, customField);
        })
      };

      var presentCustomFieldTypes = userData.customFields.map(function(customField) {
        return customField.fieldTypeId;
      });

      Array.prototype.push.apply(
        userData.customFields, 
        this.CompanyStorage.getCustomFields('user').filter(function(customField) {
          return (
            customField.active &&
            presentCustomFieldTypes.indexOf(customField.fieldTypeId) === -1
          );
        })
      );
      
      return userData;
    };
  
    return UsersStorage;
  }));
  