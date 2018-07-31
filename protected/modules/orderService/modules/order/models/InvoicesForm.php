<?php

/**
 * Class InvoicesForm
 * Реализует функциональность для работы со счетами заявок
 */
class InvoicesForm extends KFormModel
{

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
	 * Получение счёта по ид УТК
	 * @return null
	 */
	public static function getInvoiceByUtkId($invoiceUtkId = null) {

		if (empty($invoiceUtkId)) {
			return null;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_invoices')
			->where('kt_invoices.InvoiceID_UTK = :invoiceUtkId', array(':invoiceUtkId' => $invoiceUtkId));

		return $command->queryRow();
	}

	/**
	 * Получение счёта по ид КТ
	 * @return null
	 */
	public static function getInvoiceById($invoiceId = null) {

		if (empty($invoiceId)) {
			return null;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_invoices')
			->where('kt_invoices.InvoiceID = :invoiceId', array(':invoiceId' => $invoiceId));

		return $command->queryRow();
	}

}