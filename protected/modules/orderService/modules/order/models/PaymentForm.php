<?php

/**
 * Class PaymentForm
 * Реализует функциональность для работы с данными оплаты счёта
 */
class PaymentForm extends KFormModel
{
	/**
	 * Идентифкатор оплаты в КТ
	 * @var
	 */
	public $paymentId;

	/**
	 * Идентификатор оплаты в УТК
	 * @var
	 */
	public $paymentUtkId;

	/**
	 * Дата поступления оплаты
	 * @var
	 */
	public $paymentDate;

	/**
	 * Сумма оплаты
	 * @var
	 */
	public $paymentAmount;

	/**
	 * Валюта оплаты
	 * @var
	 */
	public $currencyId;

	/**
	 * Способ оплаты
	 * @var
	 */
	public $paymentType;

	/**
	 * Статус транзакции оплаты
	 * @var
	 */
	public $paymentStatus;

	/**
	 * Ид оплачиваемого счёта
	 * @var
	 */
	public $invoiceId;
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
			['paymentId,paymentUtkId,paymentDate,paymentAmount,currencyId
				paymentType,paymentStatus,invoiceId','safe'
			]
		];
	}

	/**
	 * Создание связи оплаты и счёта
	 * @return null
	 */
	public function create() {

		$command = Yii::app()->db->createCommand();

		$res = $command->insert('kt_payments', [
			'PaymentID_UTK'     => $this->paymentUtkId,
			'PaymentDate'       => $this->paymentDate,
			'Amount'       		=> $this->paymentAmount,
			'PaymentType' 		=> $this->paymentType,
			'Status'        	=> $this->paymentStatus,
			'CurrencyID'        => $this->currencyId,
		]);

		$paymentId = Yii::app()->db->lastInsertID;

		if (!$paymentId) {
			return false;
		}

		$this->paymentId = $paymentId;

		$res = $command->insert('kt_payments_invoices', [
			'PaymentID'	 => $this->paymentId,
			'InvoiceID'  => $this->invoiceId,
			'Amount'     => $this->paymentAmount,
			'CurrencyID' => $this->currencyId,
		]);

		if (!$res) {
			return false;
		}

		return $paymentId;
	}

	/**
	 * Обновление данных связи оплаты и счёта
	 * @return null
	 */
	public function update() {

		$command = Yii::app()->db->createCommand();

		try {

			$res = $command->update('kt_payments', [
				'PaymentID_UTK'	=> $this->paymentUtkId,
				'PaymentDate' 	=> $this->paymentDate,
				'Amount' 		=> $this->paymentAmount,
				'PaymentType' 	=> $this->paymentType,
				'Status' 		=> $this->paymentStatus,
				'CurrencyID' 	=> $this->currencyId,
			], 'PaymentID =:paymentId', [':paymentId' => $this->paymentId]);
		} catch (Exception $e) {
			return false;
		}

		try {
			$res = $command->update('kt_payments_invoices', [
				'PaymentID'	 => $this->paymentId,
				'InvoiceID'  => $this->invoiceId,
				'Amount'     => $this->paymentAmount,
				'CurrencyID' => $this->currencyId,
			],'PaymentID = :paymentId', [':paymentId' => $this->paymentId]);
		} catch (Exception $e) {
			return false;
		}

		return $this->paymentId;
	}

}