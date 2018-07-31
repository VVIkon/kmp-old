<?php

use Dictionaries\HotelsDictionary\UpdateStrategy as UpdateStrategy;
use Dictionaries\HotelsDictionary\GPTSUpdateStrategy as GPTSUpdateStrategy;
use Dictionaries\HotelsDictionary\UTKUpdateStrategy as UTKUpdateStrategy;
use Dictionaries\HotelsDictionary\Workers\GPTSHotelsDownloadWorker as GPTSHotelsDownloadWorker;
use Dictionaries\HotelsDictionary\Workers\GPTSHotelsDMatchWorker as GPTSHotelsDMatchWorker;
use Dictionaries\HotelsDictionary\Entities\GPTSHotelEntity as GPTSHotelEntity;
use Dictionaries\HotelsDictionary\Entities\KTHotelEntity as KTHotelEntity;
use Dictionaries\HotelsDictionary\Entities\CityEntity as CityEntity;

class HotelsDictionary {
  const UTK_ENGINE = 4;
  const GPTS_ENGINE = 5;
  const KT_ENGINE = 6;

  private $engines = [
    self::UTK_ENGINE => 'UTKEngine',
    self::GPTS_ENGINE => 'GPTSEngine',
    self::KT_ENGINE => 'KTEngine'
  ];

  private $module;
  private $namespace;
  private $config;

  /** @var UpdateStrategy Алгоритм обновления справочника в зависимости от поставщика */
  private $updateStrategy;


  public function __construct(&$module) {
    $this->module = $module;
    $this->namespace = $this->module->getConfig('log_namespace');
    $this->config = $this->module->getConfig('hotels_dictionary_config');
  }

  /** Обнолвение справочника отелей */
  public function updateDictionary($engineType,$updateType) {
    $eg = (int)$engineType;

    if (isset($this->engines[$eg])) {
      Dictionary::livelog('start dictionary update...');

      switch ($eg) {
        case self::GPTS_ENGINE:
          $this->updateStrategy = new GPTSUpdateStrategy($this->module,$updateType);
          $this->performAsyncGPTSUpdate($updateType);
          break;
        case self::UTK_ENGINE:
          $this->updateStrategy = new UTKUpdateStrategy($this->module,$updateType);
          $this->performUpdate($updateType);
          $this->regenerateLocationSuggest();
          break;
        default:
          throw new KmpException(
              get_class(),__FUNCTION__,
              DictionaryErrors::ENGINE_NOT_SUPPORTED_BY_COMMAND,
              ['requested_engine' => $eg]
          );
      }
    } else {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::UNKNOWN_ENGINE,
          ['requested_engine' => $eg]
      );
    }
  }

  /** 
  * Создание нового отеля по данным, полученным из поиска
  * @param mixed $hotelInfo - информация об отеле из GPTS (часть info структуры предложения)
  * @param int $gptsMainCityId - ID города отеля (GPTS, по которому создается запись)
  */
  public function createHotelFromSearch($hotelInfo, $gptsMainCityId) {
    $gptsEngine = Yii::app()->getModule('supplierService')->getModule('GPTSEngine')->getEngine();

    $gptsHotel = new GPTSHotelEntity($gptsEngine);
    $mainCity = new CityEntity($gptsMainCityId, Dictionary::GPTS);
    
    if ($gptsHotel->initFromSearch($hotelInfo, $gptsMainCityId)) {
      $hotel = new KTHotelEntity();
      $hotel->setCityData($mainCity);
      $hotel->initFromParams($gptsHotel->transformToKT());

      $hotelDataIsCalculated = $gptsHotel->isInLastVersion();
      $hotelDataIsStaged = $gptsHotel->isInCurrent();

      $transaction = Yii::app()->db->beginTransaction();
      try {
        $hotel->create();
        $hotel->matchStatus = 1;
        $hotel->createMatch($gptsHotel);

        $gptsHotel->createDummyLastVersionData($hotelDataIsCalculated, $hotelDataIsStaged);

        $transaction->commit();

        return $hotel->hotelId;

      } catch (Exception $e) {
        LogHelper::logExt(
          'Dictionary','initHotelFromSearch','','cannot create hotel',
          [
            'error' => $e->getMessage(),
            'supplierCode' => $hotelInfo['supplierCode'],
            'hotelCode' => $hotelInfo['hotelCode']
          ],
          LogHelper::MESSAGE_TYPE_WARNING,
          'system.supplierservice.errors'
        );
        $transaction->rollback();
        return null;
      }
    } else {
      LogHelper::logExt(
        'Dictionary','initHotelFromSearch','',
        'cannot initialize gpts hotel: empty supplierCode, hotelCode or address',
        $hotelInfo,
        LogHelper::MESSAGE_TYPE_WARNING,
        'system.supplierservice.errors'
      );

      return null;
    }
  }

  /**
  * Запуск процедуры обновления
  * @param int $updateType тип обновления
  */
  private function performUpdate($updateType) {
    $cities = $this->updateStrategy->getUpdateList();
    Dictionary::livelog('got '.count($cities).' cities');

    foreach ($cities as $city) {
      Dictionary::livelog('processing city '.$city);
      $this->updateStrategy->loadCityData($city);

      if (UpdateStrategy::STATE_FINISHED == $this->updateStrategy->getState()) {
        Dictionary::livelog('city already processed');
        continue;
      }

      if (UpdateStrategy::STATE_INITIAL == $this->updateStrategy->getState()) {
        Dictionary::livelog('loading hotels data from gateway...');
        $hotelsCount = $this->updateStrategy->loadCurrentHotelsData();

        if ($hotelsCount == 0) {
          Dictionary::livelog('no hotels in city');
          $this->updateStrategy->finishCityProcessing();
          continue;
        }
      }

      if (UpdateStrategy::STATE_DATA_LOADED == $this->updateStrategy->getState()) {
        Dictionary::livelog('generating changes delta...');
        $this->updateStrategy->generateChangesDelta();
        Dictionary::livelog('delta generated');
      }

      if (UpdateStrategy::STATE_DELTA_GENERATED == $this->updateStrategy->getState()) {
        Dictionary::livelog('applying definite changes...');
        $this->updateStrategy->applyDefiniteChanges();
      }

      if (UpdateStrategy::STATE_CHANGES_APPLIED == $this->updateStrategy->getState()) {
        Dictionary::livelog('matching hotels...');
        $this->updateStrategy->matchHotels();
        $this->updateStrategy->finishCityProcessing();
        Dictionary::livelog('processing finished');
      }
    }
  }

  /**
  * Запуск асинхронной процедуры обновления GPTS
  * @param int $updateType тип обновления
  */
  private function performAsyncGPTSUpdate($updateType) {
    $config = $this->module->getConfig('gearman');
    $prefix = $config['workerPrefix'];

    /* gearman клиент для скачивающего воркера */
    $gmdc = new GearmanClient();
    $gmdc->addServer($config['host'], $config['port']);

    /* gearman клиент для воркера матчинга */
    $gmmc = new GearmanClient();
    $gmmc->addServer($config['host'], $config['port']);

    /* количество задач матчинга в очереди */
    $matchers = [];

    $gmdc->setDataCallback(function(GearmanTask $task) {
      Dictionary::livelog($task->data());
    });

    $gmdc->setCompleteCallback(function(GearmanTask $task) use (&$gmmc, &$matchers, $prefix) {
      $params = json_decode($task->data(),true);
      $cityId = $params['cityId'];

      $matchers = array_filter($matchers,function($job) use (&$gmmc) {
        $stat = $gmmc->jobStatus($job);
         return (bool)$stat[0];
      });

      if (!$params['finalize']) {
        Dictionary::livelog('city '.$cityId.': '.'launching match');
        $mpid = $gmmc->doBackground($prefix.'_gptsHotelsMatch',json_encode($params));
        $matchers[] = $mpid;
        Dictionary::livelog('city '.$cityId.': '.'match process ID: '.$mpid);
      } else {
        Dictionary::livelog('city '.$cityId.': '.'processing finished');
      }
    });

    $cities = array_reverse($this->updateStrategy->getUpdateList());
    Dictionary::livelog('got '.count($cities).' cities');
    foreach ($cities as $cityId) {
      $gmdc->addTask($prefix.'_gptsHotelsDownload',json_encode([
        'updateType' => $updateType,
        'cityId' => $cityId
      ]));
    }
    Dictionary::livelog('start cities processing...');

    if (!$gmdc->runTasks()) {
      Dictionary::livelog('failed to start processing');
      exit(11);
    }

    Dictionary::livelog('downloading data finished');

    while(count($matchers) > 0) {
      Dictionary::livelog('matching tasks left: '.count($matchers));
      sleep(60);
      $matchers = array_filter($matchers,function($job) use (&$gmmc) {
        $stat = $gmmc->jobStatus($job);
         return (bool)$stat[0];
      });
    }

    Dictionary::livelog('matching done');
  }

  /**
  * Обновление справочника локаций
  */
  private function regenerateLocationSuggest() {
    Dictionary::livelog('regenerating hotel locations...');
    Yii::app()->db->createCommand('call update_ho_locations()')->execute();
  }
}
