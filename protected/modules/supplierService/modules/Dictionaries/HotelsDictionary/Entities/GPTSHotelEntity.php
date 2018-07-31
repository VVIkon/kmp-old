<?php

namespace Dictionaries\HotelsDictionary\Entities;

use \Yii as Yii;
use \KmpException as KmpException;
use \LogHelper as LogHelper;
use \DictionaryErrors as DictionaryErrors;
use \CitiesMapperHelper as CitiesMapperHelper;
use Dictionaries\HotelsDictionary\Entities\GatewayHotelEntity as GatewayHotelEntity;
use Dictionaries\HotelsDictionary\Entities\CityEntity as CityEntity;

/**
* Структура отеля GPTS
* Работает с набором данных от GPTS,
* таблицами gpts_hotelsMeta_lastVersion и gpts_hotelsMeta_lastVersion
*/
class GPTSHotelEntity extends GatewayHotelEntity {
  protected $gatewayId = 5;
  protected $gatewayName = 'GPTS';

  /** @var GPTSEngineModule ссылка на модуль движка GPTS */
  private $gptsEngine;
  /** @var array структура данных отеля от GPTS */
  private $gptsHotelInfo;
  /** @var bool флаг, определяющий наличие отеля в таблице lastVersion */
  private $isNew;

  /** @var array список типов описаний отеля */
  private static $hotelDescriptionTypes = [
    'apartments',
    'bestPrice',
    'checkInOut',
    'facilities',
    'general',
    'inclusive',
    'lobby',
    'location',
    'meals',
    'other',
    'payment',
    'pleasenote',
    'position',
    'prices',
    'restaurant',
    'rooms',
    'route',
    'short',
    'spa',
    'sport',
    'transportation'
  ];

  public function __construct(&$gptsEngine) {
    $this->gptsEngine = $gptsEngine;
  }

  /**
  * Инициализация из необходимого набора переданных параметров (от api /hotels)
  * @param array $hotelinfo данные отеля для инициализации
  * @param int $cityId ID обрабатываемого города (главный город)
  */
  public function initFromParams($hotelinfo,$cityId) {
    if (empty($hotelinfo['supplierCode']) || empty($hotelinfo['hotelCode'])) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::UNKNOWN_SUPPLIER_CODE,
          $hotelinfo
      );
    }

    $this->mainCityId = $cityId;
    $this->hotelId = (isset($hotelinfo['id'])) ? $hotelinfo['id'] : $hotelinfo['hotelId'];
    $this->supplierCode = $hotelinfo['supplierCode'];
    $this->hotelCode = $hotelinfo['hotelCode'];
    $this->groupId = !empty($hotelinfo['groupId']) ? $hotelinfo['groupId'] : null;

    if (isset($hotelinfo['location'])) {
      $this->cityId = $hotelinfo['location']['cityId'];
      $this->cityName = hashtableval($hotelinfo['location']['cityName'], null);
      $this->countryId = hashtableval($hotelinfo['location']['countryId'], null);
      $this->countryName = hashtableval($hotelinfo['location']['countryName'], null);
    } else {
      $this->cityId = $hotelinfo['cityId'];
      $this->cityName = hashtableval($hotelinfo['cityName'], null);
      $this->countryId = hashtableval($hotelinfo['countryId'], null);
      $this->countryName = hashtableval($hotelinfo['countryName'], null);
    }

    $this->lastUpdate = !empty($hotelinfo['modified']) ? $hotelinfo['modified'] : null;

    return $this;
  }

  /**
  * Заполнение полной информации по отелю (данными из GPTS /hotelInfo)
  * @param array $hotelinfo полные данные отеля
  * @return bool результат операции
  */
  public function setFullInfo($hotelinfo) {
    if (
      empty($hotelinfo['hotelName']) ||
      empty($hotelinfo['address']) ||
      empty($hotelinfo['address']['addressLine'])
    ) { return false; }

    $hotelinfo['hotelId'] = $this->hotelId;
    $hotelinfo['supplierCode'] = $this->supplierCode;
    $hotelinfo['hotelCode'] = $this->hotelCode;
    $hotelinfo['groupId'] = $this->groupId;
    $hotelinfo['cityId'] = $this->cityId;
    $hotelinfo['cityName'] = $this->cityName;
    $hotelinfo['countryId'] = $this->countryId;
    $hotelinfo['countryName'] = $this->countryName;
    $hotelinfo['lastUpdate'] = $this->lastUpdate;

    /* убирает слишком длинные описания, т.к. скорее всего в них мусор */
    foreach ($hotelinfo['descriptions']['description'] as $idx => $desc) {
      if (mb_strlen($desc['description']) > 5000) {
        unset($hotelinfo['descriptions']['description'][$idx]);
      }
    }

    $this->active = isset($hotelinfo['active']) ? (bool)$hotelinfo['active'] : true;
    $hotelinfo['active'] = $this->active;
    $this->gptsHotelInfo = $hotelinfo;

    return true;
  }

  /**
  * Заполнение данными из поиска
  * @param mixed $hotelInfo - информация об отеле из GPTS
  * @param int $mainCityId - ID главного города (GPTS)
  */
  public function initFromSearch($hotelInfo, $mainCityId) {
    if (empty($hotelInfo['supplierCode']) || empty($hotelInfo['hotelCode']) || empty($hotelInfo['address'])) {
      LogHelper::logExt(
          'Dictionary','initHotelFromSearch','','cannot identify hotel',
          [
            'supplierCode' => !empty($hotelInfo['supplierCode']) ? $hotelInfo['supplierCode'] : 'null',
            'hotelCode' => !empty($hotelInfo['hotelCode']) ? $hotelInfo['hotelCode'] : 'null',
            'address' => !empty($hotelInfo['address']) ? $hotelInfo['address'] : 'null'
          ],
          LogHelper::MESSAGE_TYPE_WARNING,
          'system.supplierservice.errors'
      );

      return false;
    }

    $this->isNew = false;
    $this->active = true;

    $this->mainCityId = $mainCityId;
    $this->hotelId = null;
    $this->supplierCode = $hotelInfo['supplierCode'];
    $this->hotelCode = $hotelInfo['hotelCode'];
    $this->groupId = null;

    $this->cityId = $mainCityId;
    $this->cityName = null;
    $this->countryId = null;
    $this->countryName = null;
    
    $this->lastUpdate = null;

    $stdCategories = [
      1 => 'ONE',
      2 => 'TWO',
      3 => 'THREE',
      4 => 'FOUR',
      5 => 'FIVE'
    ];

    $this->gptsHotelInfo = [
      'hotelId' => 0,
      'supplierCode' => $hotelInfo['supplierCode'],
      'hotelCode' => $hotelInfo['hotelCode'],
      'active' => true,
      'hotelName' => $hotelInfo['name'],
      'address' => [
        'addressLine' => isset($hotelInfo['address']) ? $hotelInfo['address'] : ''
      ],
      'cityId' => $mainCityId,
      'category' => isset($stdCategories[$hotelInfo['category']]) ?
        $stdCategories[$hotelInfo['category']] : 'OTHER',
      'url' => isset($hotelInfo['url']) ? $hotelInfo['url'] : null,
      'email' => isset($hotelInfo['email']) ? $hotelInfo['email'] : null,
      'phone' => isset($hotelInfo['phone']) ? $hotelInfo['phone'] : null,
      'fax' => isset($hotelInfo['fax']) ? $hotelInfo['fax'] : null,
      'latitude' => isset($hotelInfo['latitude']) ? $hotelInfo['latitude'] : null,
      'longitude' => isset($hotelInfo['longitude']) ? $hotelInfo['longitude'] : null,
      'mainImageUrl' => isset($hotelInfo['mainImageUrl']) ? $hotelInfo['mainImageUrl'] : null,
      'images' => [],
      'services' => [],
      'descriptions' => []
    ];

    return true;
  }

  /**
  * Заполнение данных на другом языке
  * @param array $hotelinfo данные отеля на другом языке
  * @param string $lang язык (двухбуквенный код)
  */
  public function setTranslation($hotelinfo,$lang) {
    if ($lang == 'en') {
      $this->gptsHotelInfo['hotelNameEn'] = $hotelinfo['hotelName'];
      $this->gptsHotelInfo['address']['addressLineEn'] = $hotelinfo['address']['addressLine'];

      foreach ($hotelinfo['descriptions']['description'] as $idx => &$desc) {
        if (mb_strlen($desc['description']) > 5000) {
          unset($hotelinfo['descriptions']['description'][$idx]);
        } else {
          $desc['descriptionEn'] = $desc['description'];
          unset($desc['description']);
        }
      }
      unset($desc);

      $this->gptsHotelInfo['descriptions'] = array_merge_recursive(
        $this->gptsHotelInfo['descriptions'],
        $hotelinfo['descriptions']
      );
    }
  }

  /**
  * Инициализация из полного набора параметров (ex. полученных из БД)
  * @param array $hotelinfo полные данные отеля
  * @param int $cityId ID обрабатываемого города (главный город)
  * @param bool $isNew истина, если это новый отель
  */
  public function initFromFullParams($hotelinfo,$cityId,$isNew = false) {
    $this->isNew = $isNew;
    $this->active = $hotelinfo['active'];
    $this->initFromParams($hotelinfo,$cityId);
    $this->gptsHotelInfo = $hotelinfo;
  }

  /**
  * Изменение кода поставщика (необходимо при сопоставлении собственных отелей)
  * код поставщика в массиве gptsHotelInfo менять не надо,
  * т.к. на момент вызова этой функции этого массива еще нет
  * @param string $supplierCode
  */
  public function changeSupplier($supplierCode) {
    $this->supplierCode = $supplierCode;
  }

  /**
  * Сохраняет данные в таблицу текущего набора данных шлюза
  */
  public function saveToCurrentSet() {
    $hotelData = json_encode($this->gptsHotelInfo, JSON_UNESCAPED_UNICODE);

    /* здесь специально не mb_strlen */
    if (strlen($hotelData) > 16777214) {
      LogHelper::logExt(
          'Dictionary','updateDictionary:Hotels','','too much data',
          [
            'strlen' => strlen($hotelData),
            'supplierCode' => $this->supplierCode,
            'hotelCode' => $this->hotelCode,
            'mainCityId' => $this->mainCityId
          ],
          LogHelper::MESSAGE_TYPE_WARNING,
          'system.supplierservice.errors'
      );
      return false;
    }

    $csum = implode('|',[
      is_null($this->gptsHotelInfo['groupId']) ? '' : $this->gptsHotelInfo['groupId'],
      $this->gptsHotelInfo['hotelCode'],
      $this->gptsHotelInfo['hotelName'],
      isset($this->gptsHotelInfo['hotelNameEn'])
        ? $this->gptsHotelInfo['hotelNameEn']
        : '',
      $this->gptsHotelInfo['address']['addressLine'],
      isset($this->gptsHotelInfo['address']['addressLineEn'])
        ? $this->gptsHotelInfo['address']['addressLineEn']
        : '',
      isset($this->gptsHotelInfo['active'])
        ? var_export($this->gptsHotelInfo['active'],true)
        : 'true',
      hashtableval($this->gptsHotelInfo['stdCategory'],''),
      hashtableval($this->gptsHotelInfo['phone'],''),
      hashtableval($this->gptsHotelInfo['email'],''),
      hashtableval($this->gptsHotelInfo['fax'],''),
      hashtableval($this->gptsHotelInfo['url'],''),
      hashtableval($this->gptsHotelInfo['latitude'],''),
      hashtableval($this->gptsHotelInfo['longitude'],''),
    ]);

    $transaction = Yii::app()->db->beginTransaction();
    try {
      Yii::app()->db->createCommand('insert into gpts_hotelsData 
          (hotelCode, supplierCode, cityId) values (:hotelCode, :supplierCode, :cityId)
          on duplicate key update cityId = :up_cityId'
        )->execute([
          ':hotelCode' => $this->gptsHotelInfo['hotelCode'],
          ':supplierCode' => $this->gptsHotelInfo['supplierCode'],
          ':cityId' => $this->mainCityId,
          ':up_cityId' => $this->mainCityId
      ]);

      Yii::app()->db->createCommand()->update('gpts_hotelsData',[
        'hotelData' => $hotelData
      ],'hotelCode = :hotelCode and supplierCode = :supplierCode and cityId = :cityId',[
        ':hotelCode' => $this->gptsHotelInfo['hotelCode'],
        ':supplierCode' => $this->gptsHotelInfo['supplierCode'],
        ':cityId' => $this->mainCityId,
      ]);

      Yii::app()->db->createCommand()->insert('gpts_hotelsMeta_current',[
        'cityId' => $this->mainCityId,
        'supplierCode' => $this->gptsHotelInfo['supplierCode'],
        'hotelCode' => $this->gptsHotelInfo['hotelCode'],
        'groupId' =>  $this->gptsHotelInfo['groupId'],
        'lastUpdate' => $this->gptsHotelInfo['lastUpdate'],
        'csum' => $csum
      ]);

      $transaction->commit();
    } catch (\Exception $e) {
      $transaction->rollback();
      return false;
    }

    return true;
  }

  /**
  * Сохраняет данные в таблицу последнего обработанного набора данных
  */
  public function saveToLastVersionSet() {
    if ($this->isNew) {
      $transaction = Yii::app()->db->beginTransaction();

      try {
        // this for granting exclusive lock
        Yii::app()->db->createCommand('select count(*) from gpts_hotelsMeta_lastVersion for update')->execute();
        Yii::app()->db->createCommand('select count(*) from gpts_hotelsMeta_current for update')->execute();

        $command = Yii::app()->db->createCommand()
          ->update('gpts_hotelsMeta_current',
            ['action' => self::HOTEL_PRESERVE],
             'hotelCode = :hotelCode and supplierCode = :supplierCode and cityId = :cityId',
            [
              ':hotelCode' => $this->hotelCode,
              ':supplierCode' => $this->supplierCode,
              ':cityId' => $this->mainCityId
            ]
          );

        $query = 'insert into gpts_hotelsMeta_lastVersion
                    (hotelCode, supplierCode, cityId, groupId, csum, action, lastUpdate)
                  select
                    hotelCode, supplierCode, cityId, groupId, csum, action, lastUpdate
                  from gpts_hotelsMeta_current as cr
                  where
                     cr.hotelCode = :hotelCode and
                     cr.supplierCode = :supplierCode and
                     cr.cityId = :cityId
                  on duplicate key update 
                    groupId = cr.groupId, csum = cr.csum, action = cr.action, lastUpdate = cr.lastUpdate';
        Yii::app()->db->createCommand($query)->execute([
          ':hotelCode' => $this->hotelCode,
          ':supplierCode' => $this->supplierCode,
          ':cityId' => $this->mainCityId
        ]);

        $transaction->commit();
      } catch (\Exception $e) {
        $transaction->rollback();
        throw $e;
      }
    } else {
      $query = 'update gpts_hotelsMeta_lastVersion as lv
                join gpts_hotelsMeta_current as cr
                  on
                    lv.hotelCode = cr.hotelCode and
                    lv.supplierCode = cr.supplierCode and
                    lv.cityId = cr.cityId
                set
                  lv.groupId = cr.groupId,
                  lv.csum = cr.csum,
                  lv.action = :lv_action,
                  cr.action = :cr_action,
                  lv.lastUpdate = cr.lastUpdate
                where
                   lv.hotelCode = :hotelCode and
                   lv.supplierCode = :supplierCode and
                   lv.cityId = :cityId';
      Yii::app()->db->createCommand($query)->execute([
        ':lv_action' => self::HOTEL_PRESERVE,
        ':cr_action' => self::HOTEL_PRESERVE,
        ':hotelCode' => $this->hotelCode,
        ':supplierCode' => $this->supplierCode,
        ':cityId' => $this->mainCityId
      ]);
    }
  }

  /**
  * Проверка наличия данных отеля в списке на обработку (_current)
  * @return bool результат проверки (true - отель присутствует)
  */
  public function isInCurrent() {
    $presentHotel = Yii::app()->db->createCommand()
      ->select('hotelCode')
      ->from('gpts_hotelsMeta_current')
      ->where(
        'hotelCode = :hotelCode and supplierCode = :supplierCode and cityId = :cityId',
        [
          ':hotelCode' => $this->hotelCode,
          ':supplierCode' => $this->supplierCode,
          ':cityId' => $this->mainCityId
        ]
       )
       ->queryScalar();
    
    return (!is_null($presentHotel));
  }

  /**
  * Проверка наличия данных отеля в списке вычисленных изменений (вообще-то, его конечно быть тут не должно)
  * @return bool результат проверки (true - отель присутствует)
  */
  public function isInLastVersion() {
    $presentHotel = Yii::app()->db->createCommand()
      ->select('hotelCode')
      ->from('gpts_hotelsMeta_lastVersion')
      ->where(
        'hotelCode = :hotelCode and supplierCode = :supplierCode and cityId = :cityId',
        [
          ':hotelCode' => $this->hotelCode,
          ':supplierCode' => $this->supplierCode,
          ':cityId' => $this->mainCityId
        ]
       )
       ->queryScalar();
    
    return (!is_null($presentHotel));
  }

  /** 
  * Создание записи в таблице lastVersion для последующего обязательного обновления 
  * @param bool $isCalculating - признак наличия данных отеля в списке сохраненных данных
  * @param bool $isStaging - признак наличия данных отеля в списке на обработку
  */
  public function createDummyLastVersionData($isCalculating = false, $isStaging = false) {
      $csum = 'dummy';

      if ($isCalculating) {
        Yii::app()->db->createCommand()->insert('gpts_hotelsMeta_lastVersion',[
          'cityId' => $this->mainCityId,
          'supplierCode' => $this->supplierCode,
          'hotelCode' => $this->hotelCode,
          'groupId' =>  null,
          'lastUpdate' => null,
          'csum' => $csum
        ]);
      }
      
      if (!$isStaging) {
        Yii::app()->db->createCommand('insert into gpts_hotelsData 
            (cityId, supplierCode, hotelCode, hotelData, action) 
            values 
            (:cityId, :supplierCode, :hotelCode, :hotelData, :action)'
          )->execute([
            ':cityId' => $this->mainCityId,
            ':supplierCode' => $this->supplierCode,
            ':hotelCode' => $this->hotelCode,
            ':hotelData' => json_encode($this->gptsHotelInfo),
            ':action' => self::HOTEL_PRESERVE
        ]);
      } else {
        Yii::app()->db->createCommand()->update(
          'gpts_hotelsMeta_current',
          [
            'action' => self::HOTEL_UPDATE
          ],
          'hotelCode = :hotelCode and supplierCode = :supplierCode and cityId = :cityId',
          [
            ':hotelCode' => $this->hotelCode,
            ':supplierCode' => $this->supplierCode,
            ':cityId' => $this->mainCityId
        ]);
      }
  }

  /**
  * Получение данных из последнего сохраненного набора данных GPTS
  * @param string|null $param если указан, возращает конкретное поле
  * @return array|mixed|false все поля отеля или значение конкретного поля,
  * или false в случае отсутствия параметра
  */
  public function getLastVersionData($param = null) {
    /* доступные для вытаскивания поля */
    $fields = ['cityId','supplierCode','hotelCode','groupId','lastUpdate','csum'];

    $command = Yii::app()->db->createCommand();

    if (is_null($param)) {
      $command->select('*');
    } else {
      if (in_array($param,$fields)) {
        $command->select($param);
      } else {
        /** @todo throw error? */
        return false;
      }
    }

    $command->from('gpts_hotelsMeta_lastVersion')
      ->where(
        'hotelCode = :hotelCode and supplierCode = :supplierCode and cityId = :cityId',
        [
          ':hotelCode' => $this->hotelCode,
          ':supplierCode' => $this->supplierCode,
          ':cityId' => $this->mainCityId
        ]
       );

    if (is_null($param)) {
      return $command->queryRow();
    } else {
      return $command->queryScalar();
    }
  }

  /*
  * Трансформирует данные отеля в структуру КТ
  * @return array данные отеля в терминах КТ
  */
  public function transformToKT() {
    $hotel = [
      'active' => ($this->gptsHotelInfo['active'] === false) ? 0 : 1,
      'manualEdit' => 0,
      'timestamp' => date('Y-m-d H:i:s'),
      'name' => $this->gptsHotelInfo['hotelName'],
      'address' => $this->gptsHotelInfo['address']['addressLine'],
      'nameEn' => isset($this->gptsHotelInfo['hotelNameEn'])
        ? $this->gptsHotelInfo['hotelNameEn']
        : null,
      'addressEn' => isset($this->gptsHotelInfo['address']['addressLineEn'])
        ? $this->gptsHotelInfo['address']['addressLineEn']
        : null,
      'category' => hashtableval($this->gptsHotelInfo['stdCategory'],null),
      'url' => hashtableval($this->gptsHotelInfo['url'],null),
      'email' => hashtableval($this->gptsHotelInfo['email'],null),
      'phone' => hashtableval($this->gptsHotelInfo['phone'],null),
      'fax' => hashtableval($this->gptsHotelInfo['fax'],null),
      'cityId' => !empty($this->gptsHotelInfo['cityId'])
        ? CitiesMapperHelper::getCityIdBySupplierCityID(
            $this->gatewayId,
            $this->gptsHotelInfo['cityId']
          )
        : CitiesMapperHelper::getCityIdBySupplierCityID(
            $this->gatewayId,
            $this->mainCityId
          ),
      'mainCityId' => CitiesMapperHelper::getCityIdBySupplierCityID(
          $this->gatewayId,
          $this->mainCityId
        ),
      'latitude' => hashtableval($this->gptsHotelInfo['latitude'],null),
      'longitude' => hashtableval($this->gptsHotelInfo['longitude'],null),
      'checkInTime' => hashtableval($this->gptsHotelInfo['checkIn'],null),
      'checkOutTime' => hashtableval($this->gptsHotelInfo['checkOut'],null),
      'hotelChain' => null,
      'weekDaysTypes' => null,
      'mainImageUrl' => hashtableval($this->gptsHotelInfo['mainImage']['url'], null),
      'images' => [],
      'services' => [],
      'descriptions' => []
    ];

    $images = [];
    $ownHotelsImagePath = $this->gptsEngine->getOwnHotelsImagePath();
    $imgSize = 600;

    if (
      isset($this->gptsHotelInfo['hotelChain']) &&
      isset($this->gptsHotelInfo['hotelChain']['hotelChainCode']) &&
      isset($this->gptsHotelInfo['hotelChain']['hotelChain'])
    ) {
      $hotel['hotelChain'] = $this->gptsHotelInfo['hotelChain'];
    }

    // обработка изображений
    if (!is_null($hotel['mainImageUrl'])) {
      $hotel['mainImageUrl'] = str_replace('\\','',$hotel['mainImageUrl']);

      /* обработка косяков GPTS */
      if (!(bool)preg_match('/^(http|www)[^\s]+$/u', $hotel['mainImageUrl'])) {
        if (preg_match('/^hotel-images2.*/u', $hotel['mainImageUrl'])) {
          // собственные отели
          $hotel['mainImageUrl'] = $ownHotelsImagePath . $imgSize . '/' . $hotel['mainImageUrl'];

        } else if ($this->supplierCode === 'aeroclub' && preg_match('/^\d+\/\d+.jpg$/u', $hotel['mainImageUrl'])) {
          // кривые A&A
          $hotel['mainImageUrl'] = 'https://images.aanda.ru/photos/' . $hotel['mainImageUrl'];

        }
      }

      $images[] = [
        'url' => $hotel['mainImageUrl'],
        'descriptionRu' => null,
        'descriptionEn' => null,
        'active' => 1,
        'manualEdit' => 0,
        'timestamp' => date('Y-m-d H:i:s')
      ];
    }

    if (
      isset($this->gptsHotelInfo['images']['image'])
      && is_array($this->gptsHotelInfo['images']['image'])
    ) {
      $images = array_merge($images, array_map(function ($img) use ($ownHotelsImagePath, $imgSize) {
        $url = str_replace('\\','',$img['url']);

        /* обработка косяков GPTS */
        if (!(bool)preg_match('/^(http|www)[^\s]+$/u', $url)) {
          if (preg_match('/^hotel-images2.*/u', $url)) {
            // собственные отели
            $url = $ownHotelsImagePath . $imgSize . '/' . $url;

          } else if ($this->supplierCode === 'aeroclub' && preg_match('/^\d+\/\d+.jpg$/u', $url)) {
            // кривые A&A
            $url = 'https://images.aanda.ru/photos/' . $url;

          }
        }

        return [
          'url' => $url,
          'descriptionRu' => isset($img['title']) ? $img['title'] : null,
          'descriptionEn' => null,
          'active' => 1,
          'manualEdit' => 0,
          'timestamp' => date('Y-m-d H:i:s')
        ];
      }, $this->gptsHotelInfo['images']['image']));
    }

    $imageUrls = array_map(function($img) { 
      return $img['url']; 
    }, $images);
    
    $hotel['images'] = array_filter($images, function($img, $k) use (&$imageUrls) {
      // если линк встречается в массиве дальше, отбрасываем его
      if (count(array_keys(array_slice($imageUrls, $k + 1), $img['url'], true)) > 1) {
        return false;
      } else {
        return true;
      }
    }, ARRAY_FILTER_USE_BOTH);


    // обработка услуг
    if (
      isset($this->gptsHotelInfo['services']['service'])
      && is_array($this->gptsHotelInfo['services']['service'])
    ) {
      $services = [];

      foreach ($this->gptsHotelInfo['services']['service'] as $srv) {
        if (empty($srv['otacode'])) { continue; }
        $services[] = [
          'otaCode' => $srv['otacode'],
          'active' => empty($srv['active']) ? 0 : 1,
          'manualEdit' => 0,
          'timestamp' => date('Y-m-d H:i:s'),
          'isBillable' => empty($srv['fee']) ? 0 : 1
        ];
      }

      $hotel['services'] = array_filter($services,function($srv,$k) use (&$services) {
        for ($i = $k+1, $len=count($services); $i < $len; $i++) {
          if ($srv['otaCode'] == $services[$i]['otaCode']) { return false; }
        }
        return true;
      },ARRAY_FILTER_USE_BOTH);
    }

    // обработка описаний
    if (
      isset($this->gptsHotelInfo['descriptions']['description'])
      && is_array($this->gptsHotelInfo['descriptions']['description'])
    ) {
      foreach ($this->gptsHotelInfo['descriptions']['description'] as $desc) {
        if (empty($desc['type']) || !in_array($desc['type'],self::$hotelDescriptionTypes)) {
          continue;
        }

        $hotel['descriptions'][] = [
          'descriptionType' => $desc['type'],
          'descriptionRu' => isset($desc['description']) ? $desc['description'] : null,
          'descriptionEn' => isset($desc['descriptionEn']) ? $desc['descriptionEn'] : null,
          'active' => 1,
          'manualEdit' => 0,
          'timestamp' => date('Y-m-d H:i:s')
        ];
      }
    }

    return $hotel;
  }

}
