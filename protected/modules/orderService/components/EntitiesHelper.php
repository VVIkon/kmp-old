<?php

/**
 * Class EntitiesHelper
 * Класс помощник для получения информации о сущьностях системы
 */

class EntitiesHelper
{
    /**
     * Получить наименование бизнес объекта
     * @param $module
     * @param $entityTypeId
     * @return bool|string
     */
    public static function getEntityName($module, $entityTypeId)
    {
        switch ($entityTypeId) {
            case BusinessEntityTypes::BUSINESS_ENTITY_RECEIPT :
                $entities = $module->getConfig('entitiesNames');
                $entityName = BusinessEntityTypes::getTypeNameById($entityTypeId);
                return !empty($entities[$entityName]) ? $entities[$entityName] : false;
                break;
            default :
                return false;
                break;
        }
    }

}
