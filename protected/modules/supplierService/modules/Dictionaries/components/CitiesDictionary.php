<?php

class CitiesDictionary {
  const UTK_ENGINE = 4;
  const GPTS_ENGINE = 5;
  const KT_ENGINE = 6;

  private $engines = [
    self::UTK_ENGINE => 'UTKEngine',
    self::GPTS_ENGINE => 'GPTSEngine',
    self::KT_ENGINE => 'KTEngine'
  ];

  /** @var array параметры результата выполнения команд для считывания извне */
  public $resultMsg=[];

  private $module;
  private $namespace;

  public function __construct(&$module) {
    $this->module=$module;
    $this->namespace = $this->module->getConfig('log_namespace');
  }

  public function updateDictionary($engineType,$updateType) {
    $eg=(int)$engineType;

    if (isset($this->engines[$eg])) {
      switch ($eg) {
        case self::GPTS_ENGINE:
          $this->updateDictionaryFromGPTS($updateType);
          break;
        case self::UTK_ENGINE:
          $this->updateDictionaryFromUTK($updateType);
          break;
        default:
          throw new KmpException(
              get_class(),__FUNCTION__,
              DictionaryErrors::ENGINE_NOT_SUPPORTED_BY_COMMAND,
              ['requested_engine' => $eg]
          );
          break;
      }
    } else {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::UNKNOWN_ENGINE,
          ['requested_engine' => $eg]
      );
    }

    $this->regenerateLocationSuggest();
  }

  /**
  * Обновление справочника городов по данным GPTS
  * @param int $updateType тип обновления (полный/частичный)
  */
  private function updateDictionaryFromGPTS($updateType) {
    Dictionary::livelog('starting update cities from gpts...');
    $GPTSEngine=Yii::app()->getModule('supplierService')->getModule($this->engines[self::GPTS_ENGINE])->getEngine();

    $itemCount=0;
    $createCount=0;
    $updateCount=0;
    $brokenCount=0;
    $linkedCount=0;

    try {
      /** Обработка русского списка городов - матчинг, создание, ... */
      Dictionary::livelog('preparing match data...');
      $matchInfo=CitiesDictionaryForm::getMatchInfo(CitiesDictionaryForm::GPTS);

      Yii::app()->db->setActive(false);
      $GPTSEngine->reAuthenticate();

      Dictionary::livelog('getting data from gpts...');
      $cities=$GPTSEngine->runApiCommand('Locations','locations',['limitCities' => -1],true,'ru');

      /** реконнект - после долгого простоя соединение сбрасывается, поэтому отключаем его перед запросом в ГПТС */
      Yii::app()->db->setActive(true);

      /** Функция обработки элемента коллекции */
      $listener = new CitiesListener(function ($item) use
        (&$matchInfo,&$itemCount,&$createCount,&$updateCount,&$brokenCount,&$linkedCount) {
        $itemCount++;

        $city=new CitiesDictionaryForm();

        if (($itemCount%1000)==0) {
          Dictionary::livelog('parsed '.$itemCount.' cities');
        }

        if (empty($item['id']) || empty ($item['name']) || empty ($item['countryId'])) {
          $brokenCount++;

          LogHelper::logExt(
              'Dictionary','updateDictionary','',
              $this->module->getError(DictionaryErrors::BROKEN_ITEM),
              ['item' => $item],
              LogHelper::MESSAGE_TYPE_WARNING,
              $this->namespace. '.errors'
          );

          return;
        }

        $gptsId=(int)$item['id'];
        $gptsCountryId=(int)$item['countryId'];
        $countryId=CountriesDictionaryForm::getCountryIdBySupplierId(CountriesDictionaryForm::GPTS,$gptsCountryId);

        if ($countryId==false) {
          $brokenCount++;

          LogHelper::logExt(
              'Dictionary','updateDictionary','',
              $this->module->getError(DictionaryErrors::BROKEN_ITEM),
              ['err'=>'no country id','item' => $item],
              LogHelper::MESSAGE_TYPE_WARNING,
              $this->namespace. '.errors'
          );

          return;
        }

        $params=[
          'cityId' => null,
          'supplierCityId' => $gptsId,
          'countryId' => $countryId,
          'supplierCountryId' => $gptsCountryId,
          'parentType' => ($item['countryId'] == $item['parentId']) ? 'country' : 'city',
          'name' => $item['name'],
          'engName' => null,
          'iata' => !empty($item['iatacode']) ? $item['iatacode'] : null,
          'latitude' => !empty($item['latitude']) ? $item['latitude'] : null,
          'longitude' => !empty($item['longitude']) ? $item['longitude'] : null,
          'active' => CitiesDictionaryForm::STATUS_ACTIVE,
          'manualEdit' => CitiesDictionaryForm::AUTO_EDIT,
          'lastUpdate' => date('Y-m-d H:i:s')
        ];

        $city->setAttributes($params);

        if (isset($matchInfo[(string)$gptsId])) {
          /** обновление только автоматически сматченных записей */
          if ($matchInfo[(string)$gptsId]['manualEdit'] == CitiesDictionaryForm::AUTO_EDIT) {

            $city->cityId = $matchInfo[(string)$gptsId]['cityId'];

            try {
              $city->update();
              $city->updateMatch(CitiesDictionaryForm::GPTS);
              $updateCount++;

            } catch (KmpException $ke) {
              LogHelper::logExt(
                  $ke->class,
                  $ke->method,
                  $this->module->getCxtName($ke->class, $ke->method),
                  $this->module->getError($ke->getCode()) . ': ' . $ke->params['error'],
                  array_merge(
                    $params,
                    ['cityId' => $matchInfo[(string)$gptsId]['cityId']]
                  ),
                  LogHelper::MESSAGE_TYPE_ERROR,
                  $this->namespace. '.errors'
              );
              return;
            } catch (Exception $e) {
              throw $e;
            }
          }
        } else {
          /** записи матчинга нет */
          if ($city->tryToMatch()) {
            /** найдено совпадение */
            $matchInfo[(string)$gptsId] = [
              'cityId' => $city->cityId,
              'manualEdit' => CitiesDictionaryForm::AUTO_EDIT
            ];

            try {

              $city->update();
              $city->createMatch(CitiesDictionaryForm::GPTS);
              $linkedCount++;

            } catch (KmpException $ke) {
              LogHelper::logExt(
                  $ke->class,
                  $ke->method,
                  $this->module->getCxtName($ke->class, $ke->method),
                  $this->module->getError($ke->getCode()) . ': ' . $ke->params['error'],
                  array_merge(
                    $params,
                    ['cityId' => $match]
                  ),
                  LogHelper::MESSAGE_TYPE_ERROR,
                  $this->namespace. '.errors'
              );
              return;
            } catch (Exception $e) {
              throw $e;
            }
          } else {
            /**
            * @todo автоматчинг не удался, город создан
            */
            try {
              $city->create();
              $city->createMatch(CitiesDictionaryForm::GPTS);

              $matchInfo[(string)$gptsId] = [
                'cityId' => $city->cityId,
                'manualEdit' => CitiesDictionaryForm::AUTO_EDIT
              ];

              $createCount++;
            } catch (KmpException $ke) {
              LogHelper::logExt(
                  $ke->class,
                  $ke->method,
                  $this->module->getCxtName($ke->class, $ke->method),
                  $this->module->getError($ke->getCode()) . ': ' . $ke->params['error'],
                  array_merge(
                    $params,
                    ['cityId' => $city->cityId]
                  ),
                  LogHelper::MESSAGE_TYPE_ERROR,
                  $this->namespace. '.errors'
              );
              return;
            }
          }

        }
      });

      Dictionary::livelog('start parsing...');
      $parser = new JsonStreamingParser\Parser($cities,$listener);
      $parser->parse();

      CitiesDictionaryForm::markSuspicious(CitiesDictionaryForm::GPTS);

      Dictionary::livelog('parsing finished'.
        ', items:'.$itemCount.
        ', created:'.$createCount.
        ', updated:'.$updateCount.
        ', linked:'.$linkedCount.
        ', broken:'.$brokenCount);
      fclose($cities);

      $this->resultMsg=[
        'items' => $itemCount,
        'created' => $createCount,
        'updated' => $updateCount,
        'linked' => $linkedCount,
        'broken' => $brokenCount
      ];

      /** Обработка английского списка городов - перевод названия */
      Dictionary::livelog('preparing data for translation...');
      $matchInfo=CitiesDictionaryForm::getMatchInfo(CitiesDictionaryForm::GPTS);

      Yii::app()->db->setActive(false);

      $GPTSEngine->reAuthenticate();
      Dictionary::livelog('requesting translations...');
      $cities=$GPTSEngine->runApiCommand('Locations','locations',['limitCities' => -1],true,'en');

      Yii::app()->db->setActive(true);

      $processedCount=0;
      $translatedCount=0;

      /** Функция обработки элемента коллекции */
      $listener = new CitiesListener(function ($item) use (&$matchInfo,&$processedCount,&$translatedCount) {
        $city=new CitiesDictionaryForm();
        $processedCount++;

        if (($processedCount%1000) == 0) {
          Dictionary::livelog('parsed '.$processedCount.' cities');
        }

        if (!empty($item['id']) && !empty($item['name'])) {
          $gptsidkey=(string)$item['id'];

          if(isset($matchInfo[$gptsidkey]) && !$matchInfo[$gptsidkey]['manualEdit']) {

            $city->setAttributes([
              'cityId' => $matchInfo[$gptsidkey]['cityId'],
              'engName' => $item['name']
            ]);

            try {
              $city->updateTranslation();
              $translatedCount++;
            } catch (KmpException $ke) {
              LogHelper::logExt(
                  $ke->class,
                  $ke->method,
                  $this->module->getCxtName($ke->class, $ke->method),
                  $this->module->getError($ke->getCode()) . ': ' . $ke->params['error'],
                  $item,
                  LogHelper::MESSAGE_TYPE_ERROR,
                  $this->namespace. '.errors'
              );
            }

          } else {
            LogHelper::logExt(
                'Dictionary','updateDictionary','',
                $this->module->getError(DictionaryErrors::NO_CITY_MATCH),
                ['err'=>'id for translation not found','item' => $item],
                LogHelper::MESSAGE_TYPE_WARNING,
                $this->namespace. '.errors'
            );
          }

        } else {
          LogHelper::logExt(
              'Dictionary','updateDictionary','',
              $this->module->getError(DictionaryErrors::BROKEN_ITEM),
              ['err'=>'broken translation','item' => $item],
              LogHelper::MESSAGE_TYPE_WARNING,
              $this->namespace. '.errors'
          );
        }
      });

      Dictionary::livelog('start parsing...');
      $parser = new JsonStreamingParser\Parser($cities,$listener);
      $parser->parse();

      Dictionary::livelog('translation parsing finished'.
      ', processed:'.$processedCount.
      ', translated:'.$translatedCount);
      fclose($cities);

    } catch (KmpException $ke) {
      Dictionary::livelog('parsing failed'.
        ', items:'.$itemCount.
        ', created:'.$createCount.
        ', updated:'.$updateCount.
        ', linked:'.$linkedCount.
        ', broken:'.$brokenCount);

      fclose($cities);

      $this->resultMsg=[
        'items' => $itemCount,
        'created' => $createCount,
        'updated' => $updateCount,
        'linked' => $linkedCount,
        'broken' => $brokenCount
      ];

      throw $ke;

    } catch (Exception $e) {
      Dictionary::livelog('parsing failed'.
        ', items:'.$itemCount.
        ', created:'.$createCount.
        ', updated:'.$updateCount.
        ', linked:'.$linkedCount.
        ', broken:'.$brokenCount);

      fclose($cities);

      $this->resultMsg=[
        'items' => $itemCount,
        'created' => $createCount,
        'updated' => $updateCount,
        'linked' => $linkedCount,
        'broken' => $brokenCount
      ];

      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::PARSING_ERROR,
          array_merge($this->resultMsg,['error'=>$e->getMessage()])
      );
    }
  }

  /**
  * Обновление справочника городов по данным УТК
  * @param int $updateType тип обновления (полный/частичный)
  */
  private function updateDictionaryFromUTK($updateType) {
    Dictionary::livelog('starting update cities from utk...');
    $processedItems = 0;
    $linkedItems = 0;
    $createdItems = 0;

    $matcher = function($utkcity) use (&$processedItems,&$linkedItems,&$createdItems) {
      $processedItems++;

      if (($processedItems%1000) == 0) {
        Dictionary::livelog('parsed '.$processedItems.' cities');
      }

      if (!CitiesDictionaryForm::findUtkMatch($utkcity)) {
        $cityId = CitiesDictionaryForm::tryToMatchUTK($utkcity);

        $city = new CitiesDictionaryForm();
        $city->setAttributes([
          'cityId' => $cityId,
          'name' => $utkcity['name'],
          'engName' => !empty($utkcity['engName']) ? $utkcity['engName'] : null,
          'countryId' => $utkcity['countryId'],
          'supplierCityId' => $utkcity['cityId'],
          'supplierCountryId' => $utkcity['countryCode'],
          'parentType' => null,
          'iata' => null,
          'active' => 1,
          'manualEdit' => 0,
          'lastUpdate' => date('Y-m-d H:i:s')
        ]);

        if ($cityId === false) {
          $city->create();
          $createdItems++;
        } else {
          $linkedItems++;
        }

        $city->createMatch(CitiesDictionaryForm::UTK);
        unset($city);
      }
    };

    CitiesDictionaryForm::processUTKCities($matcher);
    Dictionary::livelog('update finished'.
      ', processed: '.$processedItems.
      ', linked: '.$linkedItems.
      ', not matched: '.$createdItems);
  }

  /**
  * Обновление справочника локаций
  */
  private function regenerateLocationSuggest() {
    Dictionary::livelog('regenerating flight locations...');
    Yii::app()->db->createCommand('call update_fl_locations()')->execute();
  }
}
