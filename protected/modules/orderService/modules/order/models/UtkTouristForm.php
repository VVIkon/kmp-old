<?php

/**
 * Class UtkTouristForm
 * Реализует функциональность для работы с данными о туристе из УТК
 */
class UtkTouristForm extends KFormModel
{

	const PERSON_ADULT = 1;
	const PERSON_CHILD = 2;
	const PERSON_INFANT = 3;

	const ADULT_AGE = 200;
	const CHILD_AGE = 12;
	const INFANT_AGE = 2;

	/**
	 * Ид сервиса
	 * @var
	 */
	public $serviceId;

	/**
	 * Ид туриста в УТК
	 * @var
	 */
	public $serviceIdUTK;

	/**
	 * Дата последнего изменения информации о туристе
	 * @var
	 */
	public $personDateUpdate;

	/**
	 * Статус туриста
	 * @var
	 */
	public $status;

	/**
	 * Ид туриста
	 * @var
	 */
	public $personId;

	/**
	 * Ид связки данных туриста и документа туриста
	 * @var
	 */
	public $docLinkId;

	/**
	 * Ид туриста в УТК
	 * @var
	 */
	public $personIdUTK;

	/**
	 * Тип туриста
	 * @var int
	 */
	public $personType;

	/**
	 * Имя туриста
	 * @var string
	 */
	public $firstName;

	/**
	 * Отчество туриста
	 * @var string
	 */
	public $middleName;

	/**
	 * Фамилия туриста
	 * @var string
	 */
	public $lastName;

	/**
	 * Дата рождения туриста
	 * @var string
	 */
	public $dateOfBirth;

	/**
	 * Признак турлидера(лица на которого производилось оформление тура)
	 * @var int
	 */
	public $isTourLeader;

	/**
	 * Пол
	 * @var string
	 */
	public $sex;

	/**
	 * Адрес эл. почты
	 * @var string
	 */
	public $email;

	/**
	 * Контактный телефон
	 * @var string
	 */
	public $phone;

	/**
	 * Возрастная категория
	 * @var int
	 */
	private $personAgeType;

	/**
	 * Данные о документе туриста
	 * @var object
	 */
	private $personDoc;

	/**
	 * Класс для сохранения в БД связи между данными
	 * о туристе и данными о документе туриста
	 * @var TouristDocMapperForm
	 */
	private $docMapper;

	/**
	 * Класс для сохранения в БД связи между
	 * общими данными туриста и услуги заявки
	 * @var TouristServiceMapperForm
	 */
	private $serviceMapper;

	/**
	 * Конструктор объекта
	 * @param array $values
	 */
	public function __construct() {
		$this->docMapper = new TouristDocMapperForm();
		$this->serviceMapper = new TouristServiceMapperForm();
	}

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return [
				['serviceId,serviceIdUTK,personDateUpdate,status,personId,
						personIdUTK,personType,firstName,middleName,
						lastName,dateOfBirth,isTourLeader','safe'
				]
		];
	}

	/**
	 * Установить id сервиса для туриста
	 * @param $serviceId
	 */
	public function setServiceId($serviceId) {

		if (!empty($serviceId)) {
			$this->serviceId = $serviceId;
		}
	}

	/**
	 * Установка свойств туриста
	 * @param $params array параметры туриста
	 * @param bool|true $safeOnly
	 */
	public function setAttributes($params,$safeOnly = true) {

		parent::setAttributes($params, $safeOnly);

		$this->setPersonAgeType($this->dateOfBirth);
//		todo удалить после реализации передачи параметров в запросе
		$this->sex = 'муж';
		$this->email = 'tourist@email.tur';
		$this->phone = '+78002000600';
//

		$this->personDoc = new UtkTouristDocForm();

//		todo удалить после реализации передачи параметров в запросе
		$this->personDoc->setAttributes([
				'firstName' => $this->firstName,
				'middleName'=> $this->middleName,
				'lastName'  => $this->lastName,
				'docTypeId' => 2,
				'docNameShort' => 'ЗагранпаспортРФ',
				'docNumber' => '122122',
				'validFrom' => date('2015-01-01'),
				'validTill' => date('2025-01-01'),
				'issueBy' => 'УФМС России',
				'address' => 'г.Москва ул. Новослободская д.8',
		]);

		$this->personDoc = new UtkTouristDocForm();

		$this->personDoc->setAttributes($params, $safeOnly);
//
	}

	/**
	 * Получить возраст туриста
	 * @param $dateOfBirth datetime дата рождения
	 * @return bool|int количество лет
	 */
	protected function getAge($dateOfBirth)
	{
		if (empty($dateOfBirth)) {
			return false;
		}

		// функция для определения возраста по дате рождения
		preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)T\d\d:\d\d:\d\d/',$dateOfBirth,$matches);
		$years = date("Y")-$matches[1];
		if ($matches[2] > date("m"))
		{$years--;}
		else
		{
			if ($matches[2] == date("m"))
			{
				if ($matches[3] > date("d"))
				{
					$years--;
				}
			}
		}
		return $years;
	}

	/**
	 * Установить тип возрастной категории туриста
	 * @param $dateOfBirth datetime дата рождения
	 */
	protected function setPersonAgeType($dateOfBirth) {

		$age = $this->getAge($dateOfBirth);

		if ($age >= self::CHILD_AGE)
		{
			$this->personAgeType = self::PERSON_ADULT;
		}
		else if ($age >= self::INFANT_AGE)
		{
			$this->personAgeType = self::PERSON_CHILD;
		}
		else
		{
			$this->personAgeType = self::PERSON_INFANT;
		}

	}

	/**
	 * Получить возрастную категорию туриста
	 * @return int тип возрастной категории туриста
	 */
	public function getPersonAgeType() {
		return $this->personAgeType;
	}

	/**
	 * Получить ид документа туриста
	 * @return int ид документа туриста
	 */
	public function getDocId() {
//		todo реализовать получение ид документа туриста
		return 1;
	}

	/**
	 * Проверка наличия признака
	 * тулидер(лицо на которого оформляется документы)
	 * у туриста
	 * @return bool
	 */
	public function isTourLeader() {
		return $this->isTourLeader;
	}
}