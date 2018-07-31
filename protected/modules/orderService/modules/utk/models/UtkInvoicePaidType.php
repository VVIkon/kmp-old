<?php

/**
 * Class UtkInvoicePaidType
 * Реализует функциональность для работы с типами оплат счетов для УТК
 */
class UtkInvoicePaidType extends KFormModel
{
    const PAID_UNDEFINED = 1;
    const PAID_TYPE_NOT_PAID = 2;
    const PAID_TYPE_PART_PAID = 3;
    const PAID_TYPE_PAID = 4;

    /**
     * @var array статусы оплаты счёта
     */
    public static $paidStatuses = [
        self::PAID_UNDEFINED ,
        self::PAID_TYPE_NOT_PAID,
        self::PAID_TYPE_PART_PAID,
        self::PAID_TYPE_PAID
    ];

    /**
     * Проверка наличия указанного типа оплаты счёта
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

