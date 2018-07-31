<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/1/16
 * Time: 4:09 PM
 */
class SetBOOKEDStatusDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        $BookData = $this->getObjectFromContext('BookData');
        $CommentTpl = null;

        $serviceStatus = $BookData->getKtStatus();
// LogHelper::logExt(get_class($this), __METHOD__, '----------RUN.20', '', ['$serviceStatus'=>$serviceStatus], 'info', 'system.searcherservice.info');

        switch($serviceStatus){
            case 0:     // NEW
                $OrdersServices->makeNew();
                $OrdersServices->save();
                $CommentTpl = '{{135}} {{123}}';
                $this->addLog("Услуга № {$OrdersServices->getServiceID()} получила статус 'Новая'");
                $errorCode = $BookData->getBookErrorCode();
                $this->setError($errorCode);
                break;
            case 1:     // W_BOOK
                $OrdersServices->makeWBooked();
                $OrdersServices->save();
                $CommentTpl = '{{135}} {{131}}';
                $this->addLog("Услуга № {$OrdersServices->getServiceID()} получила статус 'Ожидает бронирования'");
                break;
            case 2:     //BOOKED
                $OrdersServices->makeBooked();
                $OrdersServices->save();
                $CommentTpl = '{{135}} {{130}}';
                $this->addLog("Услуга № {$OrdersServices->getServiceID()} получила статус 'Забронирована'");
                break;
            case 6:     //CANCEL
                $OrdersServices->makeCancelled();
                $OrdersServices->save();
                $CommentTpl = '{{135}} {{133}}';
                $this->addLog("Услуга № {$OrdersServices->getServiceID()} получила статус 'Отменено'");
                $this->setError($BookData->getBookErrorCode());
                break;
            case 9:     //MANUAL
                $OrdersServices->makeManual();
                $OrdersServices->save();
                $CommentTpl = '{{135}} {{124}}';
                $this->addLog("Услуга № {$OrdersServices->getServiceID()} получила статус 'В обработке' ");
                $this->setError($BookData->getBookErrorCode());
                break;
        }

 //       LogHelper::logExt(get_class($this), __METHOD__, '----------SetBOOKEDStatusDelegate.1', '', ['$serviceStatus'=>$serviceStatus, '$CommentTpl'=>$CommentTpl, '$BookData'=>$BookData->getBookDataArray(), '$this->params'=>$this->params], 'info', 'system.searcherservice.info');

        if (!is_null($CommentTpl)) {
            $this->params['object'] = $OrdersServices->serialize();

            $OrderModel = new OrderModel();
            $OrderModel->unserialize($params['orderModel']);

            $OrdersServicesHistory = new OrdersServicesHistory();
            $OrdersServicesHistory->setObjectData($OrdersServices);
            $OrdersServicesHistory->setOrderData($OrderModel);
            $OrdersServicesHistory->setCommentTpl($CommentTpl);
            $OrdersServicesHistory->setActionResult(0);
            $this->addOrderAudit($OrdersServicesHistory);
            $this->addResponse('serviceStatus', $OrdersServices->getStatus());
        }
    }
}