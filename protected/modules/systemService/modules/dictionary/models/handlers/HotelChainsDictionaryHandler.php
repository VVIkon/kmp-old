<?php

/**
 * Class HotelChainsDictionaryHandler
 * Класс для обработки запросов к справочнику отельных сетей
 */
class HotelChainsDictionaryHandler extends AbstractDictionaryHandler
{
    /**
     * Получение справочных данных
     * @param $params array
     * @return array
     */
    public function getDictionaryData($params)
    {
        $lang = !empty($params['lang']) ? $params['lang'] : 'ru';

        /** поля запроса в формате 'имя поля в фильтре' => ['имя поля в базе','имя поля в структуре ответа'] */
        $fieldMatrix = [
            'hotelChainID' => ['idHotelChain', 'hotelChainId'],
            'NameRu' => ['nameRU', 'name'],
            'NameEn' => ['nameEN', 'name'],
            'status' => ['active', 'status'],
            'manualEdit' => ['manualEdit', 'manualEdit']
        ];

        $filters = [];
        $reqFields = [];
        $maxrows = 0;

        if (!empty($params['dictionaryFilter']) && is_array($params['dictionaryFilter'])) {
            $maxrows = !empty($params['dictionaryFilter']['maxRowCount']) ? (int)$params['dictionaryFilter']['maxRowCount'] : 0;

            if (
                !empty($params['dictionaryFilter']['fieldsFilter']) &&
                is_array($params['dictionaryFilter']['fieldsFilter']) &&
                count($params['dictionaryFilter']['fieldsFilter']) > 0
            ) {
                foreach ($params['dictionaryFilter']['fieldsFilter'] as $filter) {
                    /** поля с локализацией обрабатываем согласно выбранному языку */
                    if ($filter == 'Name') {
                        switch ($lang) {
                            case 'ru':
                                $filters[] = 'NameRu';
                                break;
                            case 'en':
                                $filters[] = 'NameEn';
                                break;
                        }
                    } elseif (isset($fieldMatrix[$filter])) {
                        $filters[] = $filter;
                    }
                }
            }
        }

        /** если массив выбранных фильтров пуст, заполняем значениями по умолчанию */
        if (count($filters) == 0) {
            $filters = ['hotelChainID', 'status', 'manualEdit'];
            switch ($lang) {
                case 'ru':
                    $filters[] = 'NameRu';
                    break;
                case 'en':
                    $filters[] = 'NameEn';
                    break;
            }
        }

        /** заполняем массив запрашиваемых полей */
        foreach ($filters as $filter) {
            $reqFields[] = implode(' ', $fieldMatrix[$filter]);
        }

        $command = Yii::app()->db->createCommand()
            ->select(implode(', ', $reqFields))
            ->from('ho_ref_hotelChain')
            ->where('active = :active', [':active' => 1]);

        if ($maxrows !== 0) {
            $command->limit($maxrows);
        }

        try {
            $hotelChains = $command->queryAll();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SysSvcErrors::CANNOT_GET_HOTEL_CHAINS,
                $command->getText(),
                $e
            );
        }

        return [
            'itemFound' => count($hotelChains),
            'items' => $hotelChains
        ];
    }
}
