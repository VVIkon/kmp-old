<?php

/**
 * Class TouristDocMapperForm
 * Реализует функциональность для работы со связкой данные туриста и документ туриста
 */
class TouristDocMapperForm extends KFormModel
{

	/**
	 * Ид туриста
	 * @var int
	 */
	public $touristId;

	/**
	 * Ид базовых данных туриста
	 * @var int
	 */
	public $touristBaseId;

	/**
	 * Ид документа туриста
	 * @var int
	 */
	public $touristDocId;

	/**
	 * Идентификатор заявки к которой привязан турист
	 * @var int
	 */
	public $orderId;

	/**
	 * Признак турлидера(того кто оплачивает услугу)
	 * @var int
	 */
	public $tourLeader;

	/**
	 * Признак необходимости оформления услуги виза
	 * @var bool
	 */
	public $needVisa;

	/**
	 * Признак необходимости оформления услуги страховка
	 * @var bool
	 */
	public $needInsurance;

	/**
	 * namespace для записи логов
	 * @var string
	 */
	private $_namespace;

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
				['touristBaseId,touristDocId,touristId,orderId,
					tourLeader,needVisa,needInsurance','safe']
		];
	}

	/**
	 * Создание|обновления соответствий между туристом и
	 * заявкой документом и услугамии
	 * @return bool
	 */
	public function save() {

		if (empty($this->touristId)) {
			return $this->create();
		} else {
			return $this->update();
		}
	}

	/**
	 * Создание соответствий между туристом и
	 * заявкой документом и услугамиигенерация общего ID
	 * @return bool|int|string
	 */
	protected function create() {

		$command = Yii::app()->db->createCommand();
		try {
			$res = $command->insert('kt_tourists_order', [
				'TouristIDbase' => $this->touristBaseId,
				'TouristIDdoc' => $this->touristDocId,
				'OrderID' => $this->orderId,
				'TourLeader' => $this->tourLeader,
				'Visa' => $this->needVisa,
				'Insurance' => $this->needInsurance,
			]);
		} catch (Exception $e) {

			LogHelper::log(PHP_EOL . get_class(). '.' . __FUNCTION__. PHP_EOL.
				'Невозможно создать связь туриста и документа' . print_r($this->getAttributes(),1)
				. 'Ошибка' . PHP_EOL. print_r($e->getMessage(),1),'trace',
				$this->_namespace. '.errors');
			return false;
		}

		$this->touristId = Yii::app()->db->lastInsertID;

		return $this->touristId;
	}

	/**
	 * Обновление соответствий между туристом и
	 * заявкой документом и услугамиигенерация общего ID
	 * @return bool|int|string
	 */
	private function update() {

		$command = Yii::app()->db->createCommand();

		try {
			$res = $command->update('kt_tourists_order', [
				'TouristIDbase' => $this->touristBaseId,
				'TouristIDdoc'  => $this->touristDocId,
				'OrderID'    	=> $this->orderId,
				'TourLeader'   	=> $this->tourLeader,
				'Visa'     		=> $this->needVisa,
				'Insurance'     => $this->needInsurance,
			], 'TouristID = :touristId', [':touristId' => $this->touristId]);
		} catch(Exception $e) {

			LogHelper::log(PHP_EOL . get_class(). '.' . __FUNCTION__. PHP_EOL.
				'Невозможно обновить данные туриста ' . print_r($this->getAttributes(),1)
				. 'Ошибка' . PHP_EOL. print_r($e->getMessage(),1),'trace',
				$this->_namespace. '.errors');
			return false;
		}

		return true;
	}

	/**
	 * Проверка необходимости оформления услуг
	 * @return bool
	 */
	public function needToIssueService()
	{
		$additionalServicesInfo = $this->getAddtionalServicesInfo();

		foreach ($additionalServicesInfo as $additionalServiceInfo) {

			if ($additionalServiceInfo == 1 ) {
				return true;
			}
		}
	}

	/**
	 * Получить признаки необходимости оформления дополнительных услуг
	 * @return array
	 */
	private function getAddtionalServicesInfo() {
		return [
			'needVisa' => $this->needVisa,
			'needInsurance' => $this->needInsurance
		];
	}
}