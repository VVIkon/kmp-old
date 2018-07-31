<?php

namespace Dictionaries\HotelsDictionary;

use \Yii as Yii;
use \CDbConnection as CDbConnection;
use \Dictionary as Dictionary;
use \DictionaryErrors as DictionaryErrors;
use \LogHelper as LogHelper;
use \KmpException as KmpException;
use \HotelChangedLocationsListener as HotelChangedLocationsListener;
use \HotelsListener as HotelsListener;
use \ContractsListener as ContractsListener;
use \JsonStreamingParser as JsonStreamingParser;
use Dictionaries\HotelsDictionary\Entities\CityEntity as CityEntity;
use Dictionaries\HotelsDictionary\Entities\KTHotelEntity as KTHotelEntity;
use Dictionaries\HotelsDictionary\Entities\GPTSHotelEntity as GPTSHotelEntity;
use Dictionaries\HotelsDictionary\Entities\GPTSHotelsCollection as GPTSHotelsCollection;
use Dictionaries\HotelsDictionary\Entities\GPTSOwnHotelEntity as GPTSOwnHotelEntity;
use Dictionaries\HotelsDictionary\Factories\GPTSOwnProductFactory as GPTSOwnProductFactory;

class GPTSUpdateStrategy extends UpdateStrategy {
  /** @var int ID обрабатываемого города (по версии шлюза) */
  private $gatewayCityId;
  /** @var CityEntity структура данных обрабатываемого города */
  private $currentCity;
  /** @var int статус процедуры обработки отелей города */
  private $state;
  /** @var GPTSSupplierEngine модуль общения со шлюзом GPTS */
  private $gptsEngine;
  /** @var bool полное ли обновление или частичное */
  private $isFullUpdate;
  /**
  * @todo для тестирования
  * @var bool тестовое обновление
  */
  private $testingUpdate = false;

  // названия временных таблиц
  private $hotelsMetaCurrent_tmp;
  private $hotelsMetaLastVersion_tmp;
  private $hotelMatch_tmp;

  public function __construct(&$module, $updateType = 0) {
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
            []
        );
        break;
    }

    Yii::app()->db->setActive(true);

    $this->gptsEngine = Yii::app()->getModule('supplierService')->getModule('GPTSEngine')->getEngine();
    parent::__construct($module);
  }

  /**
  * Получение списка городов
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::getUpdateList()
  */
  public function getUpdateList() {
    /**
    * @todo для тестирования, отдает список из одного законфигурированного города
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
              ':gatewayId' => self::GPTS
            ]
          )
        ->limit(1)
        ->queryScalar();

      $cityList[] = $gwCityId;

      $hasCity = Yii::app()->db->createCommand()
        ->select('cityId')
        ->from('gpts_hotels_updateList')
        ->where('cityId = :cityId',[':cityId' => $gwCityId])
        ->queryScalar();

      if ($hasCity === false) {
        Yii::app()->db->createCommand()
          ->insert('gpts_hotels_updateList',[
            'cityId' => $gwCityId,
            'state' => self::STATE_INITIAL
          ]);
      } else {
        Yii::app()->db->createCommand()
          ->update(
            'gpts_hotels_updateList',['state' => self::STATE_INITIAL],
            'cityId = :cityId',[':cityId' => $gwCityId]
          );
      }

      return $cityList;
    }

    $isNewMatchCycle = false;

    $cityList = Yii::app()->db->createCommand()
      ->select('cityId')
      ->from('gpts_hotels_updateList')
      ->where('state != :state_matched',[':state_matched' => self::STATE_FINISHED])
      ->order('state desc, cityId asc')
      ->queryColumn();

    if (count($cityList) == 0) {
      $isNewMatchCycle = true;
      $this->generateUpdateList();

      $cityList = Yii::app()->db->createCommand()
        ->select('cityId')
        ->from('gpts_hotels_updateList')
        ->order('cityId asc')
        ->queryColumn();
    }

    // обновление по дельте
    if (!$this->isFullUpdate && $isNewMatchCycle) {
      Dictionary::livelog('getting changed cities, total cities: '.count($cityList).'...');

      $lastUpdate = Yii::app()->db->createCommand()
        ->select('max(lastUpdate)')
        ->from('gpts_hotels_updateList')
        ->queryScalar();

      if ($lastUpdate !== false && $lastUpdate !== null) {
        $lastUpdate = date('Y-m-d', strtotime($lastUpdate));
        $changedCities = $this->gptsEngine->runApiCommand('Accomodations','hotelChangesLocations',[
            //'createdDateFrom' => $lastUpdate,
            'modifiedDateFrom' => $lastUpdate
          ],true,'ru',3);

        $changedCityIds = [];

        $listener = new HotelChangedLocationsListener(function ($item) use (&$changedCityIds) {
          $changedCityIds[] = (int)$item['locationId'];
        });
        try {
          $parser = new JsonStreamingParser\Parser($changedCities, $listener);
          $parser->parse();
          fclose($changedCities);
        } catch (Exception $e) {
          fclose($changedCities);
          throw $e;
        }

        $cityList = array_intersect($changedCityIds, $cityList);

        Dictionary::livelog('got changed cities');
      }
    }

    if ($isNewMatchCycle) {
      Yii::app()->db->createCommand()
        ->update('gpts_hotels_updateList', [
          'state' => self::STATE_INITIAL,
          'lastUpdate' => date('Y-m-d H:i:s')
          ], ['in', 'cityId', $cityList]);
    }

    return $cityList;
  }

  /**
  * Метод для загрузки необходимых данных по обновляемому городу
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::loadCityData()
  */
  public function loadCityData($gatewayCityId) {
    $this->gatewayCityId = $gatewayCityId;
    $this->currentCity = new CityEntity($gatewayCityId,self::GPTS);

    // имена используемых временных таблиц
    $this->hotelsMetaCurrent_tmp = 'hotels_meta_current_' . $this->gatewayCityId;
    $this->hotelsMetaLastVersion_tmp = 'hotels_meta_lastversion_' . $this->gatewayCityId;
    $this->hotelMatch_tmp = 'hotel_match_' . $this->gatewayCityId;

    $command = Yii::app()->db->createCommand()
      ->select('state')
      ->from('gpts_hotels_updateList')
      ->where('cityId = :cityId',[':cityId' => $this->gatewayCityId]);

    $this->state = $command->queryScalar();
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
    Yii::app()->db->createCommand()
      ->update(
        'gpts_hotels_updateList',
        ['state' => $this->state],
        'cityId = :cityId',
        [':cityId' => $this->gatewayCityId]
      );
  }

  /**
  * Заполнение списка городов для обновления справочника
  */
  private function generateUpdateList() {
    $query='insert into gpts_hotels_updateList (cityId,state)
            select cityId, '.self::STATE_INITIAL.' as state
              from (
                select kcm.SupplierCityID as cityId 
                from kt_ref_cities_match as kcm
                left join gpts_hotels_updateList as ul on ul.cityId = kcm.SupplierCityID
                where 
                  kcm.SupplierID = :supplierId 
                  and kcm.active = :active 
                  and ul.cityId is null                
              ) as cm';

    /* было еще обновление только по главным городам, а не пригородам, 
     * но от этого отказались:
     * and kcm.parentType = :parentType 
     */

    Yii::app()->db->createCommand($query)->execute([
      ':supplierId' => self::GPTS,
      ':active' => 1
      //':parentType' => 'country'
    ]);
  }

  /**
  * Метод для загрузки данных по отелям для конкретного города
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::loadCurrentHotelsData()
  */
  public function loadCurrentHotelsData() {
    $this->clearCurrentHotelsSet();

    $gptsHotels = new GPTSHotelsCollection();
    $operationLog = [
      'owntotal' => 0,
      'ownactive' => 0,
      'total' => 0,
      'processed' => 0,
      'broken' => 0,
      'bad' => 0,
      'inactive' => 0
    ];

    Dictionary::livelog('city '.$this->gatewayCityId.': '.'getting own hotels from gpts...');
    $this->gptsEngine->reAuthenticate(3);

    $ownHotels = $this->gptsEngine->runApiCommand('Contracts','contracts',[
        'cityId' => $this->gatewayCityId,
        'limit' => 10000
      ],true,'ru',3);

    /*========> функция обработки списка собственных отелей */
    $listener = new ContractsListener(function ($item) use (&$gptsHotels,&$operationLog) {
      try {
        $ownHotel = GPTSOwnProductFactory::create($item);
      } catch (\Exception $e) { return; }

      if ($ownHotel instanceof GPTSOwnHotelEntity) {
        $operationLog['owntotal'] += 1;

        if ($gptsHotels->addOwnHotel($ownHotel)) {
          $operationLog['ownactive'] += 1;
        }
      } else { return; }
    });

    /*========> получение списка собственных отелей */
    Dictionary::livelog('city '.$this->gatewayCityId.': '.'start parsing...');
    try {
      $parser = new JsonStreamingParser\Parser($ownHotels, $listener);
      $parser->parse();
      Dictionary::livelog('city '.$this->gatewayCityId.': '.'parsing finished, '.
        'total own hotels: '. $operationLog['owntotal'] .
        ', active: '. $operationLog['ownactive']
      );
      fclose($ownHotels);
    } catch (Exception $e) {
      fclose($ownHotels);
      Dictionary::livelog(
        'city '.$this->gatewayCityId.': '.
        'parsing failed, stopped after '. $operationLog['owntotal'] .' own hotels' .
        ', error: '.$e->getMessage()
      );

      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::PARSING_ERROR,
          array_merge($operationLog,['error' => $e->getMessage()])
      );
    }

    Dictionary::livelog('city '.$this->gatewayCityId.': '.'getting hotels list from gpts...');
    $this->gptsEngine->reAuthenticate(3);

    $reqparams = [
      'locationId' => $this->gatewayCityId,
      'limit' => 100000
    ];
    if (!$this->isFullUpdate) {
      $lastUpdate = $this->getLastUpdateTimestamp();
      if ($lastUpdate !== false && !is_null($lastUpdate)) {
        $dateFrom = (new \DateTime($lastUpdate))->format('Y-m-d\TH:i');
        //$reqparams['createdDateFrom'] = $dateFrom;
        $reqparams['modifiedDateFrom'] = $dateFrom;
      }
    }
    $hotels = $this->gptsEngine->runApiCommand('Accomodations','hotels',$reqparams,true,'ru',3);

    /*========> Функция обработки списка отелей */
    $listener = new HotelsListener(function ($item) use (&$gptsHotels, &$operationLog) {
      $operationLog['total'] += 1;

      try {
        $gptsHotel = new GPTSHotelEntity($this->gptsEngine);
        $gptsHotel->initFromParams($item, $this->gatewayCityId);
      } catch (KmpException $ke) {
        $operationLog['broken'] += 1;

        unset($item['roomTypes']);
        unset($item['descriptions']);
        unset($item['services']);
        unset($item['images']);
        
        $this->writeLog(
            $this->getErrorText($ke->getCode()),
            ['item' => $item],
            LogHelper::MESSAGE_TYPE_WARNING
        );
      }

      if (!$gptsHotels->addHotel($gptsHotel)) {
        $operationLog['broken'] += 1;

        unset($item['roomTypes']);
        unset($item['descriptions']);
        unset($item['services']);
        unset($item['images']);

        $this->writeLog(
            $this->getErrorText(DictionaryErrors::DUPLICATE_ENTRY),
            $item,
            LogHelper::MESSAGE_TYPE_WARNING
        );
      }
    });

    /*========> получение списка отелей */
    Dictionary::livelog('city '.$this->gatewayCityId.': '.'start parsing...');
    try {
      $parser = new JsonStreamingParser\Parser($hotels, $listener);
      $parser->parse();
      Dictionary::livelog('city '.$this->gatewayCityId.': '.
        'parsing finished, hotels: '.$operationLog['total'].', broken:'.$operationLog['broken']
      );
      fclose($hotels);
    } catch (Exception $e) {
      fclose($hotels);
      Dictionary::livelog(
        'city '.$this->gatewayCityId.': '.
        'parsing failed, stopped after '. $operationLog['total'] .' hotels ' .
        '[broken: '.$operationLog['broken'].'], error: '.$e->getMessage()
      );

      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::PARSING_ERROR,
          array_merge($operationLog,['error' => $e->getMessage()])
      );
    }

    /*========> получение полной информации по отелям */
    Dictionary::livelog('city '.$this->gatewayCityId.': '.'getting full hotels info...');
    echo '|'.implode('',array_fill(0,49,'=')).'|'."\n".'|';
    $step = ceil($operationLog['total'] / 50);

    $this->gptsEngine->reAuthenticate(3);
    $gptsHotels->processHotelsData(function(&$hotel,$counter) use ($step,&$operationLog) {
      if ($counter % $step == 0) { echo '.'; }

      $datastream = $this->gptsEngine->runApiCommand('Accomodations','hotelInfo',[
        'hotelId' => $hotel->hotelId
      ],true,'ru',3);

      try {
        $data = stream_get_contents($datastream);
        fclose($datastream);
        $hotelInfo = json_decode($data,true)['hotelDescriptionInfo'];
      } catch (Exception $e) {
        $this->writeLog(
            $this->getErrorText(DictionaryErrors::DATA_FETCH_FAILED),
            ['response' => $data],
            LogHelper::MESSAGE_TYPE_WARNING
        );
        return;
      }

      if (!$hotel->setFullInfo($hotelInfo)) {
        $operationLog['bad'] += 1;
        $this->writeLog(
            $this->getErrorText(DictionaryErrors::BROKEN_ITEM),
            $hotelInfo,
            LogHelper::MESSAGE_TYPE_WARNING
        );
        return;
      }

      if ($hotel->active == false) {
        $operationLog['inactive'] += 1;
        $operationLog['processed'] += 1;
        return;
      }

      /*========> запрос перевода */
      try {
        $datastream = $this->gptsEngine->runApiCommand('Accomodations','hotelInfo',[
          'hotelId' => $hotel->hotelId
        ],true,'en',3);
        $data = stream_get_contents($datastream);
        fclose($datastream);
        $hotelInfoEn = json_decode($data,true)['hotelDescriptionInfo'];
        $hotel->setTranslation($hotelInfoEn,'en');
      } catch (Exception $e) { /* отель без перевода все равно запишем */ }

      /*========> сохранение данных */
      if (!$hotel->saveToCurrentSet()) {
        $operationLog['bad'] += 1;
        $this->writeLog(
            $this->getErrorText(DictionaryErrors::BROKEN_ITEM) . ', too long',
            $hotel->hotelId,
            LogHelper::MESSAGE_TYPE_WARNING
        );
      }

      $operationLog['processed'] += 1;
    });

    try {
      echo '|'."\n";
    } catch (Exception $e) {
      Dictionary::livelog('city '.$this->gatewayCityId.': '.
        'processing failed, stopped at '.$operationLog['processed']. ' processed'
      );
      throw $e;
    }

    Dictionary::livelog('city '.$this->gatewayCityId.': '.'data loaded, '.
      'hotels: '.$operationLog['processed'].
      ', bad: '.$operationLog['bad'].
      ', inactive: '.$operationLog['inactive']
    );

    $this->setState(self::STATE_DATA_LOADED);
    return $operationLog['processed'];
  }

  /**
  * Получение времени самой новой записи от поставщика, которая была обработана
  * @return string Время обновления записи (формат БД)
  */
  private function getLastUpdateTimestamp() {
    $command = Yii::app()->db->createCommand()
      ->select('max(lastUpdate)')
      ->from('gpts_hotelsMeta_lastVersion')
      ->where('cityId = :cityId',[':cityId' => $this->gatewayCityId]);
    return $command->queryScalar();
  }

  /**
  * Вычисления необходимых изменений в справочнике
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::generateChangesDelta()
  */
  public function generateChangesDelta() {    
    Yii::app()->db->createCommand('drop temporary table if exists ' . $this->hotelsMetaCurrent_tmp)->execute();
    Yii::app()->db->createCommand('drop temporary table if exists ' . $this->hotelsMetaLastVersion_tmp)->execute();

    // временная таблица данных hotelsMeta_current
    $query = 'create temporary table ' . $this->hotelsMetaCurrent_tmp . '
              (primary key (hotelCode, supplierCode, cityId))
              as (select * from gpts_hotelsMeta_current where cityId = :cityId)';
    Yii::app()->db->createCommand($query)->execute([':cityId' => $this->gatewayCityId]);

    // временная таблица данных hotelsMeta_lastVersion
    $query = 'create temporary table ' . $this->hotelsMetaLastVersion_tmp . '
              (primary key (hotelCode, supplierCode, cityId))
              as (select * from gpts_hotelsMeta_lastVersion where cityId = :cityId)';
    Yii::app()->db->createCommand($query)->execute([':cityId' => $this->gatewayCityId]);

    /* вычисление создаваемых/обновляемых записей */
    $query = 'update gpts_hotelsMeta_current as cr
              left join ' . $this->hotelsMetaLastVersion_tmp . ' as lv
                on
                  lv.hotelCode = cr.hotelCode and
                  lv.supplierCode = cr.supplierCode and
                  lv.cityId = cr.cityId
              set cr.action = (
                select
                  case when lv.csum is null
                  then :action_create
                  when lv.csum = cr.csum and (
                    (lv.groupId is null and cr.groupId is null) or lv.groupId = cr.groupId
                  )
                  then :action_preserve
                  else :action_update end
              )
              where cr.cityId = :cityId';
    Yii::app()->db->createCommand($query)->execute([
      ':action_create' => self::HOTEL_CREATE,
      ':action_preserve' => self::HOTEL_PRESERVE,
      ':action_update' => self::HOTEL_UPDATE,
      ':cityId' => $this->gatewayCityId
    ]);

    if ($this->isFullUpdate) {
      /* вычисление удаляемых записей - только в случае полного обновления */
      $query = 'update gpts_hotelsMeta_lastVersion as lv
                left join ' . $this->hotelsMetaCurrent_tmp . ' as cr
                  on
                    lv.hotelCode = cr.hotelCode and
                    lv.supplierCode = cr.supplierCode and
                    lv.cityId = cr.cityId
                set
                  lv.action = if (cr.csum is null, :action_delete, :action_preserve)
                where lv.cityId = :cityId';
      Yii::app()->db->createCommand($query)->execute([
        ':action_delete' => self::HOTEL_DELETE,
        ':action_preserve' => self::HOTEL_PRESERVE,
        ':cityId' => $this->gatewayCityId
      ]);
    }

    Yii::app()->db->createCommand('drop temporary table if exists ' . $this->hotelsMetaCurrent_tmp)->execute();
    Yii::app()->db->createCommand('drop temporary table if exists ' . $this->hotelsMetaLastVersion_tmp)->execute();

    $this->setState(self::STATE_DELTA_GENERATED);
  }

  /**
  * Применение определенных изменений (не матчинг)
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::applyDefiniteChanges()
  */
  public function applyDefiniteChanges() {    
    Yii::app()->db->createCommand('drop temporary table if exists ' . $this->hotelsMetaCurrent_tmp)->execute();
    Yii::app()->db->createCommand('drop temporary table if exists ' . $this->hotelsMetaLastVersion_tmp)->execute();
    Yii::app()->db->createCommand('drop temporary table if exists ' . $this->hotelMatch_tmp)->execute();

    // временная таблица данных hotelsMeta_current
    $query = 'create temporary table ' . $this->hotelsMetaCurrent_tmp . '
              (primary key (hotelCode, supplierCode, cityId))
              as (select * from gpts_hotelsMeta_current where cityId = :cityId)';
    Yii::app()->db->createCommand($query)->execute([':cityId' => $this->gatewayCityId]);

    // временная таблица данных hotelsMeta_lastVersion
    $query = 'create temporary table ' . $this->hotelsMetaLastVersion_tmp . '
              (primary key (hotelCode, supplierCode, cityId))
              as (select * from gpts_hotelsMeta_lastVersion where cityId = :cityId)';
    Yii::app()->db->createCommand($query)->execute([':cityId' => $this->gatewayCityId]);

    // временная таблица данных hotelMatch
    $query = 'create temporary table ' . $this->hotelMatch_tmp . '
              (primary key (id))
              as (select * from ho_hotelMatch where cityId = :cityId)';
    Yii::app()->db->createCommand($query)->execute([':cityId' => $this->gatewayCityId]);

    // обработка удаленных отелей
    Dictionary::livelog('city '.$this->gatewayCityId.': '.'processing deleted hotels...');
    $this->processDeletedHotels();
    $this->flushHotelMatchTempTable();

    // обработка разгруппированных отелей
    Dictionary::livelog('city '.$this->gatewayCityId.': '.'processing ungrouped (dematched) hotels...');
    $this->processUngroupedHotels();
    $this->flushHotelsMetaCurrent();
    $this->flushHotelsMetaLastVersion();

    Dictionary::livelog('city '.$this->gatewayCityId.': '.'processing grouped hotels...');
    $this->processGroupedHotels();
    $this->flushHotelsMetaCurrent();

    Dictionary::livelog('city '.$this->gatewayCityId.': '.'updating hotel info...');
    $this->processStagedForUpdateHotels();
    $this->setState(self::STATE_CHANGES_APPLIED);
  }

  /** Обновление основной таблицы ho_hotelMatch из временной */
  private function flushHotelMatchTempTable() {
    $query = 'update ' . $this->hotelMatch_tmp . ' as hmt 
              join ho_hotelMatch as hm 
                on hm.id = hmt.id
              set hm.active = hmt.active';
    Yii::app()->db->createCommand($query)->execute();
  }

  /** Обновление основной таблицы gpts_hotelsMeta_current из временной */
  private function flushHotelsMetaCurrent() {
    $query = 'update ' . $this->hotelsMetaCurrent_tmp . ' as crt 
              join gpts_hotelsMeta_current as cr 
                on 
                  cr.hotelCode = crt.hotelCode and
                  cr.supplierCode = crt.supplierCode and 
                  cr.cityId = crt.cityId
              set cr.action = crt.action';
    Yii::app()->db->createCommand($query)->execute();
  }

  /** Обновление основной таблицы gpts_hotelsMeta_lastVersion из временной */
  private function flushHotelsMetaLastVersion() {
    $query = 'update ' . $this->hotelsMetaLastVersion_tmp . ' as lvt 
              join gpts_hotelsMeta_lastVersion as lv
                on 
                  lv.hotelCode = lvt.hotelCode and
                  lv.supplierCode = lvt.supplierCode and 
                  lv.cityId = lvt.cityId
              set lv.action = lvt.action';
    Yii::app()->db->createCommand($query)->execute();
  }

  /**
  * Матчинг отелей
  * @see Dictionaries\HotelsDictionary\UpdateStrategy::matchHotels()
  */
  public function matchHotels() {
    Dictionary::livelog('city '.$this->gatewayCityId.': '.'matching groupless hotels...');
    $this->processGrouplessHotels();
    Dictionary::livelog('city '.$this->gatewayCityId.': '.'updating hotel info...');
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
    
    Yii::app()->db->createCommand('drop temporary table if exists ' . $this->hotelsMetaCurrent_tmp)->execute();
    Yii::app()->db->createCommand('drop temporary table if exists ' . $this->hotelsMetaLastVersion_tmp)->execute();
    Yii::app()->db->createCommand('drop temporary table if exists ' . $this->hotelMatch_tmp)->execute();
    
    $this->clearCurrentHotelsSet();
  }

  /**
  * Очистка текущего набора данных по отелям от поставщика
  */
  private function clearCurrentHotelsSet() {
    try {
      Yii::app()->db->createCommand()->delete('gpts_hotelsMeta_current','cityId = :cityId',
        [':cityId' => $this->gatewayCityId]
      );

      Yii::app()->db->createCommand()->update(
        'gpts_hotelsMeta_lastVersion',['action' => self::HOTEL_PRESERVE],
        'cityId = :cityId',[':cityId' => $this->gatewayCityId]
      );
    } catch (Exception $e) {
      /** @todo не удалось удалить записи текущего набора данных отелей */
      throw $e;
    }
  }

  /**
  * Обработка удаленных отелей поставщика
  */
  private function processDeletedHotels() {
    $deletedHotelsIds = Yii::app()->db->createCommand()
      ->select('hm.hotelId')
      ->from($this->hotelMatch_tmp . ' as hm')
      ->join($this->hotelsMetaLastVersion_tmp . ' as lv', 
          'lv.hotelCode = hm.supplierHotelCode and lv.supplierCode = hm.supplierCode and lv.cityId = hm.cityId'
        )
      ->where('lv.action = :action_delete', [':action_delete' => self::HOTEL_DELETE])
      ->queryColumn();

    if (count($deletedHotelsIds) !== 0) {
      /* отметить КТ'шные отели для обновления контента */
      Yii::app()->db->createCommand()
        ->update('ho_hotelInfo',
          ['needUpdate' => 1],
          ['and',
            ['in', 'hotelId', $deletedHotelsIds],
            ['manualEdit = :manualEdit', [':manualEdit' => self::AUTO_EDIT]]
          ]
        );
    }

    /* отметить записи матчинга удаленных отелей поставщика как неактивные */
    $query = 'update ' . $this->hotelMatch_tmp . ' as hm
              join ' . $this->hotelsMetaLastVersion_tmp . ' as lv
                on
                  lv.hotelCode = hm.supplierHotelCode and
                  lv.supplierCode = hm.supplierCode and
                  lv.cityId = hm.cityID
              set hm.active = 0
              where
                lv.cityId = :cityId and
                lv.action = :action_delete and
                hm.gatewayId = :gatewayId';
    Yii::app()->db->createCommand($query)->execute([
      ':cityId' => $this->gatewayCityId,
      ':action_delete' => self::HOTEL_DELETE,
      ':gatewayId' => self::GPTS
    ]);
  }

  /**
  * Обработка отелей, удаленных поставщиком из групп ручного матчинга
  */
  private function processUngroupedHotels() {
    /* Удалить groupId у разматченных записей и выставить флаг "необходимо разматчивание" */
    $query = 'update ' . $this->hotelsMetaLastVersion_tmp . ' as lv
              join ' . $this->hotelsMetaCurrent_tmp . ' as cr
                on
                  cr.hotelCode = lv.hotelCode and
                  cr.supplierCode = lv.supplierCode and
                  cr.cityId = lv.cityId
              set lv.action = :action, lv.groupId = NULL
              where lv.cityId = :cityId and lv.groupId is not null and cr.groupId is null';
    Yii::app()->db->createCommand($query)->execute([
      ':action' => self::HOTEL_DEMATCH,
      ':cityId' => $this->gatewayCityId
    ]);

    /* получить все ID КТ'шных отелей, связанных с разматченными отелями поставщика */
    $dematchedHotelsIds = Yii::app()->db->createCommand()
      ->selectDistinct('hm.hotelId')
      ->from($this->hotelMatch_tmp . ' as hm')
      ->join($this->hotelsMetaLastVersion_tmp . ' as lv',
          'lv.hotelCode = hm.supplierHotelCode and
           lv.supplierCode = hm.supplierCode and
           lv.cityId = hm.cityID'
        )
      ->where('lv.cityId = :cityId and lv.action = :action and hm.gatewayId = :gatewayId',
          [
            ':cityId' => $this->gatewayCityId,
            ':action' => self::HOTEL_DEMATCH,
            ':gatewayId' => self::GPTS
          ]
        )
      ->queryColumn();

    if (count($dematchedHotelsIds) == 0 ) {
      return;
    }

    /* отметить полученные отели КТ для обновления */
    $command = Yii::app()->db->createCommand()
      ->update('ho_hotelInfo',
        ['needUpdate' => 1],
        ['and',
          ['in', 'hotelId', $dematchedHotelsIds],
          ['manualEdit = :manualEdit', [':manualEdit' => self::AUTO_EDIT]]
        ]
      );

    /* отметить записи связанные с полученными ID KT отели поставщика для обновления */
    $query = 'update ' . $this->hotelsMetaCurrent_tmp . ' as cr
              join ' . $this->hotelMatch_tmp . ' as hm
                on
                  hm.supplierHotelCode = cr.hotelCode and
                  hm.supplierCode = cr.supplierCode and
                  hm.cityID = cr.cityId
              set cr.action = :action_update
              where
                cr.action = :action_preserve and
                hm.hotelId in (' . implode(',',$dematchedHotelsIds) . ') and
                hm.manualEdit = :autoEdit and
                hm.gatewayId = :gatewayId';
    Yii::app()->db->createCommand($query)->execute([
      ':action_update' => self::HOTEL_UPDATE,
      ':action_preserve' => self::HOTEL_PRESERVE,
      ':autoEdit' => self::AUTO_EDIT,
      ':gatewayId' => self::GPTS
    ]);

    /* отметить разматченные записи как обработанные */
    Yii::app()->db->createCommand()->update(
      $this->hotelsMetaCurrent_tmp, ['action' => self::HOTEL_PRESERVE],
      'cityId = :cityId and action = :action_dematch',
      [
        ':cityId' => $this->gatewayCityId,
        ':action_dematch' => self::HOTEL_DEMATCH
      ]
    );
  }

  /**
  * Обработка сгруппированных (сматченных) отелей
  */
  private function processGroupedHotels() {
    $operationLog = [
      'total' => 0,
      'created' => 0,
      'updated' => 0,
      'matched' => 0
    ];

    $this->launchGroupedHotelsDataReader(function(GPTSHotelEntity $gptsHotel, $action) use (&$operationLog) {
      $operationLog['total'] += 1;

      if ($operationLog['total'] % 50 == 0) { echo '.'; }
      if ($operationLog['total'] % 1000 == 0) { echo $operationLog['total']."\n"; }

      $hotel = new KTHotelEntity();
      $hotel->setCityData($this->currentCity);

      switch ($action) {
        case self::HOTEL_CREATE:
          $hotel->initFromParams($gptsHotel->transformToKT());

          if (!$hotel->findByGroupId($gptsHotel->groupId,self::GPTS)) {
            $hotel->create();
            $hotel->matchStatus = 1;
            $operationLog['created'] += 1;
          } else {
            $hotel->stageForUpdate();
            $this->stageMatchedHotelsForUpdate($hotel->hotelId);
            $hotel->matchStatus = 0;
            $operationLog['matched'] += 1;
          }

          $hotel->createMatch($gptsHotel);
          break;
        case self::HOTEL_UPDATE:
          if (!$hotel->findBySupplierCodes($gptsHotel->supplierCode, $gptsHotel->hotelCode, $this->currentCity->cityId, self::GPTS)) {
            $this->writeLog(
                $this->getErrorText(DictionaryErrors::HOTEL_UNEXPECTEDLY_NOT_FOUND),
                [
                  'supplierCode' => $gptsHotel->supplierCode,
                  'hotelCode' => $gptsHotel->hotelCode,
                  'gateway' => 'gpts'
                ],
                LogHelper::MESSAGE_TYPE_WARNING
            );
            return;
          }

          $hotel->stageForUpdate();
          $this->stageMatchedHotelsForUpdate($hotel->hotelId);

          $previousGroupId = $gptsHotel->getLastVersionData('groupId');

          if ($previousGroupId !== false && $previousGroupId == $gptsHotel->groupId) {
            $hotel->matchStatus = 0;
            $operationLog['updated'] += 1;
            $hotel->updateMatch($gptsHotel);
          } else {
            $newHotel = new KTHotelEntity();
            $newHotel->setCityData($this->currentCity);

            if ($newHotel->findByGroupId($gptsHotel->groupId,self::GPTS)) {
              if ($hotel->getMatchesCount() == 1) {
                $hotel->deactivate();
              } else {
                $hotel->stageForUpdate();
              }
              $newHotel->stageForUpdate();
              $this->stageMatchedHotelsForUpdate($newHotel->hotelId);
              $newHotel->matchStatus = 0;
              $operationLog['updated'] += 1;
              $newHotel->updateMatch($gptsHotel);
            } else {
              if ($hotel->getGroupMatchesCount(self::GPTS) == 1) {
                $hotel->matchStatus = 0;
                $operationLog['updated'] += 1;
                $hotel->updateMatch($gptsHotel);
              } else {
                $hotel->stageForUpdate();
                $newHotel->initFromParams($gptsHotel->transformToKT());
                $newHotel->setCityData($this->currentCity);
                $newHotel->create();
                $newHotel->matchStatus = 1;
                $operationLog['created'] += 1;
                $newHotel->updateMatch($gptsHotel);
              }
            }
          }
          break;
      }

      $gptsHotel->saveToLastVersionSet();
    });

    echo "\n";
    Dictionary::livelog('city '.$this->gatewayCityId.': '.
      'finished processing grouped hotels' .
      ', total: '.$operationLog['total'] .
      ', created: '.$operationLog['created'] .
      ', matched: '.$operationLog['matched']
    );
  }

  /**
  * Обработка отелей, не имеющих группы поставщика
  */
  private function processGrouplessHotels() {
    $operationLog = [
      'total' => 0,
      'created' => 0,
      'matched' => 0
    ];

    $this->launchGrouplessHotelsDataReader(function(GPTSHotelEntity $gptsHotel, $action) use (&$operationLog) {
      $operationLog['total'] += 1;

      if ($operationLog['total'] % 50 == 0) { echo '.'; }
      if ($operationLog['total'] % 1000 == 0) { echo $operationLog['total']."\n"; }

      $hotel = new KTHotelEntity();
      $hotel->initFromParams($gptsHotel->transformToKT());
      $hotel->setCityData($this->currentCity);

      switch ($action) {
        case self::HOTEL_CREATE:
          if ($hotel->tryToMatch($this->matchRules)) {
            $hotel->fill();
            $hotel->stageForUpdate();
            $hotel->matchStatus = 0;
            $operationLog['matched'] += 1;
          } else {
            $hotel->create();
            $hotel->matchStatus = 1;
            $operationLog['created'] += 1;
          }
          $hotel->createMatch($gptsHotel);
          break;
        case self::HOTEL_UPDATE:
          if (!$hotel->findBySupplierCodes($gptsHotel->supplierCode, $gptsHotel->hotelCode, $this->currentCity->cityId, self::GPTS)) {
            $this->writeLog(
                $this->getErrorText(DictionaryErrors::HOTEL_UNEXPECTEDLY_NOT_FOUND),
                [
                  'supplierCode' => $gptsHotel->supplierCode,
                  'hotelCode' => $gptsHotel->hotelCode,
                  'gateway' => 'gpts'
                ],
                LogHelper::MESSAGE_TYPE_WARNING
            );
            return;
          }

          if ($hotel->checkMatch($this->matchRules)) {
            $hotel->fill();
            $hotel->stageForUpdate();
            $hotel->matchStatus = 0;
            $operationLog['matched'] += 1;
            $hotel->updateMatch($gptsHotel);
          } else {
            $newHotel = new KTHotelEntity();
            $newHotel->initFromParams($gptsHotel->transformToKT());
            $newHotel->setCityData($this->currentCity);

            if ($newHotel->tryToMatch($this->matchRules)) {
              if ($hotel->getMatchesCount() == 1) {
                $hotel->deactivate();
              } else {
                $hotel->stageForUpdate();
              }
              $newHotel->fill();
              $newHotel->stageForUpdate();
              $newHotel->matchStatus = 0;
              $operationLog['matched'] += 1;
              $newHotel->updateMatch($gptsHotel);
            } else {
              $hotel->stageForUpdate();
              if ($hotel->getMatchesCount() == 1) {
                $hotel->update();
                $hotel->matchStatus = 0;
                $operationLog['matched'] += 1;
                $hotel->updateMatch($gptsHotel);
              } else {
                $newHotel->create();
                $newHotel->matchStatus = 1;
                $operationLog['created'] += 1;
                $newHotel->updateMatch($gptsHotel);
              }
            }
          }
          break;
      }

      $gptsHotel->saveToLastVersionSet();
    });

    echo "\n";
    Dictionary::livelog('city '.$this->gatewayCityId.': '.
      'finished processing groupless hotels' .
      ', total: '.$operationLog['total'] .
      ', created: '.$operationLog['created'] .
      ', matched: '.$operationLog['matched']
    );
  }

  /**
  * Обновление информации отмеченных для обновления отелей
  */
  private function processStagedForUpdateHotels() {
    $operationLog = ['processed' => 0];
    $supplierPriorities = $this->config['content_supplier_priority'];

    $this->launchStagedForUpdateHotelsDataReader(
      function($hotelId, GPTSHotelsCollection $hotelsSet) use (&$operationLog,$supplierPriorities) {
        $hotel = new KTHotelEntity($hotelId);
        $hotel->initFromParams($hotelsSet->generateKTHotelContent($supplierPriorities));
        $hotel->setCityData($this->currentCity);
        $hotel->update();

        $operationLog['processed'] += 1;
        if ($operationLog['processed'] % 50 == 0) { echo '.'; }
        if ($operationLog['processed'] % 1000 == 0) { echo $operationLog['processed']."\n"; }
      }
    );

    echo "\n";
    Dictionary::livelog('city '.$this->gatewayCityId.': '.
      'finished processing staged hotels, processed: '.$operationLog['processed']
    );
  }

  /**
  * Запуск построчной обработки сгруппированных отелей
  * @param Callable $processor функция обработки записи
  */
  private function launchGroupedHotelsDataReader(Callable $processor) {
    $conn = new CDbConnection(
      Yii::app()->db->connectionString,
      Yii::app()->db->username,
      Yii::app()->db->password
    );

    $hotelsData_tmp = 'hotels_data_' . $this->gatewayCityId;

    // инициализация временной таблицы
    $query = 'create temporary table if not exists ' . $hotelsData_tmp . '
              as (
                select hd.supplierCode, hd.hotelCode, hd.hotelData, hmc.action
                from gpts_hotelsData as hd
                join gpts_hotelsMeta_current as hmc
                  on 
                    hd.hotelCode = hmc.hotelCode and 
                    hd.supplierCode = hmc.supplierCode and 
                    hd.cityId = hmc.cityId
                where
                  hmc.cityId = :cityId and
                  hmc.groupId is not null and
                  (hmc.action = :updateaction or hmc.action = :createaction)
              )';
    $conn->createCommand($query)->execute([
        ':cityId' => $this->gatewayCityId,
        ':updateaction' => self::HOTEL_UPDATE,
        ':createaction' => self::HOTEL_CREATE,
      ]);

    // запрос данных из временной таблицы
    $command = $conn->createCommand()->select('*')->from($hotelsData_tmp);
    $hotelsReader = $command->query();

    try {
      foreach ($hotelsReader as $h) {
        $hotelData = json_decode($h['hotelData'],true);
        $isNew = ($h['action'] == self::HOTEL_CREATE) ? true : false;

        if ($hotelData == null) {
          $this->writeLog(
              DictionaryErrors::BROKEN_SET_ITEM,
              [
                'supplierCode' => $h['supplierCode'],
                'hotelCode' => $h['hotelCode']
              ],
              LogHelper::MESSAGE_TYPE_WARNING
          );
          continue;
        } else {
          $gptsHotel = new GPTSHotelEntity($this->gptsEngine);
          $gptsHotel->initFromFullParams($hotelData, $this->gatewayCityId, $isNew);
          $processor($gptsHotel, $h['action']);
        }
      }
    } finally {
      $hotelsReader->close();
      $conn->setActive(false);
    }
  }

  /**
  * Запуск построчной обработки несгруппированных отелей отелей
  * @param Callable $processor функция обработки записи
  */
  private function launchGrouplessHotelsDataReader(Callable $processor) {
    $conn = new CDbConnection(
      Yii::app()->db->connectionString,
      Yii::app()->db->username,
      Yii::app()->db->password
    );

    $hotelsData_tmp = 'hotels_data_' . $this->gatewayCityId;

    // инициализация временной таблицы
    $query = 'create temporary table if not exists ' . $hotelsData_tmp . '
              as (
                select 
                  hd.supplierCode as supplierCode, 
                  hd.hotelCode as hotelCode, 
                  hd.hotelData as hotelData, 
                  hmc.action as action
                from gpts_hotelsData as hd
                join gpts_hotelsMeta_current as hmc
                  on 
                    hd.hotelCode = hmc.hotelCode and 
                    hd.supplierCode = hmc.supplierCode and 
                    hd.cityId = hmc.cityId
                where
                  hmc.cityId = :cityId and
                  hmc.groupId is null and
                  (hmc.action = :action_update or hmc.action = :action_create)
              )';
    $conn->createCommand($query)->execute([
        ':cityId' => $this->gatewayCityId,
        ':action_update' => self::HOTEL_UPDATE,
        ':action_create' => self::HOTEL_CREATE,
      ]);

    // запрос данных из временной таблицы
    $command = $conn->createCommand()->select('*')->from($hotelsData_tmp);
    $hotelsReader = $command->query();

    try {
      foreach ($hotelsReader as $h) {
        $hotelData = json_decode($h['hotelData'],true);
        $isNew = ($h['action'] == self::HOTEL_CREATE) ? true : false;

        if ($hotelData == null) {
          $this->writeLog(
              DictionaryErrors::BROKEN_SET_ITEM,
              [
                'supplierCode' => $h['supplierCode'],
                'hotelCode' => $h['hotelCode']
              ],
              LogHelper::MESSAGE_TYPE_WARNING
          );
          continue;
        } else {
          $gptsHotel = new GPTSHotelEntity($this->gptsEngine);
          $gptsHotel->initFromFullParams($hotelData,$this->gatewayCityId,$isNew);
          $processor($gptsHotel,$h['action']);
        }
      }
    } finally {
      $hotelsReader->close();
      $conn->setActive(false);
    }
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

    $hotel_match_tmp = 'hotel_match_' . $this->gatewayCityId;

    $query = 'create temporary table if not exists ' . $hotel_match_tmp . '
              as (
                select
                  hi.hotelId as hotelId, 
                  hm.supplierCode as supplierCode, 
                  hm.supplierHotelCode as hotelCode, 
                  hm.cityId as cityId
                from ho_hotelMatch as hm
                left join ho_hotelInfo as hi
                  on hm.hotelId = hi.hotelId
                where
                  hm.cityId = :cityId and
                  hm.gatewayId = :gatewayId and
                  hi.needUpdate = :needUpdate
              )';
    $conn->createCommand($query)->execute([
      ':cityId' => $this->gatewayCityId,
      ':gatewayId' => self::GPTS,
      ':needUpdate' => 1
    ]);


    $hotelsData_tmp = 'hotels_data_' . $this->gatewayCityId;

    // инициализация временной таблицы
    $query = 'create temporary table if not exists ' . $hotelsData_tmp . '
              as (
                select 
                  hm.hotelId as hotelId, 
                  hm.supplierCode as supplierCode, 
                  hm.hotelCode as hotelCode, 
                  hd.hotelData as hotelData
                from ' . $hotel_match_tmp . ' as hm
                left join gpts_hotelsData as hd
                  on 
                    hd.hotelCode = hm.hotelCode and 
                    hd.supplierCode = hm.supplierCode and 
                    hd.cityId = hm.cityId
                where hd.hotelData is not null
              )';
    $conn->createCommand($query)->execute();

    // запрос данных из временной таблицы
    $command = $conn->createCommand()->select('*')->from($hotelsData_tmp)->order('hotelId');
    $hotelsReader = $command->query();

    $currentHotelId = false;
    $hotelsSet = new GPTSHotelsCollection();
    try {
      foreach ($hotelsReader as $h) {
        $hotelData = json_decode($h['hotelData'],true);
        $gptsHotel = new GPTSHotelEntity($this->gptsEngine);
        $gptsHotel->initFromFullParams($hotelData,$this->gatewayCityId);

        if ($currentHotelId === false) {
          $currentHotelId = $h['hotelId'];
        } elseif ($currentHotelId !== $h['hotelId']) {
          $processor($currentHotelId,$hotelsSet);
          $currentHotelId = $h['hotelId'];
          $hotelsSet = new GPTSHotelsCollection();
        }

        $hotelsSet->addHotel($gptsHotel);
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
  * Отметить отели GPTS, связанные с отелем КТ, для обновления
  * @param int $hotelId ID отеля КТ
  */
  private function stageMatchedHotelsForUpdate($hotelId) {
    $query = 'update ' . $this->hotelsMetaCurrent_tmp . ' as cr
              join ' . $this->hotelMatch_tmp . ' as hm
                on
                  hm.supplierHotelCode = cr.hotelCode and
                  hm.supplierCode = cr.supplierCode and
                  hm.cityID = cr.cityId
              set cr.action = :action_update
              where
                hm.hotelId = :hotelId and
                cr.action = :action_preserve and
                cr.groupId is null and
                hm.gatewayId = :gatewayId';
    Yii::app()->db->createCommand($query)->execute([
      ':action_update' => self::HOTEL_UPDATE,
      ':hotelId' => $hotelId,
      ':action_preserve' => self::HOTEL_PRESERVE,
      ':gatewayId' => self::GPTS
    ]);
  }

}
