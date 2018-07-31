<?php

/**
 *
 *   3. APIKT Поиск предложений
 */

use Symfony\Component\Validator\Validation;
require_once('ApiHelper.php');

class ApiSearchController extends ServiceAuthController
{


    /*
   * RESTapi: Операция выполняет поиск города/аэропорта по названию/части названия
   * @Example http://dev-kmp.travel/api/v10/apiService/ClientGetSuggestLocation
   * @param [POST]:{
   *        "usertoken": "3853a2bcff70fc6a", // Токен пользователя
   *        "lang": "ru",                   // Тип услуги из справочника типов услуг kt_ref_servicesх
   *        "location": "MOW",              // Часть названия города (если более 3 символов, то начальная часть)
   *        "serviceType": 2                // тип сервиса (1-Отели; 2-Авиа)
   *   }
   * @returm
   *   "locations": [
   *     {...},
   *     {...}
   * ]
   */
    public function actionClientGetSuggestLocation(){
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);
        if (!isset($params['lang']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_LANG);
        if (!isset($params['location']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_LOCATION);
        if (!isset($params['serviceType']) || !is_numeric($params['serviceType']) ||  $params['serviceType'] < 0 )
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_SERVICE_TYPE);

        $outTemplate = [
            'locations'=>[
            ]
        ];
        $ap = new ApiPackage($outTemplate);
        $ap->addCmd($params, ['serviceName'=>'searcherService', 'action'=>'GetSuggestLocation','cmdIndex'=> 'locations'], 'SuggestLocationTemplate');
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Поиск города/аэропорта', '', $params, 'info', 'system.apiservice.info');
            $this->_sendResponseData($ap->fullResult);
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Поиск города/аэропорта',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
//            $this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
        }
    }

    /*
    * RESTapi: Функция запускает поиск предложений по услугам
    * @Example http://dev-kmp.travel/api/v10/apiService/ClientSearchStart
    * @param [POST]:{
     *              "usertoken": "3853a2bcff70fc6a", // Токен пользователя
     *              "requestDetails" :{
                        "route" : {
                            "cityId" : 123456,                  // город по KT
                            "dateStart" : "2015-10-01",         // дата заезда
                            "dateFinish" : "2015-11-01"         // дата выезда
                        },
                        "rooms": [
                            "room": {
                                "adult" : 1,                    // кол-во взрослых
                                "children" : 1,                 // кол-во детей
                                }
                        ]
                        "category": "3"                         // категория (звездность) отеля (допустимо 2, 3, 4 или 5)
                        "hotelChain": ["Hillton"],              // (необязательный) часть названия отельной сети (или их коллекция)
                        "mealType": "RO"                        // (необязательный) тип питания
                    }
    * @returm
    *   {
    *      "status": 0,
    *      "errors": "",
    *      "body": {
    *        "searchToken": "229c73be5b9a44e5"
    *      }
    *   }
    */
    public function actionClientSearchStart(){
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);
        if (!isset($params['requestDetails']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_REQUEST_DETAIL);
        if (!isset($params['serviceType']) || !is_numeric($params['serviceType']) ||  $params['serviceType'] < 0 )
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_SERVICE_TYPE);
        if ($params['serviceType'] == 1) {
            if (!isset($params['requestDetails']['route']['dateStart']) || strtotime($params['requestDetails']['route']['dateStart']) < strtotime('now'))
                $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_STARTDATE);
            if (!isset($params['requestDetails']['route']['dateFinish']) || strtotime($params['requestDetails']['route']['dateFinish']) < strtotime($params['requestDetails']['route']['dateStart']))
                $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_FINISH_LESS_START);
            if (!isset($params['requestDetails']['route']['city']) || !is_numeric($params['requestDetails']['route']['city']) ||  $params['requestDetails']['route']['city'] < 1 )
                $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_CITY);

            foreach ($params['requestDetails']['rooms'] as $room) {
                if (!isset($room['adults']))
                    $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_ROOMS_ADULTS);
                if (!isset($room['childrenAges']))
                    $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_ROOMS_CHILDRENAGES);
            }
        }


        $ap = new ApiPackage([]);
        $ap->addCmd($params, ['serviceName'=>'searcherService', 'action'=>'SearchStart','cmdIndex'=> '0'], 'SearchStartTemplate');
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Поиск предложений по услугам', '', $params, 'info', 'system.apiservice.info');
            $this->_sendResponseData($ap->fullResult);
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Поиск предложений по услугам',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
//            $this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
        }
    }

    /*
    * RESTapi: Команда предназначена получения подробной информации об отеле.
    * @param {
                "usertoken": "3853a2bcff70fc6a", // Токен пользователя
                "hotelId" : 324234, // Идентификатор отеля
                "lang" : "ru"       // требуемый язык возврата информации
            }
    * @returm
     * {
            "hotelId": 23423423432,         // Идентификатор отеля КТ
            "name": "Университетская",      // Название отеля
            "address": "Мичуринский проспект, 8/29", // Адрес
            "category": 3,                  // Звёздность
            "cityName": "Москва",           // Название города
            "latitude": "55.706484",        // Координаты отеля
            "longitude": "37.51171",
            "checkInTime" : "12:00",        // Заезд после
            "checkOutTime" : "10:00",       // Выезд до
            "hotelChain" : "HHonors INN",   // Отельная цепочка, название из справочника отельных сетей
            "descriptions": [{...},{...}],  // Массив описаний отеля, структуры ss_hotelDescription
            "services": [{...},{...}],          // Массив услуг отеля, структуры ss_hotelService
            "mainImageUrl": "http://kmp.travel/1100328_00.jpg",                 // Главная картинка отеля
            "images":["http://kmp.travel/234.jpg","http://kmp.travel/234.jpg"]  // Картинки отеля
        }
    */
    public function actionClientGetHotelInfo(){
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);
        if (!isset($params['lang']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_LANG);
        if (!isset($params['hotelId']) || !is_numeric($params['hotelId']) ||  $params['hotelId'] < 0 )
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_HOTELLID);

        $ap = new ApiPackage([]);
        $ap->addCmd($params, ['serviceName'=>'supplierService', 'action'=>'GetHotelInfo','cmdIndex'=> '0'], 'GetHotelInfoTemplate');
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Получение подробной информации об отеле', '', $params, 'info', 'system.apiservice.info');
            $this->_sendResponseData($ap->fullResult);
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Получение подробной информации об отеле',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
            //$this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
        }
    }
    
    /*
    * Команда возвращает предложения, найденные в результате запуска поиска функцией ClientSearchStart.
    * Функция использует постраничный вывод результатов.
    * @param
     * {
            "usertoken": "3853a2bcff70fc6a",
            "searchToken": "37193a80fef6d4ba",
            "lang": "ru",
            "currency": "RUB",
            "startOfferId": 0,
            "offerLimit": 3
        }
    * @returm  в зависимости от типа офера:
    *  {
    *       "aviaOffer" : {...}                // Предложение в авиаперелёте, структура sa_aviaOffer
    *  }
    * или
    * {
    *       "hotel": {...},             // Отель, структура ss_hotelInfoShort
    *       "offers" : [ {...},{...}    // Массив предложений в отеле, структуры sa_hotelOffer
    * }
    */
    public function actionClientGetSearchResult(){
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);
        if (!isset($params['searchToken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_SEARCHTOKEN);
        if (!isset($params['lang']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_LANG);
        if (!isset($params['currency']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_CYRRENCY);
        if (!isset($params['startOfferId']) || !is_numeric($params['startOfferId']) ||  $params['startOfferId'] < 0 )
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_STARTOFFER);
        if (!isset($params['offerLimit']) || !is_numeric($params['offerLimit']) ||  $params['offerLimit'] < 0 )
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_OFFERLIMIT);

        $ap = new ApiPackage([]);
        $ap->addCmd($params, ['serviceName'=>'searcherService', 'action'=>'GetSearchResult','cmdIndex'=> '0'], 'GetSearchResultTemplate');
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Поиск предложений по услугам', '', $params, 'info', 'system.apiservice.info');
            $this->_sendResponseData($ap->fullResult);
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Поиск предложений по услугам',$ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
        }
    }


}