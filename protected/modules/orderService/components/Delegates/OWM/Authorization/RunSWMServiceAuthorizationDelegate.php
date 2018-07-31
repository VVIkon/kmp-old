<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 20.11.17
 * Time: 17:18
 */
class RunSWMServiceAuthorizationDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params){
        // десереализуем объект OrderModel
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);
        // создадим SWM FSM
        $OrdersService = OrdersServicesRepository::findById($params['serviceId']);
        $SWM_FSM = new StateMachine($OrdersService);

        // запуск SWM FSM
        $params['serviceParams']['usertoken'] = $params['usertoken'];
        $params['serviceParams']['serviceId'] = $params['serviceId'];
        $params['serviceParams']['termination'] = $params['termination'];
        $params['serviceParams']['comment'] = $params['comment'];
        $params['serviceParams']['autoAuth'] = $params['autoAuth'];
        $params['serviceParams']['orderModel'] = $params['object'];
        $params['serviceParams']['userId'] = $params['userProfile']['userId'];
        $params['serviceParams']['fio'] = $params['userProfile']['userName'].''.$params['userProfile']['userLastName'];

//LogHelper::logExt(get_class($this), __METHOD__, '----------point-1.1', '', ['$params'=>$params], 'info', 'system.searcherservice.info');
        $SWMResponse = $SWM_FSM->apply('SERVICEAUTHORIZATION', $params['serviceParams']);
// LogHelper::logExt(get_class($this), __METHOD__, '----------point-1.2', '', ['$SWMResponse'=>$SWMResponse], 'info', 'system.searcherservice.info');

        // если возникла ошибка - транслируем ее
        if ($SWMResponse['status']) {
            $this->setError($SWMResponse['status']);
            return null;
        }

        // если все в порядке - запишем ответ в общий ответ
        $this->mergeResponse($SWMResponse['response']);
    }
}