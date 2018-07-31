<?php

namespace Dictionaries\HotelsDictionary;

use \LogHelper as LogHelper;

abstract class UpdateStrategy {
  const GPTS = 5;
  const UTK = 4;

  const HOTEL_PRESERVE = 0;
  const HOTEL_CREATE = 1;
  const HOTEL_UPDATE = 2;
  const HOTEL_DELETE = 3;
  const HOTEL_DEMATCH = 4;
  const HOTEL_UPDATE_CONTENT = 5;

  /* состояния процесса обновления справочника для города */
  const STATE_INITIAL = 0; //начальное состояние (ничего не сделано)
  const STATE_DATA_LOADED = 1; //данные по отелям от поставщика загружены
  const STATE_DELTA_GENERATED = 2; //дельта изменений вычислена
  const STATE_CHANGES_APPLIED = 3; //внесены детерминированные изменения (см. applyDefiniteChanges())
  const STATE_FINISHED = 4; //обновление завершено

  /* тип редактирования записи в БД */
  const AUTO_EDIT = 0;
  const MANUAL_EDIT = 1;

  /** усредненный радиус Земли в км для формулы гаверсинусов */
  const EARTH_RADIUS = 6371;

  /**
  * максимальная дистанция между координатами отелей для сматчивания (в км)
  * @todo перенести в конфигурацию
  */
  const MAX_DISTANCE = 0.2;

  protected $module;
  protected $namespace;
  protected $config;
  protected $errortext;

  /** @var array правила матчинга отелей в функциях */
  protected $matchRules;

  public function __construct(&$module) {
    $this->module = $module;
    $this->namespace = $this->module->getConfig('log_namespace');
    $this->config = $this->module->getConfig('hotels_dictionary_config');
    $this->errortext = $this->module->getConfig('error_descriptions');

    $this->initMatchRules();
  }

  /**
  * Метод для получения списка городов для обновления
  * @return array список ID городов
  */
  abstract public function getUpdateList();

  /**
  * Метод для загрузки необходимых данных по обновляемому городу
  * @param int $cityId ID города (поставщика)
  */
  abstract public function loadCityData($cityId);

  /**
  * Получение состояния обработки текущего выбранного города
  * @return int состояние обработки (см. константы STATE_*)
  */
  abstract public function getState();

  /**
  * Метод для загрузки данных по отелям для конкретного города
  * @return int количество загруженных отелей
  */
  abstract public function loadCurrentHotelsData();

  /**
  * Метод для вычисления необходимых изменений в справочнике
  * @return array сводка по изменениям
  * ([
  *   'preserve' => (int),
  *   'create' => (int),
  *   'update' => (int),
  *   'delete' => (int)
  * ])
  */
  abstract public function generateChangesDelta();

  /**
  * Применение изменений с четкими последствиями:
  * деактивация/удаление отелей, связывание отелей в группы, обновление контента изменившихся отелей
  */
  abstract public function applyDefiniteChanges();

  /**
  * Матчинг отелей
  */
  abstract public function matchHotels();

  /**
  * Операции по завершению обработки города
  * (очистка временных даных, выставление/сброс статусов записей)
  */
  abstract public function finishCityProcessing();

  /**
  * Запись в файл лога
  * @param string $error текст ошибки
  * @param array $params параметры ошибки
  * @param int @type тип ошибки (константа LogHelper)
  */
  protected function writeLog($error, $params = [], $type = LogHelper::MESSAGE_TYPE_ERROR) {
    $namespace = $this->namespace;

    switch ($type) {
      case LogHelper::MESSAGE_TYPE_WARNING:
      case LogHelper::MESSAGE_TYPE_ERROR:
        $namespace .= '.errors';
        break;
      default:
        $namespace .= '.info';
        break;
    }

    LogHelper::logExt(
        'Dictionary','updateDictionary:Hotels','',
        $error,
        $params,
        $type,
        $namespace
    );
  }

  /**
  * Получение текста ошибки
  * @param int $errcode код ошибки
  * @return string текст ошибки
  */
  protected function getErrorText($errcode) {
    if (isset($this->errortext[$errcode])) {
      return $this->errortext[$errcode];
    } else {
      return $this->errortext['undefined_error'];
    }
  }

  /**
  * Инициализация правил сравнения параметров отелей
  */
  protected function initMatchRules() {
    /*
    * Тест на расстояние между геокоординатами
    * $t - набор нормализованных данных сматчиваемого отеля
    * $c - набор нормализованных данных кандидата на сматчивание
    * $d - максимальное расстояние между точками для выполнения условия
    */
    $testdistance = function ($t,$c,$d) {
      $dist = self::EARTH_RADIUS * 2 * asin(sqrt(
        pow(sin(($t['lat']-$c['lat']) * pi()/180 / 2 ),2) +
        cos($t['lat'] * pi()/180) *
        cos(abs($c['lat']) * pi()/180) *
        pow(sin(($t['lon'] - $c['lon']) * pi()/180 / 2), 2)
      ));

      return ($dist <= $d) ? true : false;
    };

    /*
    * Вычисление расстояния по Левенштейну для строк длиннее 255 символов
    * $t - первая строка
    * $c - вторая строка
    */
    $chunkedLevenshtein = function($t,$c) {
      $t_chunks = str_split($t,100);
      $c_chunks = str_split($c,100);

      $chunksSimilarity = array_map(function($tc,$cc) {
        return levenshtein((string)$tc,(string)$cc);
      },$t_chunks,$c_chunks);

      $lev = array_reduce($chunksSimilarity,function($sum,$i) {
        $sum += $i;
        return $sum;
      });

      return $lev;
    };

    $rulesConfig = $this->config['match_rules'];

    /*
    * параметры функций:
    *   $t - набор нормализованных данных сматчиваемого отеля
    *   $c - набор нормализованных данных кандидата на сматчивание
    */
    $this->matchRules = [
      'n' => function($t,$c) use ($testdistance,$rulesConfig) {
        //names match
        if (
          (string)$t['name'] === (string)$c['name'] &&
          mb_strlen($t['name'],'utf-8') > $rulesConfig['n']['name_diff']
        ) {
          if (
            !empty($t['lat']) && !empty($t['lon']) &&
            !empty($c['lat']) && !empty($c['lon'])
          ) {
            return $testdistance($t,$c,$rulesConfig['n']['distance']);
          } else {
            return true;
          }
        } else {
          return false;
        }
      },
      'npa' => function($t,$c) use ($testdistance,$rulesConfig,$chunkedLevenshtein) {
        //names and partial address match
        $t_len = mb_strlen($t['address'],'utf-8');
        $c_len = mb_strlen($c['address'],'utf-8');

        if ($t_len>254 || $c_len>254) {
          if (abs($t_len - $c_len) > $rulesConfig['npa']['address_diff']) {
            return false;
          } else {
            if ($chunkedLevenshtein($t['address'],$c['address']) > $rulesConfig['npa']['address_diff']) {
              return false;
            } else {
              $t['address'] = '';
              $c['address'] = '';
            }
          }
        }

        if (
          (string)$t['name'] === (string)$c['name'] &&
          $t_len >= $rulesConfig['npa']['address_length'] &&
          $c_len >= $rulesConfig['npa']['address_length'] &&
          levenshtein($t['address'],$c['address']) <= $rulesConfig['npa']['address_diff']
        ) {
          if (
            !empty($t['lat']) && !empty($t['lon']) &&
            !empty($c['lat']) && !empty($c['lon'])
          ) {
            return $testdistance($t,$c,$rulesConfig['npa']['distance']);
          } else {
            return true;
          }
        } else {
          return false;
        }
      },
      'apn' => function($t,$c) use ($testdistance,$rulesConfig,$chunkedLevenshtein) {
        //addresses and partial names match
        $t_len = mb_strlen($t['name'],'utf-8');
        $c_len = mb_strlen($c['name'],'utf-8');

        if ($t_len>254 || $c_len>254) {
          if (abs($t_len - $c_len) > $rulesConfig['apn']['name_diff']) {
            return false;
          } else {
            if ($chunkedLevenshtein($t['name'],$c['name']) > $rulesConfig['apn']['name_diff']) {
              return false;
            } else {
              $t['name'] = '';
              $c['name'] = '';
            }
          }
        }

        if (
          (string)$t['address'] === (string)$c['address'] &&
          mb_strlen($t['address'],'utf-8') >= $rulesConfig['apn']['address_length'] &&
          mb_strlen($c['address'],'utf-8') >= $rulesConfig['apn']['address_length'] &&
          levenshtein($t['name'],$c['name']) <= $rulesConfig['apn']['name_diff']
        ) {
          if (
            !empty($t['lat']) && !empty($t['lon']) &&
            !empty($c['lat']) && !empty($c['lon'])
          ) {
            return $testdistance($t,$c,$rulesConfig['apn']['distance']);
          } else {
            return true;
          }
        } else {
          return false;
        }
      },
      'pnpa' => function($t,$c) use ($testdistance,$rulesConfig,$chunkedLevenshtein) {
        //partial addresses and partial names match
        $tn_len = mb_strlen($t['name'],'utf-8');
        $cn_len = mb_strlen($c['name'],'utf-8');

        $ta_len = mb_strlen($t['address'],'utf-8');
        $ca_len = mb_strlen($c['address'],'utf-8');

        if ($tn_len>254 || $cn_len>254) {
          if (abs($tn_len - $cn_len) > $rulesConfig['pnpa']['name_diff']) {
            return false;
          } else {
            if ($chunkedLevenshtein($t['name'],$c['name']) > $rulesConfig['pnpa']['name_diff']) {
              return false;
            } else {
              $t['name'] = '';
              $c['name'] = '';
            }
          }
        }

        if ($ta_len>254 || $ca_len>254) {
          if (abs($ta_len - $ca_len) > $rulesConfig['pnpa']['address_diff']) {
            return false;
          } else {
            if ($chunkedLevenshtein($t['address'],$c['address']) > $rulesConfig['pnpa']['address_diff']) {
              return false;
            } else {
              $t['address'] = '';
              $c['address'] = '';
            }
          }
        }

        if (
          $tn_len >= $rulesConfig['pnpa']['name_length'] &&
          $cn_len >= $rulesConfig['pnpa']['name_length'] &&
          $ta_len >= $rulesConfig['pnpa']['address_length'] &&
          $ca_len >= $rulesConfig['pnpa']['address_length'] &&
          levenshtein($t['name'],$c['name']) <= $rulesConfig['pnpa']['name_diff'] &&
          levenshtein($t['address'],$c['address']) <= $rulesConfig['pnpa']['address_diff']
        ) {
          if (
            !empty($t['lat']) && !empty($t['lon']) &&
            !empty($c['lat']) && !empty($c['lon'])
          ) {
            return $testdistance($t,$c,$rulesConfig['pnpa']['distance']);
          } else {
            return true;
          }
        } else {
          return false;
        }
      },
      'cc' => function($t,$c) use ($testdistance,$rulesConfig) { //contacts and coordinates match
        if (
          (
            mb_strlen($t['phone'],'utf-8') >= $rulesConfig['cc']['phone_length'] &&
            (string)$t['phone'] === (string)$c['phone']
          ) || (
            mb_strlen($t['fax'],'utf-8') >= $rulesConfig['cc']['fax_length'] &&
            (string)$t['fax'] === (string)$c['fax']
          ) || (
            mb_strlen($t['email'],'utf-8') >= $rulesConfig['cc']['email_length'] &&
            (string)$t['email'] === (string)$c['email']
          ) || (
            mb_strlen($t['url'],'utf-8') >= $rulesConfig['cc']['url_length'] &&
            (string)$t['url'] === (string)$c['url']
          )
        ) {
          if (
            !empty($t['lat']) && !empty($t['lon']) &&
            !empty($c['lat']) && !empty($c['lon'])
          ) {
            return $testdistance($t,$c,$rulesConfig['cc']['distance']);
          } else {
            return false;
          }
        } else {
          return false;
        }
      }
    ];
  }

}
