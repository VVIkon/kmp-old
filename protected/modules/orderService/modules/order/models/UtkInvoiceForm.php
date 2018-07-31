<?php

/**
 * Class UtkInvoiceForm
 * Реализует функциональность для работы с данными о счёте из УТК
 */
class UtkInvoiceForm extends KFormModel
{
	/**
	 * Ид заявки счёта
	 * @var int
	 */
	public $orderId;

	/**
	 * Ид заявки счёта из УТК
	 * @var string
	 */
	public $orderUtkId;

	/**
	 * Дата создания заявки в УТК
	 * @var string
	 */
	public $orderDate;

	/**
	 * Ид счёта в КТ
	 * @var int
	 */
	public $invoiceId;

	/**
	 * Ид счёта в УТК
	 * @var int
	 */
	public $invoiceUtkId;

	/**
	 * Дата выставления счёта в УТК
	 * @var datetime
	 */
	public $invoiceDate;

	/**
	 * Статус счёта
	 * @var int
	 */
	public $invoiceStatus;

	/**
	 * Сумма по счёту
	 * @var float
	 */
	public $invoiceAmount;

	/**
	 * Валюта оплаты счёта
	 * @var int
	 */
	public $currencyId;

	/**
	 * Услуги счёта
	 * @var array
	 */
	public $invoiceServices;


	/**
	 * Конструктор объекта
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
				['orderId,orderUtkId,orderDate,invoiceId,invoiceUtkId,
					invoiceDate,invoiceDate,invoiceStatus,invoiceAmount,
					invoiceServices','safe'
				]
		];
	}

	/**
	 * Установка свойств объекта
	 * @param array $params
	 * @param bool|true $safeOnly
	 */
	public function setAttributes($params, $safeOnly = true) {

		parent::setAttributes($params, $safeOnly);

		if (empty($this->invoiceServices) ||  count($this->invoiceServices)  == 0) {
			return;
		}

		$CurrencyRates = CurrencyRates::getInstance();

		foreach ($this->invoiceServices as $key => $invoiceService) {

			$this->invoiceAmount += (!empty($invoiceService['invoicePrice']))
										? $invoiceService['invoicePrice']
										: 0;

			$this->invoiceServices[$key]['currency'] = $CurrencyRates->getIdByCode($invoiceService['currency']);

			if (empty($this->currencyId) && !empty($invoiceService['currency'])) {
				$this->currencyId = $CurrencyRates->getIdByCode($invoiceService['currency']);
			}

		}
	}

	/**
	 * Вывод свойств счёта в формате КТ
	 * @return array
	 */
	public function toKtAttributes() {
		$info = $this->getAttributes();
		$info['invoiceStatus'] = StatusesMapperHelper::getKtByUTKStatus(
			$this->invoiceStatus,
			StatusesMapperHelper::STATUS_TYPE_INVOICE,
			''
		);

		return $info;
	}
}