<?php

/**
 * Class Order
 * Реализует функциональность для работы с данными заявок
 */
class Order extends KFormModel
{

	/**
	 * Ид заявки
	 * @var int
	 */
	public $orderId;

	/**
	 * Ид заявки в УТК
	 * @var
	 */
	public $orderUtkId;

	/**
	 * Ид заявки в ГПТС
	 * @var
	 */
	public $orderIdGpts;

	/**
	 * Дата создания заявки
	 * @var
	 */
	public $orderDate;

	/**
	 * Статус заявки
	 * @var int
	 */
	public $status;

	/**
	 * Ид турлидера(лица на
	 * которое оформляются документы)
	 * @var
	 */
	public $touristId;

	/**
	 * Идентифкатор агентства
	 * @var
	 */
	public $agencyId;

	/**
	 * Идентифкатор оператора агентства
	 * @var
	 */
	public $agencyUserId;

	/**
	 * Признак архивной заявки
	 * @var
	 */
	public $archive;

	/**
	 * Дата последнего изменения заявки
	 * @var string
	 */
	public $dolc;

	/**
	 * Признак приоритетности заявки
	 * @var int
	 */
	public $vip;

	/**
	 * Ид агентского договора
	 * @var int
	 */
	public $contractId;

	/**
	 * Признак того, что заяка заблокирована
	 * @var int
	 */
	public $blocked;

	/**
	 * Комментарий к заявке
	 * @var string
	 */
	public $comment;

	/**
	 * Идентификатор менеджера агенства создавшего заявку
	 * @var string
	 */
	public $companyManager;

	/**
	 * Идентификатор ответственного менеджера КМП куриррующего агентство
	 * @var string
	 */
	public $kmpManager;

	/**
	 * namespace для логирования
	 * @var
	 */
	public $namespace;

	/**
	 * Алиасы для запроса полей из таблицы
	 */
	/*private	$fieldsAliases = ['dateStart' => 'startdate', 'touristName' => 'touristLastName',
								'agentCompany' => 'agentCompany', 'countryName' => 'country',
								'cityName' => 'city', 'status' => 'status',
								'lastChangeDate' => 'DOLC'];*/

	/**
	 * Конструктор объекта
	 * @param array $values
	 */
	public function __construct($namespace) {

		$this->namespace = $namespace;
		parent::__construct();
	}

	/**
	 * Инициализация свойств объекта
	 * @param $params array
	 * @return bool
	 */
	public function initParams($params)
	{
		$this->orderUtkId = $params['orderUtkId'];
		$this->orderIdGpts = $params['orderIdGpts'];
		$this->orderDate = $params['orderDate'];
		$this->status = $params['status'];
		$this->agencyId = $params['agencyId'];
		$this->agencyUserId = $params['agencyUserId'];
		$this->archive = $params['archive'];
		$this->vip = $params['vip'];
		$this->contractId = $params['contractId'];
		$this->blocked = $params['blocked'];
		$this->comment = $params['comment'];
		$this->companyManager = $params['companyManagerId'];
		$this->kmpManager = $params['KMPManager'];

		return true;
	}

	public function save()
	{
		$command = Yii::app()->db->createCommand();

		try {
			$res = $command->insert('kt_orders', [
				'OrderID_UTK' 	=> $this->orderUtkId,
				'OrderID_GP'	=> $this->orderIdGpts,
				'OrderDate'		=> $this->orderDate,
				'Status'		=> $this->status,
				'AgentID'		=> $this->agencyId,
				'UserID'		=> $this->agencyUserId,
				'Archive'		=> $this->archive,
/*				'DOLC'			=> $this->archive,*/
				'VIP'			=> $this->vip,
				'ContractID'	=> $this->contractId,
				'Blocked'		=> $this->blocked,
				'comment'		=> $this->comment,
				'CompanyManager'=> $this->companyManager,
				'KMPManager'	=> $this->kmpManager
			]);

		} catch (Exception $e) {
			throw new KmpDbException(
				get_class($this),
				__FUNCTION__,
				OrdersErrors::CANNOT_CREATE_ORDER,
				$command->getText(),
				$e
			);
		}

		$this->orderId = Yii::app()->db->lastInsertID;

		return $this->orderId;
	}
}
