<?php

/**
 * Class AgencyUserForm
 * Реализует функциональность для работы с данными о пользователе агентства
 */
class AgencyUserForm extends KFormModel
{

	/**
	 * Операция создания пользователя агентства
	 */
	const OPERATION_CREATE = 1;

	/**
	 * Операция обновления пользователя агентства
	 */
	const OPERATION_UPDATE = 2;

	/**
	 * Идентифкатор пользователя
	 * @var int
	 */
	public $userId;

	/**
	 * Идентификатор пользователя в УТК
	 * @var string
	 */
	public $userUTKId;

	/**
	 * Идентифкатор пользователя в GPTS
	 * @var string
	 */
	public $userGPTSId;

	/**
	 * Идентифкатор агентства пользователя
	 * @var int
	 */
	public $agencyId;

	/**
	 * Обращение к пользователю
	 * @var string
	 */
	public $prefix;

	/**
	 * Имя пользователя
	 * @var string
	 */
	public $name;

	/**
	 * Фамилия пользователя
	 * @var string
	 */
	public $surName;

	/**
	 * Отчество пользователя
	 * @var string
	 */
	public $sndName;

	/**
	 * Дата рождения
	 * @var string
	 */
	public $birthDate;

	/**
	 * Гражданство
	 * @var int
	 */
	public $citizenship;

	/**
	 * Контактный телефон
	 * @var string
	 */
	public $contactPhone;

	/**
	 * Адрес эл. почты
	 * @var string
	 */
	public $email;

	/**
	 * Роль пользователя в системе
	 * @var int
	 */
	public $roleId;

	/**
	 * Логин пользователя
	 * @var string
	 */
	public $login;

	/**
	 * Хэш пароля пользователя
	 * @var string
	 */
	public $pswHash;

	/**
	 * Дата создания учётной записи
	 * @var string
	 */
	public $dateCreated;

	/**
	 * Дата последней авторизации в системе
	 * @var string
	 */
	public $lastLogin;

	/**
	 * Номер icq пользователя
	 * @var string
	 */
	public $icq;

	/**
	 * Аккаунт skype пользователя
	 * @var string
	 */
	public $skype;

	/**
	 * Комментарий к учётной записи пользователя
	 * @var
	 */
	public $comments;

	/**
	 * Признак активности учётной записи пользователя
	 * @var
	 */
	public $active;

	/**
	 * Namespace выполнения
	 * @var string
	 */
	private $_namespace;

	/**
	 * Конструктор объекта
	 * @param string $namespace
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
			[
				'userId, userUTKId, userGPTSId, name, agencyId, prefix, surName, sndName, birthDate, citizenship,
				 contactPhone, email, roleId, login, pswHash, dateCreated, lastLogin, icq,
				 skype, comments, active'
				,'safe'
			]
		];
	}

	/**
	 * Получить информацию о пользователе по его идентифкатору в УТК
	 * @param $userUtkId идентифкатор пользователя в УТК
	 * @return null| object AgencyUserForm
	 */
	public function getUserByUtkId($userUtkId) {

		if (empty($userUtkId)) {
			return false;
		}

		return $this->getUserByFieldValue('UserID_UTK', $userUtkId);
	}

	/**
	 * Получить информацию о пользователе по его идентифкатору в КТ
	 * @param $userId идентифкатор пользователя в КТ
	 * @return null| object AgencyUserForm
	 */
	public function getUserById($userId) {

		if (empty($userId)) {
			return false;
		}

		return $this->getUserByFieldValue('UserID', $userId);
	}

	/**
	 * Получить информацию о пользователе по его идентифкатору в GPTS
	 * @param $userGptsId идентифкатор пользователя в GPTS
	 * @return null| object AgencyUserForm
	 */
	public function getUserByGptsId($userGptsId) {

		if (empty($userUtkId)) {
			return null;
		}

		return $this->getUserByFieldValue('UserID_GP',$userGptsId);
	}

	/**
	 * Получить информацию о пользователе агентства по значению одного из полей
	 * @param $fieldName
	 * @param $value
	 * @return AgencyUserForm|bool|null
	 */
	private function getUserByFieldValue($fieldName, $value) {

		if (empty($value) || empty($fieldName)) {
			return null;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_users')
			->where("{$fieldName} = :value",[':value' => $value]);

		$userInfo = $command->queryRow();

		if (empty($userInfo)) {
			return false;
		}

		$user = new AgencyUserForm($this->_namespace);

		$user->setParamsMapping([
			'UserID' 			=> 'userId',
			'UserID_GP' 		=> 'userGPTSId',
			'UserID_UTK' 		=> 'userUTKId',
			'AgentID' 			=> 'agencyId',
			'Prefix' 			=> 'prefix',
			'Name' 				=> 'name',
			'Surname' 			=> 'surName',
			'SndName' 			=> 'sndName',
			'Birthdate' 		=> 'birthDate',
			'CitizenshipID' 	=> 'citizenship',
			'ContactPhone' 		=> 'contactPhone',
			'Email' 			=> 'email,',
			'RoleID' 			=> 'roleId',
			'Login' 			=> 'login',
			'Hash' 				=> 'pswHash',
			'DateCreated' 		=> 'dateCreated',
			'LastLogin' 		=> 'lastLogin',
			'icq' 				=> 'icq',
			'skype' 			=> 'skype',
			'comments' 			=> 'comments',
			'active' 			=> 'active'
		]);

		$user->setAttributes($userInfo);

		return $user;
	}

	/**
	 * Создание|обновление информации о пользователе агентства в БД
	 * @return bool|int|string
	 */
	public function save() {

		if (empty($this->userUTKId) && empty($this->userGPTSId)) {

			LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
				'Невозможно записать данные пользователя агентства,
				отсутствуют идентифкаторы пользователя ' . PHP_EOL.
				print_r($this, 1), 'trace',$this->_namespace . '.errors');

			return false;
		}

		if (!empty($this->userUTKId)) {
			$existedUser = $this->getUserByUtkId($this->userUTKId);
		} else {
			$existedUser = $this->getUserByGptsId($this->userGPTSId);
		}

		if (!$existedUser) {
			$operation = self::OPERATION_CREATE;
			$result = $this->createUser();
		} else {
			$operation = self::OPERATION_UPDATE;
			$this->setAttributesIfNull($existedUser);
			$this->userId = $existedUser->userId;
			$result = $this->updateUser();
		}

		return ['userId' => $result, 'operation' => $operation];
	}

	/**
	 * Создание данных о пользователе в БД
	 * @return bool|int|string
	 */
	protected function createUser() {

		if (empty($this->agencyId)) {
			return false;
		}

		$command = Yii::app()->db->createCommand();

		if (empty($this->dateCreated)) {
			$this->dateCreated=date('Y-m-d H:i:s');
		}

		try {
			$res = $command->insert('kt_users', [
				'UserID_GP' 		=> $this->userGPTSId,
				'UserID_UTK'		=> $this->userUTKId,
				'AgentID' 			=> $this->agencyId,
				'Prefix' 			=> $this->prefix,
				'Name' 				=> $this->name,
				'Surname' 			=> $this->surName,
				'SndName' 			=> $this->sndName,
				'Birthdate' 		=> $this->birthDate,
				'CitizenshipID' 	=> $this->citizenship,
				'ContactPhone' 		=> $this->contactPhone,
				'Email' 			=> $this->email,
				'RoleID' 			=> $this->roleId,
				'Login' 			=> $this->login,
				'Hash' 				=> $this->pswHash,
				'DateCreated' 		=> $this->dateCreated,
				'LastLogin' 		=> $this->lastLogin,
				'icq' 				=> $this->icq,
				'skype' 			=> $this->skype,
				'comments' 			=> $this->comments,
				'active' 			=> $this->active
			]);
		} catch (Exception $e) {

			LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
				'Невозможно записать данные пользователя агентства ' . print_r($e->getMessage(), 1), 'trace',
				$this->_namespace . '.errors');
			return false;
		}

		$this->userId = Yii::app()->db->lastInsertID;

		return $this->userId;
	}

	/**
	 * Обновление данных о пользователе в БД
	 * @return bool
	 */
	protected function updateUser() {

		if (empty($this->agencyId)) {
			return false;
		}

		$command = Yii::app()->db->createCommand();

		try {
			$res = $command->update('kt_users', [
				'UserID_GP' 		=> $this->userGPTSId,
				'UserID_UTK'		=> $this->userUTKId,
				'AgentID' 			=> $this->agencyId,
				'Prefix' 			=> $this->prefix,
				'Name' 				=> $this->name,
				'Surname' 			=> $this->surName,
				'SndName' 			=> $this->sndName,
				'Birthdate' 		=> $this->birthDate,
				'CitizenshipID' 	=> $this->citizenship,
				'ContactPhone' 		=> $this->contactPhone,
				'Email' 			=> $this->email,
				'RoleID' 			=> $this->roleId,
				'Login' 			=> $this->login,
				'Hash' 				=> $this->pswHash,
				'DateCreated' 		=> $this->dateCreated,
				'LastLogin' 		=> $this->lastLogin,
				'icq' 				=> $this->icq,
				'skype' 			=> $this->skype,
				'comments' 			=> $this->comments,
				'active' 			=> $this->active
			],'UserID = :userId', [':userId' => $this->userId]);
		} catch (Exception $e) {

			LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
				'Невозможно обновить данные пользователя агентства ' . print_r($e->getMessage(), 1), 'trace',
				$this->_namespace . '.errors');
			return false;
		}

		return $this->userId;
	}

	/**
	 * Установка идентифкатора агентства
	 * @param $agentId
	 */
	public function setAgencyId($agentId) {
		$this->agencyId = $agentId;
	}
}
