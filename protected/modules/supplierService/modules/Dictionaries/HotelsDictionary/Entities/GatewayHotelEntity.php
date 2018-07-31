<?php

namespace Dictionaries\HotelsDictionary\Entities;

/**
* Описание сущности отеля шлюза
*/
abstract class GatewayHotelEntity {

  const HOTEL_PRESERVE = 0;
  const HOTEL_CREATE = 1;
  const HOTEL_UPDATE = 2;
  const HOTEL_DELETE = 3;
  const HOTEL_DEMATCH = 4;
  const HOTEL_UPDATE_CONTENT = 5;

  /** @var int код шлюза */
  protected $gatewayId;
  /** @var string название шлюза */
  protected $gatewayName;
  /** @var string код поставщика */
  protected $supplierCode;
  /** @var string код отеля от поставщика */
  protected $hotelCode;
  /** @var int ID отеля по весии шлюза */
  protected $hotelId;
  /** @var int ID группы отелей */
  protected $groupId;
  /** @var int ID главного города отеля */
  protected $mainCityId;
  /** @var int ID города отеля */
  protected $cityId;
  /** @var string название города отеля на русском */
  protected $cityName;
  /** @var string название города отеля на английском */
  protected $cityNameEn;
  /** @var int ID страны отеля */
  protected $countryId;
  /** @var string название страны отеля на русском */
  protected $countryName;
  /** @var string название страны отеля на английском */
  protected $countryNameEn;
  /** @var string время последнего измиенения записи в шлюзе */
  protected $lastUpdate;
  /** @var bool признак активности записи */
  protected $active;

  public function __get($n) {
    switch ($n) {
      case 'gatewayId':
        return $this->gatewayId;
        break;
      case 'gatewayName':
        return $this->gatewayName;
        break;
      case 'supplierCode':
        return $this->supplierCode;
        break;
      case 'hotelCode':
        return $this->hotelCode;
        break;
      case 'hotelId':
        return $this->hotelId;
        break;
      case 'groupId':
        return $this->groupId;
        break;
      case 'mainCityId':
        return $this->mainCityId;
        break;
      case 'cityId':
        return $this->cityId;
        break;
      case 'cityName':
        return $this->cityName;
        break;
      case 'cityNameEn':
        return $this->cityNameEn;
        break;
      case 'countryId':
        return $this->countryId;
        break;
      case 'countryName':
        return $this->countryName;
        break;
      case 'countryNameEn':
        return $this->countryNameEn;
        break;
      case 'lastUpdate':
        return $this->lastUpdate;
        break;
      case 'active':
        return $this->active;
        break;
      default:
        return null;
        break;
    }
  }
}
