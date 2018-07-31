<?php

namespace Dictionaries\HotelsDictionary\Workers;

use \Yii as Yii;
use \Dictionary as Dictionary;
use Dictionaries\HotelsDictionary\UpdateStrategy as UpdateStrategy;
use Dictionaries\HotelsDictionary\GPTSUpdateStrategy as GPTSUpdateStrategy;

class GPTSHotelsDownloadWorker extends \GearmanWorker {
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
    $this->addFunction($prefix . '_gptsHotelsDownload', [$this, 'runGptsHotelsDownload']);
    while ($this->work());
  }

  public static function runGptsHotelsDownload(\GearmanJob $job) {
    $params = json_decode($job->workload(), true);
    $cityId = $params['cityId'];

    try {
      $module = Yii::app()->getModule('supplierService')->getModule('Dictionaries');
      $params['finalize'] = false;

      $job->sendData('city '.$cityId.': start downloading');

      $updateStrategy = new GPTSUpdateStrategy($module,$params['updateType']);
      $updateStrategy->loadCityData($cityId);

      switch ($updateStrategy->getState()) {
        case UpdateStrategy::STATE_FINISHED:
          Dictionary::livelog('city '.$cityId.': '.'city already processed');
          $params['finalize'] = true;
          break;
        case UpdateStrategy::STATE_INITIAL:
          Dictionary::livelog('city '.$cityId.': '.'loading hotels data from gateway...');
          $hotelsCount = $updateStrategy->loadCurrentHotelsData();
          $job->sendData('city '.$cityId.': '.'data loaded, '.$hotelsCount.' hotels');

          if ($hotelsCount == 0) {
            Dictionary::livelog('city '.$cityId.': '.'no hotels in city');
            $updateStrategy->finishCityProcessing();
            $params['finalize'] = true;
          }
          break;
        default:
          $job->sendData('city '.$cityId.': '.'data already downloaded, skipping');
          Dictionary::livelog('city '.$cityId.': '.'data already downloaded, skipping');
          break;
      }

      return json_encode($params);

    } catch (\KmpException $ke) {
      Dictionary::livelog(
        'kmp exception: city: ' . $cityId . ', code:' . $ke->getCode() . 
        ', class: ' . $ke->class . ', method: ' . $ke->method . 
        "\n" . print_r($ke->params, true)
      );
      $job->sendData('kmp exception: city '.$cityId.': code: '.$ke->getCode());
      $params = ['finalize' => true, 'cityId' => $cityId];
      return json_encode($params);
    } catch (\Exception $e) {
      Dictionary::livelog('exception: city '.$cityId.': msg'.$e->getMessage());
      $job->sendData('exception: city '.$cityId.': msg'.$e->getMessage());
      $params = ['finalize' => true, 'cityId' => $cityId];
      return json_encode($params);
    }
  }
}
