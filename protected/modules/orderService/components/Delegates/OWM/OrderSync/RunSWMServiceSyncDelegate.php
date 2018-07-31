<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 24.05.17
 * Time: 13:44
 */
class RunSWMServiceSyncDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        // десереализуем объект OrderModel
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);
        // создадим SWM FSM
        $OrdersService = OrdersServicesRepository::findById($params['serviceId']);
        $SWM_FSM = new StateMachine($OrdersService);

        // запуск SWM FSM
        $params['serviceParams']['usertoken'] = $params['usertoken'];
        $params['serviceParams']['orderModel'] = $params['object'];
        $params['serviceParams']['gateId'] = 5;
        $SWMResponse = $SWM_FSM->apply('SERVICESYNC', $params['serviceParams']);

        // если возникла ошибка - транслируем ее
        if ($SWMResponse['status']) {
            // если обнаружилось, что заявка в архиве, то ставим у нас в архив
            if ($SWMResponse['status'] == 235) {
                $OrderModel->setArchive(true);
                $OrderModel->save();

                $this->params['object'] = $OrderModel->serialize();
            }

            $this->setError($SWMResponse['status']);
            return null;
        }

        // если все в порядке - запишем ответ в общий ответ
        $this->mergeResponse($SWMResponse['response']);
    }
}