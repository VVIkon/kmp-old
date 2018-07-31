<?php

namespace Dictionaries\HotelsDictionary;

use \Yii as Yii;
use \CDbConnection as CDbConnection;
use \Dictionary as Dictionary;
use \DictionaryErrors as DictionaryErrors;
use \LogHelper as LogHelper;
use \KmpException as KmpException;
use Dictionaries\HotelsDictionary\Entities\CityEntity as CityEntity;
use Dictionaries\HotelsDictionary\Entities\KTHotelEntity as KTHotelEntity;
use Dictionaries\HotelsDictionary\Entities\UTKHotelEntity as UTKHotelEntity;
use Dictionaries\HotelsDictionary\Entities\UTKHotelsCollection as UTKHotelsCollection;

class UTKUpdateStrategy extends UpdateStrategy {
  /** @var int ID обрабатываемого города (по версии шлюза) */
  private $gatewayCityId;
  /**
  * @var string название на русском обрабатываемого города
  * (по версии УТК, для отелей УТК используется в качестве ID отеля)
  */
  private $gatewayCityName;
  /** @var CityEntity структура данных обрабатываемого города */
  private $currentCity;
  /** @var int статус процедуры обработки отелей города */
  private $state;
  /** @var bool полное ли обновление или частичное */
  private $isFullUpdate;

  /** @var string код поставщика УТК, сейчас для всех отелей один */
  private $supplierCode = 'utk';
  /**
  * @todo для тестирования
  * @var bool тестовое обновление
  */
  private $testingUpdate = false;

  public function __construct(&$module,$updateType = 0) {
    switch ($updateType) {
      case 0:
        $this->isFullUpdate = true;
        break;
      case 1:
        $this->isFullUpdate = false;
        break;
      case 2:
        $this->isFullUpdate = true;
        $this->testingUpdate = true;
        break;
      default:
        throw new KmpException(
            get_class(),__FUNCTION__,
            DictionaryErrors::ENGINE_COMMAND_ERROR,
            array_merge($operationLog,['error' => $e->getMessage()])
        );
        break;
    }

    parent::__construct($module);
  }

  /**
  * Получение списка городов
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::getUpdateList()
  */
  public function getUpdateList() {
    /**
    * @todo для тестирования
    */
    if ($this->testingUpdate) {
      $cityList = [];

      $gwCityId = Yii::app()->db->createCommand()
        ->select('SupplierCityID')
        ->from('kt_ref_cities_match')
        ->where(
            'CityID = :cityId and SupplierID = :gatewayId',
            [
              ':cityId' => (int)$this->config['test_city_id'],
              ':gatewayId' => self::UTK
            ]
          )
        ->limit(1)
        ->queryScalar();

      $cityList[] = $gwCityId;

      return $cityList;
    }

    $query = 'select cm.SupplierCityID as cityId
              from kt_ref_cities_match as cm
              join (
                  select distinct city as cityName
                  from utk_hotelInfo
                ) as hi
                on hi.cityName = cm.SupplierCityName
              where cm.SupplierID = :gatewayId';
    $cityList = Yii::app()->db->createCommand($query)->queryColumn([
      ':gatewayId' => self::UTK
    ]);

    return $cityList;
  }

  /**
  * Метод для загрузки необходимых данных по обновляемому городу
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::loadCityData()
  */
  public function loadCityData($gatewayCityId) {
    $this->gatewayCityId = $gatewayCityId;
    $this->currentCity = new CityEntity($gatewayCityId,self::UTK);

    $this->gatewayCityName = Yii::app()->db->createCommand()
      ->select('Name as name')
      ->from('utk_cities')
      ->where('id = :cityId',[':cityId' => $this->gatewayCityId])
      ->queryScalar();

    $this->state = self::STATE_INITIAL;
  }

  /**
  * Получение состояния обработки текущего выбранного города
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::getState()
  */
  public function getState() {
    return $this->state;
  }

  /**
  * Обновление состояния обработки текущего выбранного города
  * @param int $state состояние обработки
  */
  private function setState($state) {
    $this->state = $state;
  }

  /**
  * Метод для загрузки данных по отелям для конкретного города
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::loadCurrentHotelsData()
  * В данном случае проверяет, если ли в текущем обрабатываемом городе отели
  */
  public function loadCurrentHotelsData() {
    Dictionary::livelog('checking hotels count...');

    $hotelsCount = Yii::app()->db->createCommand()
      ->select('count(*)')
      ->from('utk_hotelInfo')
      ->where('city = :cityName',[':cityName' => $this->gatewayCityName])
      ->queryScalar();
    if (!$hotelsCount) { $hotelsCount = 0; }

    Dictionary::livelog('found '.$hotelsCount.' hotels');
    $this->setState(self::STATE_DATA_LOADED);
    return $hotelsCount;
  }

  /**
  * Вычисления необходимых изменений в справочнике
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::generateChangesDelta()
  */
  public function generateChangesDelta() {
    /* поля таблиц для обновления контента  */
    $updateFields = [
      'hotelNameRU', 'hotelNameEN', 'addressRU', 'addressEN',
      'category', 'city', 'country', 'countryId', /* cityId подставляется из свойства объекта */
      'latitude', 'longitude', 'idHotelChain', 'Phone', 'Fax', 'Email', 'URL',
      'checkInTime', 'checkOutTime', 'mainImageUrl', 'weekDaysTypes',
      'active', 'manualEdit', 'lastUpdate'
    ];

    /* поля, изменение которых влияет на матчинг */
    $matchFields = ['hotelNameRU', 'hotelNameEN', 'addressRU', 'addressEN'];

    /*
    * отметить записи с изменением метки времени для обновления контента
    * или обновления контента и перематчинга
    * в зависимости от изменившихся полей
    */
    $query = 'update utk_hotelsData_lastVersion as lv
              join utk_hotelInfo as hi
                on hi.hotelId_UTK = lv.hotelId_UTK
              set ' .
                implode(',',array_map(function($uf) { return 'lv.'.$uf.' = hi.'.$uf; },$updateFields)) .
              ',';
    if ($this->isFullUpdate) {
      /* в любом случае запускаем полное обновление */
      $query .= 'lv.action = if(false,:action_update_content,:action_update)
                 where
                   lv.action = :action_preserve and
                   lv.cityId = :cityId';
    } else {
      $query .= 'lv.action =
                  if (
                    concat_ws(",",' .
                      implode(',',array_map(function($mf) { return 'lv.'.$mf; },$matchFields)).
                    ') =
                    concat_ws(",",' .
                      implode(',',array_map(function($mf) { return 'hi.'.$mf; },$matchFields)) .
                    ')
                    , :action_update_content
                    , :action_update
                  )
                 where
                  lv.lastUpdate != hi.lastUpdate and
                  lv.action = :action_preserve and
                  lv.cityId = :cityId';
    }

    Yii::app()->db->createCommand($query)->execute([
      ':action_update_content' => self::HOTEL_UPDATE_CONTENT,
      ':action_update' => self::HOTEL_UPDATE,
      ':action_preserve' => self::HOTEL_PRESERVE,
      ':cityId' => $this->gatewayCityId
    ]);

    /* добавить в lastVersion новые записи с пометкой о создании */
    $insertFieldsSql = 'hotelId_UTK,'.implode(',',$updateFields);
    $selectFieldsSql = 'hi.hotelId_UTK,' .
      implode(',',array_map(function($uf){ return 'hi.'.$uf; },$updateFields));

    $query = 'insert into utk_hotelsData_lastVersion
              ('.$insertFieldsSql.',supplierCode,hotelCode,action,cityId)
              select '.
                $insertFieldsSql.',' .
                '"'.$this->supplierCode .'" as supplierCode,
                hotelId_UTK as hotelCode,' .
                self::HOTEL_CREATE.' as action,' .
                $this->gatewayCityId . ' as cityId
              from (
                select '.$selectFieldsSql.'
                from utk_hotelInfo as hi
                left join utk_hotelsData_lastVersion as lv
                  on hi.hotelId_UTK = lv.hotelId_UTK
                where
                  lv.hotelId_UTK is null and
                  hi.hotelNameRU is not null and
                  hi.city = :cityName
              ) as uh';
    Yii::app()->db->createCommand($query)->execute([
      ':cityName' => $this->gatewayCityName
    ]);

    /* отметить активные записи, которых нет в текущем наборе данных, для удаления (деактивации) */
    $query = 'update utk_hotelsData_lastVersion as lv
              left join utk_hotelInfo as hi
                on hi.hotelId_UTK = lv.hotelId_UTK
              set lv.action = :action_delete
              where
                hi.hotelId_UTK is null and
                lv.cityId = :cityId';
                /** @todo здесь еще нужен признак активности, но УТК его пока не пишет */
    Yii::app()->db->createCommand($query)->execute([
      ':action_delete' => self::HOTEL_DELETE,
      ':cityId' => $this->gatewayCityId
    ]);

    /* для отелей без координат выставить значения полей в NULL */
    $query = 'update utk_hotelsData_lastVersion
              set latitude = null, longitude = null
              where
                latitude = 0 and
                longitude = 0 and
                cityId = :cityId';
    Yii::app()->db->createCommand($query)->execute([
      ':cityId' => $this->gatewayCityId
    ]);

    $this->setState(self::STATE_DELTA_GENERATED);
  }

  /**
  * Применение изменений с четкими последствиями
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::applyDefiniteChanges()
  */
  public function applyDefiniteChanges() {
    Dictionary::livelog('processing deleted hotels...');
    $this->processDeletedHotels();
    Dictionary::livelog('processing hotels marked for content update...');
    $this->processNonRematchingHotels();
    Dictionary::livelog('processing hotels staged for update...');
    $this->processStagedForUpdateHotels();
    $this->setState(self::STATE_CHANGES_APPLIED);
  }

  /**
  * Матчинг отелей
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::matchHotels()
  */
  public function matchHotels() {
    $this->processMatchingHotels();
    Dictionary::livelog('updating hotel info...');
    $this->processStagedForUpdateHotels();
    $this->setState(self::STATE_FINISHED);
  }

  /**
  * Операции по завершению обработки города
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::finishCityProcessing()
  */
  public function finishCityProcessing() {
    if ($this->getState() !== self::STATE_FINISHED) {
      $this->setState(self::STATE_FINISHED);
    }

    $query = 'update ho_hotelInfo as hi
              join
                (
                  select hotelId,count(*) as count
                  from ho_hotelMatch
                  where active = :inactive
                  group by hotelId
                ) as hm
                on hi.hotelId = hm.hotelId
              set hi.active = :hotel_inactive
              where hi.cityId = :cityId';
    Yii::app()->db->createCommand($query)->execute([
      ':inactive' => 0,
      ':hotel_inactive' => 0,
      ':cityId' => $this->currentCity->cityId
    ]);
  }

  /**
  * Обработка удаленных отелей поставщика
  */
  private function processDeletedHotels() {
    $query = 'update ho_hotelMatch as hm
              join utk_hotelsData_lastVersion as lv
                on lv.hotelId_UTK = hm.gatewayHotelId
              set hm.active = 0, lv.active = 0
              where
                hm.gatewayId = :gatewayId and
                lv.cityId = :cityId and
                lv.action = :action_delete';
    Yii::app()->db->createCommand($query)->execute([
      ':gatewayId' => self::UTK,
      ':cityId' => $this->gatewayCityId,
      ':action_delete' => self::HOTEL_DELETE
    ]);
  }

  /**
  * Обработка отелей УТК, имеющих изменения контента, не влияющие на матчинг
  */
  private function processNonRematchingHotels() {
    /* отметить для обновления контента те отели КТ, которые созданы только по информации от отелей УТК */
    $query = 'update ho_hotelInfo as hi
              join
                (
                  select hotelId, min(gatewayId) as gw1, max(gatewayId) as gw2
                  from ho_hotelMatch
                  where CityId = :matchCityId
                  group by hotelId
                ) as matchgws
                on matchgws.hotelId = hi.hotelId
              join ho_hotelMatch as hm
                on hm.hotelId = hi.hotelId
              join utk_hotelsData_lastVersion as lv
                on hm.supplierCode = lv.supplierCode and hm.supplierHotelCode = lv.hotelCode
              set hi.needUpdate = :needUpdate
              where
                matchgws.gw1 = matchgws.gw2 and
                hm.gatewayId = :gatewayId and
                lv.cityId = :cityId and
                lv.action = :action_update_content';
    Yii::app()->db->createCommand($query)->execute([
      ':matchCityId' => $this->gatewayCityId,
      ':needUpdate' => 1,
      ':gatewayId' => self::UTK,
      ':cityId' => $this->gatewayCityId,
      ':action_update_content' => self::HOTEL_UPDATE_CONTENT
    ]);

    Dictionary::livelog('finished staging hotels for content update');
  }

  /**
  * Обработка отелей, которые необходимо сматчить (новые и с обновлением критичных полей)
  */
  private function processMatchingHotels() {
    $operationLog = [
      'total' => 0,
      'created' => 0,
      'matched' => 0
    ];

    $this->launchMatchingHotelsDataReader(function(UTKHotelEntity $utkHotel,$action) use (&$operationLog) {
      $operationLog['total'] += 1;

      if ($operationLog['total'] % 50 == 0) { echo '.'; }
      if ($operationLog['total'] % 1000 == 0) { echo $operationLog['total']."\n"; }

      $hotel = new KTHotelEntity();
      $hotel->initFromParams($utkHotel->transformToKT());
      $hotel->setCityData($this->currentCity);

      switch ($action) {
        case self::HOTEL_CREATE:
          if ($hotel->tryToMatch($this->matchRules)) {
            if (!$hotel->hasAnotherGatewayMatches(self::UTK)) {
              $hotel->stageForUpdate();
            }
            $hotel->fill();
            $hotel->matchStatus = 0;
            $operationLog['matched'] += 1;
          } else {
            $hotel->create();
            $hotel->matchStatus = 1;
            $operationLog['created'] += 1;
          }
          $hotel->createMatch($utkHotel);
          break;
        case self::HOTEL_UPDATE:
          $hotel->findBySupplierCodes($utkHotel->supplierCode, $utkHotel->hotelCode, $this->gatewayCityId, self::UTK);

          $newHotel = new KTHotelEntity();
          $newHotel->initFromParams($utkHotel->transformToKT());
          $newHotel->setCityData($this->currentCity);

          if ($newHotel->tryToMatch($this->matchRules, $hotel->hotelId)) {
            if ($hotel->getMatchesCount() == 1) {
              $hotel->deactivate();
            } else {
              if (!$hotel->hasAnotherGatewayMatches(self::UTK)) {
                $hotel->stageForUpdate();
              }
            }
            $newHotel->fill();
            if (!$newHotel->hasAnotherGatewayMatches(self::UTK)) {
              $newHotel->stageForUpdate();
            }
            $newHotel->matchStatus = 0;
            $operationLog['matched'] += 1;
            $newHotel->updateMatch($utkHotel);
          } else {
            if (!$hotel->hasAnotherGatewayMatches(self::UTK)) {
              $hotel->stageForUpdate();
            }
            if ($hotel->getMatchesCount() == 1) {
              $hotel->update();
              $hotel->matchStatus = 0;
              $operationLog['matched'] += 1;
              $hotel->updateMatch($utkHotel);
            } else {
              if ($hotel->checkMatch($this->matchRules)) {
                if (!$hotel->hasAnotherGatewayMatches(self::UTK)) {
                  $hotel->stageForUpdate();
                }
                $hotel->fill();
                $hotel->stageForUpdate();
                $hotel->matchStatus = 0;
                $operationLog['matched'] += 1;
                $hotel->updateMatch($utkHotel);
              } else {
                $newHotel->create();
                $newHotel->matchStatus = 1;
                $operationLog['created'] += 1;
                $newHotel->updateMatch($utkHotel);
              }
            }
          }
          break;
      }

      $utkHotel->markAsProcessed();
    });

    echo "\n";
    Dictionary::livelog('finished processing groupless hotels' .
      ', total: '.$operationLog['total'] .
      ', created: '.$operationLog['created'] .
      ', matched: '.$operationLog['matched']
    );
  }

  /**
  * Обновление информации отмеченных для обновления отелей КТ
  */
  private function processStagedForUpdateHotels() {
    $operationLog = ['processed' => 0];

    $this->launchStagedForUpdateHotelsDataReader(
      function($hotelId, UTKHotelsCollection $hotelsSet) use (&$operationLog) {

        $hotel = new KTHotelEntity($hotelId);
        $hotel->initFromParams($hotelsSet->generateKTHotelContent());
        $hotel->setCityData($this->currentCity);
        $hotel->update();

        $operationLog['processed'] += 1;
        if ($operationLog['processed'] % 50 == 0) { echo '.'; }
        if ($operationLog['processed'] % 1000 == 0) { echo $operationLog['processed']."\n"; }
      }
    );

    echo "\n";
    Dictionary::livelog('finished processing staged hotels, processed: '.$operationLog['processed']);
  }

  /**
  * Запуск построчной обработки отелей, отмеченных для обновления
  * @param Callable $processor функция обработки записи
  */
  private function launchStagedForUpdateHotelsDataReader(Callable $processor) {
    $conn = new CDbConnection(
      Yii::app()->db->connectionString,
      Yii::app()->db->username,
      Yii::app()->db->password
    );

    $command = $conn->createCommand()
      ->select('hi.hotelId, lv.*')
      ->from('utk_hotelsData_lastVersion as lv')
      ->join('ho_hotelMatch as hm',
          'hm.supplierCode = lv.supplierCode and hm.supplierHotelCode = lv.hotelCode'
        )
      ->join('ho_hotelInfo as hi','hi.hotelId = hm.hotelId')
      ->where(
        'lv.cityId = :cityId and
         hm.gatewayId = :gatewayId and
         hi.needUpdate = :needUpdate',
         [
           ':cityId' => $this->gatewayCityId,
           ':gatewayId' => self::UTK,
           ':needUpdate' => 1
         ]
        )
      ->order('hi.hotelId');

    $hotelsReader = $command->query();

    $currentHotelId = false;
    $hotelsSet = new UTKHotelsCollection();

    try {
      foreach ($hotelsReader as $h) {
        $hotelId = $h['hotelId'];
        $utkHotel = new UTKHotelEntity();
        $utkHotel->initFromParams($h,$this->gatewayCityId);

        if ($currentHotelId === false) {
          $currentHotelId = $hotelId;
        } elseif ($currentHotelId !== $hotelId) {
          $processor($currentHotelId,$hotelsSet);
          $currentHotelId = $hotelId;
          $hotelsSet = new UTKHotelsCollection();
        }

        $hotelsSet->addHotel($utkHotel);
      }

      if ($hotelsSet->getHotelsCount() > 0) {
        $processor($currentHotelId,$hotelsSet);
      }
    } finally {
      $hotelsReader->close();
      $conn->setActive(false);
    }
  }

  /**
  * Запуск построчной обработки отелей, которые необходимо сматчить
  * @param Callable $processor функция обработки записи
  */
  private function launchMatchingHotelsDataReader(Callable $processor) {
    $conn = new CDbConnection(
      Yii::app()->db->connectionString,
      Yii::app()->db->username,
      Yii::app()->db->password
    );

    $command = $conn->createCommand()
      ->select('*')
      ->from('utk_hotelsData_lastVersion')
      ->where(
        'cityId = :cityId and
         (action = :action_update or action = :action_create)',
        [
          ':cityId' => $this->gatewayCityId,
          ':action_update' => self::HOTEL_UPDATE,
          ':action_create' => self::HOTEL_CREATE,
        ]
      );
    $hotelsReader = $command->query();

    try {
      foreach ($hotelsReader as $h) {
        $action = $h['action'];

        $utkHotel = new UTKHotelEntity();
        $utkHotel->initFromParams($h,$this->gatewayCityId);
        $processor($utkHotel,$action);
      }
    } finally {
      $hotelsReader->close();
      $conn->setActive(false);
    }
  }

}
