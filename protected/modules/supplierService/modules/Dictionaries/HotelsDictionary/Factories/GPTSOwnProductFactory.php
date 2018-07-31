<?php

namespace Dictionaries\HotelsDictionary\Factories;

use Dictionaries\HotelsDictionary\Entities\GPTSOwnHotelEntity as GPTSOwnHotelEntity;

/*
* Создает необходимые объекты собственных продуктов
*/
class GPTSOwnProductFactory {

  public static function create($params) {
    if (!isset($params['serviceType'])) {
      throw new Exception('no service type defined');
    }

    switch ($params['serviceType']) {
      case 'ownHotel':
        return new GPTSOwnHotelEntity($params);
        break;
      default:
        return false;
    }
  }
}
