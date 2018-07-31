<?php

/**
 * Class UtkTouristDocForm
 * Реализует функциональность для работы с данными о документе туриста из УТК
 */
class UtkTouristDocForm extends KFormModel
{

	/**
	 * Идентификатор документа туриста
	 * @var int
	 */
	public $touristDocId;

	/**
	 * Идентификатор туриста
	 * @var int
	 */
	public $touristBaseId;

	/**
	 * имя по документу
	 * @var string
	 */
	public $firstName;

	/**
	 * Отчестово по документу
	 * @var string
	 */
	public $middleName;

	/**
	 * Фамилия по документу
	 * @var string
	 */
	public $lastName;

	/**
	 * Тип документа туриста
	 * @var int
	 */
	public $docTypeId;

	/**
	 * Краткое название документа
	 * @var string
	 */
	public $docNameShort;

	/**
	 * Номер документа
	 * @var string
	 */
	public $docNumber;

	/**
	 * Дата начала действия документа
	 * @var string
	 */
	public $validFrom;

	/**
	 * Дата окончания действия документа
	 * @var string
	 */
	public $validTill;

	/**
	 * Наименование организации выпустившей документ
	 * @var string
	 */
	public $issueBy;

	/**
	 * Адрес регистрации документа
	 * @var string
	 */
	public $address;

	/**
	 * Конструктор объекта
	 * @param array $values
	 */
	public function __construct() {}

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return [
				['touristDocId,touristBaseId,firstName,middleName,lastName,docTypeId,
					docNameShort,docNumber,validFrom,validTill,issueBy,address','safe']
		];
	}

	/**
	 * Установка свойств документа туриста
	 * @param $params array параметры документа
	 * @param bool|true $safeOnly
	 */
	public function setAttributes($params,$safeOnly = true) {

		parent::setAttributes($params, $safeOnly);
	}

	/**
	 * Создание записи документа туриста в БД
	 * @return int ид вставленной записи
	 */
	public function addDoc() {

		$command = Yii::app()->db->createCommand();

		$res = $command->insert('kt_tourists_doc', array(
            'TouristIDdoc'  => $this->touristDocId,
            'TouristIDbase' => $this->touristBaseId,
            'Name'    		=> $this->firstName,
            'MiddleName'    => $this->middleName,
            'Surname'   	=> $this->lastName,
            'DocTypeID'     => $this->docTypeId,
            'DocNameShort'  => $this->docNameShort,
            'DocNumber'     => $this->docNumber,
            'DocValidFrom'  => $this->validFrom,
			'DocValidUntil' => $this->validTill,
			'IssueBy' => $this->issueBy,
			'Address' => $this->address
        ));

		return Yii::app()->db->lastInsertID;
	}
}