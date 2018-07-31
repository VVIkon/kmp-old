<?php

/**
 * Class HotelSuggest
 * Реализует функциональность управления
 * информацией для подсказки при выборе отеля
 */
class HotelSuggest extends Suggest implements ISuggest
{
    const CONTAIN_MODE_REQUEST_LENGTH = 4;

    /**
     * Поиск подсказок для ввода отеля
     * @param $params
     * @param $langId
     * @return array
     */
    public function find($params, $langId)
    {
        $hotelsInfo = $this->getHotelsByName($params, $langId);

        $result = [
            'hotels' => []
        ];

        foreach ($hotelsInfo as $hotelInfo) {
            $result['hotels'][] = [
                'hotelId' => $hotelInfo['hotelId'],
                'hotel' => $hotelInfo['hotelName'],
                'popularity' => $hotelInfo['popularity'],
                'category'  => $hotelInfo['category'],
                'city' => [
                    'city'          => $hotelInfo['cityName'],
                    'country'       => $hotelInfo['countryName'],
                    'cityId'        => $hotelInfo['cityId'],
                    'cityIata'      => $hotelInfo['cityIata'],
              //      'mainLocation'   => $hotelInfo['mainLocation'],
              //      'mainLocationDistance' => $hotelInfo['mainLocationDistance'],
                    'synonyms'    =>  !empty($hotelInfo['synonyms']) ? $hotelInfo['synonyms'] : '',
                ]
            ];
            $result['foundCount'] = count($hotelsInfo);
        }

        return $result;
    }

    /**
     * Получить данные об отелях из БД по названию местности
     * @param $params array параметры поиска отеля
     * @param $langId string язык ответа
     * @return array|CDbDataReader|null
     */
    private function getHotelsByName($params, $langId) {
        $searchString = $params['hotelName'];
        if (empty($searchString)) {
            return null;
        }

        $cityId = (!empty($params['hotelFilters']) && !empty($params['hotelFilters']['cityId']))
            ? $params['hotelFilters']['cityId']
            : null;

        $selectFields = '';
        switch ($langId) {
            case LangForm::LANG_RU;
                $selectFields = 'hotelNameRU hotelName, CityNameRU cityName,
                CountryNameRU as countryName';
                break;
            case LangForm::LANG_EN;
                $selectFields = 'hotelNameEN hotelName, CityNameEN cityName,
                CountryNameEN as countryName';
                break;
        }

        $command = Yii::app()->db->createCommand()
            ->select('hotelID_KT hotelId,  popularity as popularity,
                        cityId cityId, IATA as cityIata,
                        location as synonyms, IFNULL(NULL,\'\') as category'
                        . ', ' . $selectFields


            )
            ->from('ho_hotelSearch');

        $this->setSearchConditions($command, $searchString);

        if (!empty($cityId)) {
            $command->andWhere('ho_hotelSearch.cityId = :cityId', [':cityId' => $cityId]);
        }

        if (!empty($params['maxCount'])) {
            $command->limit((int)$params['maxCount']);
        }

        try {
            $hotels = $command->queryAll();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_HOTELS_LIST,
                $command->getText(),
                $e
            );
        }
        return $hotels;
    }

    /**
     * Установить условия поиска в зависимости от длины поискового запроса
     * @param $command
     * @param $text
     */
    private function setSearchConditions($command, $text)
    {
        if (mb_strlen($text, "utf-8") < self::CONTAIN_MODE_REQUEST_LENGTH) {
            $command->orWhere(['like', 'hotelNameRU', $text . ' %']);
            $command->orWhere(['like', 'hotelNameRU', '% ' . $text . ' %']);
            $command->orWhere(['like', 'hotelNameRU', '% ' . $text ]);
            $command->orWhere(['like', 'hotelNameRU', $text]);

            $command->orWhere(['like', 'hotelNameEN', $text . ' %']);
            $command->orWhere(['like', 'hotelNameEN', '% ' . $text . ' %']);
            $command->orWhere(['like', 'hotelNameEN', '% ' . $text ]);
            $command->orWhere(['like', 'hotelNameEN', $text]);
        } else {
            $command->orWhere(['like', 'hotelNameRU', $text . '%']);
            $command->orWhere(['like', 'hotelNameRU', '% ' . $text . '%']);
            $command->orWhere(['like', 'hotelNameRU', '% ' . $text ]);
            $command->orWhere(['like', 'hotelNameRU', $text]);

            $command->orWhere(['like', 'hotelNameEN', $text . '%']);
            $command->orWhere(['like', 'hotelNameEN', '% ' . $text . '%']);
            $command->orWhere(['like', 'hotelNameEN', '% ' . $text ]);
            $command->orWhere(['like', 'hotelNameEN', $text]);
        }
    }
}
