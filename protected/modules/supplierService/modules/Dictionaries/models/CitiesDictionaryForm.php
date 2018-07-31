<?php

class CitiesDictionaryForm extends KFormModel {
  const UTK = 4;
  const GPTS = 5;
  //const KT = 6;

  /* Статусы записей городов */
  const STATUS_INACTIVE = 0;
  const STATUS_ACTIVE = 1;
  const STATUS_SUSPICIOUS = 2;

  /* Тип редактирования записи */
  const AUTO_EDIT = 0;
  const MANUAL_EDIT = 1;

  /** усредненный радиус Земли в км для формулы гаверсинусов */
  const EARTH_RADIUS = 6371;

  /** @var int ID города (KT) */
  public $cityId;
  /** @var int|string ID города (версия поставщика) */
  public $supplierCityId;
  /** @var int ID страны (KT) */
  public $countryId;
  /** @var int|string ID страны (версия поставщика) */
  public $supplierCountryId;
  /** @var string тип родителя по геодереву (город или страна) */
  public $parentType;
  /** @var string название города (краткое) */
  public $name;
  /** @var string название города (краткое, английское) */
  public $engName;
  /** @var string IATA код города */
  public $iata;
  /** @var float широта (координаты) города */
  public $latitude;
  /** @var float долгота (координаты) города */
  public $longitude;
  /** @var int статус записи (1 - действующий, 0 - неактивный) */
  public $active;
  /** @var int тип обновления записи (0 - авто, 1 - вручную ) */
  public $manualEdit;
  /** @var string TIMESTAMP обновления/создания записи */
  public $lastUpdate;

  public function rules() {
      return array(
          array('cityId, supplierCityId, countryId, supplierCountryId, parentType, name, engName, iata,
          latitude, longitude, active, manualEdit, lastUpdate', 'safe'),
      );
  }

  /**
  * Получение информации о матчинге городов для указанного поставщика
  * @param int $supplier код поставщика
  * @return array массив вида
  * "id поставщика" => ["cityId" => "id города (КТ)", "manualEdit" => "тип матчинга"]
  */
  public static function getMatchInfo($supplier) {
    if (empty($supplier)) {
      return false;
    }

    try {
        $command = Yii::app()->db->createCommand()
            ->select('CityId as cityId, SupplierCityID, manualEdit')
            ->from('kt_ref_cities_match')
            ->where('SupplierID = :supplier',[':supplier' => $supplier]);

        $result = $command->query();

        $response=[];

        foreach ($result as $row) {
          $response[(string)$row['SupplierCityID']]=[
            'cityId' => $row['cityId'],
            'manualEdit' => (bool)$row['manualEdit']
          ];
        }

        $result->close();

    } catch (Exception $e) {
        throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::DATA_FETCH_FAILED,
          ['supplier'=>$supplier,'error' => $e->getMessage()]
        );
        return false;
    }

    return $response;
  }

  /**
  * Отметить города поставщика, сматченные только по названию, как требующие проверки
  * @param $supplierId IDпоставщика, чьи города обрабатываем (см. константы)
  */
  public static function markSuspicious($supplierId) {
    $sql='update kt_ref_cities as c
          join
            (
              select cm.CityID,count(*) as count
              from kt_ref_cities_match as cm
              where cm.SupplierID = '.$supplierId.'
              group by cm.CityID
            ) as mc
            on c.CityID = mc.CityID
          set c.active = '.self::STATUS_SUSPICIOUS.'
          where c.Lat is null and mc.count > 1';

    $command = Yii::app()->db->createCommand($sql);
    $command->execute();
  }

  /**
  * Создание записи о матчинге ID городов
  * @param int $supplier код поставщика
  */
  public function createMatch($supplier) {
    if (empty($supplier)) {
      return false;
    }

    $command = Yii::app()->db->createCommand();

    $params=[
      'SupplierID' => $supplier,
      'CityID' => $this->cityId,
      'SupplierCityName' => $this->name,
      'SupplierCityID' => $this->supplierCityId,
      'SupplierCountryID' => $this->supplierCountryId,
      'parentType' => $this->parentType,
      'IataCityCode' => $this->iata,
      'active' => $this->active,
      'manualEdit' => $this->manualEdit,
      'lastUpdate' => $this->lastUpdate
    ];

    try {
      $command->insert('kt_ref_cities_match',$params);
    } catch (Exception $e) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::CITY_MATCH_FAILED,
          ['error' => $e->getMessage()]
      );
    }
  }

  /**
  * Обновление записи матчинга
  * @param int $supplier код поставщика
  */
  public function updateMatch($supplier) {
    if (empty($supplier) || empty($this->supplierCityId)) {
      return false;
    }

    $command = Yii::app()->db->createCommand();

    $params=[
      'CityID' => $this->cityId,
      'SupplierCityName' => $this->name,
      'SupplierCountryID' => $this->supplierCountryId,
      'parentType' => $this->parentType,
      'IataCityCode' => $this->iata,
      'active' => $this->active,
      'manualEdit' => $this->manualEdit,
      'lastUpdate' => $this->lastUpdate
    ];

    try {
      $command->update(
        'kt_ref_cities_match',$params,
        'SupplierID=:supplierId and SupplierCityID=:supplierCityId and manualEdit=0',
        [
          ':supplierId' => $supplier,
          ':supplierCityId' => $this->supplierCityId
        ]
      );
    } catch (Exception $e) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::MATCH_UPDATE_FAILED,
          ['error' => $e->getMessage()]
      );
    }
  }

  /**
  * Попытка автоматически сматчить город
  * @return bool результат операции
  */
  public function tryToMatch() {
    if (!empty($this->latitude) && !empty($this->longitude)) {
      $firstnamepart=preg_split('/\s+/u',mb_strtolower(trim($this->name),'utf-8'))[0];

      $command = Yii::app()->db->createCommand()
        ->select('CityID as cityId, Lat as latitude, Lon as longitude, Name as name, EngName as engName')
        ->from('kt_ref_cities')
        ->where('CountryID = :countryId and
                Lat is not null and Lon is not null and
                (
                  (name is not null and lower(name) like :name) or
                  (engName is not null and lower(engName) like :engname)
                )',[
          ':countryId' => $this->countryId,
          ':name' => $firstnamepart.'%',
          ':engname' => $firstnamepart.'%'
        ]);

      $matches=$command->queryAll();

      if (count($matches)>0) {
        /**
        * если выборка не пуста, проверяем совпадение имен и
        * расстояние с помощью формулы гаверсинусов,
        * если попали в радиус схождения - считаем за совпадение
        */
        $normalizedName=self::normalizeName($this->name);

        foreach ($matches as $city) {
          if (
            $normalizedName === self::normalizeName($city['name']) ||
            $normalizedName === self::normalizeName($city['engName'])
          ) {
            $lat1 = $city['latitude'];
            $lon1 = $city['longitude'];
            $lat2 = $this->latitude;
            $lon2 = $this->longitude;

            $dist = self::EARTH_RADIUS * 2 * asin(sqrt(
              pow(sin(($lat1-$lat2) * pi()/180 / 2 ),2) +
              cos($lat1 * pi()/180) *
              cos(abs($lat2) * pi()/180) *
              pow(sin(($lon1 - $lon2) * pi()/180 / 2), 2)
            ));

            if ($dist<10) {
              $this->cityId = $city['cityId'];
              return true;
            }
          }
        }

        return false;
      }  else {
        return false;
      }
    } else {
      /* если координат нет, все равно пытаемся сматчить по названию */
      $firstnamepart=preg_split('/\s+/u',mb_strtolower(trim($this->name),'utf-8'))[0];

      $command = Yii::app()->db->createCommand()
        ->select('CityID as cityId, Name as name, EngName as engName')
        ->from('kt_ref_cities')
        ->where('CountryID = :countryId and
                (
                  (name is not null and lower(name) like :name) or
                  (engName is not null and lower(engName) like :engname)
                )',[
          ':countryId' => $this->countryId,
          ':name' => $firstnamepart.'%',
          ':engname' => $firstnamepart.'%'
        ]);

      $matches=$command->queryAll();

      if (count($matches)>0) {
        /**
        * если выборка не пуста, проверяем совпадение имен и
        * расстояние с помощью формулы гаверсинусов,
        * если попали в радиус схождения - считаем за совпадение
        */
        $normalizedName=self::normalizeName($this->name);

        foreach ($matches as $city) {
          if (
            $normalizedName === self::normalizeName($city['name']) ||
            $normalizedName === self::normalizeName($city['engName'])
          ) {
            $this->cityId = $city['cityId'];
            return true;
          }
        }

        return false;
      }  else {
        return false;
      }
    }
  }

  /**
  * Создание записи города
  */
  public function create() {
    $command = Yii::app()->db->createCommand();

    $map=[
      'CountryID' => 'countryId',
      'Name' => 'name',
      'EngName' => 'engName',
      'Lat' => 'latitude',
      'Lon' => 'longitude',
      'active' => 'active',
      'manualEdit' => 'manualEdit',
      'lastUpdate' => 'lastUpdate'
    ];

    $fields=[];

    foreach ($map as $k => $v) {
      if (!is_null($this->$v)) {
        $fields[$k] = $this->$v;
      }
    }

    try {
      $command->insert('kt_ref_cities', $fields);
    } catch (Exception $e) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::CITY_CREATION_FAILED,
          ['error' => $e->getMessage()]
      );
    }

    $this->cityId=Yii::app()->db->getLastInsertId();
  }

  /**
  * Обновление записи города
  */
  public function update() {
    $command = Yii::app()->db->createCommand();

    $map=[
      'CountryID' => 'countryId',
      'Name' => 'name',
      'EngName' => 'engName',
      'Lat' => 'latitude',
      'Lon' => 'longitude',
      'active' => 'active',
      'manualEdit' => 'manualEdit',
      'lastUpdate' => 'lastUpdate'
    ];

    $fields=[];

    foreach ($map as $k => $v) {
      if (!is_null($this->$v)) {
        $fields[$k] = $this->$v;
      }
    }

    try {
      $command->update('kt_ref_cities', $fields, 'CityID = :cityId and manualEdit=0',[':cityId' => (int)$this->cityId]);
    } catch (Exception $e) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::CITY_CREATION_FAILED,
          ['error' => $e->getMessage()]
      );
    }
  }

  /**
  * Обновление перевода названия на английский по ID GPTS
  * @return int число затронутых строк
  */
  public function updateTranslation() {
    $command = Yii::app()->db->createCommand();

    try {
      $command->update('kt_ref_cities',[
        'EngName' => $this->engName
      ],'CityID = :cityId',[':cityId' => $this->cityId]);

    } catch (Exception $e) {
      throw new KmpException(
          get_class(),__FUNCTION__,
          DictionaryErrors::CITY_UPDATE_FAILED,
          ['error' => $e->getMessage()]
      );
    }
  }

  /**
  * Обработка списка городов УТК
  * @param Callable $matcher функция обработки
  */
  public static function processUTKCities(Callable $matcher) {
    /* маппинг полей в необходимую структуру (поле в структуре => поле в бд) */
    $qmap = [
      'cityId' => 'uc.id',
      'countryId' => 'rc.CountryID',
      'countryCode' => 'uc.CountryID',
      'name' => 'uc.Name',
      'engName' => 'uc.EngName'
    ];

    $conn = new CDbConnection(
      Yii::app()->db->connectionString,
      Yii::app()->db->username,
      Yii::app()->db->password
    );

    $command = $conn->createCommand()
      ->select( implode(',',array_map(function($r,$field) {
        return $field.' as '.$r;
      },array_keys($qmap),$qmap)) )
      ->from('utk_cities as uc')
      ->join('kt_ref_countries as rc','uc.CountryID = rc.CountryCode');
    $result = $command->query();

    foreach ($result as $utkcity) {
      try {
        $matcher($utkcity);
      } catch (Exception $e) {
        LogHelper::logExt(
            'Dictionary','updateDictionary:Cities(UTK)','','error processing item',
            $utkcity,
            LogHelper::MESSAGE_TYPE_WARNING,
            'system.supplierservice.errors'
        );

        continue;
      }
    }

    $result->close();
    $conn->setActive(false);
  }

  /**
  * Проверка наличия записи матчинга города УТК
  * @param string $utkcity структура города УТК
  * @return bool результат проверки
  */
  public static function findUtkMatch($utkcity) {
    $command = Yii::app()->db->createCommand()
      ->select('CityID')
      ->from('kt_ref_cities_match')
      ->where('SupplierID = :utkSupplier and SupplierCityName = :name',
        [
          ':utkSupplier' => self::UTK,
          ':name' => $utkcity['name']
        ]
      );

    return ($command->queryScalar() === false ? false : true);
  }

  /**
  * Попытка сматчить город УТК с городом КТ
  * @param string $utkcity структура города УТК
  */
  public static function tryToMatchUTK($utkcity) {
    $firstnameparts = [];

    $firstnameparts['ru'] = preg_replace('/[^\w]/u', '',
      preg_split('/\s+/u', mb_strtolower(trim((string)$utkcity['name']),'utf-8'))[0]
    );
    $firstnameparts['en'] = preg_replace('/[^\w]/u', '',
      preg_split('/\s+/u', mb_strtolower(trim((string)$utkcity['engName']),'utf-8'))[0]
    );

    $command = Yii::app()->db->createCommand()
      ->select('ci.CityID as cityId, ci.Name as name, ci.EngName as engName')
      ->from('kt_ref_cities as ci')
      ->join('kt_ref_countries as co','ci.CountryID = co.CountryID')
      ->where('co.CountryCode = :countryCode and (ci.Name like :name or ci.EngName like :engName)',
        [
          ':countryCode' => $utkcity['countryCode'],
          ':name' => $utkcity['name'].'%',
          ':engName' => $utkcity['engName'].'%'
        ]
      );
    $mc = $command->query();

    if ($mc->count() == 0) {
      $mc->close();
      return false;
    }

    $utkNormalizedName = self::normalizeName((string)$utkcity['name']);
    $utkNormalizedEngName = self::normalizeName((string)$utkcity['engName']);

    foreach ($mc as $city) {
      if (
        self::normalizeName($city['name']) === $utkNormalizedName ||
        self::normalizeName($city['engName']) === $utkNormalizedEngName
      ) {
        $cityId = $city['cityId'];
        $mc->close();
        return $cityId;
      }
    }

    $mc->close();
    return false;
  }

  /**
  * Метод нормализации названий
  * @param string $cityname строка для нормализации
  * @return string нормализованная строка
  */
  private static function normalizeName($cityname) {
    if (empty($cityname)) {
      return false;
    }

    $name = preg_replace('/[^\w]/u','',$cityname);
    $name = mb_strtolower($name,'utf-8');
    return $name;
  }
}
