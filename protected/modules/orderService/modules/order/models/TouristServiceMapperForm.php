<?php

/**
 * Class TouristServiceMapperForm
 * Реализует функциональность для работы с привязкой туриста к услуге
 */
class TouristServiceMapperForm extends KFormModel
{

	/**
	 * Ид туриста
	 * @var int
	 */
	public $touristId;

	/**
	 * Идентифкаторы привязанных сервисов к туристу
	 * @var array
	 */
	private $servicesIds;

	/**
	 * namespace для записи логов
	 * @var string
	 */
	private $_namespace;

	/**
	 * Конструктор объекта
	 * @param array $values
	 */
	public function __construct() {
		$this->servicesIds = [];
	}

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return [
				['touristId','safe']
		];
	}

	/**
	 * Сохранение соответствия туриcта услуге
	 * @return bool
	 */
	public function save() {

		if (count($this->servicesIds) > 0) {
			foreach ($this->servicesIds as $serviceId) {
				$this->mapService($serviceId);
			}
		}
	}

	/**
	 * Сохранение соответствия услуги туриcту
	 * @return bool
	 */
	public function mapService($serviceId, $tourLeader = 0) {

		$command = Yii::app()->db->createCommand();
		try {
			$res = $command->insert('kt_orders_services_tourists', array(
				'ServiceID' => $serviceId,
				'TouristID' => $this->touristId,
			));
		} catch (Exception $e) {
			LogHelper::log(PHP_EOL . get_class(). '.' . __FUNCTION__. PHP_EOL.
				'Невозможно создать связь туриста и услуги' . print_r($this->getAttributes(),1)
				. 'Ошибка' . PHP_EOL. print_r($e->getMessage(),1),'trace',
				$this->_namespace. '.errors');
			return false;
		}

		return true;
	}

	/**
	 * Удаление соответствия услуги туриcту
	 * @return bool
	 */
	public function unmapService($serviceId) {

		if (empty($serviceId) || empty($this->touristId)) {
			return false;
		}

		try {
			$command = Yii::app()->db->createCommand();

			$res = $command->delete('kt_orders_services_tourists',
				'ServiceID = :serviceId and TouristID = :touristId' ,
				[':serviceId' => $serviceId, ':touristId' => $this->touristId]);

		} catch (Exception $e) {

			LogHelper::log(PHP_EOL . get_class(). '.' . __FUNCTION__. PHP_EOL.
				'Невозможно удалить связь туриста и услуги' . print_r($this->getAttributes(),1)
				. 'Ошибка' . PHP_EOL. print_r($e->getMessage(),1),'trace',
				$this->_namespace. '.errors');
			return false;
		}

		return true;
	}

	/**
	 * Удаление всех соответствий услуг туриcту
	 * @return bool
	 */
	public function unmapServices() {

		if (empty($this->touristId)) {
			return false;
		}

		try {
			$command = Yii::app()->db->createCommand();

			$res = $command->delete('kt_orders_services_tourists',
				'TouristID = :touristId' , [':touristId' => $this->touristId]);

		} catch (Exception $e) {

			LogHelper::log(PHP_EOL . get_class(). '.' . __FUNCTION__. PHP_EOL.
				'Невозможно удалить связи туриста с услугами' . print_r($this->getAttributes(),1)
				. 'Ошибка' . PHP_EOL. print_r($e->getMessage(),1),'trace',
				$this->_namespace. '.errors');
			return false;
		}

		return true;
	}

	/**
	 * Привязать туриста к заявке
	 * @param $serviceId
	 * @return bool
	 */
	public function linkTouristToService($serviceId) {

		if (empty($serviceId) || empty($this->touristId)) {
			return false;
		}

		$links = $this->getTouristLinks();

		$isLinked = false;

		if (!empty($links) && count($links) > 0) {
			foreach ($links as $link) {
				if ($link['ServiceID'] == $serviceId && $link['TouristID'] == $this->touristId) {
					$isLinked = true;
				}
			}
		}

		if  (!$isLinked) {
			$this->mapService($serviceId);
		}
	}

	/**
	 * Получить связи туриста с услугами
	 * @return array|bool|CDbDataReader
	 */
	public function getTouristLinks() {

		if (empty($this->touristId)) {
			return false;
		}

		try {
			$command = Yii::app()->db->createCommand()
				->select('*')
				->from('kt_orders_services_tourists ordsvctour')
				->where('ordsvctour.TouristID = :touristId', array(':touristId' => $this->touristId));
			$linkedServices = $command->queryAll();
		} catch (Exception $e) {

			LogHelper::log(PHP_EOL . get_class(). '.' . __FUNCTION__. PHP_EOL .
				'Невозможно получить связи туриста'. print_r($this->touristId,1)
				. 'Ошибка' . PHP_EOL. print_r($e->getMessage(),1),'trace',
				$this->_namespace. '.errors');

			return false;
		}

		return $linkedServices;
	}

	/**
	 * Получить установленные связи
	 */
	public function getServicesIds() {
		return $this->servicesIds;
	}

	/**
	 * Добавить связанную услугу
	 * @param $serviceId
	 * @return bool
	 */
	public function addServiceId($serviceId) {

		if (!in_array($serviceId,$this->servicesIds)) {
			$this->servicesIds[] = $serviceId;
			return true;
		}
		return false;
	}

	/**
	 * Удалить связанную услугу
	 * @param $serviceId
	 * @return bool
	 */
	public function removeServiceId($serviceId) {

		$index = array_search($serviceId, $this->servicesIds);
		if ($index !== false) {

			unset($this->servicesIds[$index]);
			return true;
		}

		return false;
	}
}