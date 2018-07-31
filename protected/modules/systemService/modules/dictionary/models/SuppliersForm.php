<?php

/**
 * Class SuppliersForm
 * Реализует функциональность для работы данными поставщиков услуг
 */
class SuppliersForm extends KFormModel
{

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct()
    {
    }

    /**
     * Получить наименования полей из таблицы поставщиков
     * @return mixed
     */
    public static function getSupplierFields($getDbNames = true)
    {
        $table = 'kt_ref_suppliers';

        $tableSchema = Yii::app()->db->getSchema()->getTable($table);

        if (empty($tableSchema)) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SysSvcErrors::CANNOT_GET_SUPPLIER_TABLE_FIELDS,
                "SHOW COLUMNS FROM $table",
                ''
            );
        }

        if (empty($tableSchema)) {
            return false;
        }

        if ($getDbNames) {
            return $tableSchema->getColumnNames();
        }

        $returnFieldNames = [
            'SupplierID' => 'supplierId',
            'SupplierID_UTK' => 'supplierId_Utk',
            'SupplierID_GPTS' => 'supplierId_Gpts',
            'Name' => 'name',
            'EngName' => 'name',
            'GatewayID' => 'gateId'
        ];

        $columnNames = $tableSchema->getColumnNames();
        $fieldNames = [];
        foreach ($columnNames as $columnName) {

            if (!isset($returnFieldNames[$columnName])) {
              /* дурь какая-то...
                throw new KmpInvalidArgumentException(
                    get_class(),
                    __FUNCTION__,
                    SysSvcErrors::CANNOT_FIND_FIELD_NAME_SUBSTITUTION,
                    [
                        'replacements' => print_r($returnFieldNames, 1),
                        'columnName' => $columnName
                    ]
                );
                */
            } else {
              $fieldNames[] = $returnFieldNames[$columnName];
            }
        }

        return $fieldNames;
    }

    /**
     * Получение информации о поставщике
     * @param $serviceType int тип услуги оказываемой поставщиком
     * @param $count int количество записей
     * @param $fields array возвращаемые поля
     * @return mixed
     */
     /** @todo Переписать эту стремную дрянь нормально */
    public static function getSuppliersInfo($serviceType, $count = null, $fields = null, $lang = LangForm::LANG_RU)
    {
        $command = Yii::app()->db->createCommand();

        $returnFieldNames = [
            'supplierId' => 'SupplierID',
            'supplierId_Utk' => 'SupplierID_UTK',
            'supplierId_Gpts' => 'SupplierID_GPTS',
            'name' => 'Name',
            'gateId' => 'GatewayID',
        ];

        $selectFields = '';
        $tableFields = !empty($fields) ? $fields : self::getSupplierFields(false);

        foreach ($tableFields as $selectField) {

            $returnName = $returnFieldNames[$selectField];

            if ($selectField == 'name' && $lang == LangForm::LANG_EN) {
                $returnName = 'EngName';
            }

            $selectFields = $selectFields . ', suppliers.' . $returnName . ' ' . $selectField;
        }

        $selectFields .= ', suppliers.EngName as supplierCode';
        if (empty($serviceType)) {
            $selectFields .= ', services.ServiceID as serviceType';
        }

        $command->select($selectFields)
          ->from('kt_ref_suppliers suppliers')
          ->leftJoin('kt_suppliers_services services', 'suppliers.SupplierID = services.SupplierID')
          ->where('suppliers.active = :active',[':active' => 1]);

        if (!empty($serviceType)) {
            $command->andWhere('services.ServiceID = :serviceId', ['serviceId' => $serviceType]);
        }

        if (!empty($count)) {
            $command->limit($count);
        }

        try {
            $suppliersInfo = $command->queryAll();

        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SysSvcErrors::CANNOT_GET_SUPPLIER_INFO,
                $command->getText(),
                $e
            );
        }

        return $suppliersInfo;
    }

}
