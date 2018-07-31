<?php

use Symfony\Component\Validator\Validation;
require_once('ApiHelper.php');

/**
* APIKT Создание и редактирование заявки
* @param 
* @returm 
*/
class ApiServiceController extends ServiceAuthController
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




// ---------------- Basic operations ---------------------------------------
    /*
    * Функция создаёт услугу в заявке. Если заявка для создания услуги не указана, создаётся новая заявка.
    * @param
    *   // {
        //    "serviceType" : 2,          // Тип услуги
        //    "offerId" : 2365475675,     // Идентификатор предложения, для которого создаётся услуга.
        //    "orderNumber" : 12345       // (необязательный) Номер заявки, в которую следует добавить услугу.
        //}
    * @returm
    *   {
    *       "orderNumber" : 12345       // Номер заявки, в которую добавлена услуга.
    *       "serviceId" : 2540          // ID созданной услуги
    *   }
    */
    //TODO: Для Avia доработать GetCacheOffer
    //
    public function actionClientAddServiceToOrder(){
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);
        if (!isset($params['offerId']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_FINISH_OFFER_ID);
        if (!isset($params['orderNumber']) || !is_numeric($params['orderNumber']) ||  $params['orderNumber'] < 0 )
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_ORDERID);


        $ap = new ApiPackage([]);
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'OrderWorkflowManager', 'owmOper' => 'AddService', 'cmdIndex'=> '0'], 'OWM_AddServiceTemplate');
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Cоздание услуги в заявке', '', $params, 'info', 'system.apiservice.info');
            $this->_sendResponseData($ap->fullResult);
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Cоздание услуги в заявке',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
//            $this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
        }

    }
    /**
    * Функция добавляет, редактирует, удаляет туриста в/из услуги
    * @param
     * {
        "usertoken": "a3fa64216086b56b",
        "orderNumber": 17005,
        "operation" : "add",
        "serviceId": 17957,
        "actionParams": {
            "tourist" : {
                "touristId": 0,
                "isTourLeader": "1",
                "sex": "1",
                "firstName": "SERGEY",
                "lastName": "KAMENEV",
                "middleName": "ALEX",
                "birthdate": "1988-01-01",
                "email": "s.kamenev@kmp.ru",
                "phone": "+7(904)3242132",
            },
            "touristDocs" : [
            {
                "documentType": "1",
                "firstName": "SERGEY",
                "lastName": "KAMENEV",
                "middleName": "ALEX",
                "serialNumber": "2345",
                "number": "123456",
                "expiryDate": "",
                "citizenship": "RU"
            }
            ],
            "cardInfo" : {
                "bonuscardNumber2": "123451234512345",
                "aviaLoyalityProgrammId": "11",
     *          "link": 1
     *      }
            }
        }
    * @returm
     * {
            "orderNumber" : 12345,          // Номер заявки, в которую записана информация о туристах.
            "touristId" : 3245536546,       // ID (созданного/обновлённого/удалённого) туриста
       }
    */
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

        if ($OnlyOrder || $this->checkWorkflow($params['orderNumber'], $params['serviceId'], $params['usertoken'], $params['operation'])){
            $params['actionParams'] = $params['touristData'];
            unset($params['touristData']);
            $ap = new ApiPackage([]);
            switch ($params['operation']) {
                case 'add':
                    $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'OrderWorkflowManager', 'owmOper' => 'AddTourist','cmdIndex'=> '0'], 'OWM_AddTouristTemplate');
                    if (!$OnlyOrder) {
                        $ap->addCmd($params, ['serviceName' => 'orderService', 'action' => 'OrderWorkflowManager', 'owmOper' => 'TouristToService', 'cmdIndex' => '0'], 'OWM_TouristToServiceTemplate');
                    }
                    break;
                case 'remove':
                    $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'OrderWorkflowManager', 'owmOper' => 'TouristToService', 'cmdIndex'=> '0'], 'OWM_TouristToServiceTemplate');
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
//                $this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
                $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
            }
        } else{
            $this->_sendResponse(false, array(), 'Для введённых параметров операция `Добавления туриста в услугу` не возможна', '1002');
        }

    }



}