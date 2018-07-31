<?php

/**
 * Class InvoiceServiceForm
 * Реализует функциональность для работы со связями счетов и услуг заявок
 */
class InvoiceServiceForm extends KFormModel
{
    /**
     * Ид счёта
     * @var int
     */
    public $invoiceId;

    /**
     * Ид оплачиваемой услуги
     * @var int
     */
    public $serviceId;

    /**
     * Ид оплачиваемой услуги в УТК
     * @var int
     */
    public $serviceUtkId;

    /**
     * Сумма оплачиваемой услуги
     * @var float
     */
    public $servicePrice;

    /**
     * Комиссия агента за частичную оплату
     * @var float
     */
    public $partlyPaymentCommission;

    /**
     * Валюта счёта для оплаты услуги
     * @var int
     */
    public $currencyId;

    /**
     * Признак частичной оплаты
     * @var int
     */
    public $partial;

    /**
     * Конструктор объекта
     * @param array $values
     */
    public function __construct()
    {
    }

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules()
    {
        return [
            ['invoiceId, serviceId, servicePrice,serviceUtkId, partlyPaymentCommission,
				currencyId, partial', 'safe'
            ]
        ];
    }

    /**
     * Создания связи услуги и счёта
     * @return null
     */
    public function create()
    {

        $command = Yii::app()->db->createCommand();

        $res = $command->insert('kt_invoices_services', [
            'InvoiceID' => $this->invoiceId,
            'ServiceID' => $this->serviceId,
            'ServicePrice' => $this->servicePrice,
            'AgentCommissionPartly' => $this->partlyPaymentCommission,
            'CurrencyID' => $this->currencyId,
            'Partial' => $this->partial
        ]);

        return Yii::app()->db->lastInsertID;
    }

    /**
     * Удаление связей между счётом и услугами
     * @return null
     */
    public function removeInvoiceServices($invoiceId)
    {

        if (empty($invoiceId)) {
            return false;
        }

        $command = Yii::app()->db->createCommand();

        $res = $command->delete('kt_invoices_services',
            'InvoiceID = :invoiceId', [':invoiceId' => $invoiceId]);

        return true;
    }

    /**
     * Получение позиций в счетах по указанной услуге
     * @param $serviceId ид услуги
     * @return bool | array
     */
    public static function getServiceInvoices($serviceId)
    {

        if (empty($serviceId)) {
            return false;
        }

        $command = Yii::app()->db->createCommand()
            ->select('kt_invoices_services.invoiceID invoiceId, ServiceID serviceId, ServicePrice servicePrice,
						 AgentCommissionPartly agentCommissionPartly,
						 kt_invoices_services.CurrencyID currencyId, Partial partial, invoices.Status invoiceStatus')
            ->leftJoin('kt_invoices invoices', 'kt_invoices_services.InvoiceID = invoices.InvoiceID')
            ->from('kt_invoices_services')
            ->where('ServiceID = :serviceId', [':serviceId' => $serviceId]);


        return $command->queryAll();
    }

    /**
     * Получить информацию о счёте из БД
     * @param $invoiceId
     * @return bool
     */
    public static function getInvoiceInfo($invoiceId)
    {
        if (empty($invoiceId)) {
            return false;
        }

        $command = Yii::app()->db->createCommand()
            ->select('invoiceID invoiceId, ServiceID serviceId, ServicePrice servicePrice,
						 AgentCommissionPartly agentCommissionPartly,
						 CurrencyID currencyId, Partial partial')
            ->from('kt_invoices_services')
            ->where('invoiceID = :invoiceId', [':invoiceId' => $invoiceId]);

        return $command->queryAll();
    }

    /**
     * Получить идентификаторы услуг по которым выставлен счёт
     * @param $invoiceId int идентифмкатор счёта
     * @return array идентификаторы услуг
     */
    public static function getInvoiceServicesIds($invoiceId)
    {
        $invoiceInfo = self::getInvoiceInfo($invoiceId);
        $servicesIds = [];
        foreach ($invoiceInfo as $invoiceServiceInfo) {
            $servicesIds[] = $invoiceServiceInfo['serviceId'];
        }
        return $servicesIds;

    }

    /**
     * Получение оплаченной суммы по указанной услуге
     * @param $serviceId
     * @param $currencyId int валюта, в которой надо вернуть сумму всех платежей
     * @return float
     */
    public static function getServicePaidSumInCurrencyId($serviceId, $currencyId)
    {
        $serviceInvoices = self::getServiceInvoices($serviceId);

        $CurrancyRates = CurrencyRates::getInstance();

        $amount = 0.0;
        foreach ($serviceInvoices as $serviceInvoice) {
            if ($serviceInvoice['invoiceStatus'] == InvoiceForm::STATUS_PAYED_COMPLETELY) {
                $amount += $CurrancyRates->calculateInCurrencyByIds($serviceInvoice['servicePrice'], $serviceInvoice['currencyId'], $currencyId);
            }
        }

        return $amount;
    }
}