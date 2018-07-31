<?php

/**
* Класс кодов ошибок команды Dictionary
* Внимание! Эти коды выступают в качестве exit-кодов команды, поэтому надо уложиться в 254 кода =)
*/
class DictionaryErrors {
  const SUCCESS = 0;

  const UNCATCHED_ERROR = 11;
  const UNKNOWN_DICTIONARY = 12;
  const ENGINE_NOT_SUPPORTED_BY_COMMAND = 13;
  const UNKNOWN_ENGINE = 14;
  const ENGINE_COMMAND_ERROR = 15;
  const PARSING_ERROR = 16;
  const BROKEN_ITEM = 17;
  const BROKEN_SET_ITEM = 18;
  const ERROR_PROCESSING_ITEM = 19;

  const DATA_FETCH_FAILED = 50;
  const COMPANY_INSERTION_FAILED = 51;
  const COMPANY_UPDATE_FAILED = 52;
  const COMPANY_ID_NOT_SET = 53;
  const UNKNOWN_SUPPLIER_CODE = 54;
  const NO_CITY_MATCH = 55;
  const NO_COUNTRY_MATCH = 56;
  const USER_ID_NOT_SET = 57;
  const USER_INSERTION_FAILED = 58;
  const USER_UPDATE_FAILED = 59;
  const AGENCY_NOT_SET = 60;
  const COUNTRY_MATCH_FAILED = 61;
  const COUNTRY_CREATION_FAILED = 62;
  const CITY_MATCH_FAILED = 63;
  const CITY_CREATION_FAILED = 64;
  const MATCH_UPDATE_FAILED = 65;
  const CITY_UPDATE_FAILED = 66;
  const DUPLICATE_ENTRY = 67;
  const HOTEL_UNEXPECTEDLY_NOT_FOUND = 68;

}
