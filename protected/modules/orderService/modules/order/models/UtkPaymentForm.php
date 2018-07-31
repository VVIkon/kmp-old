<?php

/**
 * Class UtkPaymentForm
 * Реализует функциональность для работы с данными об оплате из УТК
 */
class UtkPaymentForm extends KFormModel
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
	 * Ид оплаты в КТ
	 * @var int
	 */
	public $paymentId;

	/**
	 * Ид оплаты в УТК
	 * @var float
	 */
	public $paymentUtkId;

	/**
	 * Дата создания платёжной транзакции
	 * @var int datetime
	 */
	public $paymentDate;

	/**
	 * Сумма оплаты
	 * @var float
	 */
	public $paymentAmount;

	/**
	 * Валюта оплаты
	 * @var int
	 */
	public $currencyId;

	/**
	 * Статус платёжной транзакции
	 * @var int
	 */
	public $paymentStatus;

	/**
	 * Способ оплаты
	 * @var int
	 */
	public $paymentType;

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
				['orderId,orderUtkId,orderDate,invoiceId,invoiceUtkId,invoiceDate,
					paymentId,paymentUtkId,paymentDate,paymentAmount,
					currencyId,paymentStatus,paymentType','safe'
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

        $CurrencyRates = CurrencyRates::getInstance();

		$this->currencyId = $CurrencyRates->getIdByCode($this->currencyId);
	}
}