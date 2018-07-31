<?php
/*
*
*    2. APIKT Получение информации о заявках
*
*/

use Symfony\Component\Validator\Validation;
require_once('ApiHelper.php');


class ApiAuthController extends ServiceAuthController
{

    /**
    * Проверка прав доступа к API
    * @param   $params["permissions": 5,, "usertoken"=> "1a5135d7d651ec34"]
     *
    * @returm
    */
    private function checkPermitions($params)
    {
        $ap = new ApiPackage([]);
        $ap->addCmd($params, ['serviceName'=>'systemService', 'action'=>'UserAccess','cmdIndex'=> '0'], 'userAcessTemplate');
        $ap->runCmd();
        $flag = nvl($ap->fullResult['hasAccess'], false);
        return $flag;
    }

    /*
     * RESTapi Аутентификация пользователя
     * @Example http://dev-kmp.travel/api/v10/apiService/ClientAuthenticate
     * @param   [POST]:{"login" : "vasia", "password" : "12345"}        // Логин и Пароль пользователя;
     * @return:пше
     * {
     *     "status": 0,
     *     "errors": "",
     *     "body": {
     *       "token": "3853a2bcff70fc6a",
     *       "tokenDuration": 4648494
     *     }
     * }
     */
    public function actionClientAuthenticate()
    {
        $params = $this->_getRequestParams();
        $ap = new ApiPackage([]);
        $ap->addCmd($params, ['serviceName'=>'systemService', 'action'=>'UserAuth','cmdIndex'=> '0'], 'authTemplate');
        $ap->runCmd();

        if ($ap->status == 0){
            LogHelper::logExt(get_class($this), __METHOD__, 'Аутентификация клиента',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
            $this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
        }elseif ($ap->status == 1 && $this->checkPermitions([ 'permissions' => 5, 'usertoken' => $ap->fullResult['token'] ])) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Аутентификация клиента', '', $params, 'info', 'system.apiservice.info');
            unset($ap->fullResult['userId']); // убиваю id пользователя при выводе.
            $this->_sendResponseData($ap->fullResult);
        } else {
            $params['userId'] = $ap->fullResult['userId']; // Добавление пользователя в лог
            LogHelper::logExt(get_class($this), __METHOD__, 'Недостаточно прав для выполнения операции', '', $params, 'error', 'system.apiservice.error');
            $this->_sendResponse(false, array(), 'Недостаточно прав для выполнения операции' , '701');
        }
    }

    /*
     * RESTapi по заявке клиента
     * @Example http://dev-kmp.travel/api/v10/apiService/ClientGetOrder
     * @param   [POST]:{
     *   "lang" : "ru",
     *   "currency" : "EUR",
     *   "usertoken": "3853a2bcff70fc6a",
     *   " orderId": 16759
     *   }
     * @return: {   // данные заявки
     *   "order" : {...},                            // заявка, структура sa_order
     *   "tourists" : [{...},{...}...],              // массив с данными туристов в заявке, структуры sa_tourist
     *   "touristdocs" : [{...},{...}...],           // массив с данными документов туристов, структуры sa_touristDoc
     *   "services" : [                              // услуги заявки
     *       "orderService" : {...},                 // Услуга, структура sa_orderService
     *       "serviceTourists": [2343,45,243],       // Привязанные к услуге туристы
     *       "serviceData" : {...}                   // Данные услуги, структура зависит от типа услуги
     *   ]
     * }
     */
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
            'services'=>[
//                'orderService'=>[],
//                'serviceTourists'=>[],
//                'serviceData'=>'[]',
            ]
        ];

        $ap = new ApiPackage($getOrdetTemplate);
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrder','cmdIndex'=> 'order'], 'orderTemplate');
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrderTourists','cmdIndex'=> 'tourists'], 'touristsTemplate' );
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrderTourists','cmdIndex'=> 'touristdocs'], 'touristDocsTemplate' );
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrder','cmdIndex'=> 'services'], 'orderServiceTemplate' );
//        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrderTourists','cmdIndex'=> 'services'], 'serviceTouristIDTemplate' );
//        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrderOffers','cmdIndex'=> 'services'], 'serviceDataTemplate' );
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Данные по заявке клиента', '', $params, 'info', 'system.apiservice.info');
            $this->_sendResponseData($ap->fullResult);
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Данные по заявке клиента',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
            //$this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
        }

    }

    /*
    * RESTapi по спискам заявке клиента
    * @Example http://dev-kmp.travel/api/v10/apiService/ClientGetOrderList
    * @param   [POST]:{
     {
        "usertoken": "{{userToken}}",
        "lang" : "ru",
        "currency" : "EUR",
        "filter" : {
            "status" : 0,
            "tourist" : "Иванов Иван",
            "orderNumber" : 17077,
            "startDate" : "2016-01-03",
            "endDate" : "2016-01-04",
            "modificationTime" : "2016-01-01T14:34:45"
        },
        "sort" : {
            "sortOrder" : "asc",
            "sortFields": ["lastChangeDate"]
        },
        "paging" : {
            "offset" : 0,
            "limit" : 20
        }
    }
    *   }
    * @returm
     *   "order" : {...,                            // заявка, структура sa_order
     *      "services" : [                          // услуги заявки
     *          "orderService" : {...},             // Услуга, структура sa_orderService
     *      ]
     *   }
    */
    public function actionClientGetOrderList(){
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);
        if (!isset($params['lang']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_LANG);
        if (!isset($params['currency']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_CURRENCY);

        $outTemplate = [
            'order'=>[
            ]
        ];
        $ap = new ApiPackage($outTemplate);
        // $ap->getModule()->getError($ap->fullResult['errorCode'])
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrderList','cmdIndex'=> 'order'], 'orderListTemplate');
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Список заявок', '', $params, 'info', 'system.apiservice.info');
            $this->_sendResponseData($ap->fullResult);
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Список заявок',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
//            $this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
            $this->_sendResponse(false, array(), 'Errors', $ap->fullResult['errorCode']);
        }
    }


}