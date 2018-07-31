<?php

class CompaniesDictionary {
  const UTK_ENGINE = 4;
  const GPTS_ENGINE = 5;
  const KT_ENGINE = 6;

  private $engines = [
    self::UTK_ENGINE => 'UTKEngine',
    self::GPTS_ENGINE => 'GPTSEngine',
    self::KT_ENGINE => 'KTEngine'
  ];

  /*
  * Соответствие типов компаний из GPTS типам KT
  */
  private $companyTypes = [
    'TOUR_OPERATOR_1LEVEL' => 1,
    'TOUR_AGENCY' => 2,
    'CORPORATOR' => 3,
  ];

  /** @var array параметры результата выполнения команд для считывания извне */
  public $resultMsg = [];

  private $module;
  private $namespace;
  private $config;

  public function __construct(&$module) {
    $this->module = $module;
    $this->namespace = $this->module->getConfig('log_namespace');
    $this->config = $this->module->getConfig('companies_dictionary_config');
  }

  public function updateDictionary($engineType,$updateType) {
    $eg = (int)$engineType;

    if (isset($this->engines[$eg])) {
      switch ($eg) {
        case self::GPTS_ENGINE:
          $this->updateDictionaryFromGPTS($updateType);
          break;
        case self::UTK_ENGINE:
          $this->updateDictionaryFromUTK($updateType);
          Dictionary::livelog('regenerating responsible managers...');
          Yii::app()->db->createCommand('call updateResponsibleManager(:kmpManagerId)')->execute([
            ':kmpManagerId' => $this->config['kmp_responsible_manager_id']
          ]);
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

  /**
  * Обновление справочника компаний из GPTS
  * @param int $updateType Тип обновления (0 - полный, 1 - частичный)
  */
  private function updateDictionaryFromGPTS($updateType) {
    $GPTSEngine = Yii::app()->getModule('supplierService')->getModule($this->engines[self::GPTS_ENGINE])->getEngine();

    Dictionary::livelog('getting data from gpts...');
    $companies=$GPTSEngine->runApiCommand('Companies','getCompanies',[],true,'ru');

    $updateCount = 0;
    $createCount = 0;

    try {
      $presentIds = CompaniesDictionaryForm::getAllIds('gpts');
      $company = new CompaniesDictionaryForm();

      /** Функция обработки элемента коллекции */
      $listener = new CompaniesListener(function ($item) use (&$presentIds,&$company,&$updateCount,&$createCount) {
          if (isset($item['type']) && isset($this->companyTypes[$item['type']]) ) {
            $present = isset($presentIds[$item['id']]);
            $nameparts = preg_split('/\s+/',$item['contactName']);
            $prefix = array_shift($nameparts);
            $firstName = array_shift($nameparts);
            $lastName = implode(' ',$nameparts);

            $cityId = empty($item['cityId']) ? null
              : CitiesMapperHelper::getCityIdBySupplierCityId(CitiesMapperHelper::GPTS_SUPPLIER_ID,$item['cityId']);
            $countryId = empty($item['countryId']) ? null
              : CountriesMapperHelper::getCountryIdBySupplierId(CountriesMapperHelper::GPTS_SUPPLIER_ID,$item['countryId']);

            if ($cityId === false) {
              $cityId = null;

              LogHelper::logExt(
                  'Dictionary','updateDictionary','',
                  $this->module->getError(DictionaryErrors::NO_CITY_MATCH),
                  ['gpts_city_id' => empty($item['cityId']) ? null : $item['cityId']],
                  LogHelper::MESSAGE_TYPE_WARNING,
                  $this->namespace. '.errors'
              );
            } else { $cityId=(int)$cityId; }

            if ($countryId === false) {
              $countryId = null;

              LogHelper::logExt(
                  'Dictionary','updateDictionary','',
                  $this->module->getError(DictionaryErrors::NO_COUNTRY_MATCH),
                  ['gpts_country_id' => empty($item['countryId']) ? null : $item['countryId']],
                  LogHelper::MESSAGE_TYPE_WARNING,
                  $this->namespace. '.errors'
              );
            } else { $countryId=(int)$countryId; }

            $params = [
              'companyIdGPTS' => (int)$item['id'],
              'companyIdUTK' => null,
              'alias' => isset($item['alias']) ? $item['alias'] : null,
              'active' => (!isset($item['active']) || $item['active'] == true) ? 1 : 0,
              'manualEdit' => 0,
              'lastUpdate' => date('Y-m-d H:i:s'),
              'companyName' => $item['name'],
              'companyType' => $this->companyTypes[$item['type']],
              'GPTScompanyCode' => isset($item['companyCode']) ? $item['companyCode'] : null,
              'countryId' => (!is_null($countryId)) ? (int)$countryId : null,
              'cityId' => (!is_null($cityId)) ? (int)$cityId : null,
              'prefix' => $prefix,
              'firstName' => $firstName,
              'middleName' => null,
              'lastName' => $lastName,
              'phone' => (isset($item['phones']) && is_array($item['phones'])) ? $item['phones'][0] : null,
              'email' => $item['email'],
              'url' => isset($item['url']) ? $item['url'] : null,
              'officialCompanyName' => isset($item['officialCompanyName']) ? $item['officialCompanyName'] : null,
              'inn' => isset($item['vatNumber']) ? $item['vatNumber'] : null
            ];

            $company->setAttributes($params);

            if ($present) {
              if (!$presentIds[$item['id']]) {
                $company->update('gpts');
                $updateCount++;
                unset($presentIds[$item['id']]);
              }
            } else {
              $company->create();
              $createCount++;
            }
          }
      });

      Dictionary::livelog('start parsing...');
      $parser = new JsonStreamingParser\Parser($companies, $listener);
      $parser->parse();

      Dictionary::livelog('parsing finished, created:'.$createCount.', updated:'.$updateCount);
      fclose($companies);

      $this->resultMsg = [
      'created' => $createCount,
      'updated' => $updateCount
      ];

    } catch (KmpException $ke) {
      fclose($companies);

      Dictionary::livelog('parsing failed, created:'.$createCount.', updated:'.$updateCount);

      $this->resultMsg = [
      'created' => $createCount,
      'updated' => $updateCount
      ];

      throw $ke;

    } catch (Exception $e) {
      fclose($companies);

      Dictionary::livelog('parsing failed, created:'.$createCount.', updated:'.$updateCount);

      $this->resultMsg = [
      'created' => $createCount,
      'updated' => $updateCount
      ];

      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::PARSING_ERROR,
          array_merge($this->resultMsg,['error'=>$e->getMessage()])
      );
    }
  }

  /**
  * Обновление справочника компаний + пользователей + договоров из УТК (обертка для getClientsList )
  * @param int $updateType Тип обновления (0 - полный, 1 - частичный)
  */
  private function updateDictionaryFromUTK($updateType) {
    Dictionary::livelog('updating dictionary from utk...');

    $params = [
      'pass' => $this->config['utk_api_password']
    ];

    if ($updateType == 0) {
      $params['operation'] = 'all';
    } elseif ($updateType == 1) {
      $params['operation'] = 'changed';
    }

    $supplierModule = Yii::app()->getModule('supplierService');
    $apiClient = new ApiClient($supplierModule);
    $response = $apiClient->makeRestRequest('orderService','GetClientsList',$params);

    if ($response !== false) {
      $response = json_decode($response,true);

      if ($response['status'] == 0) {
        Dictionary::livelog('update successfull');
      } else {
        Dictionary::livelog('update failed, error code: '.$response['errorCode']);
      }

    } else {
      Dictionary::livelog('update failed, no error code, see log');
    }
  }

}
