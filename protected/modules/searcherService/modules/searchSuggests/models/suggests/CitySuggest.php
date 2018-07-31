<?php

/**
 * Class CitySuggest
 * Реализует функциональность управления
 * информацией для подсказки при выборе города
 */
class CitySuggest extends Suggest implements ISuggest
{
    /**
     * Поиск подсказок для ввода города
     * @param $text
     * @param $langId
     * @return array
     */
    public function find($text, $langId)
    {
        $cities = $this->getCityDataByLocationName($text, $langId);
        $result['locations'] = $cities;
        return $result;
    }

    /**
     * Получить данные о городах из БД по названию
     * @param $text string целое/часть название
     * @param $lang string язык ответа
     * @return array|CDbDataReader|null
     */
    private function getCityDataByLocationName($text, $langId)
    {
        /** маппинг данных запроса: поле в результате => поле в БД */
        $qmap = [
            'cityId' => 'l.cityId',
            'countryId' => 'c.countryId',
            'cityIata' => 'l.IATA',
            'mainLocation' => 'l.mainLocation',
            'mainLocationDistance' => 'l.mainLocationdistance',
            'latitude' => 'c.Lat',
            'longitude' => 'c.Lon',
            'synonyms' => 'l.Location',
            'popularity' => 'l.num_hotels'
        ];

        switch ($langId) {
            case LangForm::LANG_RU:
                $qmap['city'] = 'CityNameRU';
                $qmap['country'] = 'countryNameRU';
                $qmap['translation'] = 'CityNameEN';
                break;
            case LangForm::LANG_EN:
                $qmap['city'] = 'CityNameEN';
                $qmap['country'] = 'countryNameEN';
                $qmap['translation'] = 'CityNameRU';
                break;
        }

        $command = Yii::app()->db->createCommand()
            ->select(implode(',', array_map(function ($r, $field) {
                return $field . ' as ' . $r;
            }, array_keys($qmap), $qmap)))
            ->from('ho_locations l')
            ->join('kt_ref_cities c', 'l.cityId = c.CityID')
            ->where('l.active=1 and
              (
                l.CityNameRU like :cityNameRu
                or l.CityNameEN like :cityNameEn
                or l.Location like :location
                or l.IATA = :iata
              )',
                [
                    ':cityNameRu' => $text . '%',
                    ':cityNameEn' => $text . '%',
                    ':location' => '%|' . $text . '%',
                    ':iata' => $text
                ]
            );

        try {
            $result = $command->query();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(), __FUNCTION__,
                SearcherErrors::CANNOT_GET_CITIES_LOCATIONS_LIST,
                $command->getText(), $e
            );
        }

        $cities = [];

        foreach ($result as $city) {
            $criteria = [];
            $criteria['nameSimilarity'] = min([
                levenshtein($text, $city['city']),
                levenshtein($text, $city['translation'])
            ]);

            $synonyms = !is_null($city['synonyms'])
                ? explode('|', mb_substr($city['synonyms'], 1))
                : null;
            $criteria['synonymSimilarity'] = is_null($synonyms)
                ? null
                : min(array_map(function ($syn) use ($text) {
                    return levenshtein($text, $syn);
                }, $synonyms));

            /* чем меньше, тем лучше */
            $city['sortQuality'] = 0;
            if (mb_strtolower($city['cityIata']) === mb_strtolower($text)) {
                $city['sortQuality'] = 0;
            } else {
                if (!is_null($criteria['synonymSimilarity'])) {
                    $city['sortQuality'] += ($criteria['nameSimilarity'] < $criteria['synonymSimilarity'])
                        ? $criteria['nameSimilarity']
                        : $criteria['synonymSimilarity'] * 1.1;
                } else {
                    $city['sortQuality'] += $criteria['nameSimilarity'];
                }
                $city['sortQuality'] += 1 / $city['popularity'];
            }

            $cities[] = $city;
        }

        usort($cities, function ($a, $b) {
            return ($a['sortQuality'] < $b['sortQuality']) ? -1 : 1;
        });

        foreach ($cities as &$city) {
            unset($city['synonyms'], $city['translation'], $city['sortQuality'], $city['popularity']);
        }

        return $cities;
    }
}
