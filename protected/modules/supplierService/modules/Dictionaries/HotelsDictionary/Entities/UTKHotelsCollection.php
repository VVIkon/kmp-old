<?php

namespace Dictionaries\HotelsDictionary\Entities;

use \Yii as Yii;
use \KmpException as KmpException;
use \CitiesMapperHelper as CitiesMapperHelper;

/**
* Коллекция отелей UTK (сущности UTKHotelEntity)
*/
class UTKHotelsCollection {
  /** @var array массив структур отелей  */
  private $hotels;

  public function __construct() {
    $this->hotels = [];
  }

  /**
  * Добавление отеля в коллекцию
  * @return bool результат операции
  */
  public function addHotel(UTKHotelEntity $hotel) {
    $this->hotels[] = $hotel;
    return true;
  }

  /**
  * Возвращает число отелей в коллекции
  * @return int число отелей
  */
  public function getHotelsCount() {
    return count($this->hotels);
  }

  /**
  * Обработка элементов коллекции с их удалением после обработки
  * @param Callable $proccessor функция обработки элемента
  */
  public function processHotelsData(Callable $processor) {
    $counter = 0;
    while (count($this->hotels) !== 0) {
      $counter++;
      $hotel = array_pop($this->hotels);
      $processor($hotel,$counter);
    }
  }

  /**
  * Объединяет данные всех отелей набора в один отель КТ
  */
  public function generateKTHotelContent() {
    $generatedContent = [];
    $checkInTimes = [];
    $checkOutTimes = [];

    foreach ($this->hotels as $hotel) {
      $hotelContent = $hotel->transformToKT();
      $fieldScores = $this->countContentScores($hotelContent);

      /* заполнение данных отеля согласно рейтингу */
      foreach ($fieldScores as $field => $score) {
        if (!isset($generatedContent[$field]) || $score > $generatedContent[$field][0]) {
          $generatedContent[$field] = [ $score, $hotelContent[$field] ];
        }
      }

      /* заполнение времени заезда/выезда */
      if (!empty($hotelContent['checkInTime'])) {
        $checkInTimes[] = \DateTime::createFromFormat('Y-m-d !H:i',$hotelContent['checkInTime']);
      }
      if (!empty($hotelContent['checkOutTime'])) {
        $checkOutTimes[] = \DateTime::createFromFormat('Y-m-d !H:i',$hotelContent['checkOutTime']);
      }
    }

    /* очистка конента от рейтинга */
    $generatedContent = array_map(function($i) {
      return $i[1];
    },$generatedContent);

    $generatedContent['descriptions'] = [];
    $generatedContent['images'] = [];
    $generatedContent['services'] = [];

    $generatedContent['checkInTime'] = (count($checkInTimes) > 0)
      ? max($checkInTimes)->format('H:i')
      : null;
    $generatedContent['checkOutTime'] = (count($checkOutTimes) > 0)
      ? min($checkOutTimes)->format('H:i')
      : null;

    $generatedContent['active'] = 1;
    $generatedContent['manualEdit'] = 0;
    $generatedContent['timestamp'] = date('Y-m-d H:i:s');

    return $generatedContent;
  }

  /**
  * Выполняет и возвращает оценку *качества* контента:
  * влияет на то, из какого отеля УТК будет выбрано значение для отеля КТ
  * @param array $hotelContent данные отеля в терминах КТ
  * @return array массив оценок в формате поле => оценка
  */
  private function countContentScores($hotelContent) {
    /*
    * Условия выставления рейтинга полям отеля ( [ *балл* => *проверка условия* ] )
    * Принцип состоит в том, что для выполняющихся условий значение будет выставлено в true,
    * далее при рассчете ключи массива с такими значениями будут просуммированы для получения
    * общего рейтинга
    */
    $scoremap = [
      'name' => [
        2 => ( !empty($hotelContent['name']) ? true : false ),
        1 => ( preg_match('/[а-яё]/ui',(string)$hotelContent['name']) ? true : false )
      ],
      'nameEn' => [
        2 => ( !empty($hotelContent['nameEn']) ? true : false ),
        1 => ( preg_match('/[а-яё]/ui',(string)$hotelContent['nameEn']) ? true : false )
      ],
      'address' => [
        2 => ( !empty($hotelContent['address']) ? true : false ),
        1 => ( preg_match('/[а-яё]/ui',(string)$hotelContent['address']) ? true : false )
      ],
      'addressEn' => [
        2 => ( !empty($hotelContent['addressEn']) ? true : false ),
        1 => ( preg_match('/[а-яё]/ui',(string)$hotelContent['addressEn']) ? true : false )
      ],
      'category' => [
        /** рассчет ниже по особым правилам */
      ],
      'url' => [
        1 => ( !empty($hotelContent['url']) ? true : false )
      ],
      'email' => [
        1 => ( !empty($hotelContent['email']) ? true : false )
      ],
      'phone' => [
        1 => ( !empty($hotelContent['phone']) ? true : false )
      ],
      'fax' => [
        1 => ( !empty($hotelContent['fax']) ? true : false )
      ],
      'mainCityId' => [
        1 => ( !empty($hotelContent['mainCityId']) ? true : false )
      ],
      'cityId' => [
        1 => ( !empty($hotelContent['cityId']) ? true : false )
      ],
      'hotelChain' => [
        1 => ( !empty($hotelContent['hotelChain']) ? true : false )
      ],
      'latitude' => [
        1 => ( (!empty($hotelContent['latitude']) && !empty($hotelContent['longitude'])) ? true : false )
      ],
      'longitude' => [
        1 => ( (!empty($hotelContent['latitude']) && !empty($hotelContent['longitude'])) ? true : false )
      ],
      'mainImageUrl' => [
        1 => ( !empty($hotelContent['phone']) ? true : false )
      ],
      'weekDaysTypes' => [
        1 => ( !empty($hotelContent['weekDaysTypes']) ? true : false )
      ]
    ];

    /** отдельный рассчет для категорий */
    $categoryscoremap = [
      'ONE'   => 6,
      'TWO'   => 5,
      'THREE' => 4,
      'FOUR'  => 3,
      'FIVE'  => 2,
      'APARTMENT' => 1,
      'PANSION'   => 1,
      'OTHER'     => 1
    ];

    if ( isset($categoryscoremap[ $hotelContent['category'] ]) ) {
      $scoremap['category'][ $categoryscoremap[$hotelContent['category']] ] = true;
    } else {
      $scoremap['category'] = [0 => true];
    }

    foreach ($scoremap as $field => $scores) {
      $scoremap[$field] = array_sum(array_keys(array_filter($scores)));
    }

    return $scoremap;
  }

}
