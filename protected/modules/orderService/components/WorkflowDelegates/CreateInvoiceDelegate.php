<?php

/**
 * Class CreateInvoiceDelegate
 * Делегат для создания объекта счёта
 */
class CreateInvoiceDelegate extends WorkflowDelegate
{
    /**
     * Запуск метода выполнения действия делегата
     * @param $params
     * @return mixed
     */
    public function run($params, $module)
    {
        $orderModule = $params['orderModule'];

        $ordersMgr = $orderModule->OrdersMgr($orderModule);

        $utkInvoiceParams = $ordersMgr->setInvoice($params);

        return ['invoiceParams' => $utkInvoiceParams];
    }

}

