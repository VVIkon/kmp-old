<?php

class CompaniesDictionaryForm {

  /** @var int ID компании (KT) */
  public $companyId;
  /** @var int ID компании (GPTS) */
  public $companyIdGPTS;
  /** @var string ID компании (УТК) */
  public $companyIdUTK;
  /** @var string код компании (GPTS) */
  public $GPTScompanyCode;
  /** @var string Алиас (кодовое наименование) компании */
  public $alias;
  /** @var int статус записи (1 - действующий, 0 - неактивный) */
  public $active;
  /** @var int тип обновления записи (0 - авто, 1 - вручную ) */
  public $manualEdit;
  /** @var string TIMESTAMP обновления/создания записи */
  public $lastUpdate;
  /** @var string название компании */
  public $companyName;
  /** @var int тип компании (TO,TA,...) */
  public $companyType;
  /** @var int Id страны компании по справочнику KT */
  public $countryId;
  /** @var int Id города компании по справочнику KT */
  public $cityId;
  /** @var string префикс обращения к контактному лицу (Mr,Ms,...) */
  public $prefix;
  /** @var string имя контактного лица */
  public $firstName;
  /** @var string отчество контактного лица */
  public $middleName;
  /** @var string фамилия контактного лица */
  public $lastName;
  /** @var string телефон контактного лица */
  public $phone;
  /** @var string email контактного лица */
  public $email;
  /** @var string url сайта компании */
  public $url;
  /** @var string официальное наименование компании */
  public $officialCompanyName;
  /** @var string ИНН компании */
  public $inn;

  public function setAttributes(array $params) {
    foreach($params as $prop => $value) {
      $this->$prop = $value;
    }
  }

  /**
  * Метод получения ID всех компаний
  * @param string $suppliercode - код поставщика, чьи ID нужны
  * @return mixed[] массив вида ID записи => (bool) статус редактирования (manualEdit)
  */
  public static function getAllIds($suppliercode) {
    switch ($suppliercode) {
      case 'gpts':
          $field = 'AgentID_GP';
        break;
      case 'kt':
          $field = 'AgentID';
        break;
      case 'utk':
          $field = 'AgentID_UTK';
        break;
      default:
        throw new KmpException(
            get_class(),__FUNCTION__,
            DictionaryErrors::UNKNOWN_SUPPLIER_CODE,
            ['supplier_code' => $suppliercode]
        );
        break;
    }

    $command = Yii::app()->db->createCommand()
      ->select($field.', manualEdit')
      ->from('kt_companies')
      ->where($field.' is not null and '.$field.' != 0');

    $result = $command->query();
    $response = [];

    foreach ($result as $row) {
      $response[$row[$field]] = (bool)$row['manualEdit'];
    }

    $result->close();

    return $response;
  }

  /**
  * Метод получения ID нужного поставщика и типов всех компаний
  * @param string $suppliercode - код поставщика, чьи ID нужны
  * @return mixed[] массив вида ID записи => тип компании
  * @todo надо придумать что-нибудь получше...
  */
  public static function getCompaniesIdsWithTypes($suppliercode) {
    switch ($suppliercode) {
      case 'gpts':
          $field = 'AgentID_GP';
        break;
      case 'kt':
          $field = 'AgentID';
        break;
      case 'utk':
          $field = 'AgentID_UTK';
        break;
      default:
        throw new KmpException(
            get_class(),__FUNCTION__,
            DictionaryErrors::UNKNOWN_SUPPLIER_CODE,
            ['supplier_code' => $suppliercode]
        );
        break;
    }

    $command = Yii::app()->db->createCommand()
      ->select($field.', Type')
      ->from('kt_companies')
      ->where($field.' is not null and '.$field.' != 0');

    $result = $command->query();
    $response = [];

    foreach ($result as $row) {
      $response[$row[$field]] = (int)$row['Type'];
    }

    $result->close();

    return $response;
  }

  public function create() {
    $command = Yii::app()->db->createCommand();

    /* поле в БД => свойство класса */
    $map = [
      'AgentID_GP' => 'companyIdGPTS',
      'AgentID_UTK' => 'companyIdUTK',
      'Agent_GP_Alias' => 'alias',
      'GPTScompanyCode' => 'GPTScompanyCode',
      'active' => 'active',
      'manualEdit' => 'manualEdit',
      'lastUpdate' => 'lastUpdate',
      'Name' => 'companyName',
      'Type' => 'companyType',
      'CountryID' => 'countryId',
      'CityID' => 'cityId',
      'Prefix' => 'prefix',
      'FirstName' => 'firstName',
      'Middlename' => 'middleName',
      'Lastname' => 'lastName',
      'Phone' => 'phone',
      'email' => 'email',
      'url' => 'url',
      'OfficialCompanyName' => 'officialCompanyName',
      'INN' => 'inn'
    ];

    $fields = [];

    foreach ($map as $k => $v) {
      if (!is_null($this->$v)) {
        $fields[$k] = $this->$v;
      }
    }

    try {
      $command->insert('kt_companies', $fields);
    } catch (Exception $e) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::COMPANY_INSERTION_FAILED,
          []
      );
    }

    $this->companyId = Yii::app()->db->lastInsertID;
  }

  /**
  * Обновление записи компании
  * @param string $byid по какому id обновлять запись
  */
  public function update($byid = 'kt') {
    $command = Yii::app()->db->createCommand();

    /* поле в БД => свойство класса */
    $map = [
      'AgentID_GP' => 'companyIdGPTS',
      'AgentID_UTK' => 'companyIdUTK',
      'Agent_GP_Alias' => 'alias',
      'GPTScompanyCode' => 'GPTScompanyCode',
      'active' => 'active',
      'manualEdit' => 'manualEdit',
      'lastUpdate' => 'lastUpdate',
      'Name' => 'companyName',
      'Type' => 'companyType',
      'CountryID' => 'countryId',
      'CityID' => 'cityId',
      'Prefix' => 'prefix',
      'FirstName' => 'firstName',
      'Middlename' => 'middleName',
      'Lastname' => 'lastName',
      'Phone' => 'phone',
      'email' => 'email',
      'url' => 'url',
      'OfficialCompanyName' => 'officialCompanyName',
      'INN' => 'inn'
    ];

    $fields = [];

    foreach ($map as $k => $v) {
      if (!is_null($this->$v)) {
        $fields[$k] = $this->$v;
      }
    }

    $dbIdField;
    $classIdField;

    switch ($byid) {
      case 'kt':
        $dbIdField = 'AgentID';
        $classIdField = 'companyId';
      break;
      case 'gpts':
        $dbIdField = 'AgentID_GP';
        $classIdField = 'companyIdGPTS';
      break;
      case 'utk':
        $dbIdField = 'AgentID_UTK';
        $classIdField = 'companyIdUTK';
      break;
    }

    if (empty($classIdField) || empty($this->$classIdField)) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::COMPANY_ID_NOT_SET,
          ['id_field' => $classIdField]
      );
    }

    try {
      $command->update(
        'kt_companies',
        $fields,
        $dbIdField.' = :'.$classIdField,
        [':'.$classIdField => $this->$classIdField]
      );
    } catch (Exception $e) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::COMPANY_UPDATE_FAILED,
          [
            'error' => $e->getMessage(),
            'fields' => $fields
          ]
      );
    }
  }

}
