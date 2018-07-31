<?php

namespace Dictionaries\HotelsDictionary\Workers;

use \Yii as Yii;
use \Dictionary as Dictionary;
use \LogHelper as LogHelper;
use Dictionaries\HotelsDictionary\UpdateStrategy as UpdateStrategy;
use Dictionaries\HotelsDictionary\GPTSUpdateStrategy as GPTSUpdateStrategy;

class GPTSHotelsMatchWorker extends \GearmanWorker {
  /** @var array конфигурация воркера */
  private $config;

  public function __construct($config) {
    parent::__construct();

    Yii::app()->db->setActive(false);
    $this->config = $config;
    $this->addServer($config['host'], $config['port']);
  }

  public function run() {
    $prefix = $this->config['workerPrefix'];
    $this->addFunction($prefix . '_gptsHotelsMatch', [$this, 'runGptsHotelsMatch']);
    while ($this->work());
  }

  public static function runGptsHotelsMatch(\GearmanJob $job) {
    $params = json_decode($job->workload(), true);
    $cityId = $params['cityId'];

    try {
      $module = Yii::app()->getModule('supplierService')->getModule('Dictionaries');

      $updateStrategy = new GPTSUpdateStrategy($module,$params['updateType']);
      $updateStrategy->loadCityData($cityId);

      if (UpdateStrategy::STATE_DATA_LOADED == $updateStrategy->getState()) {
        Dictionary::livelog('city '.$cityId.': '.'generating changes delta...');
        $updateStrategy->generateChangesDelta();
        Dictionary::livelog('city '.$cityId.': '.'delta generated');
      }

      if (UpdateStrategy::STATE_DELTA_GENERATED == $updateStrategy->getState()) {
        Dictionary::livelog('city '.$cityId.': '.'applying definite changes...');
        $updateStrategy->applyDefiniteChanges();
      }

      if (UpdateStrategy::STATE_CHANGES_APPLIED == $updateStrategy->getState()) {
        Dictionary::livelog('city '.$cityId.': '.'matching hotels...');
        $updateStrategy->matchHotels();
        $updateStrategy->finishCityProcessing();
        Dictionary::livelog('city '.$cityId.': '.'processing finished');
      }

      return json_encode($params);
    } catch (\KmpException $ke) {
      Dictionary::livelog(
        'kmp exception: city: ' . $cityId . ', code:' . $ke->getCode() . 
        ', class: ' . $ke->class . ', method: ' . $ke->method . 
        "\n" . print_r($ke->params, true)
      );
      return json_encode($params);
    } catch (\Exception $e) {
      Dictionary::livelog('exception: city '.$cityId.': msg'.$e->getMessage());
      return json_encode($params);
    }
  }
}
