<?php

class CountriesDictionaryForm extends KFormModel {
  //const UTK = 4;
  const GPTS = 5;
  //const KT = 6;

  /** @var int ID страны (KT) */
  public $countryId;
  /** @var int ID страны (GPTS) */
  public $countryIdGPTS;
  /** @var string название страны (краткое) */
  public $name;
  /** @var string название страны (краткое, английское) */
  public $engName;
  /** @var string двухбуквенный ISO код страны */
  public $iso2Code;
  /** @var string трехбуквенный ISO код страны */
  public $iso3Code;
  /** @var int татус записи (1 - действующий, 0 - неактивный) */
  public $active;
  /** @var int тип обновления записи (0 - авто, 1 - вручную ) */
  public $manualEdit;
  /** @var string TIMESTAMP обновления/создания записи */
  public $lastUpdate;

  public function rules() {
      return array(
          array('countryId, countryIdGPTS, name, engName, iso2Code, iso3Code,
          active, manualEdit, lastUpdate', 'safe'),
      );
  }

  /**
  * Получить массив id стран указанного поставщика
  * @param int $supplier код поставщика
  * @return int[] массив id
  */
  public static function getSupplierIds($supplier) {
    if (empty($supplier)) {
      return false;
    }

    try {
        $command = Yii::app()->db->createCommand()
            ->select('SupplierCountryID')
            ->from('kt_ref_countries_match')
            ->where('SupplierID = :supplier',[':supplier' => $supplier]);

        $res = $command->queryColumn();

    } catch (Exception $e) {
        throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::DATA_FETCH_FAILED,
          ['supplier'=>$supplier]
        );
        return false;
    }

    if (empty($res)) {
      return false;
    } else {
      return $res;
    }
  }

  /**
  * Найти id страны по id поставщика
  * @param int $supplier код поставщика
  * @param int|string $supplierId id страны (по версии поставщика)
  */
  public static function getCountryIdBySupplierId($supplier,$supplierId) {
    if (empty($supplier) || empty($supplierId)) {
      return false;
    }

    try {
        $command = Yii::app()->db->createCommand()
            ->select('CountryID')
            ->from('kt_ref_countries_match')
            ->where('SupplierID = :supplier and SupplierCountryID = :supplierId',
            [
              ':supplier' => $supplier,
              ':supplierId' => $supplierId,
            ]
          );

        return $command->queryScalar();

    } catch (Exception $e) {
        throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::DATA_FETCH_FAILED,
          ['supplier'=>$supplier]
        );
        return false;
    }
  }

  /**
  * Создание записи о матчинге ID стран
  * @param int $supplier код поставщика
  */
  public function createMatch($supplier) {
    if (empty($supplier)) {
      return false;
    }

    $command = Yii::app()->db->createCommand();

    $params=[
      'CountryID' => $this->countryId,
      'SupplierCountryName' => $this->name,
      'SupplierCountryCode' => $this->iso2Code,
      'active' => $this->active,
      'manualEdit' => $this->manualEdit,
      'lastUpdate' => $this->lastUpdate
    ];

    try {
      switch ($supplier) {
        case self::GPTS:
          $params['SupplierID']=self::GPTS;
          $params['SupplierCountryID']=$this->countryIdGPTS;

          $command->insert('kt_ref_countries_match',$params);

          break;
        default:
          return false;
        break;
      }
    } catch (Exception $e) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::COUNTRY_MATCH_FAILED,
          []
      );
    }
  }

  /**
  * Создание записи страны
  */
  public function create() {
    $command = Yii::app()->db->createCommand();

    $map=[
      'Name' => 'name',
      'EngName' => 'engName',
      'CountryCode' => 'iso2Code',
      'Alpha3' => 'iso3Code',
      'active' => 'active',
      'manualEdit' => 'manualEdit',
      'lastUpdate' => 'lastUpdate',
    ];

    $fields=[];

    foreach ($map as $k => $v) {
      if (!is_null($this->$v)) {
        $fields[$k] = $this->$v;
      }
    }

    try {
      $command->insert('kt_ref_countries', $fields);
    } catch (Exception $e) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::COUNTRY_CREATION_FAILED,
          []
      );
    }

    $this->countryId=Yii::app()->db->lastInsertID;
  }

  /**
  * Обновление записи страны
  */
  public function update() {
    $command = Yii::app()->db->createCommand();

    $map=[
      'Name' => 'name',
      'EngName' => 'engName',
      'CountryCode' => 'iso2Code',
      'Alpha3' => 'iso3Code',
      'active' => 'active',
      'manualEdit' => 'manualEdit',
      'lastUpdate' => 'lastUpdate',
    ];

    $fields=[];

    foreach ($map as $k => $v) {
      if (!is_null($this->$v)) {
        $fields[$k] = $this->$v;
      }
    }

    try {
      $command->update('kt_ref_countries', $fields, 'CountryID = :countryId',[':countryId' => $this->countryId]);
    } catch (Exception $e) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::COUNTRY_CREATION_FAILED,
          []
      );
    }
  }

}
