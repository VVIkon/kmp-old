<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/6/16
 * Time: 11:50 AM
 */
class BookStartRunSWMManualDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $BookData = $this->getObjectFromContext('BookData');

        if ($BookData->hasErrors()) {
            $OrdersService = OrdersServices::model()->findByPk($params['serviceId']);

            if (is_null($OrdersService)) {
                $this->setError(OrdersErrors::SERVICE_NOT_FOUND);
                return null;
            }
            $commentTPL = null;
            $ordersErrorCode = null;
            switch ($BookData->getBookErrorCode()) {
                // Бизнес ошибки
                case BookData::BOOK_ERROR_FROM_GPTS_BUS_22:
                    $commentTPL = '{{176}}{{serviceIDGP}}';
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_BUS_22;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_BUS_58:
                    $commentTPL = '{{177}}{{serviceIDGP}}';
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_BUS_58;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_BUS_142:
                    $commentTPL = '{{178}}{{serviceIDGP}}';
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_BUS_142;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_BUS_150:
                    $commentTPL = '{{179}}{{serviceIDGP}}';
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_BUS_150;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_BUS_244:
                    $commentTPL = '{{180}}{{serviceIDGP}}';
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_BUS_244;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_BUS_322:
                    $commentTPL = '{{181}}{{serviceIDGP}}';
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_BUS_322;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_BUS_378:
                    $commentTPL = '{{182}}{{serviceIDGP}}';
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_BUS_378;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_BUS_417:
                    $commentTPL = '{{183}}{{serviceIDGP}}';
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_BUS_417;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_BUS_450:
                    $commentTPL = '{{169}}{{serviceIDGP}}';     // Дублирующее бронирование
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_BUS_450;
                    break;
                case BookData::BOOK_ERROR_OFFER_REJECTED:
                    $commentTPL = '{{184}}{{serviceIDGP}}';     // Оффер недоступен
                    $ordersErrorCode = BookData::BOOK_ERROR_OFFER_REJECTED;
                    break;

                // Ошибки для менеджеров в уведомлении
                case BookData::BOOK_ERROR_FROM_GPTS_MAN_193:
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_MAN_193;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_MAN_264:
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_MAN_264;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_MAN_342:
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_MAN_342;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_MAN_422:
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_MAN_422;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_MAN_426:
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_MAN_426;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_MAN_446:
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_MAN_446;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_MAN_558:
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_MAN_558;
                    break;
                case BookData::BOOK_ERROR_FROM_GPTS_MAN_1001:
                    $ordersErrorCode = BookData::BOOK_ERROR_FROM_GPTS_MAN_1001;
                    break;
                case BookData::BOOKING_PREPARATION_ERROR:
                    $ordersErrorCode = BookData::BOOKING_PREPARATION_ERROR;
                    break;
                case BookData::BOOKING_PREPARATION_FAILED:
                    $ordersErrorCode = BookData::BOOKING_PREPARATION_FAILED;
                    break;
                case BookData::BOOKING_PREPARATION_ERROR_448:
                    $ordersErrorCode = BookData::BOOKING_PREPARATION_ERROR_448;
                    break;

            }

            if (!is_null($commentTPL)  && !is_null($ordersErrorCode)) {
                $OrderModel = new OrderModel();
                $OrderModel->unserialize($params['object']);

                // запишем историю
                $orderHystory = new OrderHistory();
                $orderHystory->setObjectData($OrderModel);
                $orderHystory->setOrderData($OrderModel);
                $orderHystory->setActionResult(1);
                $orderHystory->setCommentTpl($commentTPL);
                $orderHystory->setCommentParams([
                    'serviceIDGP' => $OrderModel->getOrderIDGP()
                ]);

                // сохраним результат аудита
                $this->addOrderAudit($orderHystory);
                $this->setError($ordersErrorCode);
            }

            $OrderModel = new OrderModel();
            $OrderModel->unserialize($params['object']);

            $params['orderModel'] = $params['object'];
            $params['orderId'] = $OrderModel->getOrderId();

            $manualCommentWithError = $BookData->getErrorDescription($ordersErrorCode);
            if (!is_null($manualCommentWithError)) {
                $params['comment'] = $manualCommentWithError;
            }
            // Только известные ошибки
//            if ( !$BookData->hasUnknownErrors()) {
            if ( $BookData->getKTStatus() == 9 ) {
                $SWM_FSM = new StateMachine($OrdersService);
                $SWM_FSM->apply('SERVICEMANUAL', $params);

                $this->addResponse('serviceStatus', OrdersServices::STATUS_MANUAL);
            }
        }
    }


}