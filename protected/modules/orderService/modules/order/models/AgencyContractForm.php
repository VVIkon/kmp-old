<?php

/**
 * Class AgencyContractForm
 * Реализует функциональность для работы с данными о пользователе агентства
 */
class AgencyContractForm extends KFormModel
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
	 * Идентифкатор агентского договора в КТ
	 * @var int
	 */
	public $contractId;

	/**
	 * Идентифкатор агентского договора в УТК
	 * @var string
	 */
	public $contractUTKId;

	/**
	 * Идентифкатор агентства
	 * @var
	 */
	public $agencyId;

	/**
	 * Описание агентского договора
	 * @var string
	 */
	public $contract;

	/**
	 * Дата заключения агентского договора
	 * @var string
	 */
	public $contractDate;

	/**
	 * Дата окончания действия агентского договора
	 * @var string
	 */
	public $contractExpiry;

	/**
	 * Комиссия агентства по договору
	 * @var float
	 */
	public $commission;

	/**
	 * Код валюты
	 * @var int
	 */
	public $currencyId;

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
				'agentId, contract, contractDate, contractExpiry, contractId,
				contractUTKId, commission, currencyId','safe'
			]
		];
	}

	/**
	 * Получить договор агентства по его идентифкатору в КТ
	 * @param $contractId идентифкатор договора в КТ
	 * @return bool
	 */
	public static function getContractById($contractId) {

		if (empty($contractId)) {
			return false;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_contracts')
			->where('ContractID = :contractId',[':contractId' => $contractId]);

		return $command->queryRow();
	}

	/**
	* Получить ID контракта по ID УТК
	* @param string $contractUtkId ID контракта УТК
	* @return int ID контракта КТ
	*/
	public static function getContractIdByUtkId($contractUtkId) {
		if (empty($contractUtkId)) {
			return false;
		}

		$command = Yii::app()->db->createCommand()
			->select('ContractID')
			->from('kt_contracts')
			->where('ContractID_UTK = :contractUtkId',[':contractUtkId' => $contractUtkId]);

		return $command->queryScalar();
	}

	/**
	 * Получить информацию о договоре по его идентифкатору в УТК
	 * @param $contractUtkId идентифкатор договора в УТК
	 * @return null| object AgencyContractForm
	 */
	public function getContractByUtkId($contractUtkId) {

		if (empty($contractUtkId)) {
			return false;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_contracts')
			->where('ContractID_UTK = :contractUtkId',[':contractUtkId' => $contractUtkId]);

		$contractInfo = $command->queryRow();

		if (empty($contractInfo)) {
			return false;
		}

		$contract = new AgencyContractForm($this->_namespace);

		$contract->setParamsMapping([
			'ContractID' 		=> 'contractId',
    		'ContractID_UTK' 	=> 'contractUTKId',
    		'AgentID' 			=> 'agencyId',
    		'Contract' 			=> 'contract',
    		'ContractDate' 		=> 'contractDate',
    		'ContractExpiry' 	=> 'contractExpiry',
    		'Commission' 		=> 'commission',
    		'CurrencyID'		=> 'currencyId'
		]);

		$contract->setAttributes($contractInfo);

		return $contract;
	}

	/**
	 * Создание|изменение данных об агентском договоре
	 * @return bool
	 */
	public function save() {

		if (empty($this->contractUTKId)) {
			return false;
		}

		$existedContract = $this->getContractByUtkId($this->contractUTKId);

		if (!$existedContract) {
			$operation = self::OPERATION_CREATE;
			$result = $this->createContract();
		} else {
			$operation = self::OPERATION_UPDATE;
			$this->setAttributesIfNull($existedContract);
			$this->contractId = $existedContract->contractId;
			$result = $this->updateContract();
		}

		return ['contractId' => $result, 'operation' => $operation];
	}

	/**
	 * Установка идентифкатора агентства
	 * @param $agentId
	 */
	public function setAgencyId($agentId) {
		$this->agencyId = $agentId;
	}

	/**
	 * Создание данных договоре агентства в БД
	 * @return bool
	 */
	public function createContract() {

		$command = Yii::app()->db->createCommand();

		try {
			$res = $command->insert('kt_contracts', [
				'ContractID_UTK' 	=> $this->contractUTKId,
				'AgentID'			=> $this->agencyId,
				'Contract' 			=> $this->contract,
				'ContractDate' 		=> $this->contractDate,
				'ContractExpiry' 	=> $this->contractExpiry,
				'Commission' 		=> $this->commission,
				'CurrencyID' 		=> $this->currencyId
			]);
		} catch (Exception $e) {

			LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
				'Невозможно записать данные договора агентства ' . print_r($e->getMessage(), 1), 'trace',
				$this->_namespace . '.errors');
			return false;
		}
		$this->contractId = Yii::app()->db->lastInsertID;

		return $this->contractId;
	}

	/**
	 * Обновление данных агентского договора
	 * @return bool
	 */
 	protected function updateContract() {

		$command = Yii::app()->db->createCommand();

		try {
			$res = $command->update('kt_contracts', [
				'ContractID_UTK' 	=> $this->contractUTKId,
				'AgentID'			=> $this->agencyId,
				'Contract' 			=> $this->contract,
				'ContractDate' 		=> $this->contractDate,
				'ContractExpiry' 	=> $this->contractExpiry,
				'Commission' 		=> $this->commission,
				'CurrencyID' 		=> $this->currencyId
			],'ContractID = :contractId', [':contractId' => $this->contractId]);
		} catch (Exception $e) {

			LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
				'Невозможно записать данные договора агентства ' . print_r($e->getMessage(), 1), 'trace',
				$this->_namespace . '.errors');
			return false;
		}

		return $this->contractId;
	}

}
