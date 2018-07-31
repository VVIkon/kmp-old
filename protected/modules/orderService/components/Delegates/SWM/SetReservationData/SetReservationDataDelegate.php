<?php

use Symfony\Component\Validator\Validation;

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12/9/16
 * Time: 11:28 AM
 */
class SetReservationDataDelegate extends AbstractDelegate
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
        $OrdersServiceHistory->setCommentParams([]);

        $Offer = $OrdersService->getOffer();

        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();

        $transaction = Yii::app()->db->beginTransaction();

        try {
            $Offer->setReservationData($params['reservationData']);

            $violations = $validator->validate($Offer);

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->setError($violation->getMessage());
                    $transaction->rollback();
                    return;
                }
            }

            if (!$Offer->save(false)) {
                $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
                $transaction->rollback();
                return;
            }

            $OrdersService->setOffline(true);
            if (!$OrdersService->save(false)) {
                $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
                $transaction->rollback();
                return;
            }

            $transaction->commit();

            $OrdersServiceHistory->setActionResult(0);
            $OrdersServiceHistory->setCommentTpl('{{162}}');
        } catch (ReservationDataException $e) {
            $this->addLog($e->getMessage(), 'error');
            $this->setError(OrdersErrors::SET_RESERVATION_DATA_ERROR);
            $transaction->rollback();

            $OrdersServiceHistory->setActionResult(1);
            $OrdersServiceHistory->setCommentTpl('{{163}}');
        } catch (InvalidArgumentException $e) {
            $this->addLog($e->getMessage(), 'warning');
            $this->setError($e->getCode());
            $transaction->rollback();

            $OrdersServiceHistory->setActionResult(1);
            $OrdersServiceHistory->setCommentTpl('{{163}}');
        } catch (Exception $e) {
            $this->addLog($e->getMessage(), 'error');
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            $transaction->rollback();

            $OrdersServiceHistory->setActionResult(1);
            $OrdersServiceHistory->setCommentTpl('{{163}}');
        } finally {
            $this->addOrderAudit($OrdersServiceHistory);
        }

        $this->params['object'] = $OrdersService->serialize();
    }
}