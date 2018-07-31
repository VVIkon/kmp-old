<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 21.11.17
 * Time: 12:25
 */
class ServiceAuthorizationDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;
    const GPTS_ENGINE = 5;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

//LogHelper::logExt(get_class($this), __METHOD__, '----------point-2.1', '', ['$params'=>$params], 'info', 'system.searcherservice.info');

        $serviceId = $params['serviceId'];
        $resultOperation = 0; //0: нет раундов или шагов для проверки; 1-все шаги роаунда выполнены; 2-не все шаги раунда выполнены
        $AuthServiceData = AuthServiceData::model()->with('iterations')->findByAttributes(['serviceId'=>$serviceId, 'termination'=>NULL, 'completed'=>0]);
        if (isset($AuthServiceData)) {
            $resultOperation = $AuthServiceData->operationAutorizationIteration($params);
        }else{
            $this->setError(OrdersErrors::AUTH_SERVICE_DATA_NOT_FOUND, $params);
            return null;
        }
        if ($resultOperation >0) {

            // Аудит
            $this->params['object'] = $OrdersServices->serialize();
            $OrdersServicesHistory = new OrdersServicesHistory();
            $OrdersServicesHistory->setObjectData($OrdersServices);
            $OrdersServicesHistory->setOrderData($OrderModel);
            if ($params['termination'] > 0) {
                $OrdersServicesHistory->setCommentTpl("{{187}} {{comment}}");
                $OrdersServicesHistory->setCommentParams(['comment' => $params['fio']]);
                $message = 'Авторизация выполнена успешно пользователем ' . $params['fio'];

            } else {
                $OrdersServicesHistory->setCommentTpl("{{188}} {{comment}}");
                $OrdersServicesHistory->setCommentParams(['comment' => $params['fio'].'. Причина: '.$params['comment']]);
                $message = 'В авторизации отказано пользователем '.$params['fio'].'. Причина: '.$params['comment'];
            }
            $OrdersServicesHistory->setActionResult(0);
            $this->addOrderAudit($OrdersServicesHistory);

            // Уведомление
//            $authRuleIterationUsers = $AuthServiceData->getAuthRuleIterationUsers();  // Id юзеров которым нужно рассылать уведомления
            $this->addNotificationTemplate('success', ['comment' => $message]);
        }else{
            $this->setError(OrdersErrors::AUTH_SERVICE_DATA_ITERATION_NOT_FOUND, $params);
            return null;
        }
        $this->params['statusAuthService'] = (int)$resultOperation;
        $this->addResponse('statusAuthService', $this->params['statusAuthService']);
    }
}