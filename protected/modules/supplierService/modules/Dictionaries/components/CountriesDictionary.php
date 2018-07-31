<?php

class CountriesDictionary {
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
  }

  private function updateDictionaryFromGPTS($updateType) {
    $GPTSEngine=Yii::app()->getModule('supplierService')->getModule($this->engines[self::GPTS_ENGINE])->getEngine();

    Dictionary::livelog('getting data from gpts...');
    $countries=$GPTSEngine->runApiCommand('Locations','locations',['limitCountries' => -1],true,'ru');

    $created=[];

    $itemCount=0;
    $createCount=0;
    $brokenCount=0;
    $linkedCount=0;

    try {
      $presentIds=CountriesDictionaryForm::getSupplierIds(CountriesDictionaryForm::GPTS);
      $country=new CountriesDictionaryForm();

      /** Функция обработки элемента коллекции */
      $listener = new CountriesListener(function ($item) use
        (&$presentIds,&$country,&$created,&$itemCount,&$createCount,&$brokenCount,&$linkedCount) {
        $itemCount++;

        if (
          empty($item['id']) ||
          empty($item['iso2Code']) ||
          empty($item['name'])
        ) {
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
        $idx=array_search($gptsId,$presentIds);

        if ($idx!==false) {
          array_splice($presentIds,$idx,1);
        } else {

          $params=[
            'countryId' => null,
            'countryIdGPTS' => $item['id'],
            'name' => $item['name'],
            'engName' => null,
            'iso2Code' => $item['iso2Code'],
            'iso3Code' => !empty($item['iso3Code']) ? $item['iso3Code'] : null,
            'active' => 1,
            'manualEdit' => 0,
            'lastUpdate' => date('Y-m-d H:i:s')
          ];

          $country->setAttributes($params);

          $countryId=CountriesMapperHelper::getIdByCode($item['iso2Code']);

          if ($countryId!==false) {
            $country->setAttributes(['countryId' => $countryId]);
            $country->createMatch(CountriesDictionaryForm::GPTS);
            $linkedCount++;
          } else {
            $country->create();
            $country->createMatch(CountriesDictionaryForm::GPTS);
            $created[]=[$country->countryIdGPTS => $country->countryId];
            $createCount++;
          }
        }
      });

      Dictionary::livelog('start parsing...');

      $parser = new JsonStreamingParser\Parser($countries,$listener);

      $parser->parse();

      Dictionary::livelog('parsing finished'.
        ', items:'.$itemCount.
        ', created:'.$createCount.
        ', linked:'.$linkedCount.
        ', broken:'.$brokenCount);
      fclose($countries);

      $this->resultMsg=[
        'items' => $itemCount,
        'created' => $createCount,
        'linked' => $linkedCount,
        'broken' => $brokenCount
      ];

      if (count($created)>0) {
        Dictionary::livelog('requesting translations...');
        $countries=$GPTSEngine->runApiCommand('Locations','locations',['limitCountries' => -1],true,'en');

        $country=new CountriesDictionaryForm();

        $listener = new CountriesListener(function ($item) use (&$country,&$created) {
          $gptsId=(int)$item['id'];

          if (isset($created[$gptsId])) {
            $country->setAttributes([
              'CountryID' => $created[$gptsId],
              'EngName' => $item['name']
            ]);

            $country->update();
          }
        });

        Dictionary::livelog('start parsing...');

        $parser = new JsonStreamingParser\Parser($countries,$listener);

        $parser->parse();

        Dictionary::livelog('translation parsing finished');
        fclose($countries);

      } else {
        Dictionary::livelog('no translations needed, finished');
      }

    } catch (KmpException $ke) {
      Dictionary::livelog('parsing failed'.
        ', items:'.$itemCount.
        ', created:'.$createCount.
        ', linked:'.$linkedCount.
        ', broken:'.$brokenCount);

      fclose($countries);

      $this->resultMsg=[
        'items' => $itemCount,
        'created' => $createCount,
        'linked' => $linkedCount,
        'broken' => $brokenCount
      ];

      throw $ke;

    } catch (Exception $e) {
      Dictionary::livelog('parsing failed'.
        ', items:'.$itemCount.
        ', created:'.$createCount.
        ', linked:'.$linkedCount.
        ', broken:'.$brokenCount);

      fclose($countries);

      $this->resultMsg=[
        'items' => $itemCount,
        'created' => $createCount,
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

}
