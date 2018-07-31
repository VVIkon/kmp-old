<?php

/**
 * Class ServiceFlPnr
 * Класс для работы с параметрами Passenger Name Record (PNR) услуги авиаперелёта
 */
class ServiceFlPnr extends KFormModel
{
	/**
	 * @var int идентифкатор
	 */
	public $offerId;

	/**
	 * @var идентификатор Passenger Name Record (PNR)
	 */
	public $pnr;

	/**
	 * @var код поставщика
	 */
	public $supplierCode;

	/**
	 * @var идентификатор предложения
	 */
	public $offerKey;

	/**
	 * @var идентификатор шлюза
	 */
	public $gateId;

	/**
	 * @var идентификатор услуги поставщика
	 */
	public $serviceRef;

	/**
	 * @var идентифкатор заявки поставщика
	 */
	public $orderRef;

	/**
	 * @var PNR
	 */
	public $status;

    public $baggageData;

	/**
	 * Конструктор объекта
	 * @param array $values
	 */
	public function __construct() {	}

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return [
			['offerId, pnr, supplierCode, offerKey, gateId, serviceRef, orderRef, status, baggageData', 'safe']
		];
	}

	public function save() {

		$pnr = new self();
		if ($pnr->load($this->pnr)) {
			$this->update();
		} else {
			$this->create();
		}

	}

	/**
	 * Создание информации об объекте в БД
	 * @return mixed
	 */
	public function create() {

		$command = Yii::app()->db->createCommand();

		try {
			$res = $command->insert('kt_service_fl_pnr', [
				'PNR'     		=> $this->pnr,
				'offerID'		=> $this->offerId,
				'supplierCode'  => $this->supplierCode,
				'offerKey'      => $this->offerKey,
				'gateId'        => $this->gateId,
				'service_ref'   => $this->serviceRef,
				'order_ref'     => $this->orderRef,
                'baggageData'   => json_encode($this->baggageData),
				'status'        => $this->status,
			]);
		} catch (Exception $e) {
			throw new KmpDbException(
				get_class(),__FUNCTION__,
				OrdersErrors::CANNOT_CREATE_PNR,
				$command->getText(),
				$e
			);
		}

		return true;
	}

	/**
	 * Обновление данных об объекте в БД
	 */
	public function update() {

		$command = Yii::app()->db->createCommand();

		try {
			$res = $command->update('kt_service_fl_pnr', [
				'offerID'		=> $this->offerId,
				'offerKey'      => $this->offerKey,
				'gateId'        => $this->gateId,
				'service_ref'   => $this->serviceRef,
				'order_ref'     => $this->orderRef,
				'status'        => $this->status,

			], 'PNR = :pnr and supplierCode = :supplierCode',
				[	':pnr' => $this->pnr,
					':supplierCode' => $this->supplierCode
				]
			);
		} catch (Exception $e) {

			throw new KmpDbException(
				get_class(),
				__FUNCTION__,
				OrdersErrors::CANNOT_UPDATE_PNR,
				$command->getText(),
				$e
			);

		}
	}

	/**
	 * Инициализация объекта по данным из БД
	 * @param $pnr string идентифкатор PNR
	 * @return CDbDataReader|mixed
	 */
	public function load($pnr) {

		$command = Yii::app()->db->createCommand();

		$command->select('PNR pnr, offerID offerId, supplierCode supplierCode, offerKey offerKey,
							gateId gateId, service_ref serviceRef, order_ref orderRef, baggageData baggageData, status status');
		$command->from('kt_service_fl_pnr');
		$command->where('PNR = :pnr',[':pnr' => $pnr]);

		try {
			$pnrInfo = $command->queryRow();
		} catch (Exception $e) {
			throw new KmpDbException(
				get_class(),
				__FUNCTION__,
				OrdersErrors::CANNOT_GET_PNR,
				$command->getText(),
				$e
			);
		}

		$this->setAttributes($pnrInfo);
		return $pnrInfo;
	}

    public function getBaggageData()
    {
        return json_decode($this->baggageData, true);
	}

	/**
	 * Загрузить данные по указанному идентификатору предложения
	 * @param $offerKey
	 * @return bool
	 */
	public function loadByOfferId($offerId)
	{
		$command = Yii::app()->db->createCommand();

		$command->select('PNR pnr, offerID offerId, supplierCode supplierCode, offerKey offerKey,
							gateId gateId, service_ref serviceRef, order_ref orderRef, baggageData baggageData, status status');
		$command->from('kt_service_fl_pnr');
		$command->where('offerID = :offerId',[':offerId' => $offerId]);

		try {
			$pnrInfo = $command->queryRow();
		} catch (Exception $e) {
			throw new KmpDbException(
				get_class(),
				__FUNCTION__,
				OrdersErrors::CANNOT_GET_PNR,
				$command->getText(),
				$e
			);
		}

		if (empty($pnrInfo)) {
			return false;
		}

		$this->setAttributes($pnrInfo);
		return $pnrInfo;
	}
}
