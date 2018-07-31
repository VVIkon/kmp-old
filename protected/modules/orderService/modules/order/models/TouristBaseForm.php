<?php

/**
 * Class TouristBaseForm
 * Реализует функциональность для работы
 * с базовой информацией о туристе
 */
class TouristBaseForm extends KFormModel
{

	/**
	 * Ид туриста в таблице базовой информации
	 * @var
	 */
	public $touristBaseId;

	/**
	 * Ид базовой информации о туристе в УТК
	 * @var
	 */
	public $touristUtkId;

    /**
     * ID пользователя - сотрудника компании клиента (если был добавлен из профиля).
     * @var int
     */
    public $userID;


	/**
	 * Имя туриста
	 * @var
	 */
	public $name;

	/**
	 * Фамилия туриста
	 * @var
	 */
	public $surname;

	/**
	 * Отчество туриста
	 * @var
	 */
	public $middleName;

	/**
	 * Пол туриста
	 * @var
	 */
	public $sex;

	/**
	 * Дата рождения туриста
	 * @var int
	 */
	public $birthDate;

	/**
	 * Электронная почта туриста
	 * @var string
	 */
	public $email;

	/**
	 * Контактный телефон туриста
	 * @var string
	 */
	public $phone;

	/**
	 * Пространство имён для логирования
	 * @var string
	 */
	private $_namespace;

	/**
	 * Конструктор объекта
	 * @param array $values
	 */
	public function __construct() {
		$this->_namespace = "system.orderservice";
	}

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return [
			['touristBaseId,touristUtkId,userID, email,phone,sex,middleName', 'safe'],
			['name,surname,birthDate','required']
		];
	}

	/**
	 * Создание|обновление персональных данных туриста
	 * @return bool|void
	 */
	public function save() {

		if (empty($this->touristBaseId)) {

			return $this->create();
		} else {

			return $this->update();
		}

	}

	/**
	 *	Создание в БД персональных данных туриста
	 */
	private function create() {

		$command = Yii::app()->db->createCommand();
		try {

			$res = $command->insert('kt_tourists_base', array(
				'TouristID_UTK' => $this->touristUtkId,
                'UserID'=> $this->userID,
				'Name' => $this->name,
				'MiddleName' => $this->middleName,
				'Surname' => $this->surname,
				'MaleFemale' => $this->sex,
				'Birthdate' => $this->birthDate,
				'Email' => $this->email,
				'Phone' => $this->phone
			));
		} catch (Exception $e) {

			LogHelper::log(PHP_EOL . get_class(). '.' . __FUNCTION__. PHP_EOL.
				'Невозможно создать данные туриста ' . print_r($this->getAttributes(),1)
				. 'Ошибка' . PHP_EOL. print_r($e->getMessage(),1),'trace',
				$this->_namespace. '.errors');
			return false;
		}

		$this->touristBaseId = Yii::app()->db->lastInsertID;

		return $this->touristBaseId;
	}

	/**
	 * Обновление в БД персональных данных туриста
	 * @return bool
	 */
	private function update() {

		$command = Yii::app()->db->createCommand();

		try {
			$res = $command->update('kt_tourists_base', [
				'TouristID_UTK' => $this->touristUtkId,
                'UserID'        => $this->userID,
                'Name' 			=> $this->name,
				'MiddleName' 	=> $this->middleName,
				'Surname' 		=> $this->surname,
				'MaleFemale' 	=> $this->sex,
				'Birthdate' 	=> $this->birthDate,
				'Email' 		=> $this->email,
				'Phone' 		=> $this->phone
			], 'TouristIDbase = :touristBaseId', [':touristBaseId' => $this->touristBaseId]);
		} catch(Exception $e) {

			LogHelper::log(PHP_EOL . get_class(). '.' . __FUNCTION__. PHP_EOL.
				'Невозможно обновить данные туриста ' . print_r($this->getAttributes(),1)
				. 'Ошибка' . PHP_EOL. print_r($e->getMessage(),1),'trace',
				$this->_namespace. '.errors');
			return false;
		}

		return true;
	}
}
