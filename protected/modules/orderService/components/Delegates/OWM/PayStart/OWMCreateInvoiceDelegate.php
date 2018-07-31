<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/16/16
 * Time: 4:21 PM
 */
class OWMCreateInvoiceDelegate extends AbstractDelegate
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

        if ($OrderModel->getCompany()->isAgent() || $OrderModel->getCompany()->isDirectSales()) {
            // найдем сумму, на которую выставить счет
            $invoiceAmount = 0;

            if (count($params['services'])) {
                foreach ($params['services'] as $service) {
                    $invoiceAmount += $service['invoicePrice'];
                }
            }

            // создадим счет
            $Invoice = new Invoice();
            $Invoice->setAmount($invoiceAmount, CurrencyStorage::findByString($params['currency']));
            $Invoice->wait();

            if (!$Invoice->save()) {
                $this->setError(OrdersErrors::DB_ERROR);
                return;
            }

            // сохраним в контекст
            $this->setObjectToContext($Invoice);

            // запишем историю
            $OrderHistory = new OrderHistory();
            $OrderHistory->setObjectData($OrderModel);
            $OrderHistory->setOrderData($OrderModel);
            $OrderHistory->setActionResult(0);
            $OrderHistory->setCommentTpl('{{166}} {{sum}}');
            $OrderHistory->setCommentParams([
                'sum' => (string)$Invoice
            ]);

            // сохраним результат аудита
            $this->addOrderAudit($OrderHistory);
        }
    }
}