<?php

use Dictionaries\HotelsDictionary\Workers\GPTSHotelsDownloadWorker as GPTSHotelsDownloadWorker;
use Dictionaries\HotelsDictionary\Workers\GPTSHotelsMatchWorker as GPTSHotelsMatchWorker;

class DictionaryWorkersLauncher extends CConsoleCommand {

  /**
  * Воркер загрузки данных отелей из GPTS
  */
  public function actionGPTSHotelsDownloadWorker() {
    $module = Yii::app()->getModule('supplierService')->getModule('Dictionaries');
    $config = $module->getConfig('gearman');

    $Worker = new GPTSHotelsDownloadWorker($config);
    $Worker->run();
  }

  /**
  * Воркер матчинга отелей GPTS
  */
  public function actionGPTSHotelsMatchWorker() {
    $module = Yii::app()->getModule('supplierService')->getModule('Dictionaries');
    $config = $module->getConfig('gearman');

    $Worker = new GPTSHotelsMatchWorker($config);
    $Worker->run();
  }
}
