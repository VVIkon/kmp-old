<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 07.08.17
 * Time: 13:46
 */
class ApiOrderController extends ServiceAuthController
{

    /**
     * Проверка возможности выполнения операции при добавлении туриска к услуге
     * @param $orderId - id заявки
     * @param $serviceId - id услуги
     * @param $usertoken - токен
     * @param $oper  - проверяемая операция: 'add' = 'AddTourist'; 'remove'='RemoveTourist'
     * @returm true - операция возможна
     */
    private function checkWorkflow($orderId, $serviceId, $usertoken, $oper)
    {
        if (!isset($orderId) || !is_numeric($orderId))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_ORDERID);
        if (!isset($serviceId) || !is_numeric($serviceId))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_SERVICEID);
        if (!isset($usertoken))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);

        $params['operation'] = 'validate';
        $params['orderId']  =  $orderId;
        switch ($oper) {
            case 'add':
                $params['action'] = "AddTourist";
                break;
            case 'remove':
                $params['action'] = "RemoveTourist";
                break;
            default:
                $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_NOT_OPERATION);
        }
        $params['actionParams'][]=['serviceId' => $serviceId, 'agreementSet' => true];
        $params['usertoken']= $usertoken;

        $ap = new ApiPackage([]);
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'CheckWorkflow','cmdIndex'=> '0'], 'OWM_CheckTouristToServiceTemplate');
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Проверка возможности выполнения перации для "Турист в услуге"', '', $params, 'info', 'system.apiservice.info');
            return isset($ap->fullResult['validationResult']) ? $ap->fullResult['validationResult'] : false;
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Проверка возможности выполнения перации для "Турист в услуге"',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
            //$this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
            return false;
        }

    }

    public function actionClientAddDataToService()
    {
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['serviceId']) || !is_numeric($params['serviceId']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_SERVICEID);
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);
//        if (!isset($params['serviceType']))
//            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_SERVICE_TYPE);

        $ap = new ApiPackage([]);
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'OrderWorkflowManager', 'owmOper' => 'SetAdditionalData', 'cmdIndex'=> '0'], 'OWM_SetAdditionalDataTemplate');
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Операция "Добавления или изменения данные дополнительной информации клиента в услуге в заявке"', '', $params, 'info', 'system.apiservice.info');
            $this->_sendResponseData($ap->fullResult);
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Операция "Добавления или изменения данные дополнительной информации клиента в услуге в заявке"',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
        }
    }


    public function actionClientAddServiceToOrder()
    {
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['serviceType']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_SERVICE_TYPE);
        if (!isset($params['offerId']) || !is_numeric($params['offerId']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_FINISH_OFFER_ID);
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);

        $ap = new ApiPackage([]);
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'OrderWorkflowManager', 'owmOper' => 'AddService', 'cmdIndex'=> '0'], 'OWM_AddService_SetAdditionalDataTemplate');
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Операция "Добавления или изменения данные дополнительной информации клиента в услуге в заявке"', '', $params, 'info', 'system.apiservice.info');
            $this->_sendResponseData($ap->fullResult);
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Операция "Добавления или изменения данные дополнительной информации клиента в услуге в заявке"',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
        }
    }

    public function actionClientSetTouristsToService()
    {
        $params = $this->_getRequestParams();
        $OnlyOrder = False;

        // валидация параметров
        if ($params['operation'] == 'add' && empty($params['orderNumber']) ) {
            $OnlyOrder = True; // Согласно ТЗ, если orderNumber=null, то нужно задать заявку и туриста, в услугу туриста не добавлять (её ещё нет!)
        }
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);

    //    if ($OnlyOrder || $this->checkWorkflow($params['orderNumber'], $params['serviceId'], $params['usertoken'], $params['operation'])){
            $params['actionParams'] = $params['touristData'];
            unset($params['touristData']);
            $ap = new ApiPackage([]);
            switch ($params['operation']) {
                case 'add':
                    $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'OrderWorkflowManager', 'owmOper' => 'AddTourist','cmdIndex'=> '0'], 'OWM_AddTourist_SetAdditionalDataTemplate');
                    if (!$OnlyOrder) {
                        $ap->addCmd($params, ['serviceName' => 'orderService', 'action' => 'OrderWorkflowManager', 'owmOper' => 'TouristToService', 'cmdIndex' => ''], 'OWM_TouristToService_15_Template');
                    }
                    break;
                case 'remove':
                    $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'OrderWorkflowManager', 'owmOper' => 'TouristToService', 'cmdIndex'=> ''], 'OWM_TouristToService_15_Template');
                    //$ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'OrderWorkflowManager', 'owmOper' => 'RemoveTourist','cmdIndex'=> '0'], 'OWM_RemoveTouristTemplate');
                    break;
                default:
                    $this->_sendResponseWithErrorCode(ApiErrors::ERROR_NOT_VALID_OWM_OPERATION);
            }
            $ap->runCmd();

            if ($ap->status == 1) {
                LogHelper::logExt(get_class($this), __METHOD__, 'Операции "Турист в услуге"', '', $params, 'info', 'system.apiservice.info');
                $this->_sendResponseData($ap->fullResult);
            } else {
                LogHelper::logExt(get_class($this), __METHOD__, 'Операции "Турист в услуге"',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
                $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
            }
//        } else{
//            $this->_sendResponse(false, array(), 'Для введённых параметров операция `Добавления туриста в услугу` не возможна', '1002');
//        }

    }

    public function actionClientGetOrder(){
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['lang']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_LANG);

        if (!isset($params['currency']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_CURRENCY);

        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);

        if (!isset($params['orderId']) || !is_numeric($params['orderId']) )
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_ORDERID);

        $getOrdetTemplate = [
            'order'=>[],
            'tourists'=>[],
            'touristdocs'=>[],
            'services'=>[]
        ];

        $ap = new ApiPackage($getOrdetTemplate);
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrder','cmdIndex'=> 'order'], 'orderTemplate');
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrderTourists','cmdIndex'=> 'tourists'], 'tourists_15_Template' );
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrderTourists','cmdIndex'=> 'touristdocs'], 'touristDocsTemplate' );
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrder','cmdIndex'=> 'services'], 'orderService_15_Template' );
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Данные по заявке клиента', '', $params, 'info', 'system.apiservice.info');
            $this->_sendResponseData($ap->fullResult);
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Данные по заявке клиента',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
        }

    }
}