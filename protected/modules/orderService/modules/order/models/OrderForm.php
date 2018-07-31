<?php

/**
 * Class OrderForm
 * Реализует функциональность для работы с данными заявок
 */
class OrderForm extends KFormModel
{

	const ORDER_STATUS_NEW = 0;
	const ORDER_STATUS_MANUAL = 1;
	const ORDER_STATUS_PAID = 2;
	const ORDER_STATUS_CLOSED = 3;
	const ORDER_STATUS_ANNULED = 4;
	const ORDER_STATUS_W_PAID = 5;
	const ORDER_STATUS_DONE = 9;
	const ORDER_STATUS_BOOKED = 10;

	/** @var int Ид заявки */
	public $orderId;

	/**  @var string Ид заявки в УТК */
	public $orderUtkId;

	/** @var int Ид заявки в ГПТС */
	public $orderIdGpts;

	/** @var Дата создания заявки */
	public $orderDate;

	/** @var int Статус заявки */
	public $status;

	/** @var int Ид турлидера */
	public $touristId;

	/** @var int  Идентифкатор агентства */
	public $agencyId;

	/** @var int Идентифкатор оператора агентства */
	public $agencyUserId;

	/** @var bool Признак архивной заявки */
	public $archive;

	/** @var string Дата последнего изменения заявки */
	public $dolc;

	/** @var int Признак приоритетности заявки */
	public $vip;

	/** @var int Ид агентского договора */
	public $contractId;

	/** @var int Признак того, что заяка заблокирована */
	public $blocked;

	/** @var string Комментарий к заявке */
	public $comment;

	/** @var namespace для логирования */
	public $_namespace;

	/** @var array Алиасы для запроса полей из таблицы */
	private	$fieldsAliases = ['dateStart' => 'startdate', 'touristName' => 'touristLastName',
								'agentCompany' => 'agentCompany', 'countryName' => 'country',
								'cityName' => 'city', 'status' => 'status',
								'lastChangeDate' => 'DOLC'];

	/**
	 * Конструктор объекта
	 * @param array $values
	 */
	public function __construct($params, $namespace) {

		/*$this->values = $params;*/
		$this->_namespace = $namespace;
		parent::__construct();
		$this->validate();
	}

	public static function createInstance($namespace) {
		$fakeparams = [];
		return new self($fakeparams,$namespace);
	}

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return [
			['orderId,orderUtkId,orderIdGpts,orderDate,status,blocked,touristId,
				agencyId,agencyUserId,archive,dolc,vip,contractId', 'safe']
		];

	}

	/**
	 * Валидация параметров
	 * @return bool
	 */
	/*
	public function validateParams()
	{
		$object = new inputValidator($this->values, $this->rules);

		foreach ($this->rules as $rule) {
			if (isset($rule[0], $rule[1])) {
				$validator = CValidator::createValidator(
					$rule[1],
					$object,
					$rule[0],
					array_slice($rule, 2)
				);
				$validator->validate($object);
			} else { /* throw error; */
	/*	}
		}

		return !$object->hasErrors();
	}*/

	/**
	 * Создание новой заявки
	 * @return null
	 */
	public function createOrder($orderUtkId, $orderGptsId, $agencyId, $agentId, $agencyContractId) {

		if (empty($orderUtkId)) {
			return null;
		}

		$agentId = !empty($agentId) ? $agentId : null;

		$command = Yii::app()
			->db
			->createCommand("select CreateOrder(:in_orderIdUtk, :in_orderGptsId,
								:in_agencyId, :in_agentId, :in_agencyContractId);");

		$command->bindParam(":in_orderIdUtk", $orderUtkId, PDO::PARAM_STR);
		$command->bindParam(":in_agencyId", $agencyId, PDO::PARAM_STR);
		$command->bindParam(":in_agentId", $agentId, PDO::PARAM_STR);
		$command->bindParam(":in_orderGptsId", $orderGptsId, PDO::PARAM_STR);

		$command->bindParam(":in_agencyContractId", $agencyContractId, PDO::PARAM_STR);

		$this->orderId = $command->queryScalar();

		return $this->orderId;
	}

	/**
	 *  Обновление информации по заявке
	 */
	public function updateOrder() {

		if (empty($this->orderId)) {
			return null;
		}

		$command = Yii::app()->db->createCommand();

		try {
            return $command->update('kt_orders', [
				'Status' 	 => is_null($this->status) ? 0 : $this->status,
				'OrderID_UTK' => $this->orderUtkId,
//				'OrderID_GP' => $this->orderIdGpts,
				'AgentID' 	 => $this->agencyId,
				'UserID' 	 => $this->agencyUserId,
				'Archive' 	 => $this->archive,
				'VIP' 		 => $this->vip,
				'ContractID' => $this->contractId
			], 'OrderID = :order_id', [':order_id' => $this->orderId]);
		} catch (Exception $e) {

			LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
				'Невозможно обновить данные заявки ' . print_r($this, 1)
				.print_r($e->getMessage(), 1), 'trace', $this->_namespace . '.errors');
			return false;
		}
	}

	/**
	 * Проверка существования полей в таблице
	 * по указанных алиасам
	 * @return bool
	 */
	public function fieldsExists() {

		if ($this->sortFields == null) {
			return true;
		}

		if (!is_array($this->sortFields)) {
			$this->addError('sortFields','invalid');
			return false;
		}

		foreach ($this->sortFields as $sortField) {
			if (!$this->_checkfieldExist($sortField)) {
				$this->addError('sortFields','invalid');
				return false;
			}
		}

	}

	/**
	 * Получение названия поля в таблице по его алиасу
	 * @param $alias string  алиас для поля
	 * @return string название поля в таблице | false
	 */
	private function _getfieldByAlias($alias) {

		if ($this->_checkfieldExist($alias)) {
			return $this->fieldsAliases[$alias];
		}

		return false;
	}

	public function getOrderByUTKId($utkId) {

		if (empty($utkId)) {
			return false;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_orders')
			->where('kt_orders.OrderID_UTK = :utkId', array(':utkId' => $utkId));

		return $command->queryRow();
	}

	/**
	 * Получение записи из БД с данными о заявке
	 * @param $orderId
	 * @return bool|CDbDataReader|mixed
	 */
	public function getOrderById($orderId) {

		if (empty($orderId)) {
			return false;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_orders')
			->where('kt_orders.OrderID = :id', array(':id' => $orderId));

		return $command->queryRow();
	}

	/**
	 * Получение информации из БД с данными о заявке в виде объекта
	 * @param $orderId
	 * @return bool|OrderForm
	 */
	public function getOrderByIdObj($orderId) {

		if (empty($orderId)) {
			return false;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_orders')
			->where('kt_orders.OrderID = :id', array(':id' => $orderId));

		$orderInfo = $command->queryRow();

		return empty($orderInfo) ? false : $this->orderfromDbData($orderInfo);
	}

	/**
	 * Получение заявки по идентификатору услуги в КТ
	 * @param $serviceId
	 * @return CDbDataReader|mixed|null
	 */
	public static function getOrderByServiceId($serviceId) {

		if (empty($serviceId)) {
			return null;
		}

		$command = Yii::app()->db->createCommand()
			->select('kt_orders.*')
			->from('kt_orders')
			->join('kt_orders_services ordersvc', 'ordersvc.OrderID  = kt_orders.OrderID')
			->where('ServiceID = :serviceId', array(':serviceId' => $serviceId));

		return $command->queryRow();
	}

	/**
	 * Проверка существования указанного поля в таблице заявок
	 * @param $fieldName алиас поля
	 */
	private function _checkfieldExist($fieldName) {

		if (!array_key_exists($fieldName,$this->fieldsAliases)) {
			return false;
		}

		return true;
	}

	/**
	 *  Физическое удаление заявки из БД
	 */
	public function removeOrder() {

		if (empty($this->orderId)) {
			return false;
		}

		try {
			$command = Yii::app()->db->createCommand()
				->delete('kt_orders','OrderID=:orderId', array(':orderId'=>$this->orderId));
		} catch(Exception $e) {
			return false;
		}

		return true;
	}

	/**
	 * Проверка направления сортировки
	 * @return bool
	 */
	public function checkSortDirection() {

		if (empty($this->sortDir)) {
			return true;
		}

		if (strtolower($this->sortDir) != 'asc' && strtolower($this->sortDir) != 'desc') {
			$this->addError('sortDir','invalid');
			return false;
		}
		return true;
	}

	/**
	 * Заполнить объект заявки данными из БД
	 * @param $params
	 * @return OrderForm
	 */
	public function orderfromDbData($params) {

		$order = self::createInstance($this->_namespace);
		$order->setParamsMapping([
			'OrderID' 		=> 'orderId',
    		'OrderID_UTK' 	=> 'orderUtkId',
    		'OrderID_GP' 	=> 'orderIdGpts',
    		'OrderDate' 	=> 'orderDate',
    		'Status' 		=> 'status',
    		'TouristID' 	=> 'touristId',
    		'AgentID' 		=> 'agencyId',
    		'UserID' 		=> 'agencyUserId',
    		'Archive' 		=> 'archive',
    		'DOLC' 			=> 'dolc',
    		'VIP' 			=> 'vip',
    		'ContractID' 	=> 'contractId',
    		'Blocked' 		=> 'blocked',
    		'comment' 		=> 'comment'
		]);

		$order->setAttributes($params);
		return $order;
	}

}
