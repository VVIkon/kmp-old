<?php

/**
 * Class UtkServicePaidType
 * Реализует функциональность для работы с типами оплат услуг для УТК
 */
class UtkServicePaidType extends KFormModel
{
    const PAID_TYPE_PAID = 0;
    const PAID_TYPE_PART_PAID = 1;
    const PAID_TYPE_NOT_PAID = 2;

    /**
     * @var array результаты операции бронирования
     */
    public static $paidStatuses = [
        self::PAID_TYPE_PAID,
        self::PAID_TYPE_PART_PAID,
        self::PAID_TYPE_NOT_PAID
    ];

    /**
     * Проверка наличия указанного типа оплаты услуги
     * @param $typeCode
     * @return bool
     */
    public static function checkTypeExists($typeCode)
    {
        if (in_array($typeCode,self::$paidStatuses)) {
            return true;
        }

        return false;
    }
}

