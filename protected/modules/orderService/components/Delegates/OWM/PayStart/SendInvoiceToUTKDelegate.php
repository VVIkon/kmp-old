<?php

/**
 * Отправка счета в УТК
 */
class SendInvoiceToUTKDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        // Выставить счёт в УТК (только для клиента с типом 2 (агент))
        if ($OrderModel->getCompany()->isAgent() || $OrderModel->getCompany()->isDirectSales()) {
            $Invoice = $this->getObjectFromContext(Invoice::class);
            $UTKInvoice = UTKInvoiceRepository::getByInvoiceId($Invoice->getInvoiceId());

            if (is_null($UTKInvoice)) {
                $this->params['UTKSendSuccess'] = false;
                return;
            }

            $utkClient = new UtkClient(Yii::app()->getModule('orderService')->getModule('utk'));
            $response = $utkClient->makeRestRequest(UtkOrdersClient::REQUEST_DO_INVOICE, $UTKInvoice->toArray());

            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Ответ из УТК по счету', '',
                $response,
                LogHelper::MESSAGE_TYPE_INFO,
                'system.orderservice.utkrequests'
            );

            $this->params['UTKSendSuccess'] = isset($response['status']) && ($response['status'] == 0);
        } else {
            $this->params['UTKSendSuccess'] = true;
        }
    }
}