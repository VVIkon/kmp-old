<?php

/**
 * Class ServiceTypeForm
 * Реализует функциональность для работы данными типов услуг КТ
 */
class ServiceTypeForm extends KFormModel
{

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct()
    {
    }

    /**
     * Получение информации о типе услуги
     * @param $serviceType
     * @return CDbDataReader|mixed
     */
    public static function getServiceTypeInfo($serviceType)
    {
        $command = Yii::app()->db->createCommand();

        $command->select('*');
        $command->from('kt_ref_services');
        $command->where('ServiceID = :serviceId', [':serviceId' => $serviceType]);

        try {
            $serviceTypeInfo = $command->queryRow();
        } catch (Exception $e) {

            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SysSvcErrors::CANNOT_GET_SERVICE_TYPE_INFO,
                $command->getText(),
                $e
            );
        }
        return $serviceTypeInfo;
    }

    /**
     * Проверка существования типа услуги
     * @param $offerKeys
     * @return array|CDbDataReader
     */
    public static function isServiceTypeExist($serviceType)
    {

        $serviceTypeInfo = self::getServiceTypeInfo($serviceType);

        return !empty($serviceTypeInfo) ? true : false;
    }
}

