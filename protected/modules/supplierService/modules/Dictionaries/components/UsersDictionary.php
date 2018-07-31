<?php

class UsersDictionary {
  const UTK_ENGINE = 4;
  const GPTS_ENGINE = 5;
  const KT_ENGINE = 6;

  private $engines = [
    self::UTK_ENGINE => 'UTKEngine',
    self::GPTS_ENGINE => 'GPTSEngine',
    self::KT_ENGINE => 'KTEngine'
  ];

  /** @var array параметры результата выполнения команд для считывания извне */
  public $resultMsg = [];

  private $module;
  private $namespace;
  private $config;
  private $companiesConfig;

  public function __construct(&$module) {
    $this->module = $module;
    $this->namespace = $this->module->getConfig('log_namespace');
    $this->config = $this->module->getConfig('users_dictionary_config'); 
    $this->companiesConfig = $this->module->getConfig('companies_dictionary_config');
  }

  public function updateDictionary($engineType,$updateType) {
    $eg = (int)$engineType;

    if (isset($this->engines[$eg])) {
      switch ($eg) {
        case self::GPTS_ENGINE:
          $this->updateDictionaryFromGPTS($updateType);
          Dictionary::livelog('regenerating responsible managers...');
          Yii::app()->db->createCommand('call updateResponsibleManager(:kmpManagerId)')->execute([
            ':kmpManagerId' => $this->companiesConfig['kmp_responsible_manager_id']
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

  private function updateDictionaryFromGPTS($updateType) {
    $GPTSEngine = Yii::app()->getModule('supplierService')->getModule($this->engines[self::GPTS_ENGINE])->getEngine();

    Dictionary::livelog('getting data from gpts...');
    $users = $GPTSEngine->runApiCommand('Persons', 'getPersons', [], true);

    $updateCount = 0;
    $createCount = 0;
    $failCount = 0;

    try {
      /**
      * функция получения имени/фамилии/отчества
      * @param array $item элемент коллекции пользователей
      * @param string $namepart часть имени (firstName|lastName|...)
      * @param string $lang предпочтительный язык
      */
      $processName = function(&$item, $namepart, $lang) {
        $locnamepart = $namepart.'Localized';

        if (!empty($item[$locnamepart]) && is_array($item[$locnamepart]) && count($item[$locnamepart]) > 0) {
          if (!empty($item[$locnamepart]['ru'])) {
            return $item[$locnamepart]['ru'];
          } elseif ($lang !== 'ru' && !empty($item[$locnamepart][$lang])) {
            return $item[$locnamepart][$lang];
          } else {
            return reset($item[$locnamepart]);
          }
        } else {
          if (!empty($item[$namepart])) {
            return $item[$namepart];
          } else {
            return null;
          }
        }
      };

      /**
      * Обработка префикса из GPTS
      * @param string $prefix - префикс из GPTS
      */
      $processPrefix = function($prefix) {
        switch ((string)$prefix) {
          case 'Mr':
            return 1;
          case 'Mrs':
            return 0;
          default:
            return null;
        }
      };

      $userRolesMap = $this->config['gp_user_roles_mapping'];

      /** Функция обработки элемента коллекции */
      $listener = new PersonsListener(function ($item) use
        ($userRolesMap, &$updateCount, &$createCount, &$failCount, &$processName, &$processPrefix) {
            
          $Account = AccountRepository::getByGPTSId((int)$item['personId']);
          $isNew = false;

          if (is_null($Account)) {
            $Account = new Account();
            $Account->UserID_GP = (int)$item['personId'];
            $isNew = true;
          } else {
            if ($Account->manualEdit) {
              return;
            }
          }

          $lang = isset($item['lang']) ? $item['lang'] : 'ru';

          $Citizenship = !isset($item['countryId']) ? null :
            CountryRepository::getBySupplierId($item['countryId'], CountryRepository::GPTS);

          $Company = !isset($item['companyId']) ? null :
            CompanyRepository::getByGPTSId($item['companyId']);

          if (is_null($Company)) {
            LogHelper::logExt(
                'Dictionary','updateDictionary','',
                $this->module->getError(DictionaryErrors::AGENCY_NOT_SET),
                ['company_id' => $item['companyId']],
                LogHelper::MESSAGE_TYPE_WARNING,
                $this->namespace. '.errors'
            );
            $failCount++;
            return;
          }

          $Account->fromArray([
            'firstName' => $processName($item, 'firstName', $lang),
            'middleName' => $processName($item, 'middleName', $lang),
            'lastName' => $processName($item, 'lastName', $lang),
            'birthdate' => isset($item['birthdate']) ? $item['birthdate'] : null,
            'сontactPhone' => isset($item['contactPhone']) ? $item['contactPhone'] : null,
            'email' => isset($item['email']) ? $item['email'] : null,
            'prefix' => isset($item['prefix']) ? $processPrefix($item['prefix']) : null
          ]);

          $Account->active = (!isset($item['active']) || $item['active'] == true) ? 1 : 0;
          $Account->RoleType = $Company->Type;
          $Account->manualEdit = 0;
          $Account->lastUpdate = date('Y-m-d H:i:s');
          $Account->AgentID = $Company->AgentID;
          $Account->CitizenshipID = is_null($Citizenship) ? null : $Citizenship->CountryID;
          $Account->icq = isset($item['icq']) ? $item['icq'] : null;
          $Account->skype = isset($item['skype']) ? $item['skype'] : null;
          $Account->Login = isset($item['login']) ? $item['login'] : null;
          $Account->comments = isset($item['comments']) ? $item['comments'] : null;
          $Account->RoleID = (isset($item['role']) && isset($userRolesMap[$item['role']])) ?
            $userRolesMap[$item['role']] : null;

          try {
            $Account->save();

            if ($isNew) {
              $createCount++;
            } else {
              $updateCount++;
            }
          } catch (Exception $e) {
            LogHelper::logExt(
                __CLASS__, __FUNCTION__, '',
                $this->module->getError(($isNew ? 
                  DictionaryErrors::USER_INSERTION_FAILED : 
                  DictionaryErrors::USER_UPDATE_FAILED
                )),
                $item,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace. '.errors'
            );
            return;
          }

          if (!empty($item['passports'])) {
            $Documents = $Account->UserDocuments;
            $existingDocuments = [];

            foreach($Documents as $Document) {
              $existingDocuments[] = (string)$Document->getDocNumber();
            }

            foreach($item['passports'] as $passport) {
              if (!in_array((string)$passport['number'], $existingDocuments)) {
                $Document = new UserDocument();
                $Document->fromArray([
                  'firstName' => $Account->getName(),
                  'lastName' => $Account->getSurname(),
                  'middleName' => $Account->getSndName(),
                  'serialNumber' => null,
                  'number' => $passport['number'],
                  'citizenship' => is_null($Citizenship) ? null : $Citizenship->CountryCode,
                  'documentType' => 18, //OTHER_DOCUMENT, другой документ, см DocumentType
                  'expiryDate' => isset($passport['expiryDate']) ? $passport['expiryDate'] : null
                ]);
                $Document->bindUser($Account);
                $Document->save();
              }
            }            
          }
      });

      Dictionary::livelog('start parsing...');

      $parser = new JsonStreamingParser\Parser($users, $listener);
      $parser->parse();
      fclose($users);

      Dictionary::livelog('parsing finished, ' .
        'created:'.$createCount . ', updated:' . $updateCount . ', broken:' . $failCount);

      $this->resultMsg = [
        'created' => $createCount,
        'updated' => $updateCount,
        'broken' => $failCount
      ];

    } catch (KmpException $ke) {
      fclose($users);

      Dictionary::livelog('parsing failed, ' .
        'created:' . $createCount . ', updated:' . $updateCount . ', broken:' . $failCount);

      $this->resultMsg = [
        'created' => $createCount,
        'updated' => $updateCount,
        'broken' => $failCount
      ];

      throw $ke;

    } catch (Exception $e) {
      fclose($users);

      Dictionary::livelog('parsing failed, ' .
        'created:' . $createCount . ', updated:' . $updateCount. ', broken:' . $failCount);

      $this->resultMsg = [
        'created' => $createCount,
        'updated' => $updateCount,
        'broken' => $failCount
      ];

      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::PARSING_ERROR,
          array_merge($this->resultMsg,['error' => $e->getMessage()])
      );
    }
  }
}
