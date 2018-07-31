<?php

/**
 * Class ServicesForm
 * Реализует функциональность для работы с услугами
 */
class ServicesForm extends KFormModel
{
	const SERVICE_STATUS_NEW = 0;
	const SERVICE_STATUS_W_BOOKED = 1;
	const SERVICE_STATUS_BOOKED = 2;
	const SERVICE_STATUS_W_PAID = 3;
	const SERVICE_STATUS_P_PAID = 4;
	const SERVICE_STATUS_PAID = 5;
	const SERVICE_STATUS_CANCELLED = 6;
	const SERVICE_STATUS_VOIDED = 7;
	const SERVICE_STATUS_DONE = 8;
	const SERVICE_STATUS_MANUAL = 9;

	/**
	 * Конструктор объекта
	 * @param array $values
	 */
	public function __construct() {

		parent::__construct();
		$this->validate();
	}

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return [];

	}

	/**
	 * Получение услуги по ид УТК
	 * @return null
	 */
	public static function getServiceByUtkId($serviceUtkId) {

		if (empty($serviceUtkId)) {
			return null;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_orders_services')
			->where('ServiceID_UTK = :serviceUtkId', array(':serviceUtkId' => $serviceUtkId));

		return $command->queryRow();
	}

	/**
	 * Получение услуги по ид КТ
	 * @return null
	 */
	public static function getServiceById($serviceId) {

		if (empty($serviceId)) {
			return null;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_orders_services')
			->where('ServiceID = :serviceId', array(':serviceId' => $serviceId));

		return $command->queryRow();
	}

	/**
	 * Установить комиссию агентства для услуги
	 * @param $commissionSum
	 * @param $serviceId
	 * @return bool
	 */
	public static function setServiceCommission($commissionSum, $serviceId) {

		$command = Yii::app()->db->createCommand();

	try {
			$res = $command->update('kt_orders_services', [
				'AgentCommission'     => $commissionSum,

			],'ServiceID = :serviceId', [':serviceId' => $serviceId]);
		} catch (Exception $e) {
			return false;
		}

		return true ;
	}
}