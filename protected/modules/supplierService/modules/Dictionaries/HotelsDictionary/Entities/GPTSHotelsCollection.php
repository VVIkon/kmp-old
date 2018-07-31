<?php

namespace Dictionaries\HotelsDictionary\Entities;

use \Yii as Yii;

/**
* Коллекция отелей GPTS (сущности GPTSHotelEntity)
*/
class GPTSHotelsCollection {
  /** @var array массив структур отелей  */
  private $hotels;
  /** @var array массив собственных отелей */
  private $ownHotels;
  /**
  * @var array список кодов собственных отелей в коллекции (в формате "$supplierCode|$hotelCode")
  * @todo это костыль, т.к. для собственных отелей есть дублирующиеся коды, работать с ними мы не можем
  */
  private $gpCodes;
  /* @var array приоритеты для контента по поставщикам */
  private $supplierPriorities;

  public function __construct() {
    $this->hotels = [];
    $this->ownHotels = [];
    $this->gpCodes = [];
  }

  /**
  * Добавление собственного отеля к коллекции
  */
  public function addOwnHotel(GPTSOwnHotelEntity $ownHotel) {
    if ($ownHotel->active) {
      $this->ownHotels[$ownHotel->hotelId] = $ownHotel;
      return true;
    } else {
      return false;
    }
  }

  /**
  * Добавление отеля в коллекцию
  * @return bool результат операции
  */
  public function addHotel(GPTSHotelEntity $hotel) {
    if ($hotel->supplierCode == 'gp') {
      if (isset($this->ownHotels[$hotel->hotelId])) {
        $hotel->changeSupplier($this->ownHotels[$hotel->hotelId]->supplierCode);

        $gpCode = $hotel->supplierCode . '|' . $hotel->hotelCode;
        if (in_array($gpCode,$this->gpCodes)) {
          return false;
        } else {
          $this->gpCodes[] = $gpCode;
        }
      } else {
        return false;
      }
    }

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
  * @param array $supplierPriorities конфигурация приоритетов поставщика
  */
  public function generateKTHotelContent($supplierPriorities) {
    $this->supplierPriorities = $supplierPriorities;
    $generatedContent = [];
    $generatedDescriptions = [];
    $generatedImages = [];
    $generatedServices = [];
    $checkInTimes = [];
    $checkOutTimes = [];

    foreach ($this->hotels as $hotel) {
      $hotelContent = $hotel->transformToKT();
      $isGrouped = !empty($hotel->groupId) ? true : false;
      $fieldScores = $this->countContentScores(
        $hotelContent,  $hotel->supplierCode, $isGrouped
      );
      $descriptionScores = $this->countHotelDescriptionScores(
        $hotelContent['descriptions'],  $hotel->supplierCode, $isGrouped
      );

      /* заполнение данных отеля согласно рейтингу */
      foreach ($fieldScores as $field => $score) {
        if (!isset($generatedContent[$field]) || $score > $generatedContent[$field][0]) {
          $generatedContent[$field] = [ $score, $hotelContent[$field] ];
        }
      }

      /* заполнение описаний отеля согласно рейтингу */
      foreach ($hotelContent['descriptions'] as $desc) {
        $desctype = $desc['descriptionType'];

        if (
          !isset($generatedDescriptions[$desctype])
          || $descriptionScores[$desctype] > $generatedDescriptions[$desctype][0]
        ) {
          $generatedDescriptions[$desctype] = [ $descriptionScores[$desctype], $desc ];
        }
      }

      /* заполнение изображений отеля */
      foreach ($hotelContent['images'] as $img) {
        $generatedImages[$img['url']] = $img;
      }

      /* заполнение услуг отеля */
      foreach ($hotelContent['services'] as $srv) {
        $generatedServices[$srv['otaCode']] = $srv;
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

    $generatedContent['descriptions'] = array_map(function($i) {
      return $i[1];
    },$generatedDescriptions);

    $generatedContent['images'] = array_values($generatedImages);
    $generatedContent['services'] = array_values($generatedServices);

    $generatedContent['checkInTime'] = (count($checkInTimes) > 0)
      ? max($checkInTimes)->format('H:i')
      : null;
    $generatedContent['checkOutTime'] = (count($checkOutTimes) > 0)
      ? min($checkOutTimes)->format('H:i')
      : null;

    $generatedContent['weekDaysTypes'] = null;
    $generatedContent['active'] = 1;
    $generatedContent['manualEdit'] = 0;
    $generatedContent['timestamp'] = date('Y-m-d H:i:s');

    return $generatedContent;
  }

  /**
  * Выполняет и возвращает оценку *качества* контента:
  * влияет на то, из какого отеля GPTS будет выбрано значение для отеля КТ
  * @param array $hotelContent данные отеля в терминах КТ
  * @param string $supplierCode код поставщика
  * @param bool $isGrouped флаг, определяющий, принадлежит ли отель какой-либо группе шлюза
  * @return array массив оценок в формате поле => оценка
  */
  private function countContentScores($hotelContent,$supplierCode,$isGrouped) {
    $supplierCode = (string)$supplierCode;
    /*
    * Условия выставления рейтинга полям отеля ( [ *балл* => *проверка условия* ] )
    * Принцип состоит в том, что для выполняющихся условий значение будет выставлено в true,
    * далее при рассчете ключи массива с такими значениями будут просуммированы для получения
    * общего рейтинга.
    * NB если суммарный максимальный вес получается больше 10, то стоит изменить
    * множитель далее в рассчете оценки по поставщику
    */
    $scoremap = [
      'name' => empty($hotelContent['name']) ? [0 => true] : [
        2 => ( preg_match('/[а-яё]/ui',(string)$hotelContent['name']) ? true : false ),
        1 => $isGrouped
      ],
      'nameEn' => empty($hotelContent['nameEn']) ? [0 => true] : [
        1 => $isGrouped
      ],
      'address' => empty($hotelContent['address']) ? [0 => true] : [
        2 => ( preg_match('/[а-яё]/ui',(string)$hotelContent['address']) ? true : false ),
        1 => $isGrouped
      ],
      'addressEn' => empty($hotelContent['addressEn']) ? [0 => true] : [
        1 => $isGrouped
      ],
      'category' => [
        /* рассчет ниже по особым правилам */
      ],
      'url' => empty($hotelContent['url']) ? [0 => true] : [
        1 => $isGrouped
      ],
      'email' => empty($hotelContent['email']) ? [0 => true] : [
        1 => $isGrouped
      ],
      'phone' => empty($hotelContent['phone']) ? [0 => true] : [
        1 => $isGrouped
      ],
      'fax' => empty($hotelContent['fax']) ? [0 => true] : [
        1 => $isGrouped
      ],
      'mainCityId' => [
        1 => ( !empty($hotelContent['mainCityId']) ? true : false )
      ],
      'cityId' => empty($hotelContent['cityId']) ? [0 => true] : [
        1 => $isGrouped
      ],
      'hotelChain' => empty($hotelContent['hotelChain']) ? [0 => true] : [
        1 => $isGrouped
      ],
      'latitude' => (empty($hotelContent['latitude']) || empty($hotelContent['longitude'])) ? [0 => true] : [
        1 => $isGrouped
      ],
      'longitude' => (empty($hotelContent['latitude']) || empty($hotelContent['longitude'])) ? [0 => true] : [
        1 => $isGrouped
      ],
      'mainImageUrl' => empty($hotelContent['mainImageUrl']) ? [0 => true] : [
        1 => $isGrouped
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
      /* рассчет коэффициента приоритета по поставщику */
      if ($scoremap[$field] != 0) {
        if (
          isset($this->supplierPriorities[$field]) &&
          isset($this->supplierPriorities[$field][$supplierCode])
        ) {
          $scoremap[$field] += 10 * $this->supplierPriorities[$field][$supplierCode];
        }
      }
    }

    return $scoremap;
  }

  /**
  * Выполняет и возвращает оценку *качества* описаний отеля
  * @see GPTSHotelsCollection::countContentScores Работает аналогично общему методу оценки
  * @param array $hotelDescriptions описания отеля в терминах КТ
  * @param string $supplierCode код поставщика
  * @param bool $isGrouped флаг, определяющий, принадлежит ли отель какой-либо группе шлюза
  * @return array массив оценок в формате поле => оценка
  */
  private function countHotelDescriptionScores($hotelDescriptions,$supplierCode,$isGrouped) {
    $scoremap = [];
    foreach ($hotelDescriptions as $desc) {
      $scoremap[$desc['descriptionType']] = empty($hotelContent['descriptionRu']) ? [0 => true] : [
        2 => ( preg_match('/[а-яё]/ui',(string)$desc['descriptionRu']) ? true : false ),
        1 => $isGrouped
      ];
    }

    foreach ($scoremap as $field => $scores) {
      $scoremap[$field] = array_sum(array_keys(array_filter($scores)));
      if ($scoremap[$field] != 0) {
        if (
          isset($this->supplierPriorities['description']) &&
          isset($this->supplierPriorities['description'][$supplierCode])
        ) {
          $scoremap[$field] += 10 * $this->supplierPriorities['description'][$supplierCode];
        }
      }
    }

    return $scoremap;
  }
}
