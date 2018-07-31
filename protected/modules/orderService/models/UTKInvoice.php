<?php

/**
 * Сущность счета УТК
 */
class UTKInvoice
{
    /**
     * @var InvoiceService[]
     */
    protected $InvoiceServices;

    /**
     * @param InvoiceService[] $InvoiceServices
     */
    public function setInvoiceServices($InvoiceServices)
    {
        $this->InvoiceServices = $InvoiceServices;
    }

    /**
     * Получение счета УТК в виде массива
     * @return array
     */
    public function toArray()
    {
        $response = [];

        $servicesData = null;

        if (count($this->InvoiceServices)) {
            $response['orderId'] = (string)$this->InvoiceServices[0]->getOrdersService()->getOrderModel()->getOrderId();
            $response['orderIdUTK'] = (string)$this->InvoiceServices[0]->getOrdersService()->getOrderModel()->getOrderID_UTK();
            $response['orderDate'] = (string)$this->InvoiceServices[0]->getOrdersService()->getOrderModel()->getOrderDate()->format('Y-m-d\TH:i:s');
            $response['invoiceId'] = (string)$this->InvoiceServices[0]->getInvoiceID();
            $response['invoiceIdUTK'] = (string)$this->InvoiceServices[0]->getInvoice()->getInvoiceIDUTK();
            $response['invoiceDate'] = $this->InvoiceServices[0]->getInvoice()->getInvoiceDate()->format('Y-m-d\TH:i:s');
            $response['status'] = (int)$this->InvoiceServices[0]->getInvoice()->getInvoiceStatus();

            foreach ($this->InvoiceServices as $InvoiceService) {
                $servicesData[] = [
                    'serviceId' => (string)$InvoiceService->getOrdersService()->getServiceID(),
                    'invoicePrice' => (string)$InvoiceService->getServicePrice(),
                    'serviceIdUTK' => (string)$InvoiceService->getOrdersService()->getServiceIDUTK(),
                    'serviceName' => (string)$InvoiceService->getOrdersService()->getServiceName(),
                    'currency' => (string)$InvoiceService->getCurrency()->getCode(),
                    'commission' => 0,
                    'paymentType' => (string)(($InvoiceService->getPartial()) ? 2 : 1)
                ];
            }
        } else {
            return [];
        }

        $response['serviceTableShort'] = $servicesData;

        return $response;
    }
}