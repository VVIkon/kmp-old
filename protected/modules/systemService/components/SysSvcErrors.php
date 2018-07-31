<?php

/**
 * Class SysSvcErrors
 * Класс для хранения констант ошибок системного модуля
 */
class SysSvcErrors
{
    const ERROR_NONE = 0;

    const ERROR_LOGIN_INVALID = 2;
    const ERROR_PASSWORD_INVALID = 3;

    const PRESENTATION_FILE_NAME_NOT_SET = 11;
    const FILE_COMMENT_NOT_SET = 12;
    const INCORRECT_VALIDATION_RULES = 13;
    const ORDER_ID_NOT_SET = 14;
    const OBJECT_TYPE_NOT_SET = 15;
    const INCORRECT_OBJECT_TYPE = 16;
    const OBJECT_ID_NOT_SET = 17;
    const URL_NOT_SET = 18;
    const INCORRECT_FILE_SAVE_PARAMETERS = 19;
    const FILE_NOT_FOUND = 20;
    const DICTIONARY_TYPE_NOT_SET = 21;
    const INCORRECT_DICTIONARY_TYPE = 22;
    const DICTIONARY_FILTER_NOT_SET = 23;
    const LANGUAGE_NOT_SET = 24;
    const INCORRECT_LANGUAGE_CODE = 25;
    const SERVICE_TYPE_NOT_SET = 26;
    const INCORRECT_SERVICE_TYPE = 27;
    const INCORRECT_ROWS_COUNT = 28;
    const INCORRECT_FILTER_FIELD_VALUE = 29;
    const USER_ID_NOT_SET = 30;
    const USER_NOT_FOUND = 31;
    const USER_PERMISSIONS_NOT_SET = 32;
    const INPUT_PARAMS_ERROR = 33;
    const CLIENT_USER_NOT_FOUND = 34;
    const DOCUMENT_NOT_FOUND = 35;
    const INVALID_TOKEN = 36;
    const INVALID_USER = 37;
    const INVALID_MSG_STRUCTURE = 38;
    const UNKNOWN_ACTION_TYPE = 39;
    const ABONENT_NOT_SET = 40;
    const UNSUBSCRIBED_USER = 41;
    const NOT_ENOUGH_RIGHTS = 42;
    const INVALID_BIRTHDATE = 43;
    const INVALID_SURNAME = 44;
    const INVALID_NAME = 45;
    const INVALID_PREFIX = 46;
    const INVALID_COMPANY = 47;
    const INVALID_DOCUMENT = 48;
    const INVALID_LOYALTY_PROGRAM = 49;
    const INCORRECT_USER_ROLE_TYPE = 50;
    const INCORRECT_USER_ROLE_ID = 51;
    const INCORRECT_FIELD_TYPE_ID = 52;
    const INVALID_ADD_FIELD_CATEGORY = 53;
    const INVALID_ADD_FIELD_TYPE_TEMPLATE = 54;
    const INVALID_ADD_FIELD_VALUE_LIST = 55;
    const INVALID_ADD_FIELD_VALUE = 56;
    const INCORRECT_SUPPLIER = 57;
    const INCORRECT_TP_RULE_TYPE = 58;
    const INCORRECT_TP_CONDITIONS = 59;
    const INCORRECT_TP_ACTIONS = 60;
    const TP_RULE_NOT_FOUND = 61;
    const INCORRECT_TP_CONDITION_TYPE = 62;


    const DB_ERROR = 500;
    const CANNOT_GET_SERVICE_TYPE_INFO = 510;
    const CANNOT_GET_SUPPLIER_TABLE_FIELDS = 511;
    const CANNOT_GET_SUPPLIER_INFO = 512;
    const CANNOT_GET_HOTEL_CHAINS = 513;
    const CANNOT_GET_AIRLINE_ALLIANCES = 514;

    const CANNOT_CREATE_FILE = 601;
    const INCORRECT_STORAGE_PATH = 602;
    const CANNOT_DOWNLOAD_FILE = 603;
    const METHOD_NOT_IMPLEMENTED = 604;
    const CANNOT_FIND_FIELD_NAME_SUBSTITUTION = 605;

    const NOT_ENOUGH_RIGHTS_FOR_OPERATION = 701;
    const MAX_LENGHT_PASSWORD = 702;


    const CANNOT_GET_ACCESS_FILE_BY_URL = 802;
    const CANNOT_LINK_FILE_TO_ORDER = 803;
    const FATAL_ERROR = 804;
    // Jib



    // Ошибки UserAuth
    const ERROR_MAST_CHANGE_PASSWORD = 2000; // Пользователь должен сменить пароль
    const ERROR_MAST_CHANGE_LOGIN = 2001;    // Пользователь должен сменить логин
    const ERROR_AUTHENTICATE = 3;         // Ошибка авторизации
//    const ERROR_AUTHENTICATE = 2002;         // Ошибка авторизации
    const ERROR_DOUBLE_HASH = 2003;          // Ошибка: Имеются аккаунты в кот. присутствует одинаковые хеши
    const ERROR_REGISTRATION_DATA = 2004;    // Ошибка: В БД присутствует логически неправильная регистрационная запись
    const ERROR_GETTING_USERINFO = 2005;     // Ошибка получении аккаунта пользователя
    const ERROR_AUTHENTICATE_GPTS = 2006;    // Ошибка авторизации в GPTS
    const ERROR_NOT_ACCOUNT = 2;          // Ошибка логин не имеет аккаунта
    //const ERROR_NOT_ACCOUNT = 2007;          // Ошибка логин не имеет аккаунта
}
