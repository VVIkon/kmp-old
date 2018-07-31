<?php

/**
 * Class InvoiceForm
 * Реализует функциональность для работы со счетом заявки
 */
class InvoiceForm extends KFormModel
{
	const STATUS_WAIT_TO_CREATE = 1;
	const STATUS_CREATED = 2;
	const STATUS_PAYED_PARTIAL = 3;
	const STATUS_PAYED_COMPLETELY = 4;
	const STATUS_CANCELED = 5;
	/**
	 * Ид счёта
	 * @var int
	 */
	public $invoiceId;

	/**
	 * Ид счёта в УТК
	 * @var string
	 */
	public $invoiceIdUtk;

	/**
	 * Дата выставления счёта
	 * @var string
	 */
	public $invoiceDate;

	/**
	 * Ссылка на электронную
	 * копию документа счёта
	 * @var
	 */
	public $hardCopyUrl;

	/**
	 * Сумма по счёту
	 * @var float
	 */
	public $invoiceAmount;

	/**
	 * Ид валюты счёта
	 * @var int
	 */
	public $currencyId;

	/**
	 * Статус счёта
	 * @var int
	 */
	public $invoiceStatus;

	/**
	 * Дата до которой счёт является действительным
	 * @var string
	 */
	public $validTillDate;

	/**
	 * Описание предмета счёта
	 * @var string
	 */
	public $descritpion;

	/**
	 * Услуги счёта
	 * @var array
	 */
	public $invoiceServices;

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
			['invoiceId,invoiceIdUtk,invoiceDate,hardCopyUrl,
					invoiceAmount,currencyId,invoiceStatus,
					descritpion','safe'
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

		if (empty($params['invoiceServices']) || count($params['invoiceServices']) == 0) {
			return;
		}

		$this->invoiceServices = [];

		foreach ($params['invoiceServices'] as $invoiceServiceInfo) {

			$invoiceServiceInfo['invoiceId'] = $this->invoiceId;

			$invoiceService = new InvoiceServiceForm();

			$invoiceService->setParamsMapping([

				'invoicePrice' 	=> 'servicePrice',
				'serviceIdUTK' 	=> 'serviceUtkId',
				'currency' 		=> 'currencyId',
				'commission' 	=> 'partlyPaymentCommission',
				'paymentType' 	=> 'partial'
			]);

			$invoiceService->setAttributes($invoiceServiceInfo);
			$this->invoiceServices[] = $invoiceService;
		}
	}

	/**
	 * Сохранение данных о счёте в БД
	 * @return bool|string
	 */
	public function create() {

		$command = Yii::app()->db->createCommand();

		$res = $command->insert('kt_invoices', [
			'InvoiceID_UTK'     => $this->invoiceIdUtk,
			'InvoiceDate'       => $this->invoiceDate,
			'HardcopyURL'       => $this->hardCopyUrl,
			'InvoiceAmount'		=> $this->invoiceAmount,
			'CurrencyID'        => $this->currencyId,
			'Status'        	=> $this->invoiceStatus,
			'Description'      	=> $this->descritpion
		]);

		$invoiceId = Yii::app()->db->lastInsertID;

		if (!$invoiceId) {
			return false;
		}

		if (empty($this->invoiceServices) || count($this->invoiceServices) == 0) {
			return $invoiceId;
		}

		foreach ($this->invoiceServices as $invoiceService) {
			$invoiceService->invoiceId = $invoiceId;
			$invoiceService->create();
		}

		return $invoiceId;
	}


	/**
	 * Обновление данных о счёте в БД
	 * @return bool|string
	 */
	public function update() {

		$command = Yii::app()->db->createCommand();

		$res = $command->update('kt_invoices', [
			'InvoiceID_UTK'     => $this->invoiceIdUtk,
			'InvoiceDate'       => $this->invoiceDate,
			'HardcopyURL'       => $this->hardCopyUrl,
			'InvoiceAmount'		=> $this->invoiceAmount,
			'CurrencyID'        => $this->currencyId,
			'Status'        	=> $this->invoiceStatus,
			'Description'      	=> $this->descritpion
		],'InvoiceID = :invoiceId', [':invoiceId' => $this->invoiceId]);

		$invoiceService = new InvoiceServiceForm();

		$invoiceService->removeInvoiceServices($this->invoiceId);

		if (empty($this->invoiceServices) || count($this->invoiceServices) == 0) {
			return true;
		}

		foreach ($this->invoiceServices as $invoiceService) {
			$invoiceService->create();
		}

		return $this->invoiceId;
	}
}