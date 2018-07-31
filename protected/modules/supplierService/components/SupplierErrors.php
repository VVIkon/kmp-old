<?php

/**
 * Class SupplierErrors
 * Класс для хранения констант ошибок
 */
class SupplierErrors
{
    const ERROR_NONE = 0;

    const CANNOT_DETERMINE_ENGINE = 11;
    const ENGINE_MODULE_NOT_FOUND = 12;
    const SERVICE_TYPE_NOT_SET = 13;
    const SERVICE_TYPE_NOT_DETERMINED = 14;
    const SUPPLIER_ID_NOT_SET = 15;
    const COMMAND_DETAILS_NOT_SET = 16;
    const INCORRECT_COMMAND_PARAMS = 17;
    const ORDER_ID_NOT_SET = 18;
    const SERVICE_ID_NOT_SET = 19;
    const OFFER_KEY_NOT_SET = 20;
    const INCORRECT_TOURISTS_PARAMS = 21;
    const INCORRECT_VALIDATION_RULES = 22;
    const INCORRECT_PNR_DATA = 23;
    const INCORRECT_SEGMENTS_DATA = 24;
    const NO_SERVICES_IN_ORDER = 25;
    const NO_TOURISTS_IN_SERVICE = 26;
    const INCORRECT_TOURIST_PASSPORT_PARAMS = 27;
    const INCORRECT_TICKET_DATA = 28;
    const API_MODULE_NOT_FOUND = 29;
    const API_METHOD_NOT_FOUND = 30;
    const GATE_ID_NOT_SET = 31;
    const BOOK_DATA_NOT_SET = 32;
    const LANG_NOT_SET = 33;
    const HOTEL_ID_NOT_SET = 34;
    const HOTEL_NOT_FOUND = 35;
    const TICKETS_NOT_SET = 35;
    const SERVICE_DATA_NOT_SET = 36;
    const SUPPLIER_NOT_FOUND = 37;
    const SUPPLIER_DOES_NOT_SUPPORT_MODIFICATION = 38;
    const INPUT_PARAMS_ERROR = 39;
    const MODIFY_SERVICE_FOR_HOTELS_ONLY = 40;
    const PREPARE_ACCOMODATION_MODIFY_ERROR = 41;
    const ACCOMODATION_MODIFY_ERROR = 42;
    const NO_SERVICE_IN_ORDER_WITH_PNR_DATA = 43;
    const SERVICE_GET_STATUS_DATA_NOT_SET = 44;
    const CANNOT_CREATE_HOTEL = 45;
    const INVALID_COMPANY_ID = 46;
    const SERVICE_STATUS_NOT_SET = 47;
    const SERVICE_STATUS_NOT_CORRECT = 48;
    const ORDER_NOT_FOUND = 49;
    const VIEWCURRENCY_NOT_DEFINED = 50;
    const SUPPLIERCURRENCY_NOT_FOUND = 51;
    const OFFER_ID_NOT_SET = 52;



    const CANNOT_CREATE_GEARMAN_CLIENT = 601;
    const INCORRECT_GEARMAN_CONFIG = 602;
    const CANNOT_START_BOOKING_POLL_TASK = 603;
    const FARE_PARSING_RULES_NOT_FOUND = 604;
    const INCORRECT_FARE_PARSING_CONFIG = 605;
    const INCORRECT_FARE_PARSING_FUNCTION_CONFIG = 606;

    const API_REQUEST_ERROR = 801;
    const GPTS_API_AUTH_FAILED = 802;
    const API_REQUEST_FAIL = 803;
    const BOOKING_PREPARATION_ERROR = 804;
    const BOOKING_PREPARATION_FAILED = 805;
    const BOOKING_START_FAILED = 806;
    const OFFER_UNAVAILABLE = 807;
    const BOOKING_FAILED = 808;
    const TICKET_ISSUING_ERROR = 809;
    const CANNOT_GET_SUPPLIER_ORDER = 810;
    const CANNOT_GET_ETICKET = 811;
    const CANNOT_GET_FLIGHT_FARES = 812;
    const SUPPLIER_GET_SERVICE_STATUS_FAIL = 813;
    const TICKET_ISSUING_ERROR_MESSAGE = 814;
}