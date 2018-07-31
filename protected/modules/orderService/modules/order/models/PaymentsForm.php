<?php

/**
 * Class PaymentsForm
 * Реализует функциональность для работы с данными оплат счётов, выставленных по услугам заявки
 */
class PaymentsForm extends CFormModel
{
	/**
	 * Получение оплат по указанной услуге
	 * @param $svcId идентификатор услуги
	 * @return string сумма оплат
	 */
	public static function getServiceSumByPayments($svcId)	{
		$command = Yii::app()->db->createCommand()
			->select('sum(kt_payments_invoices.Amount) serviceSum')
			->from('kt_payments_invoices')

			->leftJoin('kt_invoices_services invsvc',
				' invsvc.InvoiceID = kt_payments_invoices.InvoiceID')

			->where('invsvc.ServiceID=:svcId', array(':svcId'=> $svcId))
			->group('invsvc.ServiceID');

		$row = $command->queryRow();

		if (isset($row['serviceSum'])) {
			$sum = $row['serviceSum'];
		} else {
			$sum = 0;
		}

		return $sum;
	}

	/**
	 * Получить буквенный код валюты оплаты указанной услуги
	 * @param $svcId идентификатор услуги
	 * @return string | null буквенный код
	 */
	public static function getServicePaymentCurrency($svcId) {

		$command = Yii::app()->db->createCommand()
			->select('refcur.CurrencyCode')
			->from('kt_payments_invoices')

			->leftJoin('kt_invoices_services invsvc',
				' invsvc.InvoiceID = kt_payments_invoices.InvoiceID')
			->leftJoin('kt_ref_currencies refcur',
				' kt_payments_invoices.CurrencyID = refcur.CurrencyID')

			->where('invsvc.ServiceID=:svcId', array(':svcId'=> $svcId))
			->group('invsvc.ServiceID');

		$row = $command->queryRow();

		$cur = (isset($row['CurrencyCode'])) ? $row['CurrencyCode'] : null;

		return $cur;
	}

	/**
	 * Получить данные оплаты по ид в КД
	 * @param $paymentId int
	 * @return mixed
	 */
	public static function getPaymentById($paymentId) {

		$command = Yii::app()->db->createCommand()
			->select('PaymentID')
			->from('kt_payments')

			->where('PaymentID=:paymentUtkId', array(':paymentUtkId'=> $paymentId));

		$row = $command->queryRow();

		return $row;
	}

	/**
	 * Получить данные оплаты по ид в УТК
	 * @param $paymentUtkId int
	 * @return mixed
	 */
	public static function getPaymentByUtkId($paymentUtkId) {

		$command = Yii::app()->db->createCommand()
			->select('PaymentID')
			->from('kt_payments')

			->where('PaymentID_UTK=:paymentUtkId', array(':paymentUtkId'=> $paymentUtkId));

		$row = $command->queryRow();

		return $row;
	}

	/**
	 * Получить данные оплаты по номеру счёта
	 * @param $invoiceId
	 */
	public static function getPaymentByInvoiceId($invoiceId) {

		$command = Yii::app()->db->createCommand()
			->select('PaymentID')
			->from('kt_payments_invoices')
			->where('InvoiceID=:invoiceId', array(':invoiceId' => $invoiceId));

		$row = $command->queryRow();

		return $row;
	}
}