<?php

namespace Dictionaries\HotelsDictionary\Entities;

use \Yii as Yii;
use \DictionaryErrors as DictionaryErrors;
use \KmpException as KmpException;

/**
* Структура города
* Обеспечивает получение данных города из kt_ref_cities и kt_ref_cities_match
* и связанную с городом информацию по стране из kt_ref_countries и kt_ref_countries_match
*/
class CityEntity {
  /** @var int ID города (КТ) */
  public $cityId;
  /** @var string название города на русском */
  public $name;
  /** @var string название города на английском */
  public $nameEn;
  /** @var string название страны на русском */
  public $countryName;
  /** @var string название страны на английском */
  public $countryNameEn;

  /** @var array ID города по версии шлюзов */
  private $gatewayCityIds = [];

  /**
  * @param int $cityId ID города (КТ если $gateway == null или соответствующего шлюза)
  * @param int $gateway код шлюза
  */
  public function __construct($cityId, $gateway = null) {
    $command = Yii::app()->db->createCommand()
      ->select('
        ci.CityID as cityId,
        ci.Name as name,
        ci.EngName as nameEn,
        co.Name as countryName,
        co.EngName as countryNameEn')
      ->from('kt_ref_cities as ci')
      ->leftJoin('kt_ref_countries as co','ci.CountryID = co.CountryID');

    if ($gateway !== null) {
      $command
        ->leftJoin('kt_ref_cities_match as cm','cm.CityID = ci.CityID')
        ->where('cm.active = 1 and cm.SupplierID = :gateway and cm.SupplierCityID = :gatewayCityId',
          [
            ':gateway' => $gateway,
            ':gatewayCityId' => $cityId
          ]
        );

      $this->gatewayCityIds[$gateway] = $cityId;
    } else {
      $command->where('ci.CityID = :cityId',[':cityId' => $cityId]);
    }

    $cityData = $command->queryRow();
    if (!is_array($cityData)) {
      /** @todo KmpException: нет искомого города */
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::ENGINE_COMMAND_ERROR,
          ['error' => 'нет данных городов']
      );
    }

    $this->cityId = $cityData['cityId'];
    $this->name = $cityData['name'];
    $this->nameEn = $cityData['nameEn'];
    $this->countryName = $cityData['countryName'];
    $this->countryNameEn = $cityData['countryNameEn'];
  }

  /**
  * Возвращает ID города по версии шлюза
  * @param int $gateway код шлюза
  * @return int ID города
  */
  public function getGatewayCityId($gateway) {
    return Yii::app()->db->createCommand()
      ->select('SupplierCityID')
      ->from('kt_ref_cities_match')
      ->where('active = 1 and CityID = :cityId and SupplierID = :gatewayId', [
        ':cityId' => $this->cityId,
        ':gatewayId' => $gateway,
      ])
      ->queryScalar();
  }
}
