<?php

/**
 * Отель:
 * Проверить наличие штрафов в офере сервиса за отмену
 * и выставить счет на сумму отмены вызвав OWM.PayStart (для выставления счета)
 */
class BookCancelSetInvoiceDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        // проверим нужно ли вообще выставлять счет
        if ($params['createPenaltyInvoice'] && $this->params['serviceCancelled']) {
            $this->addLog('Выставляем счет за отмену...');
        } else {
            $this->addLog('Счет за отмену не выставляем');
            return;
        }

        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        // если не отель, то выходим
        if ($OrdersServices->getServiceType() != 1 && $params['serviceCancelled']) {
            return;
        }

        // найдем активный штраф за отмену
        $offer = $OrdersServices->getOffer();

        // выставим счет на сумму штрафа
        $cancelPenalty = $offer->getActiveCancelPenalty();

        if ($cancelPenalty) {
            $saleCurrency = $OrdersServices->getSaleCurrency();
            $penaltyCurrency = CurrencyStorage::findByString($cancelPenalty->getCurrencyCode());
            $invoiceAmount = CurrencyRates::getInstance()->calculateInCurrencyByIds($cancelPenalty->getAmount(), $penaltyCurrency->getId(), $saleCurrency->getId());

            // зачислим штраф в услугу
            $OrdersServices->createServicePenaltyFromCancelPenalty($cancelPenalty);

            $this->addLog("Найден актуальный штраф за отмену брони - $invoiceAmount {$saleCurrency->getCode()}");

            $AsyncTask = new AsyncTask();
            $AsyncTask->setModule(Yii::app()->getModule('orderService'));
            $AsyncTask->setTaskParams('orderService', 'OrderWorkflowManager',
                [
                    'action' => 'PayStart',
                    'orderId' => $OrderModel->getOrderId(),
                    'actionParams' => [
                        'currency' => $saleCurrency->getCode(),
                        'services' => [
                            [
                                'serviceId' => $OrdersServices->getServiceID(),
                                'invoicePrice' => $invoiceAmount,
                            ]
                        ]
                    ],
                    'usertoken' => '0f7369671632f427'  // магический токен для 33 пользователя
                ]
            );
            $this->setObjectToContext($AsyncTask);
        } else {
            $this->addLog('Актуальные штрафы за отмену брони не найдены, счет не выставляем');
        }
    }
}