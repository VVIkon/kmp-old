<?php

/**
 * Class CountriesDictionaryHandler
 * Класс для обработки запросов к справочнику стран
 */
class CountriesDictionaryHandler extends AbstractDictionaryHandler
{
    /**
     * Получение справочных данных
     * @param $params array
     * @return bool
     */
    public function getDictionaryData($params)
    {
      $lang = !empty($params['lang']) ? $params['lang'] : 'ru';
      
      if (empty($params['dictionaryFilter']) || !is_array($params['dictionaryFilter'])) {
        $params['dictionaryFilter'] = [];
      }
      $dictionaryFilter = $params['dictionaryFilter'];

      /** поля запроса в формате 'имя поля в фильтре' => ['имя поля в базе','имя поля в структуре ответа'] */
      $fieldMatrix = [
        'countryId' => ['CountryID', 'countryId'],
        'name' => [(($lang === 'ru') ? 'Name' : 'EngName'), 'name'],
        'countryCode' => ['CountryCode', 'countryCode'],
      ];

      $filters = [];
      $reqFields = [];

      if (
        !empty($dictionaryFilter['fieldsFilter']) &&
        is_array($dictionaryFilter['fieldsFilter']) &&
        count($dictionaryFilter['fieldsFilter']) > 0
      ) {
        foreach ($dictionaryFilter['fieldsFilter'] as $filter) {
          if (isset($fieldMatrix[$filter])) {
            $filters[] = $filter;
          }
        }
      }

      if (count($filters) === 0) {
        $filters = array_keys($fieldMatrix);
      }
      
      /** заполняем массив запрашиваемых полей */
      foreach ($filters as $filter) {
          $reqFields[] = implode(' ', $fieldMatrix[$filter]);
      }
      
      $command = Yii::app()->db->createCommand()
        ->select(implode(', ', $reqFields))
        ->from('kt_ref_countries')
        ->where('active = :active', [':active' => 1]);

      if (!empty($dictionaryFilter['textFilter'])) {
        $queryField = (preg_match('/^[A-z\s]+$/u', $dictionaryFilter['textFilter'])) 
          ? 'EngName' 
          : 'Name';

        $command->andWhere('(' . 
            $queryField . ' like "' . $dictionaryFilter['textFilter'] . '%"' . 
            ' or ' . 
            $queryField . ' like "% ' . $dictionaryFilter['textFilter'] . '%"' . 
          ')');
      }

      if (!empty($dictionaryFilter['countryId'])) {
        $command->andWhere('countryId = :countryId', [':countryId' => $dictionaryFilter['countryId']]);
      }

      if (!empty($dictionaryFilter['maxRowCount'])) {
        $command->limit($dictionaryFilter['maxRowCount']);
      }

      try {
          $countries = $command->queryAll();
      } catch (Exception $e) {
          throw new KmpDbException(
              get_class(),
              __FUNCTION__,
              SysSvcErrors::DB_ERROR,
              $command->getText(),
              $e
          );
      }

      return [
          'itemFound' => count($countries),
          'items' => $countries
      ];
    }
}