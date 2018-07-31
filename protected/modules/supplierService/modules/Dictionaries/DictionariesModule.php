<?php

/**
* Модуль команд работы со справочниками поставщиков
*/
class DictionariesModule extends KModule {
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

	public function init() {
		$this->setImport(array(
			'Dictionaries.commands.*',
			'Dictionaries.components.*',
			'Dictionaries.models.*'
		));

		//Yii::setPathOfAlias('Dictionaries','system.modules.supplierService.modules.Dictionaries');
	}

	/**
	* Возвращает экземпляр класса управления справочником
	* @param int $dictionaryType Тип справочника (см. константы)
	*/
	public function useDictionary($dictionaryType) {
		if (!isset($this->dictionaryTypes[$dictionaryType])) {
			return false;
		} else {
			return new $this->dictionaryTypes[$dictionaryType]($this);
		}
	}

}

?>
