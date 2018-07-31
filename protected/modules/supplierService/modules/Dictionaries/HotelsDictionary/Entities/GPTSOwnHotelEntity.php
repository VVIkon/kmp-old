<?php

namespace Dictionaries\HotelsDictionary\Entities;

/**
* Собственный отель GPTS
*/
class GPTSOwnHotelEntity {
  /** @var string код поставщика отеля */
  private $supplierCode;
  /** @var string код отеля */
  private $hotelCode;
  /** @var int ID отеля (GP) */
  private $hotelId;
  /** @var bool признак активности отеля */
  private $active;

  public function __construct($params) {
    if (
      !isset($params['supplierId']) ||
      !isset($params['active']) ||
      !isset($params['contractDetails']) ||
      !isset($params['contractDetails']['hotelId']) ||
      !isset($params['contractDetails']['hotelCode'])
    ) {
      throw new \Exception('Cannot create own hotel: bad data');
    }

    $this->supplierCode = $params['supplierId'];
    $this->hotelCode = $params['contractDetails']['hotelCode'];
    $this->hotelId = $params['contractDetails']['hotelId'];
    $this->active = $params['active'];
  }

  public function __get($n) {
    switch ($n) {
      case 'supplierCode':
        return $this->supplierCode;
      case 'hotelCode';
        return $this->hotelCode;
      case 'hotelId':
        return $this->hotelId;
      case 'active':
        return $this->active;
      default:
        return null;
    }
  }
}
