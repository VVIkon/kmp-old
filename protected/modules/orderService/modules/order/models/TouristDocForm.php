<?php

/**
 * Class TouristDocForm
 * Реализует функциональность для работы с данными о документе туриста
 */
class TouristDocForm extends KFormModel
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
     * ID документа из профиля сотрудника (если был добавлен из профиля)
     * @var int
     */
    public $userDocId;

	/**
	 * имя по документу
	 * @var string
	 */
	public $name;

	/**
	 * Отчестово по документу
	 * @var string
	 */
	public $middleName;

	/**
	 * Фамилия по документу
	 * @var string
	 */
	public $surname;

	/**
	 * Тип документа туриста
	 * @var int
	 */
	public $docTypeId;

	/**
	 * Номер документа
	 * @var string
	 */
	public $docSerial;

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
	 * Гражданство
	 * @var string
	 */
	public $citizenship;

	/**
	 * namespace для записи логов
	 * @var string
	 */
	private $_namespace;

	/**
	 * Конструктор объекта
	 * @param array $values
	 */
	public function __construct($namespace) {
		$this->_namespace = $namespace;
	}

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return [
				['touristDocId,touristBaseId,userId, name,middleName,surname,docTypeId,
					docNumber,docSerial,validFrom,validTill,issueBy,address,citizenship, userDocId','safe']
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
	 * Создание|обновление персональных данных туриста
	 * @return bool|void
	 */
	public function save() {

		if (empty($this->touristDocId)) {
			return $this->create();
		} else {
			return $this->update();
		}

	}

	/**
	 * Создание записи документа туриста в БД
	 * @return int ид вставленной записи
	 */
	public function create() {

		$command = Yii::app()->db->createCommand();

		$res = $command->insert('kt_tourists_doc', [
            'TouristIDdoc'  => $this->touristDocId,
            'UserDocId'     => $this->userDocId,
            'TouristIDbase' => $this->touristBaseId,
            'Name'    		=> $this->name,
            'MiddleName'    => $this->middleName,
            'Surname'   	=> $this->surname,
            'DocTypeID'     => $this->docTypeId,
            'DocNumber'     => $this->docNumber,
			'DocSerial'     => $this->docSerial,
            'DocValidFrom'  => $this->validFrom,
			'DocValidUntil' => $this->validTill,
			'IssueBy' 		=> $this->issueBy,
			'Address' 		=> $this->address,
			'Citizenship' 	=> $this->citizenship
        ]);

		$this->touristDocId = Yii::app()->db->lastInsertID;

		return $this->touristBaseId;
	}

	/**
	 * Обновление записи документа туриста в БД
	 * @return bool
	 */
	private function update() {

		$command = Yii::app()->db->createCommand();

		try {
			$res = $command->update('kt_tourists_doc', [
                'UserDocId'     => $this->userDocId,
                'TouristIDbase' => $this->touristBaseId,
				'Name'    		=> $this->name,
				'MiddleName'    => $this->middleName,
				'Surname'   	=> $this->surname,
				'DocTypeID'     => $this->docTypeId,
				'DocNumber'     => $this->docNumber,
				'DocSerial'     => $this->docSerial,
				'DocValidFrom'  => $this->validFrom,
				'DocValidUntil' => $this->validTill,
				'IssueBy' 		=> $this->issueBy,
				'Address' 		=> $this->address,
				'Citizenship' 	=> $this->citizenship
			], 'TouristIDdoc = :touristDocId', [':touristDocId' => $this->touristDocId]);
		} catch(Exception $e) {

			LogHelper::log(PHP_EOL . get_class(). '.' . __FUNCTION__. PHP_EOL.
				'Невозможно обновить данные документа туриста ' . print_r($this->getAttributes(),1)
				. 'Ошибка' . PHP_EOL. print_r($e->getMessage(),1),'trace',
				$this->_namespace. '.errors');
			return false;
		}

		return true;
	}
}