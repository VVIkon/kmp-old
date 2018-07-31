<?php

/** Фабрика поставщиков услуг: инициализирует движок поставщика в зависимости от параметров запроса */
abstract class SupplierFactory
{
    const UTK_ENGINE = 4;
    const GPTS_ENGINE = 5;
    const KT_ENGINE = 6;

    /** @var mixed[] соответствие модулей движков supplierID из справочника */
    private static $supplierEngines = [
        self::GPTS_ENGINE => 'GPTSEngine'
    ];

    /**
     * метод отдает нужный движок поставщика в зависимости от условий
     * @return SupplierEngine подходящий движок поставщика
     * @throws KmpException
     */
    public static function getSupplierEngine($type)
    {
        if (empty(self::$supplierEngines[$type])) {
            throw new KmpException(
                get_class(), __FUNCTION__,
                SupplierErrors::CANNOT_DETERMINE_ENGINE,
                ['supplierId' => $type] //пока движок определяется через supplierId
            );
        } elseif (!YII::app()->getModule('supplierService')->hasModule(self::$supplierEngines[$type])) {
            throw new KmpException(
                get_class(), __FUNCTION__,
                SupplierErrors::ENGINE_MODULE_NOT_FOUND,
                ['supplierEngine' => self::$supplierEngines[$type]]
            );
        } else {
            return YII::app()->getModule('supplierService')->getModule(self::$supplierEngines[$type])->getEngine();
        }
    }
}