<?php

/**
 * Class SendRequestInvoiceToUtkDelegate
 * Делегат для отправки запроса в УТК для на создание счёта
 */
class SendRequestInvoiceToUtkDelegate extends WorkflowDelegate
{
    /**
     * Запуск метода выполнения действия делегата
     * @param $params
     * @return mixed
     */
    public function run($params, $module)
    {
        $utkModule = $params['utkModule'];

        $utkClient = $utkModule->UtkClient($utkModule);
//        $params['invoiceParams']['orderIdUTK'] = '000032075';
//        var_dump($params['invoiceParams']);
//        exit;

        $response = $utkClient->makeRestRequest(UtkOrdersClient::REQUEST_DO_INVOICE, $params['invoiceParams']);

//        var_dump($response);
//        exit;

        if ($response) {
            return ['invoiceId' => $params['invoiceParams']['invoiceId']];
        } else {
            throw new KmpException(
                get_class(), __FUNCTION__,
                OrdersErrors::CANNOT_SEND_INVOICE_UTK_COMMAND,
                ['invoiceParams' => print_r($params['invoiceParams'], 1)]
            );
        }
    }

}

