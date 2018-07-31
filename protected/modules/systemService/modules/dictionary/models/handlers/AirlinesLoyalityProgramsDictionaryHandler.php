<?php

/**
 * Справочник альянсов аивакомпаний (поставщики программ лояльности)
 */
class AirlinesLoyalityProgramsDictionaryHandler extends AbstractDictionaryHandler
{
    /**
     * Получение справочних данных
     * @param array $params
     * @return array
     */
    public function getDictionaryData($params)
    {
        /** поля запроса в формате 'имя поля в фильтре' => 'имя поля в базе' */
        $fieldMatrix = [
          'programId' => 'al.id',
          'loyalityProgramName' => 'lp.LoyalityProgramName',
          'IATAcode' => 'al.carrierIATA',
          'aircompanyName' => 'cr.name',
          'allianceName' => 'al.allianzName'
        ];

        $fields = [];
        $maxrows = 0;
        if (!empty($params['dictionaryFilter']) && is_array($params['dictionaryFilter'])) {
          $maxrows = !empty($params['dictionaryFilter']['maxRowCount']) ? (int)$params['dictionaryFilter']['maxRowCount'] : 0;

          if (
            !empty($params['dictionaryFilter']['fieldsFilter']) &&
            is_array($params['dictionaryFilter']['fieldsFilter'])
          ) {
            foreach ($params['dictionaryFilter']['fieldsFilter'] as $filter) {
              if (isset($fieldMatrix[$filter])) {
                $fields[$filter] = $fieldMatrix[$filter];
              }
            }
          }
        }

        if (count($fields) == 0) {
          $fields = $fieldMatrix;
        }

        $command = Yii::app()->db->createCommand()
          ->select(implode(',', array_map(function($alias, $field) {
              return $field . ' as ' . $alias;
            }, array_keys($fields), $fields)))
          ->from('kt_ref_loyalityprogram as lp')
          ->join('kt_ref_allianz as al', 'lp.LoyalityProgramId = al.LoyalityProgramId')
          ->join('fl_carriers as cr', 'al.carrierIATA = cr.carrierIATA');

        if ($maxrows !== 0) {
          $command->limit($maxrows);
        }

        try {
            $airlinesAlliances = $command->queryAll();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SysSvcErrors::CANNOT_GET_AIRLINE_ALLIANCES,
                $command->getText(),
                $e
            );
        }

        return [
            'itemFound' => count($airlinesAlliances),
            'items' => $airlinesAlliances
        ];
    }

}