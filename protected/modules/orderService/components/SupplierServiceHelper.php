<?php

/**
 * Class SupplierServiceHelper
 * Класс помощник для взаимодействия с сервисом поставщиков
 */
class SupplierServiceHelper
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

    const CANNOT_CREATE_GEARMAN_CLIENT = 601;
    const INCORRECT_GEARMAN_CONFIG = 602;
    const CANNOT_START_BOOKING_POLL_TASK = 603;

    const API_REQUEST_ERROR = 801;
    const GPTS_API_AUTH_FAILED = 802;
    const API_REQUEST_FAIL = 803;
    const BOOKING_PREPARATION_ERROR = 804;
    const BOOKING_PREPARATION_FAILED = 805;
    const BOOKING_START_FAILED = 806;
    const OFFER_UNAVAILABLE = 807;
    const BOOKING_FAILED = 808;

    public function __construct()
    {
    }

    public static function translateSupplierSvcErrorId($supplierErrorId)
    {
        switch ($supplierErrorId) {
            case self::BOOKING_PREPARATION_FAILED :
                return OrdersErrors::SUPPLIER_SERVICE_TRY_AGAIN_ERROR;
            case self::OFFER_UNAVAILABLE :
                return OrdersErrors::CANNOT_BOOK_SERVICE_WITH_SELECTED_OFFERKEY;
            default :
                return OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR;
        }
    }

}
