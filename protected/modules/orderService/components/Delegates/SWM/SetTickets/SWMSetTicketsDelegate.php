<?php

use Symfony\Component\Validator\Validation;

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12/14/16
 * Time: 12:37 PM
 */
class SWMSetTicketsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        // запишем аудит
        $OrdersServiceHistory = new OrdersServicesHistory();
        $OrdersServiceHistory->setOrderData($OrderModel);
        $OrdersServiceHistory->setObjectData($OrdersService);

        $transaction = Yii::app()->db->beginTransaction();

        try {
            $Offer = $OrdersService->getOffer();
            $Offer->setService($OrdersService);
            $Offer->setTicket($params['ticketData']);

            $OrdersService->setOffline(true);
            if (!$OrdersService->save(false)) {
                $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
                $transaction->rollback();
                return;
            }

            $transaction->commit();

            $OrdersServiceHistory->setActionResult(0);
            switch ($params['ticketData']['ticketAction']) {
                case 'add':
                    $OrdersServiceHistory->setCommentTpl('{{159}} {{ticketNumber}}');
                    $OrdersServiceHistory->setCommentParams([
                        'ticketNumber' => $params['ticketData']['ticketData']['ticketNumber']
                    ]);
                    break;
                case 'update':
                    $OrdersServiceHistory->setCommentTpl('{{160}}');
                    $OrdersServiceHistory->setCommentParams([
                        'oldNumber' => $params['ticketData']['ticketData']['newTicket'],
                        'ticketNumber' => $params['ticketData']['ticketData']['ticketNumber']
                    ]);
                    break;
            }
        } catch (InvalidArgumentException $e) {
            $this->setError($e->getCode());
            $this->addLog($e->getMessage(), 'warning');
            $transaction->rollback();
            $OrdersServiceHistory->setActionResult(1);
            $OrdersServiceHistory->setCommentTpl('{{161}}');
            $OrdersServiceHistory->setCommentParams([]);
        } catch (Exception $e) {
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            $this->addLog($e->getMessage(), 'error');
            $transaction->rollback();
            $OrdersServiceHistory->setActionResult(1);
            $OrdersServiceHistory->setCommentTpl('{{161}}');
            $OrdersServiceHistory->setCommentParams([]);
        } finally {
            $this->addOrderAudit($OrdersServiceHistory);
        }
    }
}