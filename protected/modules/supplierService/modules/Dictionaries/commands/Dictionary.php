<?php

class Dictionary extends CConsoleCommand {
  const UTK = 4;
  const GPTS = 5;

  /** @deprecated
  const COMPANIES_DICTIONARY = 1;
  const USERS_DICTIONARY = 5;
  const COUNTRIES_DICTIONARY = 6;
  const CITIES_DICTIONARY = 7;
  const HOTELS_DICTIONARY = 15;

  private $dictionaryTypes = [
    self::COMPANIES_DICTIONARY => 'CompaniesDictionary',
    self::USERS_DICTIONARY => 'UsersDictionary',
    self::COUNTRIES_DICTIONARY => 'CountriesDictionary',
    self::CITIES_DICTIONARY => 'CitiesDictionary',
    self::HOTELS_DICTIONARY => 'HotelsDictionary'
  ];
  */

  /**  @var KModule Используется для хранения ссылки на текущий модуль */
  private $module;
  /** @var string namespace для записи логов */
  private $namespace;
  /** @var int Код ошибки */
  private $errorCode;

  public function init() {
    Yii::getLogger()->autoFlush = 1;
    Yii::getLogger()->autoDump = true;

    $this->module = Yii::app()->getModule('supplierService')->getModule('Dictionaries');
    $this->namespace = $this->module->getConfig('log_namespace');
  }

  /**
  * Метод вывода текущего лога операций
  * @param string $logtext сообщение лога
  * @param bool $newline нужно ли вставить перенос строки
  */
  public static function livelog($logtext,$newline = true) {
    //echo '['.microtime(true).'] '.$logtext. ($newline ? "\n" : '');
    echo '['.date('d.m H:i:s').'] '.$logtext. ($newline ? "\n" : '');
  }

  /**
  * Метод обновления словаря
  * @param int $dictionaryType код словаря
  * @param int $engineType код поставщика
  * @param int $updateType тип обновления (0-полный, 1-частичный)
  */
  public function actionUpdateDictionary($dictionaryType, $engineType, $updateType) {
    $dictionary = $this->module->useDictionary((int)$dictionaryType);

    if ($dictionary !== false) {
      try {
        $dictionary->updateDictionary($engineType, $updateType);
      } catch (KmpException $ke) {

        LogHelper::logExt(
            $ke->class,
            $ke->method,
            $this->module->getCxtName($ke->class, $ke->method),
            $this->module->getError($ke->getCode()),
            $ke->params,
            LogHelper::MESSAGE_TYPE_ERROR,
            $this->namespace. '.errors'
        );

        self::livelog('error; code: '.$ke->getCode());
        exit( $ke->getCode() );

      } catch (Exception $e) {
        self::livelog('error; code: '.$e->getMessage());
        LogHelper::logExt(
            'Dictionary','updateDictionary',
            $this->module->getError('undefined_error'),
            $e->getMessage(),
            ['trace' => $e->getTraceAsString()],
            LogHelper::MESSAGE_TYPE_ERROR,
            $this->namespace. '.errors'
        );

        self::livelog('error; code: '.DictionaryErrors::UNCATCHED_ERROR . ', msg: '.$e->getMessage());
        exit( DictionaryErrors::UNCATCHED_ERROR );
      }

      LogHelper::logExt(
          __CLASS__,__FUNCTION__,
          'Dictionary Type: '.$dictionaryType.', engine: '.$engineType.', updateType: '.$updateType,
          $this->module->getError(DictionaryErrors::SUCCESS),
          [],
          LogHelper::MESSAGE_TYPE_INFO,
          $this->namespace. '.requests'
      );

      self::livelog('success');
      exit( DictionaryErrors::SUCCESS );

    } else {
      self::livelog('error; code: '.DictionaryErrors::UNKNOWN_DICTIONARY);
      exit( DictionaryErrors::UNKNOWN_DICTIONARY );
    }
  }

  /**
  * вывод расстояния между точками, заданными координатами (в км)
  * @param float $lat1 широта первой точки
  * @param float $lon1 долгота первой точки
  * @param float $lat2 широта второй точки
  * @param float $lon2 долгота второй точки
  */
  public function actionFindDistance($lat1,$lon1,$lat2,$lon2) {
    $lat1=(float)$lat1;
    $lon1=(float)$lon1;
    $lat2=(float)$lat2;
    $lon2=(float)$lon2;

    self::livelog('indata: '.$lat1.', '.$lon1.', '.$lat2.', '.$lon2);

    $dist = 6371 * 2 * asin(sqrt(
      pow(sin(($lat1-$lat2) * (pi()/180) / 2 ),2) +
      cos($lat1 * pi()/180) *
      cos(abs($lat2) * pi()/180) *
      pow(sin(($lon1 - $lon2) * (pi()/180) / 2), 2)
    ));

    /**
    * Проверка вычисления допустимой разницы координат при заданном расстоянии:
    * $deltaLat - допустимая разница широт при одинаковой долготе
    * $deltaLon - допустимая разница долгот при одинаковой широте
    * т.е. для двух координат где $lat1=$lat2 расстояние между точками при соблюдении
    * условия abs($lon2-$lon1) < $deltaLon будет не более $maxdist
    */
    $maxdist = 0.2; // 200 метров
    $deltaLat = (180 * $maxdist) / 6371 * pi();
    $deltaLon = asin(
      sin($maxdist / 12742) /
      sqrt(cos($lat1 * pi()/180) * cos(abs($lat1) * pi()/180))
    ) * 180 * 2 / pi();

    self::livelog('deltaLat: '.$deltaLat.' , deltaLon: '.$deltaLon);
    self::livelog('distance: '.$dist);
    exit(0);
  }

}
