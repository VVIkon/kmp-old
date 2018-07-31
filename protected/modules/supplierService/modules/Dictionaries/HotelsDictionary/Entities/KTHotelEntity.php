<?php
namespace Dictionaries\HotelsDictionary\Entities;

use \Yii as Yii;
use \CDbConnection as CDbConnection;
use \NormalizeHelper as NormalizeHelper;
use Dictionaries\HotelsDictionary\Entities\CityEntity as CityEntity;

/**
* Структура отеля KT
* Работает с таблицами отеля КТ: ho_hotelInfo, ho_hotelMatch, ho_hotelInfoNormalized,
* ho_hotelDescription, ho_hotelService, ho_hotelImage
*/
class KTHotelEntity {
  const AUTO_EDIT = 0;
  const MANUAL_EDIT = 1;

  /* биты блокировки прав на обновление полей в hotelInfo */
  const BLOCK_HOTELNAME_RU = 2**0;
  const BLOCK_HOTELNAME_EN = 2**1;
  const BLOCK_ADDRESS_RU = 2**2;
  const BLOCK_ADDRESS_EN = 2**3;
  const BLOCK_CATEGORY = 2**4;
  const BLOCK_CITYID = 2**5;
  const BLOCK_LATITUDE = 2**6;
  const BLOCK_LONGITUDE = 2**7;
  const BLOCK_IDHOTELCHAIN = 2**8;
  const BLOCK_PHONE = 2**9;
  const BLOCK_FAX = 2**10;
  const BLOCK_EMAIL = 2**11;
  const BLOCK_URL = 2**12;
  const BLOCK_CHECKINTIME = 2**13;
  const BLOCK_CHECKOUTTIME = 2**14;
  const BLOCK_MAINIMAGEURL = 2**15;
  const BLOCK_WEEKDAYSTYPES = 2**16;

  /* усредненный радиус Земли в км для формулы гаверсинусов */
  const EARTH_RADIUS = 6371;

  /**
  * максимальная дистанция между координатами отелей для сматчивания (в км)
  * @todo перенести в конфигурацию
  */
  const MAX_DISTANCE = 0.2;

  /** @var int статус матчинга */
  public $matchStatus;

  /** @var CityEntity данные города */
  private $city;

  /** @var int ID отеля */
  private $hotelId;
  /** @var int признак активности записи */
  private $active;
  /** @var int признак типа редактирования записи (ручной/автоматический) */
  private $manualEdit;
  /** @var string время последнего изменения */
  private $timestamp;
  /** @var string название отеля (на русском) */
  private $name;
  /** @var string название отеля на английском */
  private $nameEn;
  /** @var string адрес отеля (на русском) */
  private $address;
  /** @var string адрес отеля на английском */
  private $addressEn;
  /** @var string категория отеля (ex. THREE,FOUR,...) */
  private $category;
  /** @var int ID главного города отеля (КТ) */
  private $mainCityId;
  /** @var float координаты: широта */
  private $latitude;
  /** @var float координаты: долгота */
  private $longitude;
  /** @var int ID отельной цепи */
  private $hotelChainId;
  /** @var string телефон отеля */
  private $phone;
  /** @var string факс отеля */
  private $fax;
  /** @var string email отеля */
  private $email;
  /** @var string адрес сайта отеля */
  private $url;
  /** @var string время заселения в номер */
  private $checkInTime;
  /** @var string время выезда из номера */
  private $checkOutTime;
  /** @var string URL главного изображения отеля */
  private $mainImageUrl;
  /** @var int дни недели в контексте распределения тарифов (битовая маска) */
  private $weekDaysTypes;
  /** @var array список фотографий отелей */
  private $images;
  /** @var int дни недели в контексте распределения тарифов (битовая маска) */
  private $services;
  /** @var int дни недели в контексте распределения тарифов (битовая маска) */
  private $descriptions;
  /** @var string русское название отеля [нормализованное] */
  private $normalizedName;
  /** @var string английское название отеля [нормализованное] */
  private $normalizedNameEn;
  /** @var string адрес на русском [нормализованный] */
  private $normalizedAddress;
  /** @var string адрес на английском [нормализованный] */
  private $normalizedAddressEn;
  /** @var string телефон отеля [нормализованный]  */
  private $normalizedPhone;
  /** @var string факс отеля [нормализованный] */
  private $normalizedFax;
  /** @var string адрес сайта отеля [нормализованный] */
  private $normalizedUrl;

  /** @var array соответствие полей таблицы hotelInfo параметрам класса */
  private $hotelInfoMap;
  /** @var array соответсвие полей таблицы контантам блокировки */
  private $hotelInfoBlockMap;
  /** @var array соответствие полей таблицы hotelInfoNormalized параметрам класса */
  private $hotelInfoNormalizedMap;
  /** @var array соответсвие полей таблицы контантам блокировки */
  private $hotelInfoNormalizedBlockMap;
  /** @var array соответствие полей таблицы hotelMatch параметрам класса */
  private $hotelMatchMap;

  /** @var array доступные типы описаний отеля */
  private $descriptionTypesDictionary;

  /**
  * @param int $hotelId ID отеля КТ
  */
  public function __construct($hotelId = null) {
    if (!is_null($hotelId)) {
      $this->hotelId = (int)$hotelId;
    }
    $this->setMapping();
  }

  /**
  * Установка соответствия полей базы данных и параметров класса
  */
  private function setMapping() {
    /* поле в БД => поле в структуре */
    $this->hotelInfoMap = [
      'manualEdit' => 'manualEdit',
      'lastUpdate' => 'timestamp',
      'hotelNameRU' => 'name',
      'hotelNameEN' => 'nameEn',
      'addressRU' => 'address',
      'addressEN' => 'addressEn',
      'idHotelChain' => 'hotelChainId',
      'category' => 'category',
      'cityId' => 'mainCityId',
      'latitude' => 'latitude',
      'longitude' => 'longitude',
      'Phone' => 'phone',
      'Fax' => 'fax',
      'Email' => 'email',
      'URL' => 'url',
      'checkInTime' => 'checkInTime',
      'checkOutTime' => 'checkOutTime',
      'mainImageUrl' => 'mainImageUrl',
      'weekDaysTypes' => 'weekDaysTypes'
    ];

    $this->hotelInfoBlockMap = [
      'hotelNameRU' => self::BLOCK_HOTELNAME_RU,
      'hotelNameEN' => self::BLOCK_HOTELNAME_EN,
      'addressRU' => self::BLOCK_ADDRESS_RU,
      'addressEN' => self::BLOCK_ADDRESS_EN,
      'category' => self::BLOCK_CATEGORY,
      'cityId' => self::BLOCK_CITYID,
      'latitude' => self::BLOCK_LATITUDE,
      'longitude' => self::BLOCK_LONGITUDE,
      'idHotelChain' => self::BLOCK_IDHOTELCHAIN,
      'Phone' => self::BLOCK_PHONE,
      'Fax' => self::BLOCK_FAX,
      'Email' => self::BLOCK_EMAIL,
      'URL' => self::BLOCK_URL,
      'checkInTime' => self::BLOCK_CHECKINTIME,
      'checkOutTime' => self::BLOCK_CHECKOUTTIME,
      'mainImageUrl' => self::BLOCK_MAINIMAGEURL,
      'weekDaysTypes' => self::BLOCK_WEEKDAYSTYPES
    ];

    $this->hotelInfoNormalizedMap = [
      'hotelNameRU' => 'normalizedName',
      'hotelNameEN' =>'normalizedNameEn',
      'addressRU' => 'normalizedAddress',
      'addressEN' => 'normalizedAddressEn',
      'phone' => 'normalizedPhone',
      'fax' => 'normalizedFax',
      'url' => 'normalizedUrl',
    ];

    $this->hotelInfoNormalizedBlockMap = [
      'hotelNameRU' => self::BLOCK_HOTELNAME_RU,
      'hotelNameEN' => self::BLOCK_HOTELNAME_EN,
      'addressRU' => self::BLOCK_ADDRESS_RU,
      'addressEN' => self::BLOCK_ADDRESS_EN,
      'phone' => self::BLOCK_PHONE,
      'fax' => self::BLOCK_FAX,
      'url' => self::BLOCK_URL,
    ];
  }

  /**
  * Инициализация справочника типов описаний
  */
  private function initDescriptionTypesDictionary() {
    if (!isset($this->descriptionTypesDictionary) || !is_array($descriptionTypesDictionary)) {
      $typesmap = Yii::app()->db->createCommand()
        ->select('typeId, descriptionType')
        ->from('ho_ref_descriptionType')
        ->where('active = :active',[':active' => 1])
        ->queryAll();
      $this->descriptionTypesDictionary = [];
      foreach ($typesmap as $type) {
        $this->descriptionTypesDictionary[$type['descriptionType']] = $type['typeId'];
      }
    }
  }

  /**
  * Подготовка нормализованных данных отеля
  */
  public function prepareNormalizedData() {
    $this->normalizedName = self::normalizeHotelName($this->name,$this->city);
    $this->normalizedNameEn = (isset($this->nameEn) && !empty($this->nameEn))
      ? self::normalizeHotelName($this->nameEn,$this->city) : null;
    $this->normalizedAddress = (isset($this->address) && !empty($this->address))
      ? self::normalizeHotelAddress($this->address,$this->city) : null;
    $this->normalizedAddressEn = (isset($this->addressEn) && !empty($this->addressEn))
      ? self::normalizeHotelAddress($this->addressEn,$this->city) : null;
    $this->normalizedPhone = (isset($this->phone) && !empty($this->phone))
      ? self::normalizePhoneNumber($this->phone) : null;
    $this->normalizedFax = (isset($this->fax) && !empty($this->fax))
      ? self::normalizePhoneNumber($this->fax) : null;
    $this->normalizedUrl = (isset($this->url) && !empty($this->url))
      ? self::normalizeUrl($this->url) : null;
  }

  /**
  * Поиск отеля по ID группы шлюза
  * @param int $groupId ID группы отелей шлюза
  * @param int $gatewayId ID шлюза
  * @return bool результат поиска
  */
  public function findByGroupId($groupId,$gatewayId) {
    $command = Yii::app()->db->createCommand()
      ->selectDistinct('hotelId')
      ->from('ho_hotelMatch')
      ->where('gatewayGroupId = :groupId and gatewayId = :gatewayId',
          [
            ':groupId' => $groupId,
            ':gatewayId' => $gatewayId
          ]
        );
    $hotelId = $command->queryScalar();

    if ($hotelId !== false) {
      $this->hotelId = $hotelId;
      return true;
    } else {
      return false;
    }
  }

  /**
  * Поиск отеля по кодам поставщика
  * @param string $supplierCode код поставщика
  * @param string $hotelCode код отеля
  * @param int $cityId идентификатор города (КТ)
  * @param int $gatewayId ID шлюза
  * @return bool результат поиска
  */
  public function findBySupplierCodes($supplierCode,$hotelCode,$cityId,$gatewayId) {
    $command = Yii::app()->db->createCommand()
      ->selectDistinct('hm.hotelId')
      ->from('ho_hotelMatch as hm')
      ->join('ho_hotelInfo as hi', 'hm.hotelId = hi.hotelId')
      ->where(
        'hm.supplierCode = :supplierCode and
         hm.supplierHotelCode = :hotelCode and
         hi.cityId = :cityId and
         hm.gatewayId = :gatewayId',
        [
          ':supplierCode' => (string)$supplierCode,
          ':hotelCode' => (string)$hotelCode,
          ':cityId' => $cityId,
          ':gatewayId' => $gatewayId
        ]
      );
    $hotelId = $command->queryScalar();

    if ($hotelId !== false) {
      $this->hotelId = $hotelId;
      return true;
    } else {
      return false;
    }
  }

  /**
  * Инициализация из переданного набора параметров
  * @param array $hotelData Данные отеля в структуре КТ
  */
  public function initFromParams($hotelData) {
    foreach ($this->hotelInfoMap as $param) {
      if (isset($hotelData[$param])) {
        $this->$param = $hotelData[$param];
      } else {
        $this->$param = null;
      }
    }

    if (isset($hotelData['hotelChain']) && $hotelData['hotelChain'] != null) {
      $this->hotelChainId = Yii::app()->db->createCommand()
        ->select('idHotelChain')
        ->from('ho_ref_hotelChain')
        ->where('hotelChainCode = :chainCode',[':chainCode' => $hotelData['hotelChain']['hotelChainCode']])
        ->queryScalar();

      if ($this->hotelChainId == false) {
        Yii::app()->db->createCommand()->insert('ho_ref_hotelChain',[
          'hotelChainCode' => $hotelData['hotelChain']['hotelChainCode'],
          'nameRU' => $hotelData['hotelChain']['hotelChain'],
          'nameEN' => $hotelData['hotelChain']['hotelChain'],
          'active' => 1,
          'manualEdit' => 0,
          'lastUpdate' => date('Y-m-d H:i:s')
        ]);

        $this->hotelChainId = Yii::app()->db->getLastInsertId();
      }
    }

    $this->images = (!isset($hotelData['images']) || !is_array($hotelData['images'])) ? [] : 
      array_filter($hotelData['images'], function($img) {
        return (bool)preg_match('/^(http|www)[^\s]+$/u', $img['url']);
      });

    $this->services = (isset($hotelData['services']) && is_array($hotelData['services']))
      ? $hotelData['services'] : [];

    $this->descriptions = (isset($hotelData['descriptions']) && is_array($hotelData['descriptions']))
      ? $hotelData['descriptions'] : [];
  }

  /**
  * Установка данных города
  * @param CityEntity $city
  */
  public function setCityData(CityEntity $city) {
    $this->city = $city;
    $this->mainCityId = $city->cityId;
  }

  /**
  * Создание отеля
  */
  public function create() {
    $hotelInfo = [];
    foreach ($this->hotelInfoMap as $field => $param) {
      if (isset($this->$param) && !is_null($this->$param)) {
        $hotelInfo[$field] = $this->$param;
      }
    }
    $hotelInfo['lastUpdate'] = date('Y-m-d H:i:s');
    Yii::app()->db->createCommand()->insert('ho_hotelInfo', $hotelInfo);

    $this->hotelId = Yii::app()->db->getLastInsertId();

    $this->prepareNormalizedData();
    $hotelInfoNormalized = [];

    foreach ($this->hotelInfoNormalizedMap as $field => $param) {
      if (isset($this->$param) && !is_null($this->$param)) {
        $hotelInfoNormalized[$field] = $this->$param;
      }
    }
    $hotelInfoNormalized['hotelId'] = $this->hotelId;
    $command = Yii::app()->db->createCommand()
      ->insert('ho_hotelInfoNormalized', $hotelInfoNormalized);

    /* создание данных изображений отеля */
    foreach ($this->images as $image) {
      $this->createHotelImage($image);
    }

    $this->initDescriptionTypesDictionary();

    /* создание описаний отеля */
    foreach ($this->descriptions as $description) {
      $descType = $description['descriptionType'];
      $typeId = hashtableval($this->descriptionTypesDictionary[$descType],false);

      if ($typeId !== false) {
        $description['typeId'] = $typeId;
        $this->createHotelDescription($description);
      }
    }

    /* создание данных услуг отеля */
    foreach ($this->services as $service) {
      $this->createHotelService($service);
    }
  }

  /**
  * Обновление отеля
  */
  public function update() {
    // обновление данных отеля
    $updateParams = [
      'needUpdate' => ':needUpdate',
      'lastUpdate' => ':lastUpdate'
    ];
    $paramsMapping = [
      ':needUpdate' => 0,
      ':lastUpdate' => date('Y-m-d H:i:s'),
      ':hotelId' => $this->hotelId
    ];

    $sql = 'update ho_hotelInfo';
    foreach ($this->hotelInfoMap as $field => $param) {
      if (isset($this->$param) && !is_null($this->$param)) {
        if (isset($this->hotelInfoBlockMap[$field])) {
          $blockFlag = $this->hotelInfoBlockMap[$field];
          $updateParams[$field] = 'if(' . 
          'manualEdit & ' . $blockFlag . ' = ' . $blockFlag . ',' . 
          $field . ', ' . 
          ':' . $field . ')';
        } else {
          $updateParams[$field] = ':'.$field;
        }
          $paramsMapping[':'.$field] = $this->$param;
      }
    }

    $sql .= ' set ' . implode(',',array_map(function($f, $v) {
      return $f . ' = ' . $v;
    }, array_keys($updateParams), array_values($updateParams)));
    $sql .= ' where hotelId = :hotelId';
    Yii::app()->db->createCommand($sql)->execute($paramsMapping);

    // обновление таблицы с нормализованными данными
    $this->prepareNormalizedData();
    $updateParams = [];
    $paramsMapping = [
      ':hotelId' => $this->hotelId
    ];

    $sql = 'update ho_hotelInfoNormalized as hn join ho_hotelInfo as hi on hn.hotelId = hi.hotelId';
    foreach ($this->hotelInfoNormalizedMap as $field => $param) {
      if (isset($this->$param) && !is_null($this->$param)) {
        if (isset($this->hotelInfoNormalizedBlockMap[$field])) {
          $blockFlag = $this->hotelInfoNormalizedBlockMap[$field];
          $updateParams[$field] = 'if(' .
              'hi.manualEdit & ' . $blockFlag . ' = ' . $blockFlag . ', ' .
              'hn.' . $field . ', ' .
              ':' . $field . ')';
        } else {
          $updateParams[$field] = ':'.$field;
        }
        $paramsMapping[':'.$field] = $this->$param;
      }
    }

    $sql .= ' set ' . implode(',',array_map(function($f, $v) {
      return 'hn.' . $f . ' = ' . $v;
    }, array_keys($updateParams), array_values($updateParams)));
    $sql .= ' where hn.hotelId = :hotelId';
    Yii::app()->db->createCommand($sql)->execute($paramsMapping);

    // обновление изображений
    $existingImageUrls = Yii::app()->db->createCommand()
      ->select('imageURL')
      ->from('ho_hotelImage')
      ->where('hotelId = :hotelId',[':hotelId' => $this->hotelId])
      ->queryColumn();

    if (is_array($existingImageUrls)) {
      $this->images = array_filter($this->images,function($img) use ($existingImageUrls) {
        return !in_array($img['url'],$existingImageUrls);
      });
    }
    foreach ($this->images as $image) {
      $this->createHotelImage($image);
    }

    $this->initDescriptionTypesDictionary();

    $existingDescriptionTypes = Yii::app()->db->createCommand()
      ->select('typeId, manualEdit')
      ->from('ho_hotelDescription')
      ->where('hotelId = :hotelId',[':hotelId' => $this->hotelId])
      ->queryAll();
    $descriptionTypesMap = [];
    foreach ($existingDescriptionTypes as $type) {
      $descriptionTypesMap[$type['typeId']] = $type['manualEdit'];
    }

    foreach ($this->descriptions as $description) {
      $descType = $description['descriptionType'];
      $typeId = hashtableval($this->descriptionTypesDictionary[$descType],false);

      if ($typeId !== false) {
        $description['typeId'] = $typeId;

        if (isset($descriptionTypesMap[$typeId])) {
          if ($descriptionTypesMap[$typeId] == self::AUTO_EDIT) {
            $this->updateHotelDescription($description);
          }
        } else {
          $this->createHotelDescription($description);
        }
      }
    }

    $existingServices = Yii::app()->db->createCommand()
      ->select('otaCode, manualEdit')
      ->from('ho_hotelService')
      ->where('hotelId = :hotelId',[':hotelId' => $this->hotelId])
      ->queryAll();
    $servicesMap = [];
    foreach ($existingServices as $srv) {
      $servicesMap[$srv['otaCode']] = $srv['manualEdit'];
    }

    foreach ($this->services as $service) {
      $otaCode = $service['otaCode'];

      if (isset($servicesMap[$otaCode])) {
        if ($servicesMap[$otaCode] == self::AUTO_EDIT) {
          $this->updateHotelService($service);
        }
      } else {
        $this->createHotelService($service);
      }
    }
  }

  /**
  * Заполнение недостающих полей отеля
  */
  public function fill() {
    $updateFields = [];
    $bindings = [];
    foreach ($this->hotelInfoMap as $field => $param) {
      if (isset($this->$param) && !is_null($this->$param)) {
        $updateFields[] = $field.' = if('.$field.' is null,:'.$field.','.$field.')';
        $bindings[':'.$field] = $this->$param;
      }
    }
    $bindings[':hotelId'] = $this->hotelId;

    $query = 'update ho_hotelInfo set '.implode(',',$updateFields).' where hotelId = :hotelId';
    Yii::app()->db->createCommand($query)->execute($bindings);

    $this->prepareNormalizedData();
    $updateFields = [];
    $bindings = [];
    foreach ($this->hotelInfoNormalizedMap as $field => $param) {
      if (isset($this->$param) && !is_null($this->$param)) {
        $updateFields[] = $field.' = if('.$field.' is null,:'.$field.','.$field.')';
        $bindings[':'.$field] = $this->$param;
      }
    }
    $bindings[':hotelId'] = $this->hotelId;

    $query = 'update ho_hotelInfoNormalized set '.implode(',',$updateFields).' where hotelId = :hotelId';
    $command = Yii::app()->db->createCommand($query)->execute($bindings);
  }

  /**
  * Отметить отель как неактивный
  */
  public function deactivate() {
    $command = Yii::app()->db->createCommand()
      ->update('ho_hotelInfo', ['active' => 0], 'hotelId = :hotelId', [':hotelId' => $this->hotelId]);
  }

  /**
  * Создает запись фотографии отеля
  * @param array $imagedata данные фотографии
  */
  private function createHotelImage($image) {
    $command = Yii::app()->db->createCommand()
      ->insert('ho_hotelImage', [
        'hotelId' => $this->hotelId,
        'imageURL' => $image['url'],
        'descriptionRU' => $image['descriptionRu'],
        'descriptionEN' => $image['descriptionEn'],
        'active' => $image['active'],
        'manualEdit' => $image['manualEdit'],
        'lastUpdate' => $image['timestamp']
      ]);
  }

  /**
  * Создает запись описания отеля
  * @param array $description данные описания
  */
  private function createHotelDescription($description) {
    $command = Yii::app()->db->createCommand()
      ->insert('ho_hotelDescription', [
        'hotelId' => $this->hotelId,
        'typeId' => $description['typeId'],
        'descriptionRU' => $description['descriptionRu'],
        'descriptionEN' => $description['descriptionEn'],
        'active' => $description['active'],
        'manualEdit' => $description['manualEdit'],
        'lastUpdate' => $description['timestamp']
      ]);
  }

  /**
  * Обновляет запись описания отеля
  * @param array $description данные описания
  */
  private function updateHotelDescription($description) {
    $command = Yii::app()->db->createCommand()
      ->update(
        'ho_hotelDescription',
        [
          'descriptionRU' => $description['descriptionRu'],
          'descriptionEN' => $description['descriptionEn'],
          'lastUpdate' => $description['timestamp']
        ],
        'hotelId = :hotelId and typeId = :typeId',
        [
          ':hotelId' => $this->hotelId,
          ':typeId' => $description['typeId'],
        ]
      );
  }

  /**
  * Создает запись услуги отеля
  * @param array $service данные услуги
  */
  private function createHotelService($service) {
    $command = Yii::app()->db->createCommand()
      ->insert('ho_hotelService', [
        'hotelId' => $this->hotelId,
        'otaCode' => $service['otaCode'],
        'isBillable' => $service['isBillable'],
        'active' => $service['active'],
        'manualEdit' => $service['manualEdit'],
        'lastUpdate' => $service['timestamp']
      ]);
  }

  /**
  * Обновляет запись услуги отеля
  * @param array $service данные услуги
  */
  private function updateHotelService($service) {
    $command = Yii::app()->db->createCommand()
      ->update(
        'ho_hotelService',
        [
          'isBillable' => $service['isBillable'],
          'lastUpdate' => $service['timestamp']
        ],
        'hotelId = :hotelId and otaCode = :otaCode',
        [
          ':hotelId' => $this->hotelId,
          ':otaCode' => $service['otaCode'],
        ]
      );
  }

  /**
  * Создание записи матчинга отеля
  * @param GatewayHotelEntity $gatewayHotel отель из шлюза
  */
  public function createMatch(GatewayHotelEntity $gatewayHotel) {
    $hotelMatchMap = [
      'hotelId' => $this->hotelId,
      'gatewayId' => $gatewayHotel->gatewayId,
      'gateway' => $gatewayHotel->gatewayName,
      'gatewayHotelId' => $gatewayHotel->hotelId,
      'gatewayGroupId' => $gatewayHotel->groupId,
      'supplierCode' => $gatewayHotel->supplierCode,
      'supplierHotelCode' => $gatewayHotel->hotelCode,
      'CityID' => $gatewayHotel->mainCityId,
      'cityName' => $gatewayHotel->cityName,
      'CountryID' => $gatewayHotel->countryId,
      'countryName' => $gatewayHotel->countryName,
      'matchType' => self::AUTO_EDIT,
      'matchStatus' => $this->matchStatus,
      'active' => 1,
      'manualEdit' => self::AUTO_EDIT,
      'lastUpdate' => date('Y-m-d H:i:s')
    ];

    Yii::app()->db->createCommand()->insert('ho_hotelMatch', $hotelMatchMap);
  }

  /**
  * Обновление записи матчинга отеля
  * @param GatewayHotelEntity $gatewayHotel отель из шлюза
  */
  public function updateMatch(GatewayHotelEntity $gatewayHotel) {
    $hotelMatchMap = [
      'hotelId' => $this->hotelId,
      'gatewayHotelId' => $gatewayHotel->hotelId,
      'gatewayGroupId' => $gatewayHotel->groupId,
      'cityName' => $gatewayHotel->cityName,
      'CountryId' => $gatewayHotel->countryId,
      'countryName' => $gatewayHotel->countryName,
      'matchType' => self::AUTO_EDIT,
      'matchStatus' => $this->matchStatus,
      'active' => 1,
      'manualEdit' => self::AUTO_EDIT,
      'lastUpdate' => date('Y-m-d H:i:s')
    ];

    $command = Yii::app()->db->createCommand()
      ->update('ho_hotelMatch', $hotelMatchMap,
        'supplierCode = :supplierCode and
         supplierHotelCode = :hotelCode and
         CityId = :mainCityId and
         gatewayId = :gatewayId and
         manualEdit = :autoedit',
        [
          ':supplierCode' => $gatewayHotel->supplierCode,
          ':hotelCode' => $gatewayHotel->hotelCode,
          ':mainCityId' => $gatewayHotel->mainCityId,
          ':gatewayId' => $gatewayHotel->gatewayId,
          ':autoedit' => self::AUTO_EDIT
        ]
       );
  }

  /**
  * Попытка матчинга с уже имеющимися отелями
  * @param array $matchRules функции с правилами матчинга,
  * на вход передаются два массива соответственно с данными текущего отеля и кандидата на сматчивание
  * @param int|null $excludeHotelId - ID отеля для исключения (не проверять матчинг с самим собой)
  * @todo переделать на передачу экземпляров класса?
  * @return bool результат операции
  */
  public function tryToMatch($matchRules, $excludeHotelId = null) {
    $this->prepareNormalizedData();

    $matchData = [
      'name' => $this->normalizedName,
      'nameEn' => $this->normalizedNameEn,
      'address' => $this->normalizedAddress,
      'addressEn' => $this->normalizedAddressEn,
      'phone' => $this->normalizedPhone,
      'fax' => $this->normalizedFax,
      'url' => $this->normalizedUrl,
      'email' => (isset($this->email) && !empty($this->email)) ? $this->email : null,
      'lat' => (isset($this->latitude) && !is_null($this->latitude)) ? $this->latitude : null,
      'lon' => (isset($this->longitude) && !is_null($this->longitude)) ? $this->longitude : null
    ];

    $this->launchMatchCandidatesDataReader(function ($candidate) use ($matchData,&$matchRules) {

      foreach ($matchRules as $nm => $rule) {
        if ($rule($matchData,$candidate)) {
          $this->hotelId = $candidate['hotelId'];
          return true;
        }
      }
      return false;
    }, $excludeHotelId);

    if (isset($this->hotelId) && !empty($this->hotelId)) {
      return true;
    } else {
      return false;
    }
  }

  /**
  * Проверка матчинга обновленных данных с имеющимся в базе отелем
  * @return bool результат операции
  */
  public function checkMatch($matchRules) {
    $this->prepareNormalizedData();

    $matchData = [
      'name' => $this->normalizedName,
      'nameEn' => $this->normalizedNameEn,
      'address' => $this->normalizedAddress,
      'addressEn' => $this->normalizedAddressEn,
      'phone' => $this->normalizedPhone,
      'fax' => $this->normalizedFax,
      'url' => $this->normalizedUrl,
      'email' => (isset($this->email) && !empty($this->email)) ? $this->email : null,
      'lat' => (isset($this->latitude) && !empty($this->latitude)) ? $this->latitude : null,
      'lon' => (isset($this->longitude) && !empty($this->longitude)) ? $this->longitude : null
    ];

    $savedHotelData = Yii::app()->db->createCommand()
      ->select([
        'hn.phone',
        'hn.fax',
        'hn.url',
        'hn.hotelNameRU as name',
        'hn.hotelNameEn as nameEn',
        'hn.addressRU as address',
        'hn.addressEn as addressEn',
        'hi.Email as email',
        'hi.latitude as lat',
        'hi.longitude as lon'
      ])
      ->from('ho_hotelInfo as hi')
      ->join('ho_hotelInfoNormalized as hn','hi.hotelId = hn.hotelId')
      ->where('hi.hotelId = :hotelId',[':hotelId' => $this->hotelId])
      ->queryRow();

    foreach ($matchRules as $rule) {
      if ($rule($matchData,$savedHotelData)) {
        return true;
      }
    }
    return false;
  }

  /**
  * Отметить отель для обновления контента
  */
  public function stageForUpdate() {
    $command = Yii::app()->db->createCommand()
      ->update(
        'ho_hotelInfo',['needUpdate' => 1],
        'hotelId = :hotelId and manualEdit = :autoedit',
        [
          ':hotelId' => $this->hotelId,
          ':autoedit' => self::AUTO_EDIT
        ]
      );
  }

  /**
  * Получение количества связанных с отелем записей матчинга
  * @param int|null $gatewayId если указан ID шлюза, считать только его матчинг
  * @return int число связанных записей
  */
  public function getMatchesCount($gatewayId = null) {
    $command = Yii::app()->db->createCommand()
      ->select('count(*)')
      ->from('ho_hotelMatch')
      ->where('hotelId = :hotelId',[':hotelId' => $this->hotelId]);

    if (!is_null($gatewayId)) {
      $command->andWhere('gatewayId = :gatewayId',[':gatewayId' => $gatewayId]);
    }

    return $command->queryScalar();
  }

  /**
  * Получение количества связанных с отелем записей матчинга с группой конкретного поставщика
  * @param int $gatewayId если указан ID шлюза, считать только его матчинг
  * @return int число связанных записей
  */
  public function getGroupMatchesCount($gatewayId) {
    $command = Yii::app()->db->createCommand()
      ->select('count(*)')
      ->from('ho_hotelMatch')
      ->where(
        'hotelId = :hotelId and gatewayId = :gatewayId and gatewayGroupId is not null',
        [
          ':hotelId' => $this->hotelId,
          ':gatewayId' => $gatewayId
        ]
       );

    return $command->queryScalar();
  }

  /**
  * Проверяет, есть ли у отеля записи матчинга от других шлюзов
  * @param int $gatewayId ID шлюза
  * @return bool результат проверки
  */
  public function hasAnotherGatewayMatches($gatewayId) {
    $hasAGM = Yii::app()->db->createCommand()
      ->select('(max(gatewayId) = min(gatewayId) and gatewayId = '.$gatewayId.')')
      ->from('ho_hotelMatch')
      ->where('hotelId = :hotelId', [ ':hotelId' => $this->hotelId ])
      ->queryScalar();

    return (bool)$hasAGM;
  }

  public function __get($n) {
    switch ($n) {
      case 'hotelId':
        return $this->hotelId;
        break;
      default:
        return null;
        break;
    }
  }

  /**
  * Запуск построчной обработки кандидатов на сматчивание
  * @param Callable $processor функция обработки записи,
  * @param int|null $excludeHotelId ID отеля для исключения из выборки
  * должна возвращать true если сматчилось, и false если запись матчинга еще не найдена
  */
  private function launchMatchCandidatesDataReader(Callable $processor, $excludeHotelId) {
    /* вычисление условий поиска кандидатов для матчинга */
    $conditions = [];
    $bindings = [];

    $conditions[] = 'name = :name';
    $bindings[':name'] = $this->normalizedName;

    if (!is_null($this->normalizedNameEn)) {
      $conditions[] = 'nameEn = :nameEn';
      $bindings[':nameEn'] = $this->normalizedNameEn;
    }
    if (!is_null($this->normalizedAddress)) {
      $conditions[] = 'address = :address';
      $bindings[':address'] = $this->normalizedAddress;
    }
    if (!is_null($this->normalizedAddressEn)) {
      $conditions[] = 'addressEn = :addressEn';
      $bindings[':addressEn'] = $this->normalizedAddressEn;
    }
    if (isset($this->email) && !empty($this->email)) {
      $conditions[] = 'email = :email';
      $bindings[':email'] = preg_replace('/\s/u','',$this->email);
    }
    if (!is_null($this->normalizedPhone)) {
      $conditions[] = 'phone = :phone';
      $bindings[':phone'] = $this->normalizedPhone;
    }
    if (!is_null($this->normalizedFax)) {
      $conditions[] = 'fax = :fax';
      $bindings[':fax'] = $this->normalizedFax;
    }
    if (!is_null($this->normalizedUrl)) {
      $conditions[] = 'url = :url';
      $bindings[':url'] = $this->normalizedUrl;
    }

    if (
      isset($this->latitude) && isset($this->longitude)
      && !is_null($this->latitude) && !is_null($this->longitude)
    ) {
      /*
      * Вычисление допустимой разницы координат при заданном расстоянии:
      * $deltaLat - допустимая разница широт при одинаковой долготе
      * $deltaLon - допустимая разница долгот при одинаковой широте
      * т.е. для двух координат где lat1=lat2 расстояние между точками при соблюдении
      * условия abs(lon2-lon1) < deltaLon будет не более MAX_DISTANCE
      *
      * Формулы выведены из формулы гаверсинусов
      */
      $deltaLat = (180 * self::MAX_DISTANCE) / self::EARTH_RADIUS * pi();
      $deltaLon = asin(
        sin( self::MAX_DISTANCE / (self::EARTH_RADIUS * 2) ) /
        sqrt( cos($this->latitude * pi()/180) * cos(abs($this->latitude) * pi()/180) )
      ) * 180 * 2 / pi();

      /*
      * для упрощения выборки вместо вычисления расстояния между точками выбираем координаты,
      * попадающие в квадрат, описанный около круга с заданным радиусом
      */
      $conditions[] = 'latitude is not null and longitude is not null
        and abs(latitude - :lat) <= :deltaLat and abs(longitude - :lon) <= :deltaLon';
      $bindings[':lat'] = $this->latitude;
      $bindings[':deltaLat'] = $deltaLat;
      $bindings[':lon'] = $this->longitude;
      $bindings[':deltaLon'] = $deltaLon;
    }

    $conn = new CDbConnection(
      Yii::app()->db->connectionString,
      Yii::app()->db->username,
      Yii::app()->db->password
    );


    $hotelsInfo_tmp = 'hotels_info_' . $this->mainCityId;

    $query = 'create temporary table if not exists ' . $hotelsInfo_tmp . '
              as (
                select ' . implode(',', [
                    'hi.hotelId as hotelId',
                    'hn.phone as phone',
                    'hn.fax as fax',
                    'hn.url as url',
                    'hn.hotelNameRU as name',
                    'hn.hotelNameEn as nameEn',
                    'hn.addressRU as address',
                    'hn.addressEn as addressEn',
                    'hi.Email as email',
                    'hi.latitude as latitude',
                    'hi.longitude as longitude'
                  ]) . '
                from ho_hotelInfo as hi
                join ho_hotelInfoNormalized as hn
                 on hi.hotelId = hn.hotelId
                where
                  hi.cityId = :cityId and 
                  hi.active = 1
              )';
    $conn->createCommand($query)->execute([':cityId' => $this->mainCityId]);

    $command = $conn->createCommand()
      ->select('*')
      ->from($hotelsInfo_tmp)
      ->where(implode(' or ',$conditions), $bindings);

    if (!is_null($excludeHotelId)) {
      $command->andWhere('hotelId != :excludeHotelId',[':excludeHotelId' => $excludeHotelId]);
    }

    $candidatesReader = $command->query();

    foreach ($candidatesReader as $candidate) {
      if ($processor($candidate)) { break; }
    }

    $candidatesReader->close();
    $conn->active = false;
  }

  /**
  * Нормализация названия отеля
  * @param string $s название отеля
  * @param CityEntity $city данные города отеля
  * @return string нормализованное название
  */
  public static function normalizeHotelName($s,CityEntity $city) {
    $s = preg_replace('/[^\s\w]/u','',$s);
    $s = preg_replace('/\s+/u',' ',$s);
    $s = trim($s);
    $s = NormalizeHelper::normalizeUtf8String($s);
    $s = Yii::app()->db->createCommand(
        'select NormalizeString(
          "'.$s.'",
          0,0,0,1,
          '.$city->cityId.',
          3
        )'
      )->queryScalar();
    $s = preg_replace('/\s+/u',' ',$s);
    $s = trim($s);
    return $s;
  }

  /**
  * Нормализация адреса отеля
  * @param string $s адрес отеля
  * @param CityEntity $city данные города отеля
  * @return string нормализованное название
  */
  public static function normalizeHotelAddress($s,CityEntity $city) {
    $s = preg_replace('/[^\s\w]/u','',$s);
    $s = preg_replace('/\s+/u',' ',$s);
    $s = trim($s);
    $s = NormalizeHelper::normalizeUtf8String($s);
    $s = Yii::app()->db->createCommand(
        'select NormalizeString(
          "'.$s.'",
          0,0,0,1,
          '.$city->cityId.',
          3
        )'
      )->queryScalar();
    $s = preg_replace('/\s+/u',' ',$s);
    $s = trim($s);
    return $s;
  }

  /**
  * Нормализация номера телефона
  * @param string $n номер телефона/факса
  * @return string нормализованный номер
  */
  public static function normalizePhoneNumber($s) {
    $s = preg_replace('/\D/u','',$s);
    return $s;
  }

  /**
  * Нормализация url
  * @param string $s URL
  * @return string нормализованный URL
  */
  public static function normalizeUrl($s) {
    $s = preg_replace(['/\s/u','/\\\\/u'],'',$s);
    $s = preg_replace(['@^[^./]+://@u','@www\.@u'],'',$s);
    return $s;
  }
}
