<?php
use Symfony\Component\Validator\Validation;
require_once('ApiHelper.php');

/**
* APIKT Бронирование и отмена услуг
* @param 
* @returm 
*/
class ApiBookController extends ServiceAuthController
{
    // ---------------- Check operations ---------------------------------------
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
            case 'BookStart':
                $params['action'] = "BookStart";
                break;
            case 'IssueTickets':
                $params['action'] = "IssueTickets";
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
            LogHelper::logExt(get_class($this), __METHOD__, 'Проверка возможности выполнения перации для "Бронирования услуги"', '', $params, 'info', 'system.apiservice.info');
            return isset($ap->fullResult['validationResult']) ? $ap->fullResult['validationResult'] : false;
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Проверка возможности выполнения перации для "Бронирования услуги"',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
//            $this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
            return false;
        }

    }


    /**
    * Проверка статуса сервиса
    * @param $orderId
    * @param $serviceId,
    * @param $usertoken
    * @returm serviceStatus (String)
    */
    public function checkStatusService($orderId, $serviceId, $usertoken)
    {
        if (!isset($orderId) || !is_numeric($orderId))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_ORDERID);
        if (!isset($serviceId) || !is_numeric($serviceId))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_SERVICEID);
        if (!isset($usertoken))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);

        $params['usertoken'] = $usertoken;
        $params['orderId']  =  $orderId;
        $params['getInCurrency']= 'EUR';

        $ap = new ApiPackage([]);
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrder','cmdIndex'=> '0'], 'OWM_CheckGetOrderTemplate');
        $ap->runCmd();

        if ($ap->status == 1) {
//            LogHelper::logExt(get_class($this), __METHOD__, 'Проверка статуса услуги', '', $params, 'info', 'system.apiservice.info');
            return nvl($ap->fullResult[$serviceId], 'Error');
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Проверка статуса услуги',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
            //$this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
            return false;
        }
    }

    // ---------------- Basic operations ---------------------------------------
    /**
    * Команда инициирует бронирование указанной услуги
     * @param   $usertoken - токен
                "agreementSet" : true,  // Подтверждение ознакомления и согласия с тарифами и офертой (true/false)
                "orderNumber" : 12345,  // Номер заявки
                "serviceId" : 234234    // Идентификатор услуги для выполнения бронирования
              }
     * @returm {

    */
    public function actionClientBookService()
    {
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['orderNumber']) || !is_numeric($params['orderNumber']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_ORDERID);
        if (!isset($params['serviceId']) || !is_numeric($params['serviceId']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_SERVICEID);
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);
        if (!isset($params['agreementSet']) || !is_bool($params['agreementSet']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_AGREEMENTSET);

        if ($this->checkWorkflow($params['orderNumber'], $params['serviceId'], $params['usertoken'], 'BookStart' )){
            $ap = new ApiPackage([]);
            $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'OrderWorkflowManager', 'owmOper' => 'BookStart', 'cmdIndex'=> '0'], 'OWM_BookStartTemplate');
            $ap->runCmd();

            if ($ap->status == 1) {
                LogHelper::logExt(get_class($this), __METHOD__, 'Операция "Бронирования услуги"', '', $params, 'info', 'system.apiservice.info');
                $this->_sendResponseData($ap->fullResult);
            } else {
                LogHelper::logExt(get_class($this), __METHOD__, 'Операция "Бронирования услуги"',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
//                $this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
                $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
            }
        } else{
            $this->_sendResponse(false, array(), 'Для введённых параметров операция `Бронирование услуги` не возможна', '1001');
        }

    }
    /**
    * Команда отменяет указанную услугу
    * @param
    *  {        "usertoken"             // токен,
                "orderNumber" : 12345,  // Номер заявки
                "serviceId" : 234234    // Идентификатор услуги для отмены
              }
     * @returm
     *  {
            "orderStatus": 4,          // Статус заявки после отмены
            "serviceStatus": 4         // Статус услуги после отмены
        }
    */
    public function actionClientCancelService()
    {
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['orderNumber']) || !is_numeric($params['orderNumber']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_ORDERID);
        if (!isset($params['serviceId']) || !is_numeric($params['serviceId']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_SERVICEID);
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);


        $owmOper = null;
        $template = null;
        $servStatus = $this->checkStatusService($params['orderNumber'], $params['serviceId'], $params['usertoken']);
        switch ($servStatus) {
            case 'NEW':
                $owmOper = 'ServiceCancel';
                $template ='OWM_ServiceCancelTemplate';
                break;
            case 'BOOKED':
                $owmOper = 'BookCancel';
                $template ='OWM_BookCancelTemplate';
                break;
            default:
                $this->_sendResponse(false, array(), 'Для введённых параметров операция `Отмена услуги бронирования` не возможна', '1003');
        }

        $par['orderId'] = $params['orderNumber'];
        $par['actionParams']['serviceId'] = $params['serviceId'];
        $par['usertoken'] = $params['usertoken'];

        $ap = new ApiPackage([]);
        $ap->addCmd($par, ['serviceName' => 'orderService', 'action' => 'OrderWorkflowManager', 'owmOper' => $owmOper, 'cmdIndex' => '0'], $template);
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Операция "Отмены услуги бронирования"', '', $params, 'info', 'system.apiservice.info');
            $this->_sendResponseData($ap->fullResult);
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Операция "Отмены услуги бронирования"',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
//            $this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
        }
    }

    /**
     * {
     *     "usertoken": "{{userToken}}",
     *     "orderId" : 17775,
     *     "serviceId" : 19111
     * }
     */
    public function actionClientIssueTickets()
    {
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['orderId']) || !is_numeric($params['orderId']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_ORDERID);
        if (!isset($params['serviceId']) || !is_numeric($params['serviceId']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_SERVICEID);
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);

        if ($this->checkWorkflow($params['orderId'], $params['serviceId'], $params['usertoken'], 'IssueTickets' )){
            $ap = new ApiPackage([]);
            $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'OrderWorkflowManager', 'owmOper' => 'IssueTickets', 'cmdIndex'=> '0'], 'OWM_IssueTicketsTemplate');
            $ap->runCmd();

            if ($ap->status == 1) {
                LogHelper::logExt(get_class($this), __METHOD__, 'Операция "Выписка билета в услуге" ', '', $params, 'info', 'system.apiservice.info');
                $this->_sendResponseData($ap->fullResult);
            } else {
                LogHelper::logExt(get_class($this), __METHOD__, 'Операция "Выписка билета в услуге" ',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
                $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
            }
        } else{
            $this->_sendResponse(false, array(), 'Для введённых параметров операция `Выписка билета в услуге` не возможна', '1001');
        }

    }

}