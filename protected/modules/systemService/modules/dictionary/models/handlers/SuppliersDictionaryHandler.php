<?php

/**
 * Class SuppliersDictionaryHandler
 * Класс для обработки запросов к справочнику поставщиков
 */
class SuppliersDictionaryHandler extends AbstractDictionaryHandler
{
    /**
     * Получение справочных данных
     * @param $params array
     * @return bool
     */
    public function getDictionaryData($params)
    {
        $validator = new SuppliersDictionaryRequestValidator($this->module);

        if (!empty($params['dictionaryFilter']) && is_array($params['dictionaryFilter'])) {
            /**
             * Костыль
             * Переносим параметр lang внутрь массива dictionaryFilter
             */
            if(!empty($params['lang'])){
                $params['dictionaryFilter']['lang'] = $params['lang'];
            }
            $validator->checkDictionaryFilterParams($params['dictionaryFilter']);
        } else {
            $params['dictionaryFilter'] = [];
        }

        $params = $this->normalizeRequest($params);

        $suppliersInfo = SuppliersForm::getSuppliersInfo(
            $params['dictionaryFilter']['serviceId'],
            $params['dictionaryFilter']['maxRowCount'],
            $params['dictionaryFilter']['fieldsFilter'],
            LangForm::GetLanguageCodeByName($params['lang'])
        );


        $suppliers['itemFound'] = count($suppliersInfo);
        $suppliers['items'] = $suppliersInfo;
        return $suppliers;
    }

    /**
     * Добавление необязателных полей, которые не были указаны при запросе
     * @param $params array
     * @return mixed
     */
    private function normalizeRequest($params)
    {
        if (!isset($params['dictionaryFilter']['serviceId'])) {
            $params['dictionaryFilter']['serviceId'] = null;
        }

        if (!isset($params['dictionaryFilter']['maxRowCount'])) {
            $params['dictionaryFilter']['maxRowCount'] = null;
        }

        if (!isset($params['dictionaryFilter']['fieldsFilter'])) {
            $params['dictionaryFilter']['fieldsFilter'] = null;
        }

        return $params;
    }
}