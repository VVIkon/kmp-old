<?php

namespace Dictionaries\HotelsDictionary\Entities;

use \Yii as Yii;
use \KmpException as KmpException;
use \DictionaryErrors as DictionaryErrors;
use \CitiesMapperHelper as CitiesMapperHelper;
use Dictionaries\HotelsDictionary\Entities\GatewayHotelEntity as GatewayHotelEntity;

/**
* Структура отеля УТК
* Работает с набором данных от УТК,
* таблица utk_hotelsData_lastVersion
*/
class UTKHotelEntity extends GatewayHotelEntity {
  protected $gatewayId = 4;
  protected $gatewayName = 'UTK';

  protected $supplierCode = 'utk';

  /** @var string название отеля */
  private $name;
  /** @var string название отеля на английском */
  private $nameEn;
  /** @var string адрес отеля */
  private $address;
  /** @var string адрес отеля на английском */
  private $addressEn;
  /** @var string телефон отеля */
  private $phone;
  /** @var string факс отеля */
  private $fax;
  /** @var string email отеля */
  private $email;
  /** @var string адрес сайта отеля */
  private $url;
  /** @var float кординаты отеля: широта */
  private $latitude;
  /** @var float координаты отлея: долгота */
  private $longitude;
  /** @var string время заезда в номер */
  private $checkInTime;
  /** @var string время выезда из номера */
  private $checkOutTime;
  /** @var int ID отельной цепи */
  private $hotelChainId;
  /** @var int дни недели в контексте распределения тарифов (битовая маска)  */
  private $weekDaysTypes;
  /** @var string главная фотография отеля */
  private $mainImageUrl;

  public function __construct() {
    $this->active = true;
  }

  /**
  * Инициализация из необходимого набора переданных параметров (из БД)
  * @param array $hotelinfo данные отеля для инициализации
  * @param int $cityId ID обрабатываемого города (главный город)
  */
  public function initFromParams($hotelinfo,$cityId) {
    if (empty($hotelinfo['hotelNameRU'])) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::BROKEN_ITEM,
          $hotelinfo
      );
    }

    $this->mainCityId = $cityId;
    $this->hotelId = $hotelinfo['hotelId_UTK'];
    $this->name = $hotelinfo['hotelNameRU'];
    $this->nameEn = $hotelinfo['hotelNameEN'];
    $this->address = $hotelinfo['addressRU'];
    $this->addressEn = $hotelinfo['addressEN'];
    /* supplierCode is hardcoded */
    $this->hotelCode = $this->hotelId;
    $this->groupId = null;
    $this->cityId = $hotelinfo['cityId'];
    $this->cityName = $hotelinfo['city'];
    $this->countryId = $hotelinfo['countryId'];
    $this->countryName = $hotelinfo['country'];
    $this->lastUpdate = !empty($hotelinfo['lastUpdate']) ? $hotelinfo['lastUpdate'] : null;

    switch ($hotelinfo['category']) {
      case 5:
        $this->category = 'FIVE';
        break;
      case 4:
        $this->category = 'FOUR';
        break;
      case 3:
        $this->category = 'THREE';
        break;
      case 2:
        $this->category = 'TWO';
        break;
      case 1:
        $this->category = 'ONE';
        break;
      default:
        $this->category = 'OTHER';
        break;
    }

    $this->phone = $hotelinfo['Phone'];
    $this->fax = $hotelinfo['Fax'];
    $this->email = $hotelinfo['Email'];
    $this->url = $hotelinfo['URL'];
    $this->latitude = $hotelinfo['latitude'];
    $this->longitude = $hotelinfo['longitude'];
    $this->checkInTime = $hotelinfo['checkInTime'];
    $this->checkOutTime = $hotelinfo['checkOutTime'];
    $this->hotelChainId = $hotelinfo['idHotelChain'];
    $this->weekDaysTypes = $hotelinfo['weekDaysTypes'];
    $this->mainImageUrl = $hotelinfo['mainImageUrl'];
  }

  /*
  * Трансформирует данные отеля в структуру КТ
  * @return array данные отеля в терминах КТ
  */
  public function transformToKT() {
    $hotel = [
      'active' => 1,
      'manualEdit' => 0,
      'timestamp' => date('Y-m-d H:i:s'),
      'name' => mb_convert_case($this->name, MB_CASE_TITLE),
      'address' => $this->address,
      'nameEn' => !empty($this->nameEn) ? mb_convert_case($this->nameEn, MB_CASE_TITLE) : null,
      'addressEn' => $this->addressEn,
      'category' => $this->category,
      'url' => $this->url,
      'email' => $this->email,
      'phone' => $this->phone,
      'fax' => $this->fax,
      'cityId' => CitiesMapperHelper::getCityIdBySupplierCityID(
        $this->gatewayId,
        $this->cityId
      ),
      'mainCityId' => CitiesMapperHelper::getCityIdBySupplierCityID(
        $this->gatewayId,
        $this->mainCityId
      ),
      'latitude' => $this->latitude,
      'longitude' => $this->longitude,
      'checkInTime' => $this->checkInTime,
      'checkOutTime' => $this->checkOutTime,
      'hotelChain' => $this->hotelChainId,
      'weekDaysTypes' => $this->weekDaysTypes,
      'mainImageUrl' => $this->mainImageUrl,
      'images' => [],
      'services' => [],
      'descriptions' => []
    ];

    return $hotel;
  }

  /**
  * Отметить запись в наборе данных lastVersion как обработанную
  */
  public function markAsProcessed() {
    $command = Yii::app()->db->createCommand()
      ->update(
        'utk_hotelsData_lastVersion',['action' => self::HOTEL_PRESERVE],
        'hotelId_UTK = :hotelId',[':hotelId' => $this->hotelId]
      );
  }

}
