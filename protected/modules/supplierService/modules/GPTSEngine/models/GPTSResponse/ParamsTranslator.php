<?php

/**
 * Класс для конвертации параметров из GPTS в КТ
 * Class ParamsTranslator
 */
class ParamsTranslator {

  public static function getKtSex($value)
  {
    return ($value == 'Mr') ? 1 : 0;
  }

  public static function getKtDateOfBirth($value)
  {
    return preg_replace('/\s\d\d(:\d\d)+/u','',$value);
  }

  public static function getKtCountry($value)
  {
    return CountriesMapperHelper::getCountryIdBySupplierId(GPTSSupplierEngine::ENGINE_ID, $value);
  }
}
