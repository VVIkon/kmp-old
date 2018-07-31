<?php

/**
 * Class AirportSuggest
 * Реализует функциональность управления
 * информацией для подсказки при выборе аэропорта
 */
class AirportSuggest extends Suggest implements ISuggest
{
    /**
     * Поиск подсказок для ввода аэропорта
     * @param $text
     * @param $langId
     * @return array
     */
    public function find($text, $langId)
    {
        $airportsInfo = $this->getAirportDataByLocationName($text, $langId);

        $result = [];
        foreach ($airportsInfo as $airportsInfo) {
            $result['locations'][] = [
                'city' => $airportsInfo['cityName'],
                'cityId' => $airportsInfo['cityId'],
                'country' => $airportsInfo['countryName'],
                'countryId' => $airportsInfo['countryId'],
                'cityIata' => $airportsInfo['cityIata'],
                'airportIata' => $airportsInfo['apCodes'],
                'airportIcao' => '',
                'airportTkp' => '',
                'airportName' => $airportsInfo['apName'],
                'distance' => $airportsInfo['distance'],
                //'synonyms'      => $airportsInfo['synonyms']
            ];
        }

        return $result;
    }

    /**
     * Получить данные об аэропортах из БД по названию местности
     * @param $text string целое/часть названия местности(город,страна, IATA города)
     * @param $lang string язык ответа
     * @return array|CDbDataReader|null
     */
    private function getAirportDataByLocationName($text, $langId)
    {
        if (empty($text)) {
            return null;
        }

        $fieldsMap = [];

        switch ($langId) {
            case LangForm::LANG_RU;
                $fieldsMap = [
                    'l.CityNameRU' => 'cityName',
                    'l.CountryNameRU' => 'countryName',
                    'l.AirportNameRU' => 'apName'
                ];
                break;
            case LangForm::LANG_EN;
                $fieldsMap = [
                    'l.CityNameEN' => 'cityName',
                    'l.CountryNameEN' => 'countryName',
                    'l.AirportNameEN' => 'apName'
                ];
                break;
        }

        try {
            $airports = Yii::app()->db->createCommand()
                ->select(
                    'l.AirportList apCodes, l.IATA cityIata, l.Location synonyms, l.distance, a.CityID cityId, a.CountryID countryId, ' .
                    implode(',', array_map(function ($field, $alias) {
                        return $field . ' ' . $alias;
                    }, array_keys($fieldsMap), $fieldsMap))
                )
                ->from('fl_locations l')
                ->join('fl_airports a', 'l.AirportID = a.airportID')
                ->where(
                    'l.active = 1 and l.IATA like binary :iata and l.AirportList <> ""',
                    [':iata' => $text]
                )
                ->andWhere('distance IS NULL')
                ->queryAll();

            // если найдено совпадение по IATA-коду, возвращаем результат
            if (count($airports)) {
                return $airports;
            }

            $conditions = [
                'l.Location like :location',
                '(l.AirportNameRU like :airportNameRu and l.AirportList <> "")',
                '(l.AirportNameEN like :airportNameEn and l.AirportList <> "")',
                'l.CityNameRU like :cityNameRu',
                'l.CityNameEn like :cityNameEn',
                'l.CountryNameRU like :countryNameRu',
                'l.CountryNameEN like :countryNameEn',
                '(l.IATA like :iata and l.AirportList <> "")'
            ];
            $bindings = [
                ':location' => '%|' . $text . '%',
                ':airportNameRu' => $text . '%',
                ':airportNameEn' => $text . '%',
                ':cityNameRu' => $text . '%',
                ':cityNameEn' => $text . '%',
                ':countryNameRu' => $text . '%',
                ':countryNameEn' => $text . '%',
                ':iata' => $text . '%'
            ];

            $airports = Yii::app()->db->createCommand()
                ->select(
                    'l.AirportList apCodes, l.IATA cityIata, l.Location synonyms, l.distance, ' .
                    'l.CityNameRU, l.CountryNameRU, l.AirportNameRU, ' .
                    'l.CityNameEN, l.CountryNameEN, l.AirportNameEN,' .
                    'a.CityID cityId, a.CountryID countryId'
                )
                ->from('fl_locations l')
                ->join('fl_airports a', 'l.AirportID = a.airportID')
                ->where('l.active = 1')
                ->andWhere(implode(' or ', $conditions), $bindings)
                ->queryAll();

            function getConcurrenceQuality($query, $field)
            {
                return (mb_stripos($field, $query) === 0 ? mb_strlen($field) * 0.1 : 1000);
            }

            // определение критерия для сортировки: чем меньше, тем лучше
            foreach ($airports as &$airport) {
                $airport['sortQuality'] = 0;

                if (mb_strtolower($airport['cityIata']) === mb_strtolower($text)) {
                    $airport['sortQuality'] = 0;
                } else {

                    $criteria = min([
                        getConcurrenceQuality($text, $airport['AirportNameRU']),
                        getConcurrenceQuality($text, $airport['AirportNameEN'])
                    ]);
                    // if ($airport['sortQuality'] > $criteria) {
                    $airport['sortQuality'] = $criteria;
                    // }

                    $criteria = min([
                            getConcurrenceQuality($text, $airport['CityNameRU']),
                            getConcurrenceQuality($text, $airport['CityNameEN'])
                        ]) * 10;
                    if ($airport['sortQuality'] > $criteria) {
                        $airport['sortQuality'] = $criteria;
                    }

                    if (isset($airport['synonyms']) && !is_null($airport['synonyms'])) {
                        $synonyms = explode('|', mb_substr($airport['synonyms'], 1));
                        $criteria = min(array_map(function ($syn) use ($text) {
                                return getConcurrenceQuality($text, $syn);
                            }, $synonyms)) * 20;
                        if ($airport['sortQuality'] > $criteria) {
                            $airport['sortQuality'] = $criteria;
                        }
                    }

                    $criteria = min([
                            getConcurrenceQuality($text, $airport['CountryNameRU']),
                            getConcurrenceQuality($text, $airport['CountryNameEN'])
                        ]) * 30;
                    if ($airport['sortQuality'] > $criteria) {
                        $airport['sortQuality'] = $criteria;
                    }
                    // Перекинуть
                    $airport['sortQuality'] += is_null($airport['distance']) ? 0 : 500;
                }
            }

            usort($airports, function ($a, $b) {
                return ($a['sortQuality'] < $b['sortQuality']) ? -1 : 1;
            });

            foreach ($airports as &$airport) {
                foreach ($fieldsMap as $field => $alias) {
                    $airport[$alias] = $airport[str_replace('l.', '', $field)];
                }
                unset(
                    $airport['synonyms'],
                    $airport['sortQuality'],
                    $airport['CityNameRU'],
                    $airport['CountryNameRU'],
                    $airport['AirportNameRU'],
                    $airport['CityNameEN'],
                    $airport['CountryNameEN'],
                    $airport['AirportNameEN']
                );
            }

            return $airports;

        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_AIRPORT_LOCATIONS_LIST,
                $e->getMessage(),
                $e
            );
        }
    }
}

