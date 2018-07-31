/* global ktStorage */
(function(global,factory){
  
      KT.storage.CompanyStorage = factory();
  
  }(this,function() {
    'use strict';
    
    /** Класс типа дополнительного поля */
    var CustomFieldType = function(customField) {
      $.extend(this, customField);
    };

    CustomFieldType.fieldCategoriesMap = {
      1: 'user',
      2: 'service',
      3: 'order'
    };

    CustomFieldType.fieldTypesMap = {
      1: 'Text',
      2: 'TextArea',
      3: 'Number',
      4: 'Date'
    };

    /** 
    * Возвращает категорию поля 
    * @return {String} - категория (user|service)
    */
    CustomFieldType.prototype.getFieldCategory = function() {
      return CustomFieldType.fieldCategoriesMap[+this.fieldCategory];
    };


    /**
    * Хранилище данных комании
    * @module CompanyStorage
    * @constructor
    * @param {Integer} companyId - ID компании
    */
    var CompanyStorage = ktStorage.extend(function(companyId) {
      this.namespace = 'CompanyStorage';
  
      this.companyId = companyId;
  
    });
  
    KT.addMixin(CompanyStorage, 'Dispatcher');
  
    /**
    * Инициализация хранилища
    * @param {Object} companyData - данные компании (из getDictionary)
    */
    CompanyStorage.prototype.initialize = function(companyData) {
      this.name = companyData.name;

      this.roleType = (function(roleType) {
          switch (+roleType) {
            case 1: return 'op';
            case 2: return 'agent';
            case 3: return 'corp';
            default: throw new Error('unknown role type: ' + roleType);
          }
        }(companyData.companyRoleType));
      
      this.holdingCompany = (typeof companyData.companyMainOffice === 'string') ?
        companyData.companyMainOffice :
        null;

      this.contracts = companyData.Contracts;
      this.minimalPriceField = null;
      this.customFieldsMap = {};

      var self = this;
      
      this.customFields = (!Array.isArray(companyData.addFields)) ? [] : 
        companyData.addFields
          .filter(function(field) {
            if (CustomFieldType.fieldTypesMap.hasOwnProperty(field.typeTemplate)) {
              return true;
            } else {
              if (+field.typeTemplate === 5) {
                // минимальная цена предложения
                self.minimalPriceField = new CustomFieldType(field);
              }
              return false;
            }
          })
          .map(function(field, idx) {
            self.customFieldsMap[field.fieldTypeId] = idx;
            field.companyId = self.companyId;
            return new CustomFieldType(field);
          });


      this.dispatch('initialized', this);
    };

    /**
    * Возвращает дополнительные поля компании
    * @param {String} [typeFilter] - возвращать только определенные типы доп. полей (user|service)
    */
    CompanyStorage.prototype.getCustomFields = function(typeFilter) {
      var customFields;

      if (typeFilter !== undefined) {
        customFields = this.customFields.filter(function(customField) { 
          return customField.getFieldCategory() === typeFilter;
        });
      } else {
        customFields = this.customFields;
      }

      return customFields.map(function(customField) {
        return $.extend(true, {}, customField);
      });
    };

    /**
    * Возвращает данные дополнительного поля
    * @param {Integer} fieldTypeId - ID поля 
    * @return {Object} - данные поля
    */
    CompanyStorage.prototype.getCustomField = function(fieldTypeId) {
      var idx = this.customFieldsMap[fieldTypeId];

      if (idx !== undefined) {
        return $.extend(true, {}, this.customFields[idx]);
      } else {
        // специальная проверка на поле minimalPrice
        if (this.minimalPriceField !== null && this.minimalPriceField.fieldTypeId === fieldTypeId) {
          return $.extend(true, {}, this.minimalPriceField);
        } else {
          console.error('unknown custom field requested: ' + fieldTypeId);
          return null;
        }
      }
    };

    /**
    * Установка дополнительного поля компании
    * @param {Object} fieldData - данные дополнительного поля
    */
    CompanyStorage.prototype.setCustomField = function(fieldData) {
      fieldData.companyId = this.companyId;

      // специальная обработка для minimalPrice
      if (+fieldData.typeTemplate === 5) {
        this.minimalPriceField = new CustomFieldType(fieldData);
      } else {
        var idx = this.customFieldsMap[fieldData.fieldTypeId];
        if (idx !== undefined) {
          this.customFields[idx] = new CustomFieldType(fieldData);
        } else {
          idx = this.customFields.push(new CustomFieldType(fieldData)) - 1;
          this.customFieldsMap[fieldData.fieldTypeId] = idx;
        }
      }
    };

    /**
    * Возвращает экземпляр класса типа дополнительного поля, созданный по переданным данным
    * @param {Object} fieldData - данные дополнительного поля
    */
    CompanyStorage.prototype.createCustomField = function(fieldData) {
      fieldData.companyId = this.companyId;
      return new CustomFieldType(fieldData);
    };
  
    return CompanyStorage;
  }));
  