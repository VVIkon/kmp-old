<?php


class ApiCmd implements IApiCmd
{
    public $cmdIndex;           //  json tag
    private $params = [];        //  входные параметры команды
    private $servName;          //  Наименование запускаемого сервиса
    public $action;             //  Наименование запускаемого акшина
    private $cmdTemplate;       //  Наименование шаблона заполнения выходных данных
    public $fullResult =[];     // выходной массив
    public $errorCode = 0;      // Ошибка команды
    public $errorName = '';     // Переданное сообщение об ошибке
    private $cmdArray =[];      // готовый массив переданный из запроса
    public $callBackArray = []; // Набор данных кот. команда запрашивала
    private $currJSON;          // JSON кот. отправляется внутреннему сервису (для проверки)
    public $owmOper = null;     // Операция выполняемая OrderWorkflowManager
    private $module;            // Модуль


    private $serviceStatus = [
                                '0' =>'NEW',
                                '1' =>'W_BOOKED',
                                '2' =>'BOOKED',
                                '3' =>'W_PAID',
                                '4' =>'P_PAID',
                                '5' =>'PAID',
                                '6' =>'CANCELLED',
                                '7' =>'VOIDED',
                                '8' =>'DONE',
                                '9' =>'MANUAL'
                             ];

    private $orderStatus = [
                                '0' =>'NEW',
                                '1' =>'MANUAL',
                                '2' =>'PAID',
                                '3' =>'CLOSE',
                                '4' =>'ANNULED',
                                '5' =>'W_PAID',
                                '6' =>'',
                                '7' =>'',
                                '8' =>'',
                                '9' =>'DONE',
                                '10' =>'BOOKED'
                            ];

    const HOTEL_SERVICE_TYPE = 1;
    const AVIA_SERVICE_TYPE = 2;



    /**
    *  Добавление/Изменение входных параметров
    * @param
    * @returm
    */
    public function setParams(array $par, $template = null)
    {

        $this->params = $par;
        if (!empty($template)) {
            switch ($template) { // Выбор выходного шаблона данных
                case 'orderListTemplate':

// {
//     "lang" : "ru",                 // Требуемый язык возврата информации, возможные значения: ru/en
//    "currency" : "EUR",             // Запрашиваемая валюта просмотра информации, коды валют ISO 4217
//    "filter" : {                    // параметры фильтрации заявок (все - необязательные)
//        "status" : 0,               // Отбираются заявки с укзанным статусом
//        "tourist" : "Иванов Иван",  // Указанная строка должна быть в ФИО туриста заявки
//        "orderNumber" : 232,        // Заявка должна иметь такой номер
//        "startDate" : "2016-01-03", // Даты начала и окончания оказания услуги для одной из услуг заявки должны попадать в startDate-endDate
//        "endDate" : "2016-01-04",
//        "modificationTime" : "2016-01-01T14:34:45" // Дата/время модификации заявки должно быть больше указанного
//    },
//    "sort" : {                      // Параметры сортировки результата
//        "sortOrder" : "asc",    // Порядок сортировки, asc = по возрастанию, desc = по убыванию
//        "sortFields": ["startDate","status"] // массив критериев сортировки: modificationTime(время модификации)/startDate(самая ранняя дата начала оказания услуги)/tourLeaderName (ФИ турлидера)/clientName(ФИ туриста)/countryName(название страны)/status(статус)
//    },
//    "paging" : {                    // Параметры постраничного вывода
//        "offset" : 0,           // Номер заявки, начиная с указанной по порядку
//        "limit" : 10,           // выдать количество заявок
//            }
//}

                    $this->params['orderId'] = nvl($this->params['filter']['orderNumber']);             // 113, // [необязательный] фильтр по ID заявки
                    $this->params['getInCurrency'] = nvl($this->params['currency']);                    // 978, // валюта, в которой выдавать стоимость заявок
                    $this->params['detailsType'] = 'long';                                              // "long", // тип детализации (на данный момент только long)
                    // $this->params['archived'] = nvl($this->params['']);                              // true, // [необязательный] если true, отображать дополнительно архивные завки
                    $this->params['sortBy'] = nvl($this->params['sort']['sortFields']);                 // ["dateStart" , "touristName" , "agentCompany" , "countryName" ,"cityName" , "status" , "lastChangeDate", "offline"], // поле для сортировки
                    $this->params['sortDir'] = nvl($this->params['sort']['sortOrder']);                 // "desc", // направление сортировки
                    //$this->params['countryName'] = nvl($this->params['']);                            // "Россия", // [необязательный] фильтр по стране
                    //$this->params['managerName'] = nvl($this->params['']);                            // "Петров", // [необязательный] фильтр по создателю заявки
                    $this->params['touristName'] = nvl($this->params['filter']['tourist']);                              // "Баронов", // [необязательный] фильтр по турлидеру
                    //$this->params['offline'] = nvl($this->params['']);                                // 0, // если true, отображать только оффлайновые, если false - только онлайновые, если не указан - все
                    $this->params['orderStatus'] = nvl($this->params['filter']['status']);              // 0, // [необязательный] искать по статусу заявки
                    $this->params['modificationDateFrom'] = nvl($this->params['filter']['modificationTime']); // "2017-01-04", // [необязательный] фильтр по дате модификации (начиная с)
                    // $this->params['modificationDateTo'] = nvl($this->params['']);                    // "2017-01-19", // [необязательный] фильтр по дате модификации (заканчивая указанной)
                    $this->params['startDate'] = nvl($this->params['filter']['startDate']);             // "2017-01-04", // [необязательный] фильтр по дате заезда (начиная с)
                    $this->params['finishDate'] = nvl($this->params['filter']['endDate']);              // "2017-01-13", // [необязательный] фильтр по дате заезда (заканчивая указанной)
                    $this->params['offset'] = nvl($this->params['paging']['offset']);                   // 0, // смещение (выдать заявки начиная с указанной по порядку
                    $this->params['limit'] = nvl($this->params['paging']['limit']);                     // 20, // лимит (сколько выдать заявок)

                    unset($this->params['filter']);
                    unset($this->params['sort']);
                    unset($this->params['paging']);
                    unset($this->params['currency']);


                    break;
                case 'SearchStartTemplate':
                    if($this->params['serviceType'] == 1) {       // Отели
                        if ( !isset($this->params['requestDetails']['supplierCode']) ) {
                            $this->params['requestDetails']['supplierCode'] = '';//'academ';
                        }

                        $this->params['requestDetails']['clientId'] = 0;//123423;
                        $this->params['requestDetails']['flexibleDates'] = 0;
                        $this->params['requestDetails']['freeOnly'] = 0;
                        $this->params['requestDetails']['hotelCode'] = '';//"ALMA"; //*
                        $this->params['requestDetails']['hotelSupplier'] = '';//"1045539"; //*
                        $this->params['requestDetails']['hotelIdKt'] = null;

                        if ( !isset($this->params['requestDetails']['hotelChains']) ){
                            $this->params['requestDetails']['hotelChains'] = [];
                        }
                        if ( !isset($this->params['requestDetails']['mealType']) ){
                            $this->params['requestDetails']['mealType'] = '';
                        }
                        if ( !isset($this->params['requestDetails']['category']) ){
                            $this->params['requestDetails']['category'] = '';
                        }

                    } else if($this->params['serviceType'] == 2) { // Авиа
                        if ( !isset($this->params['requestDetails']['childrenAges']) ) {
                            $this->params['requestDetails']['childrenAges'] = [];
                        }
                        $this->params['requestDetails']['flexibleDays'] = 0;
                        $this->params['requestDetails']['flightNumber'] = 0;
                        $this->params['requestDetails']['supplierCode'] = 0;
                        $this->params['requestDetails']['uniteOffers'] = 0;

                        if ( !isset($this->params['requestDetails']['flightClass']) ){
                            $this->params['requestDetails']['flightClass'] = '';
                        }
                        if ( !isset($this->params['requestDetails']['airlineCode']) ){
                            $this->params['requestDetails']['airlineCode'] = [];
                        }

                        if ( isset($this->params['requestDetails']['directFlight'])) {
                            $this->params['requestDetails']['directFlight'] = (int) $this->params['requestDetails']['directFlight'];
                        }else{
                            $this->params['requestDetails']['directFlight'] = 0;
                        }

                    }
                    break;
                    case 'OWM_AddServiceTemplate':
// {
//    "serviceType" : 2,          // Тип услуги
//    "offerId" : 2365475675,     // Идентификатор предложения, для которого создаётся услуга.
//    "orderNumber" : 12345       // (необязательный) Номер заявки, в которую следует добавить услугу.
//}

// {
//    "action": "AddService",
//    "orderId":  17005,
//    "actionParams": {
//                    "serviceType": 1,
//        "serviceParams": {
//                        "offerKey": "gLoVcxVCXs0cvDEX"
//        }
//    },
//    "usertoken": "e891d65b36ca58fb",
//    "token": "fe27b68acc59770"
//}

                        $this->params['action'] =  nvl($this->owmOper);       //'AddService';
                        $this->params['orderId'] =  nvl($this->params['orderNumber']);
                        unset($this->params['orderNumber']);
                        $this->params['actionParams']['serviceType'] = nvl($this->params['serviceType']);
                        unset($this->params['serviceType']);
                        $this->params['actionParams']['serviceParams']['offerId'] =  nvl($this->params['offerId']);
//                        $this->params['actionParams']['serviceParams']['offerKey'] =  nvl($this->params['offerId']);
                        unset($this->params['offerId']);

                        break;
                    case 'OWM_AddTouristTemplate':
                        $this->params['orderId'] = nvl($this->params['orderNumber']);
                        unset($this->params['orderNumber']);
                        $this->params['action'] =   nvl($this->owmOper);       //'AddTourist';
                        unset($this->params['operation']);

                        $a = nvl($this->params['actionParams']['tourist']);
                        $a['document'] = nvl($this->params['actionParams']['touristDocs']);
                        $this->params['actionParams'] = $a;
                        break;
                    case 'OWM_RemoveTouristTemplate':
                        $this->params['orderId'] = nvl($this->params['orderNumber']);
                        unset($this->params['orderNumber']);
                        $this->params['action'] = nvl($this->owmOper);       //'RemoveTourist';
                        unset($this->params['operation']);

                        $a = nvl($this->params['actionParams']['tourist']);
                        $a['document'] = nvl($this->params['actionParams']['touristDocs']);
                        $this->params['actionParams'] = $a;

                        break;
                    case 'OWM_TouristToServiceTemplate':
                        $this->params['orderId'] = nvl($this->params['orderNumber']);
                        unset($this->params['orderNumber']);
                        $this->params['action'] = nvl($this->owmOper);       //'TouristToService';
                        $a['serviceId'] = nvl($this->params['serviceId']);

                        if ( isset($this->params['actionParams']['cardInfo']['bonuscardNumber2']) ||  isset($this->params['actionParams']['cardInfo']['aviaLoyalityProgrammId']) ) {
                            $b['bonuscardNumber2'] = nvl($this->params['actionParams']['cardInfo']['bonuscardNumber2']);
                            $b['aviaLoyalityProgrammId'] = nvl($this->params['actionParams']['cardInfo']['aviaLoyalityProgrammId']);
                        }
                        switch ($this->params['operation']) { // операции
                            case 'add':
                                $b['link'] = 1;
                                $b['touristId'] = nvl($this->params['gatherTouristID']);
                                break;
                            case 'remove':
                                $b['link'] = 0;
                                $b['touristId'] = nvl($par['actionParams']['tourist']['touristId']);
                                break;
                            default:
                                $b['link'] = null;
                        }
                        $a['touristData'][] = $b;
                        $this->params['actionParams'] = $a;

                        break;
                    case 'OWM_BookStartTemplate':
                        $this->params['action'] = nvl($this->owmOper);       //'BookStart';
                        $this->params['orderId'] = nvl($this->params['orderNumber']);
                        unset($this->params['orderNumber']);
                        $a['serviceId'] = nvl($this->params['serviceId']);
                        $a['agreementSet'] = nvl($this->params['agreementSet']);
                        $this->params['actionParams'] = $a;
                        break;

                    case 'OWM_IssueTicketsTemplate':
                        $this->params['action'] = nvl($this->owmOper);       //'BookStart';
                        $this->params['actionParams']['serviceId'] = nvl($this->params['serviceId']);
                        unset($this->params['serviceId']);
                        break;

                    case 'OWM_ServiceCancelTemplate':
                        $this->params['action'] = 'ServiceCancel';
                        break;

                    case 'OWM_BookCancelTemplate':
                        $this->params['action'] = 'BookCancel';
                        break;

                    case 'addDocumentToOrderTemplate':

                        $this->params['presentationFileName'] = nvl($this->params['filename']);
                        unset($this->params['filename']);
                        $this->params['comment'] = nvl($this->params['comment']);
                        $this->params['orderId'] = nvl($this->params['orderNumber']);
                        unset($this->params['orderNumber']);
                        $this->params['objectType'] = BusinessEntityTypes::BUSINESS_ENTITY_ORDER;
                        $this->params['objectId'] = nvl($this->params['orderId']);
                        unset($this->params['filedata']);

                        break;
                    case 'getOrderDocumentsTemplate':
                        $this->params['orderId'] = nvl($this->params['orderNumber']);
                        break;
// v1.5 ----------------------------------------------------------------------------------------------
                    case 'OWM_AddService_SetAdditionalDataTemplate':
                        $this->params['action'] =  nvl($this->owmOper);       //'AddService';
                        $this->params['orderId'] =  nvl($this->params['orderNumber']);
                        unset($this->params['orderNumber']);
                        $this->params['actionParams']['serviceType'] = nvl($this->params['serviceType']);
                        unset($this->params['serviceType']);
                        $this->params['actionParams']['serviceParams']['offerId'] =  nvl($this->params['offerId']);
                        unset($this->params['offerId']);

                        break;

                    case 'OWM_SetAdditionalDataTemplate':
                        $this->params['action'] = 'SetAdditionalData';
//                        $this->params['actionParams']['serviceType'] = $this->params['serviceType'];

                        if (isset($this->params['additionalFields'])) {
                            $this->params['actionParams']['additionalFields'] = nvl($this->params['additionalFields'], []);
                            unset($this->params['additionalFields']);
                        }elseif(isset($this->params['userAdditionalFields'])) {
                            $this->params['actionParams']['additionalFields'] = nvl($this->params['userAdditionalFields'], []);
                            unset($this->params['userAdditionalFields']);
                        }
                        unset($this->params['serviceId']);
                        break;

                    case 'OWM_AddTourist_SetAdditionalDataTemplate':
                        $this->params['orderId'] = nvl($this->params['orderNumber']);
                        unset($this->params['orderNumber']);
                        $this->params['action'] =   nvl($this->owmOper);       //'AddTourist';
                        unset($this->params['operation']);

                        $a = nvl($this->params['actionParams']['tourist']);
                        $a['document'] = nvl($this->params['actionParams']['touristDocs']);
                        $a['userAdditionalFields'] = nvl($this->params['actionParams']['userAdditionalFields']);
                        $this->params['actionParams'] = $a;
                        break;

                    case 'OWM_TouristToService_15_Template':
                        $this->params['orderId'] = nvl($this->params['orderNumber']);
                        unset($this->params['orderNumber']);
                        $this->params['action'] = nvl($this->owmOper);       //'TouristToService';
                        $a['serviceId'] = nvl($this->params['serviceId']);

                        if ( isset($this->params['actionParams']['cardInfo']['bonuscardNumber2']) ||  isset($this->params['actionParams']['cardInfo']['aviaLoyalityProgrammId']) ) {
                            $b['bonuscardNumber2'] = nvl($this->params['actionParams']['cardInfo']['bonuscardNumber2']);
                            $b['aviaLoyalityProgrammId'] = nvl($this->params['actionParams']['cardInfo']['aviaLoyalityProgrammId']);
                        }
                        switch ($this->params['operation']) { // операции
                            case 'add':
                                $b['link'] = 1;
                                $b['touristId'] = nvl($this->params['gatherTouristID']);
                                break;
                            case 'remove':
                                $b['link'] = 0;
                                $b['touristId'] = nvl($par['actionParams']['tourist']['touristId']);
                                break;
                            default:
                                $b['link'] = null;
                        }
                        $a['touristData'][] = $b;
                        $this->params['actionParams'] = $a;

                        break;

                default:
                    $this->params = $par;
            }
        }
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
    * @param $par - входные параметры команды
    * @param $serAct - Наименование запускаемого сервиса
    * @param $template - Наименование запускаемого акшина
    * @returm
    */
    public function loadParams(array $par, array $serAct, $template, $module)
    {
        $this->servName = nvl($serAct['serviceName']);
        $this->action   = nvl($serAct['action']);
        $this->cmdIndex = nvl($serAct['cmdIndex']);
        $this->owmOper  = nvl($serAct['owmOper']);    // OWM операция
        $this->module = $module;

        if(isset($serAct['cmdArray']))
            $this->cmdArray = $serAct['cmdArray'];

        $this->setParams($par, $template);
        $this->cmdTemplate = $template;

    }

    /*
    * Внешнее добавление массива данных (нужно для передачи данных из масиива полученных данных - $queriedArray[])
    * @param array $cmdArray
    * @returm void
    */
    public function setCmdArray($cmdArray){
        if(isset($cmdArray)) {
            $this->cmdArray = $cmdArray;
        }
    }



    /**
    * Аутентификация
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_Auth(array $arr)
    {
        if (isset($arr)) {
//            $this->fullResult = $arr;

            $this->fullResult['token'] = nvl($arr['token']);
            $this->fullResult['tokenDuration'] = nvl(round((strtotime($arr['tokenExpiry']) - strtotime('now')) / 60));
            $this->fullResult['userId'] = nvl($arr['userId']);
        }

    }

    /**
    * Наложение данных на шаблон Order
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_Order(array $arr)
    {
        if (isset($arr)) {
            $this->fullResult['orderId'] = nvl($arr['orderId']);                        // ID, он же номер заявки
            $this->fullResult['orderNumber'] = nvl($arr['orderNumber']);                        // ID, он же номер заявки
            $this->fullResult['status'] = nvl($arr['status']);                          // Статус заявки
            $this->fullResult['clientId'] = nvl($arr['agentId']);                       // ID клиента
            $this->fullResult['clientName'] = nvl($arr['agencyName']);                  // Название клиента
            $this->fullResult['clientManagerId'] = nvl($arr['clientManager']['id']);    // Идентификатор менеджера компании для заявки
            $this->fullResult['clientManagerName'] = trim(nvl($arr['clientManager']['lastName']) . ' ' . nvl($arr['clientManager']['firstName']) . ' ' . nvl($arr['clientManager']['middleName']));   // ФИ менеджера компании для заявки
            $this->fullResult['kmpManagerId'] = nvl($arr['managerKMP']['id']);          // Идентификатор менеджера КМП для заявки
            $this->fullResult['kmpManagerName'] = trim(nvl($arr['managerKMP']['lastName']) . ' ' . nvl($arr['managerKMP']['firstName']) . ' ' . nvl($arr['managerKMP']['middleName']));   // ФИ менеджера КМП для заявки
            $this->fullResult['startDate'] = nvl($arr['startDate']);                    // Дата/время начала оказания первой (по времени) услуги
            $this->fullResult['endDate'] = nvl($arr['endDate']);                        // Дата/время окончания оказания последней (по времени) услуги
            $this->fullResult['country'] = nvl($arr['country']);                        // Название страны для которой услуга
            $this->fullResult['city'] = nvl($arr['city']);                              // Название города, для которого услуга
            $this->fullResult['countryIataCode'] = nvl($arr['countryIataCode']);        // IATA код города, для которого услуга
            $this->fullResult['tourLeadName'] = trim(nvl($arr['touristLastName']) . ' ' . nvl($arr['touristFirstName']));     // ФИ турлидера
            $this->fullResult['touristsCount'] = nvl($arr['touristsNums']);
        }
    }
    /**
    * Наложение данных на шаблон OrderList
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_OrderList(array $arr)
    {
        if (isset($arr)) {
            foreach ($arr as $key => $value) {

                $ord['orderId'] = nvl($value['orderId']);                        // ID, он же номер заявки
                $ord['orderNumber'] = nvl($value['orderNumber']);                        // ID, он же номер заявки
                $ord['dolc'] = nvl($value['dolc']);                              // Date Of Last Changes - дата время последнего изменения заявки.
                $ord['vip'] = nvl($value['vip']);                               //
                $ord['archive'] = nvl($value['archive']);                       //
                $ord['status'] = nvl($value['status']);
                $ord['clientId'] = nvl($value['clientId']);
                $ord['clientName'] = nvl($value['agentCompany']);                  // Название клиента
                $ord['clientManagerId'] = nvl($value['clientManager']['id']);    // Идентификатор менеджера компании для заявки
                $ord['clientManagerName'] = trim(nvl($value['clientManager']['lastName']) . ' ' . nvl($value['clientManager']['firstName']) . ' ' . nvl($value['clientManager']['middleName']));   // ФИ менеджера компании для заявки
                $ord['kmpManagerId'] = nvl($value['managerID']);          // Идентификатор менеджера КМП для заявки
                $ord['kmpManagerName'] = trim(nvl($value['mgrLastName']) . ' ' . nvl($value['mgrFirstName']) . ' ' . nvl($value['mgrMiddleName']));   // ФИ менеджера КМП для заявки
                $ord['startDate'] = nvl($value['startdate']);                    // Дата/время начала оказания первой (по времени) услуги
                $ord['endDate'] = nvl($value['enddate']);                        // Дата/время окончания оказания последней (по времени) услуги
                $ord['country'] = nvl($value['country']);                        // Название страны для которой услуга
                $ord['city'] = nvl($value['city']);                              // Название города, для которого услуга
                $ord['countryIataCode'] = nvl($value['countryIataCode']);        // IATA код города, для которого услуга
                $ord['tourLeadName'] = trim(nvl($value['touristLastName']) . ' ' . nvl($value['touristFirstName']));     // ФИ турлидера
                $ord['touristsCount'] = nvl($value['touristsCount']);
                $this->fullResult[$key] = $ord;

                $params = $this->params;
//                if(isset($value['orderId']))
//                    $params['orderId'] = $value['orderId'];
                $ap = new ApiPackage(['orderService' => []]);
                $ap->addCmd($params, ['serviceName' => 'orderService', 'action' => 'GetOrder', 'cmdIndex' => 'orderService', 'cmdArray'=>$value], 'orderServiceListTemplate');
                $ap->runCmd();
                $this->fullResult[$key]['services'][] = nvl($ap->fullResult);
            }
        }
    }

    /**
    * Наложение данных на шаблон Tourists
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_Tourists(array $arr)
    {
        if (isset($arr)) {
            foreach ($arr as $key => $value) {
                $this->fullResult[$key]['touristId']  = nvl($value['touristId']);       // (необязательный) ID туриста
                $this->fullResult[$key]['maleFemale'] = nvl($value['sex']);             // Пол туриста, в терминах КТ (kt_tourist_base)
                $this->fullResult[$key]['firstName']  = nvl($value['firstName']);       // Имя
                $this->fullResult[$key]['middleName'] = nvl($value['middleName']);      // Отчество
                $this->fullResult[$key]['lastName']   = nvl($value['surName']);         // Фамилия
                $this->fullResult[$key]['dateOfBirth'] = nvl($value['birthdate']);      // ДР  "1975-01-01 00:00",
                $this->fullResult[$key]['email'] = nvl($value['email']);                // email "o.karelin@kmp.ru",
                $this->fullResult[$key]['phone'] = nvl($value['phone']);                // телефон "+79197229001"
            }
        }
    }

    /**
     * Наложение данных на шаблон Tourists
     * @param $arr - массив с полученными данными
     * @returm $this->fullResult - Выходноц массив
     */
    private function imposeTemplate_Tourists_15(array $arr)
    {
        if (isset($arr)) {
            foreach ($arr as $key => $value) {
                $this->fullResult[$key]['touristId']  = nvl($value['touristId']);       // (необязательный) ID туриста
                $this->fullResult[$key]['maleFemale'] = nvl($value['sex']);             // Пол туриста, в терминах КТ (kt_tourist_base)
                $this->fullResult[$key]['firstName']  = nvl($value['firstName']);       // Имя
                $this->fullResult[$key]['middleName'] = nvl($value['middleName']);      // Отчество
                $this->fullResult[$key]['lastName']   = nvl($value['surName']);         // Фамилия
                $this->fullResult[$key]['dateOfBirth'] = nvl($value['birthdate']);      // ДР  "1975-01-01 00:00",
                $this->fullResult[$key]['email'] = nvl($value['email']);                // email "o.karelin@kmp.ru",
                $this->fullResult[$key]['phone'] = nvl($value['phone']);                // телефон "+79197229001"
                $this->fullResult[$key]['touristAdditionalData'] = nvl($value['touristAdditionalData'],[]);                // телефон "+79197229001"
            }
        }
    }

    /**
    * Наложение данных на шаблон TouristDoc
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_TouristDocs(array $arr)
    {
        if (isset($arr)) {
            foreach ($arr as $key => $value) {
                $this->fullResult[$key]['touristId'] = nvl($value['touristId']);                    // ID туриста
                $this->fullResult[$key]['docType'] = nvl($value['document']['documentType']);       // Тип документа из справочника типов документов kt_toursts_doc_type
                $this->fullResult[$key]['firstName'] = nvl($value['firstName']);                    // Имя
                $this->fullResult[$key]['middleName'] = nvl($value['middleName']);                  // Отчество
                $this->fullResult[$key]['lastName'] = nvl($value['surName']);                       // Фамилия
                $this->fullResult[$key]['docSerial'] = nvl($value['document']['serialNum']);        // Серия документа
                $this->fullResult[$key]['docNumber'] = nvl($value['document']['number']);           // номер документа
                $this->fullResult[$key]['docDate'] = nvl($value['document']['issueDate']);          // Дата выдачи
                $this->fullResult[$key]['docExpiryDate'] = nvl($value['document']['expiryDate']);   // Дата окончания действия
                $this->fullResult[$key]['issuedBy'] = nvl($value['document']['issueDepartment']);   // Кем выдан
                $this->fullResult[$key]['address'] = nvl($value['address']);                        // Адрес регистрации
                $this->fullResult[$key]['citizenship'] = nvl($value['document']['citizenship']);    // Код страны выдавшей документ, Alpha2 код ISO 3166
            }
        }
    }

    /**
    * Наложение данных на шаблон OrderService
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
//    private function imposeTemplate_OrderService(array $arr)
//    {
//        if (isset($arr)) {
//            foreach ($arr as $key => $value) {
//                if(isset($value['serviceID'])) {
//                    $this->params["servicesIds"][] = $value['serviceID'];
//                    // Внутренние параметры услуги
//                    $params = $this->params;
//                    $params["servicesIds"] = [$value['serviceID'],];
//                }
//
//                $this->fullResult['orderService'][$key]['serviceId'] = nvl($value['serviceID']);                     // Идентификатор сущности
//                $this->fullResult['orderService'][$key]['serviceType'] = nvl($value['serviceType']);                 // Тип услуги
//                $this->fullResult['orderService'][$key]['status'] = nvl($value['status']);                           // Статус услуги
//                $this->fullResult['orderService'][$key]['online'] = nvl($value['offline']) == true ? false : true;   // Признак онлайн - услуги
//                $this->fullResult['orderService'][$key]['dateOrdered'] = nvl($value['dateOrdered']);                 // Дата создания услуги
//                $this->fullResult['orderService'][$key]['dateStart'] = nvl($value['startDateTime']);                 // Начало действия услуги
//                $this->fullResult['orderService'][$key]['dateFinish'] = nvl($value['endDateTime']);                  // Окончание действия услуги
//                $this->fullResult['orderService'][$key]['onlineModificationAllowed'] = nvl($value['onlineModificationAllowed']);           // Можно ли изменять услугу онлайн
//                $this->fullResult['orderService'][$key]['lastTicketingDate'] = nvl($value['lastTicketingDate']);     // Крайнее время выписки билета
//
//                // Ценовые компоненты услуги, структура ss_salesTerm
//                $apSalesTerm = new ApiPackage(['salesTerm' => [],]);
//                $apSalesTerm->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'salesTerm'], 'salesTermTemplate');
//                $apSalesTerm->runCmd();
//                $cmd = $apSalesTerm->getApiCommandsArr()[0];  // Массив
//                $this->fullResult['orderService'][$key] = nvl($apSalesTerm->fullResult);
//
//                // Штрафы  so_servicePenalties
//                $apSalesTerm = new ApiPackage(['servicePenalty' => [],]);
//                $apSalesTerm->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'servicePenalty', 'cmdArray'=>$cmd->callBackArray], 'servicePenaltyTemplate');
//                $apSalesTerm->runCmd();
//                $this->fullResult['orderService'][$key] = nvl($apSalesTerm->fullResult);
//
//                // Доп. Сервис
//                $this->fullResult['orderService'][$key]['addService']['idAddService'] = nvl($value['idAddService']);                  //id доп.услуги
//                $this->fullResult['orderService'][$key]['addService']['serviceId'] = nvl($value['serviceId']);                     //id доп.услуги
//                $this->fullResult['orderService'][$key]['addService']['serviceSubType'] = nvl($value['serviceSubType']);                //id доп.услуги
//                $this->fullResult['orderService'][$key]['addService']['status'] = nvl($value['status']);                        //id доп.услуги
//                $this->fullResult['orderService'][$key]['addService']['salesTerms']['amountNetto'] = nvl($value['amountNetto']);     //Нетто-цена
//                $this->fullResult['orderService'][$key]['addService']['salesTerms']['amountBrutto'] = nvl($value['amountBrutto']);    //Брутто-цена
//                $this->fullResult['orderService'][$key]['addService']['salesTerms']['currency'] = nvl($value['currency']);        //Валюта предложения
//                $this->fullResult['orderService'][$key]['addService']['salesTerms']['commission']['currency'] = nvl($value['commission_currency']); //Валюта комиссии
//                $this->fullResult['orderService'][$key]['addService']['salesTerms']['commission']['amount'] = nvl($value['commission_amount']); //Сумма комиссии, является частью amountBrutto
//                $this->fullResult['orderService'][$key]['addService']['salesTerms']['commission']['percent'] = nvl($value['commission_percent']); //0 = commission.amount-абсолютная сумма, 1 = commission.amount- % от price.amountNetto
//
//                // Люди привызанные к сервису из состава людей заявки
//                $apSalesTerm = new ApiPackage(['tourists' => [],]);
//                $apSalesTerm->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'tourists', 'cmdArray'=>$cmd->callBackArray], 'serviceTouristsTemplate');
//                $apSalesTerm->runCmd();
//
//                $this->fullResult['orderService'][$key]['addService']['tourists'] = nvl($apSalesTerm->fullResult['tourists']);
//                $this->fullResult['orderService'][$key]['addService']['specParamAddService'] = nvl($value['specParamAddService']);           //массив дополнительных параметров для доп.услуги. Опционально
//                $this->fullResult['orderService'][$key]['addService']['bookedWithService'] = nvl($value['bookedWithService']);             //данный экземпляр доп.услуги бронируется ТОЛЬКО вместе с основной: true/false. Если true – то "status" в доп.услуге ставится 2, если false – то "status" в доп.услуге принимается любой.
//                $this->fullResult['orderService'][$key]['addService']['Name'] = nvl($value['Name']);                          // Имя типа допуслуги
//                $this->fullResult['orderService'][$key]['addService']['TypeName'] = nvl($value['addService']);                      // Тип доп услуги
//
//                $this->fullResult['orderService'][$key]['addServicesAvailable'] = nvl($value['addServicesAvailable']);                        // Возможность добавления доп.услуги к основной
//                $this->fullResult['orderService'][$key]['serviceName'] = nvl($value['serviceName']);                 // Название услуги (формируемое для UI)
//                $this->fullResult['orderService'][$key]['countryName'] = nvl($value['countryName']);                // Название страны для услуги
//                $this->fullResult['orderService'][$key]['cityName'] = nvl($value['cityName']);                   // Город страны для услуги
//                $this->fullResult['orderService'][$key]['countryIataCode'] = nvl($value['countryIataCode']);            // IATA Код страны для услуги
//
//            }
//        }
//    }

    private function imposeTemplate_OrderService(array $arr)
    {
        if (isset($arr)) {
            $params = $this->params;

            foreach ($arr as $key => $value) {
                $orderService = [];
                if(isset($value['serviceID'])) {
                    $this->params["servicesIds"][] = $value['serviceID'];
                    // Внутренние параметры услуги
                    $params["servicesIds"] = [$value['serviceID'],];
                }

                // Ценовые компоненты услуги, структура ss_salesTerm
                $apSalesTerm = new ApiPackage(['salesTerm' => [],]);
                $apSalesTerm->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'salesTerm'], 'salesTermTemplate');
                $apSalesTerm->runCmd();
                $cmd = $apSalesTerm->getApiCommandsArr()[0];  // Массив

                // Штрафы  so_servicePenalties
                $apServicePenaly = new ApiPackage(['servicePenalty' => [],]);
                $apServicePenaly->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'servicePenalty', 'cmdArray'=>$cmd->callBackArray], 'servicePenaltyTemplate');
                $apServicePenaly->runCmd();

                // Люди привызанные к сервису из состава людей заявки
                $apTourists = new ApiPackage(['tourists' => [],]);
                $apTourists->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'tourists', 'cmdArray'=>$cmd->callBackArray], 'serviceTouristsTemplate');
                $apTourists->runCmd();

                $addService=[
                    'idAddService' => nvl($value['idAddService']),                  //id доп.услуги
                    'serviceId' => nvl($value['serviceId'], nvl($value['serviceID'])),                     //id доп.услуги
                    'serviceSubType' => nvl($value['serviceSubType']),                //id доп.услуги
                    'status' => nvl($value['status']),                        //id доп.услуги
                    'salesTerms' =>[
                        'amountNetto' => nvl($value['amountNetto']),     //Нетто-цена
                        'amountBrutto' => nvl($value['amountBrutto']),    //Брутто-цена
                        'currency' => nvl($value['currency']),        //Валюта предложения
                        'commision' =>[
                            'currency' => nvl($value['commission_currency']), //Валюта комиссии
                            'amount' =>   nvl($value['commission_amount']), //Сумма комиссии, является частью amountBrutto
                            'percent' =>  nvl($value['commission_percent']), //0 = commission.amount-абсолютная сумма, 1 = commission.amount- % от price.amountNetto
                        ]
                    ],
                    'tourists' => nvl($apTourists->fullResult['tourists']),
                    'specParamAddService' => nvl($value['specParamAddService']),
                    'bookedWithService' => nvl($value['bookedWithService']),
                    'Name' => nvl($value['Name']),                          // Имя типа допуслуги
                    'TypeName' => nvl($value['addService']),                      // Тип доп услуги
                ];

                $orderService=[
                    'serviceId'=>nvl($value['serviceID']),
                    'serviceType'=>nvl($value['serviceType']),
                    'status'=>nvl($value['status']),
                    'online'=>nvl($value['offline']) == true ? false : true,
                    'dateOrdered'=>nvl($value['dateOrdered']),
                    'dateStart'=>nvl($value['startDateTime']),
                    'dateFinish'=>nvl($value['endDateTime']),
                    'onlineModificationAllowed'=>nvl($value['onlineModificationAllowed']),
                    'lastTicketingDate'=>nvl($value['lastTicketingDate']),
                    'salesTerm'=>nvl($apSalesTerm->fullResult, []),
                    'servicePenalty'=>nvl($apServicePenaly->fullResult, []),
                    'addService'=>nvl($addService),
                    'addServicesAvailable'=>nvl($value['addServicesAvailable'],false),
                    'serviceName'=>nvl($value['serviceName']),
                    'countryName'=>nvl($value['countryName']),
                    'cityName'=>nvl($value['cityName']),
                    'countryIataCode'=>nvl($value['countryIataCode']),
                ];

                // Привязанные к услуге туристы
                $apServiceTourists = new ApiPackage(['serviceTourists'=>[] ]);
                $apServiceTourists->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderTourists', 'cmdIndex' => 'serviceTourists'],  'serviceTouristIDTemplate');
                $apServiceTourists->runCmd();

                // Данные услуги
                $apServiceData = new ApiPackage(['serviceData'=>[] ]);
                $apServiceData->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'serviceData'], 'serviceDataTemplate');
                $apServiceData->runCmd();

                // Сборка по service
                $complexTag =[
                    'orderService' => $orderService,
                    'serviceTourists'=>nvl($apServiceTourists->fullResult['serviceTourists'], []),
                    'serviceData'=>nvl($apServiceData->fullResult, []),
                ];

                $this->fullResult[] = $complexTag;
            }
        }
    }
    /**
     * Наложение данных на шаблон OrderService
     * @param $arr - массив с полученными данными
     * @returm $this->fullResult - Выходноц массив
     */
    private function imposeTemplate_OrderService_15(array $arr)
    {
        if (isset($arr)) {
            $params = $this->params;

            foreach ($arr as $key => $value) {
                $orderService = [];
                if(isset($value['serviceID'])) {
                    $this->params["servicesIds"][] = $value['serviceID'];
                    // Внутренние параметры услуги
                    $params["servicesIds"] = [$value['serviceID'],];
                }

                // Ценовые компоненты услуги, структура ss_salesTerm
                $apSalesTerm = new ApiPackage(['salesTerm' => [],]);
                $apSalesTerm->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'salesTerm'], 'salesTermTemplate');
                $apSalesTerm->runCmd();
                $cmd = $apSalesTerm->getApiCommandsArr()[0];  // Массив

                // Штрафы  so_servicePenalties
                $apServicePenaly = new ApiPackage(['servicePenalty' => [],]);
                $apServicePenaly->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'servicePenalty', 'cmdArray'=>$cmd->callBackArray], 'servicePenaltyTemplate');
                $apServicePenaly->runCmd();

                // Люди привызанные к сервису из состава людей заявки
                $apTourists = new ApiPackage(['tourists' => [],]);
                $apTourists->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'tourists', 'cmdArray'=>$cmd->callBackArray], 'serviceTouristsTemplate');
                $apTourists->runCmd();

                $addService=[
                    'idAddService' => nvl($value['idAddService']),                  //id доп.услуги
                    'serviceId' => nvl($value['serviceId']),                     //id доп.услуги
                    'serviceSubType' => nvl($value['serviceSubType']),                //id доп.услуги
                    'status' => nvl($value['status']),                        //id доп.услуги
                    'salesTerms' =>[
                        'amountNetto' => nvl($value['amountNetto']),     //Нетто-цена
                        'amountBrutto' => nvl($value['amountBrutto']),    //Брутто-цена
                        'currency' => nvl($value['currency']),        //Валюта предложения
                        'commision' =>[
                            'currency' => nvl($value['commission_currency']), //Валюта комиссии
                            'amount' =>   nvl($value['commission_amount']), //Сумма комиссии, является частью amountBrutto
                            'percent' =>  nvl($value['commission_percent']), //0 = commission.amount-абсолютная сумма, 1 = commission.amount- % от price.amountNetto
                        ]
                    ],
                    'tourists' => nvl($apTourists->fullResult['tourists']),
                    'specParamAddService' => nvl($value['specParamAddService']),
                    'bookedWithService' => nvl($value['bookedWithService']),
                    'Name' => nvl($value['Name']),                          // Имя типа допуслуги
                    'TypeName' => nvl($value['addService']),                      // Тип доп услуги
                ];

                $orderService=[
                    'serviceId'=>nvl($value['serviceID']),
                    'serviceType'=>nvl($value['serviceType']),
                    'status'=>nvl($value['status']),
                    'online'=>nvl($value['offline']) == true ? false : true,
                    'dateOrdered'=>nvl($value['dateOrdered']),
                    'dateStart'=>nvl($value['startDateTime']),
                    'dateFinish'=>nvl($value['endDateTime']),
                    'onlineModificationAllowed'=>nvl($value['onlineModificationAllowed']),
                    'lastTicketingDate'=>nvl($value['lastTicketingDate']),
                    'salesTerm'=>nvl($apSalesTerm->fullResult, []),
                    'servicePenalty'=>nvl($apServicePenaly->fullResult, []),
                    'addService'=>nvl($addService),
                    'addServicesAvailable'=>nvl($value['addServicesAvailable'], false),
                    'serviceName'=>nvl($value['serviceName']),
                    'countryName'=>nvl($value['countryName']),
                    'cityName'=>nvl($value['cityName']),
                    'countryIataCode'=>nvl($value['countryIataCode']),
                    'additionalData'=>nvl($value['additionalData'], []),
                ];

                // Привязанные к услуге туристы
                $apServiceTourists = new ApiPackage(['serviceTourists'=>[] ]);
                $apServiceTourists->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderTourists', 'cmdIndex' => 'serviceTourists'],  'serviceTouristIDTemplate');
                $apServiceTourists->runCmd();

                // Данные услуги
                $apServiceData = new ApiPackage(['serviceData'=>[] ]);
                $apServiceData->addCmd(nvl($params), ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'serviceData'], 'serviceDataTemplate');
                $apServiceData->runCmd();

                // Сборка по service
                $complexTag =[
                    'orderService' => $orderService,
                    'serviceTourists'=>nvl($apServiceTourists->fullResult['serviceTourists'], []),
                    'serviceData'=>nvl($apServiceData->fullResult, []),
                    'additionalData'=>nvl($value['additionalData'], []),
                ];

                $this->fullResult[] = $complexTag;
            }
        }
    }
    /**
* Наложение данных на шаблон OrderServiceList (упрощенный шаблон OrderService)
* @param $arr - массив с полученными данными
* @returm $this->fullResult - Выходноц массив
*/
    private function imposeTemplate_OrderServiceList(array $arr)
    {
        if (isset($arr)) {
            foreach ($arr as $key => $value) {
                $this->fullResult['orderService'][$key]['serviceId'] = nvl($value['serviceID']);                        // Идентификатор сущности
                $this->fullResult['orderService'][$key]['serviceType'] = nvl($value['serviceType']);                    // Тип услуги
                $this->fullResult['orderService'][$key]['serviceName'] = nvl($value['serviceName']);                    // Наименование услуги
                $this->fullResult['orderService'][$key]['serviceIconURL'] = nvl($value['serviceIconURL']);              //
                $this->fullResult['orderService'][$key]['status'] = nvl($value['status']);                              // Статус услуги
                $this->fullResult['orderService'][$key]['startDateTime'] = nvl($value['startDateTime']);                // Начало действия услуги
                $this->fullResult['orderService'][$key]['endDateTime'] = nvl($value['endDateTime']);                    // Окончание действия услуги
                $this->fullResult['orderService'][$key]['supplierId'] = nvl($value['supplierId']);                      // ID плоставщика услуг
                $this->fullResult['orderService'][$key]['requestedSum'] = nvl($value['requestedSum']);                  //
                $this->fullResult['orderService'][$key]['requestedNetSum'] = nvl($value['requestedNetSum']);            //
                $this->fullResult['orderService'][$key]['localSum'] = nvl($value['localSum']);                          //
                $this->fullResult['orderService'][$key]['localNetSum'] = nvl($value['localNetSum']);                    //
                $this->fullResult['orderService'][$key]['localCommission'] = nvl($value['localCommission']);            // комиссия агента в локальной валюте
                $this->fullResult['orderService'][$key]['requestedCommission'] = nvl($value['requestedCommission']);    // комиссия агента в валюте просмотра
                $this->fullResult['orderService'][$key]['discount'] = nvl($value['discount']);                          // Скидка
                $this->fullResult['orderService'][$key]['amendAllowed'] = nvl($value['amendAllowed']);                  // Можно ли изменять услугу онлайн
                $this->fullResult['orderService'][$key]['dateAmend'] = nvl($value['dateAmend']);                        // Таймлимит на оплату
                $this->fullResult['orderService'][$key]['dateOrdered'] = nvl($value['dateOrdered']);                    // Дата создания услуги
                $this->fullResult['orderService'][$key]['countryName'] = nvl($value['countryName']);                    // Дата создания услуги
                $this->fullResult['orderService'][$key]['cityName'] = nvl($value['cityName']);                          // Дата создания услуги
                $this->fullResult['orderService'][$key]['countryIataCode'] = nvl($value['countryIataCode']);            // IATA Код страны для услуги
                $this->fullResult['orderService'][$key]['offline'] = nvl($value['offline']);                            // Признак "офлайновости" услуги
            }
        }
    }

    /**
    * Наложение данных на шаблон SalesTerm
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_SalesTerm(array $arr)
    {
        if (isset($arr)) {
            foreach ($arr as $key => $value) {
                    $this->fullResult[$key] = nvl($value['offerInfo']['salesTermsInfo']);
            }
        }
    }

    /**
    * Наложение данных на шаблон ServicePenalty
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_ServicePenalty(array $arr)
    {
        if (isset($arr)) {
            foreach ($arr as $key => $value) {
                    $this->fullResult[$key] = nvl($value['penalties']);
            }
        }
    }

    /**
        * Наложение данных на шаблон ServiceTourists
        * @param $arr - массив с полученными данными
        * @returm $this->fullResult - Выходноц массив
        */
    private function imposeTemplate_ServiceTourists(array $arr)
    {
        if (isset($arr)) {
            foreach ($arr as $key => $value) {
                $this->fullResult[$key] = nvl($value['serviceTourists']);
            }
        }
    }

    /**
        * Наложение данных на шаблон ServiceTourists
        * @param $arr - массив с полученными данными
        * @returm $this->fullResult - Выходноц массив
        */
    private function imposeTemplate_ServiceTouristID(array $arr)
    {
        if (isset($arr)) {
            foreach ($arr as $key => $value) {
                    $this->fullResult['serviceTourists'][] = nvl($value['touristId']);
            }
        }
    }

    /**
    * Наложение данных на шаблон ServiceData
     * $this->params - здесь не важен, т.к. передаётся готовый array ($value)
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_ServiceData(array $arr)
    {
        if (isset($arr)) {
            foreach ($arr as $key => $value) {
                switch ($value['serviceType']) {
                    case 1:  //Отели
                        $hotellTemplate = [
                            'hotelOffer' => [],         // Предложение отеля, структура sa_hotelOffer
                            'hotelReservations' => [],  // массив броней, структуры sa_hotelReservation
                            'hotelVouchers' => [],      // Ваучеры, структуры sa_hotelVoucher
                            'cancelPenalties' => []     // Условия отмены (Штрафы за отмену), массив структур ss_cancelPenalty
                        ];
                        $apDataTerm = new ApiPackage($hotellTemplate);
                        $apDataTerm->addCmd($this->params, ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'hotelOffer', 'cmdArray'=>$value], 'hotelOfferTemplate');
                        $apDataTerm->addCmd($this->params, ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'hotelReservations', 'cmdArray'=>$value], 'hotelReservationsTemplate');
                        $apDataTerm->addCmd($this->params, ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'hotelVouchers', 'cmdArray'=>$value], 'hotelVouchersTemplate');
                        $apDataTerm->addCmd($this->params, ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'cancelPenalties', 'cmdArray'=>$value], 'cancelPenaltiesTemplate');
                        $apDataTerm->runCmd();
                        $this->fullResult['serviceData'][$key] = nvl($apDataTerm->fullResult);
                        break;
                    case 2:     //Avia
                        $aviaTemplate = [
                            'aviaOffer' => [],               // Предложение в авиаперелёте, структура sa_aviaOffer
                            'cancelPenalties' => [],        // Условия отмены (Штрафы за отмену), массив структур ss_cancelPenalty
                            'aviaReservations' => [],       // Брони в авиаперелёте, массив структур ss_aviaReservation
                            'aviaTickets' => [],            // Билеты, структуры ss_aviaTicket
                            'aviaTicketReceipts' => [],     // Маршрутные квитанции, структуры sa_aviaTicketReceipt
                            'fareRules' => [                // массив правил тарифов для авиаперелёта
                                [
                                    'segments' => [],
                                    'aviaFareRule' => []    // Правило тарифа для участка перелёта, структура ss_aviaFareRule
                                ],
                            ]
                        ];
                        $apDataTerm = new ApiPackage($aviaTemplate);
                        $apDataTerm->addCmd($this->params, ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'aviaOffer', 'cmdArray'=>$value], 'aviaOfferTemplate');
                        $apDataTerm->addCmd($this->params, ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'cancelPenalties', 'cmdArray'=>$value], 'aviaCancelPenaltiesTemplate');
                        $apDataTerm->addCmd($this->params, ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'aviaReservations', 'cmdArray'=>$value], 'aviaReservationsTemplate');
                        $apDataTerm->addCmd($this->params, ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'aviaTickets', 'cmdArray'=>$value], 'aviaTicketsTemplate');
                        $apDataTerm->addCmd($this->params, ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'aviaTicketReceipts', 'cmdArray'=>$value], 'aviaTicketReceiptsTemplate');
                        $apDataTerm->addCmd($this->params, ['serviceName' => 'orderService', 'action' => 'GetOrderOffers', 'cmdIndex' => 'fareRules', 'cmdArray'=>$value], 'fareRulesTemplate');
                        $apDataTerm->runCmd();
                        $this->fullResult['serviceData'][$key] = nvl($apDataTerm->fullResult);
                        break;
                }

            }
        }
    }

    /**
    * Наложение данных на шаблон HotelOffer
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_hotelOffer(array $arr)
    {
        if (isset($arr)) {
            $value = nvl($arr['offerInfo']); // -- пока так оферов м.б. много
            if (isset($value)) {
                $tmp['offerId'] = nvl($value['offerId']);                            // Идентификатор предложения
                $tmp['dateFrom'] = nvl($value['dateFrom']);                          // Дата заезда
                $tmp['dateTo'] = nvl($value['dateTo']);                              // Дата выезда
                // Убираю все salesTerms кроме viewCurrency
                foreach ($value['salesTermsInfo'] as $key3 => $salesTermInfo) {
                    if ($key3 != 'viewCurrency'){
                        unset($value['salesTermsInfo'][$key3]);
                    }
                }
                $tmp['salesTerm'] = nvl($value['salesTermsInfo']['viewCurrency']);  //'...ss_salesTerm...';                        // ценовые компоненты предложения, структура ss_salesTerm
                $tmp['salesTermBreakdown'] = nvl($value['salesTermsBreakdownInfo']);      // (необязательное) ценовые компоненты предложения с разбивкой по дням, структура ss_salesTermBreakdown
                $tmp['available'] = nvl($value['available']);                        // Доступно при поиске (false = проверка доступности при бронировании)
                $tmp['specialOffer'] = nvl($value['specialOffer']);                  // Признак специального предложения
                $tmp['roomType'] = nvl($value['roomType']);                          // Тип предложения (Можно использовать как имя)
                $tmp['roomTypeDescription'] = nvl($value['roomTypeDescription']);    // Описание типа предложения (Почему то всё совпадает с типом)
                $tmp['mealType'] = nvl($value['mealType']);                          // Тип питания из справочника типов питания КТ
                $tmp['roomServices'] = nvl($value['roomServices']);                  // Массив услуг в номерах, структуры ss_roomService
                $tmp['adults'] = nvl($value['adult']);                               // Количество взрослых в номере
                $tmp['children'] = nvl($value['child']);                             // Количество детей в номере
                $tmp['checkInTime'] = nvl($value['hotelInfo']['checkInTime']);       // Заезд после
                $tmp['checkOutime'] = nvl($value['hotelInfo']['checkOutTime']);      // Выезд до
                $tmp['fareName'] = nvl($value['fareName']);                          // Название тарифа (с версии GPTS 6.11)
                $tmp['fareDescription'] = nvl($value['fareDescription']);            //Описание тарифа (с версии GPTS 6.11)
                $tmp['mealOptionsAvailable'] = nvl($value['mealOptionsAvailable']);  // Наличие доп.питания (с версии GPTS 6.11). false - отсутствует, true - есть в наличии.
                $tmp['availableRooms'] = nvl($value['availableRooms']);              // Количество доступных номеров для данного предложения (с версии GPTS 6.11)
                $tmp['travelPolicy'] = nvl($value['travelPolicy']);                 /// Признаки корпоративных правил в предложении ss_TP_OfferValue
            }
        }
        $this->fullResult = nvl($tmp);
    }
    /**
    * Наложение данных на шаблон hotelReservations
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_hotelReservations(array $arr)
    {
        if (isset($arr)) {
            $value = nvl($arr['offerInfo']['hotelReservations']);
            if (isset($value)) {
                $resTmp['reservationId'] = nvl($value['reservationId']);     // Идентификатор брони
                $resTmp['tourists'] = nvl($value['tourists']);               // Массив идентификаторов туристов, для которых создана бронь
                $resTmp['status'] = nvl($value['status']);      // статус брони, 1 = Действует, 2 = Отменена
            }
        }
        $this->fullResult[] = nvl($resTmp);
    }
    /**
    * Наложение данных на шаблон hotelVouchers
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_hotelVouchers(array $arr)
    {
        if (isset($arr)) {
            $value = $arr['offerInfo']['hotelReservations'];
            if (isset($value['hotelVouchers'])) {
                //$vouchTmp['reservationId'] = $value['reservationId'];     // Идентификатор брони
                foreach ($value['hotelVouchers'] as $key2 => $value2) {
                    $vouchTmp[$key2]['voucherID'] = nvl($value2['voucherId']);           // Идентификатор ваучера
                    $vouchTmp[$key2]['reservationId'] = nvl($value2['reservationId']);   // Идентификатор брони
                    $vouchTmp[$key2]['receiptUrl'] = nvl($value2['receiptUrl']);         // ссылка на файл ваучера
                }
            }
        }
        $this->fullResult = nvl($vouchTmp);
    }
    /**
    * Наложение данных на шаблон cancelPenalties
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_cancelPenalties(array $arr)
    {
        if (isset($arr)) {
            if(isset($arr['offerInfo']['cancelPenalties']['supplier']))
                $penaltTmp = $arr['offerInfo']['cancelPenalties']['supplier'];
            $this->fullResult = nvl($penaltTmp);
        }
    }
    /**
    * Наложение данных на шаблон aviaOffer
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_aviaOffer(array $arr)
    {
        if (isset($arr)) {
            $value = nvl($arr['offerInfo']); // -- пока так оферов м.б. много
            if (isset($value)) {
                $aviaTmp['offerID'] = nvl($value['offerID']);                         // Идентификатор сущности
                $aviaTmp['offerKey'] = nvl($value['offerKey']);                        // Идентификатор сущности
                $aviaTmp['touristsAges'] = nvl($value['requestData']);                 // Возростная статистика
                $aviaTmp['lastPayDate'] = nvl($value['lastPayDat']);                  // До какой даты/времени нужно оплатить услугу.
                $aviaTmp['lastTicketingDate'] = nvl($value['lastTicketcketingDate']); // До какой даты/времени можно выписать билет.
                $aviaTmp['flightTariff'] = nvl($value['flightTarif']);                // Тариф
                $aviaTmp['fareType'] = nvl($value['fareType']);                       // тип тарифа
                $aviaTmp['salesTermsInfo'] = nvl($arr['serviceSalesTermsInfo']);                       // тип тарифа
                $aviaTmp['itinerary'] = nvl($value['itinerary']);                     //  Маршрут, массив трипов, структур  sa_aviaTrip,
                $aviaTmp['travelPolicy'] = nvl($value['travelPolicy']);                 /// Признаки корпоративных правил в предложении ss_TP_OfferValue
            }
        }

        $this->fullResult[] = nvl($aviaTmp);
    }
    /**
    * Наложение данных на шаблон cancelPenalties
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_aviaCancelPenalties(array $arr)
    {
//        if (isset($arr)) {
//            $penaltTmp = nvl();
//        }
        $this->fullResult = nvl($arr['offerInfo']['cancelPenalties']);
    }

    /**
    * Наложение данных на шаблон aviaReservations
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_aviaReservations(array $arr)
    {
        $val = null;
//        $tmp = [];
        if (isset($arr)) {
//            if(isset($arr['offerInfo']['pnr']['pnrNumber'])){
//                $val = isset($arr['offerInfo']['pnr']['pnrNumber']);
//            }
            $reservTmp['PNR'] = nvl($arr['offerInfo']['pnr']['pnrNumber']);
            $reservTmp['status'] = nvl($arr['offerInfo']['pnr']['status']);   // статус PNR, 1 = действует, 2 = Отменён
            if(isset($arr['offerInfo']['itinerary'])){
                $val = $arr['offerInfo']['itinerary'];
                foreach ($val as $key => $values) {
                    if (isset($values['segments'])) {
                        $value = $values['segments'];
                        foreach ($value as $key2 => $value2) {
                            $tmp['segments'] = nvl($value2['segment']);                   // массив идентификаторов сегментов для которых создана бронь
                            $tmp['supplierCode'] = nvl($value2['supplierCodeSegment']);   // идентификатор GDS
                        }
                    }
                    $reservTmp['itinerary'][] = nvl($tmp);
                }
            }
        }
        $this->fullResult[] = nvl($reservTmp);
    }
    /**
    * Наложение данных на шаблон aviaTickets
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_aviaTickets(array $arr)
    {
        if (isset($arr)) {
            if (isset($arr['offerInfo']['pnr']['tickets'])) {
                $value = $arr['offerInfo']['pnr']['tickets'];
                foreach($value as $key2=>$value2) {
                    $ticketTmp[$key2]['pnr'] = nvl($value2['pnr']);
                    $ticketTmp[$key2]['touristId'] = nvl($value2['touristId']);          // Идентификатор туриста, для которого выписан билет
                    $ticketTmp[$key2]['ticketNumber'] = nvl($value2['ticketNumber']);    // Номер билета
                    $ticketTmp[$key2]['ticketStatus'] = nvl($value2['ticketStatus']);    // Статус билета
                    $ticketTmp[$key2]['newTicket'] = nvl($value2['newTicket']);          // Идентификатор билета, на который поменян данный билет (для статуса CHANGED)
                }
            }
        }
        $this->fullResult = nvl($ticketTmp);
    }
    /**
    * Наложение данных на шаблон aviaTicketReceipts
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_aviaTicketReceipts(array $arr)
    {
        if (isset($arr)) {
            if (isset($arr['offerInfo']['pnr']['receipts'])) {
                $value = $arr['offerInfo']['pnr']['receipts'];
                foreach ($value as $key1 => $value1) {
                    $tiResTmp[$key1]['ticketNumbers'] = nvl($value1['ticketNumbers']);  // Номера билетов, для которых создана квитанция
                    $tiResTmp[$key1]['receiptUrl'] = nvl($value1['receiptUrl']);        // ссылка на файл маршрутной квитанции
                }
            }
        }
        $this->fullResult = nvl($tiResTmp);
    }
    /**
    * Наложение данных на шаблон fareRules
    * @param $arr - массив с полученными данными
    * @returm $this->fullResult - Выходноц массив
    */
    private function imposeTemplate_fareRules(array $arr)
    {
//        $tmp = null;
//        $tmp1 = null;
        if (isset($arr)) {
            if (isset($arr['offerInfo']['fareRules']['segments'])) {
                $val = $arr['offerInfo']['fareRules']['segments'];
                foreach ($val as $key => $value) {
                    $tmp[$key]['tripId'] = nvl($value['tripId']);
                    $tmp[$key]['flightSegmrntName'] = nvl($value['tripId']);
                }
            }
            $faruTmp['segments'] = nvl($tmp);
            if (isset($arr['offerInfo']['fareRules']['aviaFareRule'])) {
                $val = $arr['offerInfo']['fareRules']['aviaFareRule'];
                foreach ($val as $key => $value) {
                    $tmp1[$key] = nvl($value);
                }
            }
            $faruTmp['aviaFareRule'] = nvl($tmp1);
        }
        $this->fullResult[] = nvl($faruTmp);
    }

    /**
     * Наложение данных на шаблон
     * @param
     * @returm
     */
    private function imposeTemplate_SuggestLocation(array $arrs)
    {
        if (isset($arrs)) {
            foreach ($arrs as $key => $arr){
                unset($arr['synonyms']);
                $this->fullResult[$key] = $arr;
            }
        }
    }

    /**
     * Наложение данных на стандартный шаблон
     * @param
     * @returm
     */
    private function imposeTemplate_Basic(array $arr)
    {
        if (isset($arr)) {
            $this->fullResult = nvl($arr);
        }
    }

    /**
     * Наложение данных на шаблон GetSearchResult
     * @param
     * @returm
     */
    private function imposeTemplate_GetSearchResult(array $arr, $params)
    {
        $resps = [];
        if (isset($arr)) {
            $serviceType = nvl($arr['serviceType'], '0');

            $responses = nvl($arr['response'],[]);
            $resps=[];

            switch ($serviceType) {             // Выбор выходного шаблона данных
                case self::HOTEL_SERVICE_TYPE:  //Отельный шаблон

                    foreach ($responses as $key1 => $response) {
                        $services = nvl($response['hotel']['services'], []);
                        foreach ($services as $key => $service) {
                            $srv['name'] = nvl($service['name']);
                            $srv['isBillable'] = nvl($service['isBillable'], 0) > 0 ? 'true' : 'false';
                            $services[$key] = $srv;
                        }
                        $offers = nvl($response['offers'], []);
                        foreach ($offers as $key2 => $offer) {
                            $salesTermInfos = nvl($offer['salesTermsInfo']);
                            unset($offer['supplierCode']);
                            unset($offer['hotelId']);
                            unset($offer['offerKey']);
                            $offer['salesTermsBreakdown'] = nvl($offer['salesTermsBreakdownInfo']);
                            unset($offer['salesTermsBreakdownInfo']);

                            $offer['adults'] = nvl($offer['adult']);
                            unset($offer['adult']);
                            $offer['children'] = nvl($offer['child']);
                            unset($offer['child']);

                            $offer['available'] = nvl($offer['available'], 0) > 0 ? 'true' : 'false';
                            $offer['specialOffer'] = nvl($offer['specialOffer'], 0) > 0 ? 'true' : 'false';
                            $offer['cancelAbility'] = nvl($offer['cancelAbility'], 0) > 0 ? 'true' : 'false';
                            $offer['modifyAbility'] = nvl($offer['modifyAbility'], 0) > 0 ? 'true' : 'false';
                            $offer['mealOptionsAvailable'] = nvl($offer['mealOptionsAvailable'], 0) > 0 ? 'true' : 'false';
                            $offer['roomServices'] = nvl($offer['roomServices'], []);

                            foreach ($salesTermInfos as $key3 => $salesTermInfo) {
                                if ($key3 != 'viewCurrency') {
                                    unset($salesTermInfos[$key3]);
                                }
                            }
                            $offers[$key2] = $offer;
                            unset($salesTermInfos['viewCurrency']['supplier']);
                            $offers[$key2]['salesTerm'] = nvl($salesTermInfos['viewCurrency']['client']);
                            unset($offers[$key2]['salesTermsInfo']);

                        }
                        $responses[$key1]['hotel']['services'] = $services;
                        $responses[$key1]['offers'] = $offers;
                        $resps = $responses;
                    }
                    break;
                case self::AVIA_SERVICE_TYPE:  // Авиационный шаблон
                    foreach ($responses as $key1 => $response) {
                        $offer = [];
                        $offer['offerID'] = nvl($response['offerId']);                           // Идентификатор сущности
                        $offer['touristsAges'] = [
                            'adult' => nvl($response['adult'], 0),                          // Количество взрослых
                            'child' => nvl($response['child'], 0),                          // Количество детей
                            'infant' => nvl($response['infant'], 0),                         // Количество младенцев
                            'InfantWithPlace' => nvl($response['InfantWithPlace'], 0),      // Количество предоставляемых для младенцев мест
                        ];

                        $offer['lastPayDate'] = "";                                         // До какой даты/времени нужно оплатить услугу.
                        $offer['lastTicketingDate'] = nvl($response['lastTicketingDate']);  // До какой даты/времени можно выписать билет.
                        $offer['flightTariff'] = nvl($response['flightTariff']);            // тариф
                        $offer['fareType'] = nvl($response['fareType']);                    // тип тарифа
                        $salesTerm = nvl($response['price']['viewCurrency']);

                        $offer['salesTerm'] = [                                                                 // цена КМП для клиента, структура ss_salesTerm
                            'amountNetto' => nvl($salesTerm['amountNetto']),                                    // Нетто-цена
                            'amountBrutto' => nvl($salesTerm['amountBrutto']),                                  // Брутто-цена
                            'currency' => nvl($response['price']['nativeSupplier']['supplierCurrency']),        // Валюта предложения
                            'commission' => [
                                'currency' => nvl($response['price']['nativeSupplier']['supplierCurrency']),    // Валюта комиссии
                                'amount' => nvl($salesTerm['agentCommission']),                                 // Сумма комиссии, является частью amountBrutto
                                'percent' => nvl($salesTerm['percent'], 0)                                       // 0 = commission.amount-абсолютная сумма, 1 = commission.amount- % от salesTerm.amountNetto
                            ],
                        ];

                        $initerarys = nvl($response['itinerary'], []);
                        $intr = [];
                        foreach ($initerarys as $initerary) {
                            $intr['tripID'] = nvl($initerary['tripId'], 0);
                            $intr['routeName'] = nvl($initerary['routeName']);
                            $intr['duration'] = nvl($initerary['duration'], 0);

                            $segments = $initerary['segments'];
                            foreach ($segments as $segm) {
                                $segment['tripId'] = nvl($initerary['tripId'], 0);
                                $segment['flightSegmentName'] = nvl($segm['flightSegmentName']);
                                $segment['segmentNum'] = nvl($segm[''], 0);
                                $segment['validatingAirline'] = nvl($segm['validatingAirline']);
                                $segment['marketingAirline'] = nvl($segm['marketingAirline']);
                                $segment['operatingAirline'] = nvl($segm['operatingAirline']);
                                $segment['flightNumber'] = nvl($segm['flightNumber'], 0);
                                $segment['aircraft'] = nvl($segm['aircraftName']);
                                $segment['categoryClassType'] = nvl($segm['categoryClass']['classType']);
                                $segment['duration'] = nvl($segm['duration']);
                                $segment['departureAirportCode'] = nvl($segm['departureAirportCode']);
                                $segment['departureCityName'] = nvl($segm['departureCityName']);
                                $segment['departureDate'] = nvl($segm['departureDate']);
                                $segment['departureTerminal'] = nvl($segm['departureTerminal']);
                                $segment['arrivalAirportCode'] = nvl($segm['arrivalAirportCode']);
                                $segment['arrivalCityName'] = nvl($segm['arrivalCityName']);
                                $segment['arrivalDate'] = nvl($segm['arrivalDate']);
                                $segment['arrivalTerminal'] = nvl($segm['arrivalTerminal']);
                                $segment['mealCode'] = nvl($segm['mealCode']);
                                $baggages = nvl(nvl($segm['baggage']),[]);
                                $segment['baggage']=[];
                                foreach ($baggages as $baggage){
                                    $currBaggage['measureCode'] = nvl($baggage['measureCode']);
                                    $currBaggage['measureQuantity'] = nvl($baggage['measureQuantity']);
                                    $segment['baggage'][] =$currBaggage;
                                }
                                $segment['stopQuantity'] = nvl($segm['stopQuantity']);
                                $segment['stopLocations']['stopAirportCode'] = nvl($segm['stops']['stops_airport']);
                                $segment['stopLocations']['stopDuration'] = nvl($segm['stops']['stopDuration'], 0);
                                $intr['segments'][] = $segment;
                            }
                            $offer['itinerary'][] = $intr;
                            $offer['travelPolicy'] = nvl($response['travelPolicy']);
                        }
                        $resps[$key1]['aviaOffer'] = $offer;
                    }
                    break;
                default:
                    $resps = [];
            }
        }


        $this->fullResult['completed'] = nvl($arr['completed'],0);
        $this->fullResult['tokentimelimit'] = nvl($arr['tokenlimit'],0);
        $this->fullResult['response'] = nvl($resps);
        $this->fullResult['startOfferId'] = nvl($params['startOfferId'],0);
        $this->fullResult['offerLimit'] = nvl($params['offerLimit'],0);
    }
    /**
     * Наложение данных на стандартный шаблон
     * @param
     * @returm
     */
    private function imposeTemplate_AddTourist(array $arr)
    {
        if (isset($arr)) {
            $this->fullResult = nvl($arr);
            $this->params['gatherTouristID'] = nvl($arr['touristId']);
        }
    }

    /**
     * Наложение данных на шаблон OrderService
     * @param $arr - массив с полученными данными
     * @returm $this->fullResult - Выходноц массив
     */
    private function imposeTemplate_OWM_AddService_SetAdditionalData(array $arr)
    {
        if (isset($arr)) {
            $additionalFields = $this->params['additionalFields'];
            foreach ($additionalFields as $key => &$additionalField) {
                $this->params['additionalFields'][$key]['orderId'] = null;
                $this->params['additionalFields'][$key]['serviceId'] = $arr['serviceId'];
            }
            $this->params['serviceType'] = $this->params['actionParams']['serviceType'];

            $setAdditionalData = new ApiPackage([]);
            $setAdditionalData->addCmd(nvl($this->params), ['serviceName' => 'orderService', 'action' => 'OrderWorkflowManager', 'owmOper' => 'SetAdditionalData', 'cmdIndex'=> '0'], 'OWM_SetAdditionalDataTemplate');
            $setAdditionalData->runCmd();
            $cmd = $setAdditionalData->getApiCommandsArr()[0];
            if ($cmd->errorCode == 0){
                $this->fullResult = nvl($arr);
            }else{
                $this->errorCode = $cmd->errorCode;
                $this->errorName = $cmd->errorName;
            }
        }
    }
    /**
     * Наложение данных на шаблон OrderService
     * @param $arr - массив с полученными данными
     * @returm $this->fullResult - Выходноц массив
     */
    private function imposeTemplate_AddTourist_SetAdditionalData(array $arr)
    {
        if (isset($arr)) {
            $additionalFields = $this->params['actionParams']['userAdditionalFields'];

            foreach ($additionalFields as $key => $additionalField) {
                $this->params['actionParams']['additionalFields'][$key]['orderId'] = null;
                $this->params['actionParams']['additionalFields'][$key]['serviceId'] = null;
                $this->params['actionParams']['additionalFields'][$key]['touristId'] = $arr['touristId'];
                $this->params['actionParams']['additionalFields'][$key]['fieldTypeId'] = $additionalField['fieldTypeId'];
                $this->params['actionParams']['additionalFields'][$key]['value'] = $additionalField['value'];
            }
            $this->params['gatherTouristID'] = nvl($arr['touristId']);

            $setAdditionalData = new ApiPackage([]);
            $setAdditionalData->addCmd(nvl($this->params), ['serviceName' => 'orderService', 'action' => 'OrderWorkflowManager', 'owmOper' => 'SetAdditionalData', 'cmdIndex'=> '0'], 'OWM_SetAdditionalDataTemplate');
            $setAdditionalData->runCmd();
            $cmd = $setAdditionalData->getApiCommandsArr()[0];
            if ($cmd->errorCode == 0){
                $this->fullResult = nvl($arr);
            }else{
                $this->errorCode = $cmd->errorCode;
                $this->errorName = $cmd->errorName;
            }
        }
    }

    /**
     * Наложение данных на стандартный шаблон
     * @param
     * @returm
     */
    private function imposeTemplate_BookStart(array $arr)
    {
        if (isset($arr)) {
            $this->fullResult['orderStatus'] = nvl($arr['orderStatus']);
            $this->fullResult['serviceStatus'] = nvl($arr['serviceStatus']);
            $this->fullResult['supplierMessages'] = nvl($arr['BookData']['supplierMessages']);
            $this->fullResult['newOfferData'] = nvl($arr['BookData']['newOfferData']);
        }
    }

    /**
     * Наложение данных на шаблона с массивом
     *
     * @param
     * @returm
     */
    private function imposeTemplate_BasicArray(array $arr)
    {
        if (isset($arr)) {
            $this->fullResult[] = nvl($arr);
        }
    }


    /**
     * Наложение данных на стандартный шаблон
     * @param
     * @returm
     */
    private function imposeTemplate_CheckGetOrder(array $arrs)
    {
        if (isset($arrs)) {
            foreach ($arrs as $arr) {
                $this->fullResult[$arr['serviceID']] = nvl($this->serviceStatus[$arr['status']] );
            }
        }
    }

    /**
    * Семафор команд
    * @param
    * @returm массив полученных данных заполненных по правилу шаблога  $this->cmdTemplate
    */
    public function onCommand(array $param, $module)
    {
        //Добавление/Изменение входных параметров
        $this->setParams($param, $this->cmdTemplate);
        $apiClient = new ApiClient($module);

        $ServArr = null;
        if(isset($this->cmdArray) && count($this->cmdArray)>0) { // Использовать массив переданных родителем данных для парсинга
            $ServArr = $this->cmdArray;
            $this->errorCode = 0;
        }else{   //HTTP подзапрос
            $this->currJSON = json_encode($this->params);
            $serviceResponse = $apiClient->makeRestRequest($this->servName, $this->action, $this->params);
            $ServArr = json_decode($serviceResponse, true);

            if(isset($ServArr) && is_array($ServArr) && nvl($ServArr['status'],0) > 0 && nvl($ServArr['errorCode']) == 500) {
                $this->errorCode = ApiErrors::ERROR_500;
                $this->errorName = 'Ошибка сервера поставщика';
            } else if(isset($ServArr) && is_array($ServArr) && nvl($ServArr['status'],0) > 0) {
                $this->errorCode = nvl($ServArr['errorCode'], 0);
                $this->errorName = nvl($ServArr['errors']);
            } else if (isset($ServArr) && is_array($ServArr) && nvl($ServArr['status'],0) == 0 && count(nvl($ServArr['body'],0)) > 0) {
                $this->errorCode = nvl($ServArr['errorCode'], 0);
                $this->errorName = nvl($ServArr['errors']);
            } else if (isset($ServArr) && is_array($ServArr) && nvl($ServArr['status'],0) == 0 && count(nvl($ServArr['body'],0)) == 0) {
                $this->errorCode = 0;
                $this->errorName = '' ;
                $this->fullResult = nvl($ServArr['body'],[]);
            }else{
                $this->errorCode = '999';
                $this->errorName = 'Нет данных';//nvl($serviceResponse);
            }
        }

        if($this->errorCode == 0) {
            $this->callBackArray = $ServArr;
            switch ($this->cmdTemplate) { // Выбор выходного шаблона данных
                // --------  Аутентификация ---------------
                case 'authTemplate':
                    $this->imposeTemplate_Auth(nvl($ServArr['body'],[]));
                    break;

                case 'userAcessTemplate':
                    $this->imposeTemplate_Basic(nvl($ServArr['body'],[]));
                    break;

                // --------- Заявки ----------------------
                case 'orderTemplate':
                    $this->imposeTemplate_Order(nvl($ServArr['body'],[]));
                    break;
                case 'orderListTemplate':
                    $this->imposeTemplate_OrderList(nvl($ServArr['body']['orders'],[]));
                    break;
                case 'touristsTemplate':
                    $this->imposeTemplate_Tourists(nvl($ServArr['body']['tourists'],[]));
                    break;
                case 'touristDocsTemplate':
                    $this->imposeTemplate_TouristDocs(nvl($ServArr['body']['tourists'],[]));
                    break;
                case 'orderServiceTemplate':
                    $this->imposeTemplate_OrderService(nvl($ServArr['body']['services'],[]));
                    break;
                case 'orderServiceListTemplate':
                    $this->imposeTemplate_OrderServiceList(nvl($ServArr['services'],[]));
                    break;
                case 'salesTermTemplate':
                    $this->imposeTemplate_SalesTerm(nvl($ServArr['body'],[]));
                    break;
                case 'servicePenaltyTemplate':
                    $this->imposeTemplate_ServicePenalty(nvl($ServArr['body'],[]));
                    break;
                case 'serviceTouristsTemplate':
                    $this->imposeTemplate_ServiceTourists(nvl($ServArr['body'],[]));
                    break;
                case 'serviceTouristIDTemplate':
                    $this->imposeTemplate_ServiceTouristID(nvl($ServArr['body']['tourists'],[]));
                    break;

// -------------    serviceData ---------------------------------
                case 'serviceDataTemplate':
                    $this->imposeTemplate_ServiceData(nvl($ServArr['body'],[]));
                    break;
        // -------- Hotell ----------
                case 'hotelOfferTemplate':
                    $this->imposeTemplate_hotelOffer($ServArr);
                    break;
                case 'hotelReservationsTemplate':
                    $this->imposeTemplate_hotelReservations($ServArr);
                    break;
                case 'hotelVouchersTemplate':
                    $this->imposeTemplate_hotelVouchers($ServArr);
                    break;

                case 'cancelPenaltiesTemplate':
                    $this->imposeTemplate_cancelPenalties($ServArr);
                    break;

        // --------  Avia  ----------
                case 'aviaOfferTemplate':
                    $this->imposeTemplate_aviaOffer($ServArr);
                    break;
                case 'aviaCancelPenaltiesTemplate':
                    $this->imposeTemplate_aviaCancelPenalties($ServArr);//
                    break;
                case 'aviaReservationsTemplate':
                    $this->imposeTemplate_aviaReservations($ServArr);
                    break;
                case 'aviaTicketsTemplate':
                    $this->imposeTemplate_aviaTickets($ServArr);
                    break;
                case 'aviaTicketReceiptsTemplate':
                    $this->imposeTemplate_aviaTicketReceipts($ServArr);
                    break;
                case 'fareRulesTemplate':
                    $this->imposeTemplate_fareRules($ServArr);
                    break;
        // --------  Search Location ----------------
                case 'SuggestLocationTemplate':
                    $this->imposeTemplate_SuggestLocation(nvl($ServArr['body']['locations'],[]));
                    break;
                case 'SearchStartTemplate':
                    $this->imposeTemplate_Basic(nvl($ServArr['body'],[]));
                    break;
                case 'GetHotelInfoTemplate':
                    $this->imposeTemplate_Basic(nvl($ServArr['body'],[]));
                    break;
                case 'GetSearchResultTemplate':
                    $this->imposeTemplate_GetSearchResult(nvl($ServArr['body'],[]), $param);
                    break;
                // --------  OWM ----------------
                case 'OWM_AddServiceTemplate':
                    $this->imposeTemplate_Basic(nvl($ServArr['body'],[]));
                    break;
                case 'OWM_CheckTouristToServiceTemplate':
                    $this->imposeTemplate_Basic(nvl($ServArr['body'],[]));
                    break;
                case 'OWM_AddTouristTemplate':
                    $this->imposeTemplate_AddTourist(nvl($ServArr['body'],[]));
                    break;
                case 'OWM_RemoveTouristTemplate':
                    $this->imposeTemplate_Basic(nvl($ServArr['body'],[]));
                    break;
                case 'OWM_TouristToServiceTemplate':
                    $this->imposeTemplate_Basic(nvl($ServArr['body']['result'],[]));
                    break;
                case 'OWM_BookStartTemplate':
                    $this->imposeTemplate_BookStart(nvl($ServArr['body'],[]));
                    break;
                case 'OWM_CheckGetOrderTemplate':
                    $this->imposeTemplate_CheckGetOrder(nvl($ServArr['body']['services'],[]));
                    break;
                case 'OWM_BookCancelTemplate':
                    $this->imposeTemplate_Basic(nvl($ServArr['body'],[]));
                    break;
                case 'OWM_ServiceCancelTemplate':
                    $this->imposeTemplate_Basic(nvl($ServArr['body'],[]));
                    break;
                case 'OWM_IssueTicketsTemplate':
                    $this->imposeTemplate_Basic(nvl($ServArr['body'],[]));
                    break;
// v1.5 ----------------------------------------------------------------------------------------
                case 'OWM_SetAdditionalDataTemplate':
                    $this->imposeTemplate_Basic(nvl($ServArr['body'],[]));
                    break;
                case 'OWM_AddService_SetAdditionalDataTemplate':
                    $this->imposeTemplate_OWM_AddService_SetAdditionalData(nvl($ServArr['body'],[]));
                    break;
                case 'OWM_AddTourist_SetAdditionalDataTemplate':
                    $this->imposeTemplate_AddTourist_SetAdditionalData(nvl($ServArr['body'],[]));
                    break;

                case 'OWM_TouristToService_15_Template':
                    $this->imposeTemplate_Basic(nvl($ServArr['body']['result'],[]));
                    break;

                case 'orderService_15_Template':
                    $this->imposeTemplate_OrderService_15(nvl($ServArr['body']['services'],[]));
                    break;
                case 'tourists_15_Template':
                    $this->imposeTemplate_Tourists_15(nvl($ServArr['body']['tourists'],[]));
                    break;


                // --------  Document to Order ----------------
                case 'addDocumentToOrderTemplate':
                    $this->imposeTemplate_Basic(nvl($ServArr['body'],[]));
                    break;
                case 'getOrderDocumentsTemplate':
                    $this->imposeTemplate_BasicArray(nvl($ServArr['body'],[]));
                    break;



                default:
                    $this->fullResult = $ServArr;
            }
        }

    }


}

