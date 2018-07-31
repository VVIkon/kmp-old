<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/4/16
 * Time: 5:33 PM
 */
class AviaServiceCancelDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        // только для авиации отмена брони
        if ($OrdersService->getServiceType() == 2 && isset($params['serviceCancelled']) && $params['serviceCancelled']) {
            $OrdersServicesHistory = new OrdersServicesHistory();
            $OrdersServicesHistory->setOrderData($OrderModel);

//            // выберем билеты с помощью старого кода
//            $ticketForm = TicketsFactory::createTicket(2);
//            $tickets = $ticketForm->getTicketsByServiceId($OrdersService->getServiceID());
//
//            // выберем счета
//            $Invoices = $OrdersService->getInvoices();
//
//            if (count($tickets) || count($Invoices)) {
//                $OrdersService->setStatus(OrdersServices::STATUS_VOIDED);
//                $OrdersServicesHistory->setCommentTpl('{{135}} {{144}}');
//            } else {
                $OrdersService->cancel();
                $OrdersServicesHistory->setCommentTpl('{{135}} {{133}}');
//            }

            $this->params['object'] = $OrdersService->serialize();
            $OrdersService->save();

            $OrdersServicesHistory->setObjectData($OrdersService);
            $OrdersServicesHistory->setActionResult(0);
            $OrdersServicesHistory->setCommentParams([]);

            $this->addLog("Услуга № {$OrdersService->getServiceID()} получила статус Отменена");

            $this->addOrderAudit($OrdersServicesHistory);
        }
    }
}