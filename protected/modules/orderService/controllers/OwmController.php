<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 7/29/16
 * Time: 6:49 PM
 */
class OwmController extends SecuredRestController
{
    /**
     * Валидация общих параметров
     * @param CAction $action
     * @return bool
     */
    public function beforeAction($action)
    {
        parent::beforeAction($action);

        // инициализация Eventmanager
        $EventManager = EventManager::getInstance();
        $EventManager->init(Yii::app()->getModule('orderService')->getConfig('gearman'));

        return true;
    }

    /**
     * Вызов команды Order Workflow Manager
     * для выполнения операции над заявкой
     */
    public function actionOrderWorkflowManager()
    {
        $params = $this->_getRequestParams();

        if (!array_key_exists('action', $params) || empty($params['action'])) {
            $this->_sendResponseWithErrorCode(OrdersErrors::ACTION_TYPE_NOT_SET);
        }

        if (!array_key_exists('actionParams', $params) || !is_array($params['actionParams'])) {
            $this->_sendResponseWithErrorCode(OrdersErrors::ACTION_DETAILS_NOT_SET);
        }

        $Event = EventRepository::getEventByCommand($params['action']);

        if (is_null($Event)) {
            $this->_sendResponseWithErrorCode(OrdersErrors::INCORRECT_OWM_ACTION);
        }

        // список реализованных экшенов, для которых работа идет через FSM+EventManager
        $done_actions = [
            'Test',
            'New',
            'AddService',
            'AddExtraService',
            'RemoveExtraService',
            'AddTourist',
            'TouristToService',
            'BookStart',
            'BookComplete',
            'BookCancel',
            'BookChange',
            'PayStart',
            'PayFinish',
            'IssueTickets',
            'Manual',
            'ManualSetStatus',
            'SetAdditionalData',
            'SetReservationData',
            'SetTicketsData',
            'SetServiceData',
            'Done',
            'Import',
            'OrderSync',
            'OrderAuthorization'
        ];

        // если действие в списке реализованных действий, то надо запускать новый код
        $runNewCode = in_array($params['action'], $done_actions);

        /**
         * Костыль для перенаправления авиа бронирования на старый код
         * вытаскиваем из услуги тип и делаем от него перенаправление
         */
        if (isset($params['actionParams']['serviceId']) && isset($params['action'])) {
            if (in_array($params['action'], array('BookComplete'))) {
                $OrdersService = OrdersServices::model()->findByPk($params['actionParams']['serviceId']);

                // запустим старый код, если сервис - авиа
                if ($OrdersService && (2 == $OrdersService->getServiceType())) {
                    $runNewCode = false;
                }
            }
        }

        if ($runNewCode) {
            // добавим в параметры экшена пользователя
            $params['actionParams']['userProfile'] = Yii::app()->user->getState('userProfile');
            $params['actionParams']['userPermissions'] = Yii::app()->user->getState('userPermissions');
            $params['actionParams']['usertoken'] = $params['usertoken'];
//            // передадим сервисный токен
            $params['actionParams']['token'] = $params['token'];

            // инициализация стандартного ответа
            $params['actionParams']['response'] = [];
            $params['actionParams']['status'] = 0;

            // если не указан параметр, то заполним пустым полем
            $params['orderId'] = isset($params['orderId']) ? $params['orderId'] : '';
            $params['actionParams']['orderId'] = $params['orderId'];

            // проверим схему
            // создадим класс для работы с фильтрами checkWorkflow
            try {
                if(isset($params['actionParams']['serviceType'])){
                    $serviceType = $params['actionParams']['serviceType'];
                } elseif (isset($params['actionParams']['serviceId'])){
                    $service = OrdersServicesRepository::findById($params['actionParams']['serviceId']);

                    if(is_null($service)){
                        $serviceType = null;
                    } else {
                        $serviceType = $service->getServiceType();
                    }
                } else {
                    $serviceType = null;
                }

                $CheckTransitionFilter = new CheckTransitionFilter();
                $CheckTransitionFilter->setServiceType($serviceType);

                if(!$CheckTransitionFilter->isAllowedEvent($Event)){
                    $this->_sendResponseWithErrorCode(OrdersErrors::NO_PERMISSION_BY_WORKFLOW_SCHEMA);
                }
            } catch (Exception $e) {
                LogHelper::logExt(get_class($this), __METHOD__, '', $e->getMessage(), '', 'error', 'system.orderservice.error');
                $this->_sendResponseWithErrorCode(OrdersErrors::CONFIGURATION_ERROR);
            }

            // получаем объект Заявки
            $OrderModel = OrderModelRepository::getFromIdOrCreateNew($params['orderId']);

            // инициализируем машину состояний и применяем действие
            $OWM_FSM = new StateMachine($OrderModel);

            if (!$OWM_FSM->can($Event->getEvent())) {
                $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_STATUS_INCORRECT_FOR_ACTION);
            }

            if (!$OWM_FSM->userHasAccess($Event->getEvent())) {
                $this->_sendResponseWithErrorCode(OrdersErrors::NOT_ENOUGH_USER_RIGHTS);
            }

            try {
                $response = $OWM_FSM->apply($Event->getEvent(), $params['actionParams']);
                if (empty($response)) {
                    $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
                }
            } catch (FSMException $e) {
                LogHelper::logExt(
                    __CLASS__, __METHOD__,
                    'Запуск StateMachine', "Ошибка машины состояний {$e->getMessage()}",
                    '',
                    LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.error'
                );
                $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
                return;
            }
            if ($response['status']) {
                $this->_sendResponseWithErrorCode($response['status'], StdLib::nvl($response['errorMessages']));
            } else {
                $this->_sendResponseData($response['response']);
            }
        } else {
            $module = YII::app()->getModule('orderService');
            $orderWfMgr = $module->OrderWorkflowManager();

            $params = $this->_getRequestParams();

            $rval = $orderWfMgr->runAction($params);

            if ($rval !== false) {
                $this->_sendResponse(true, $rval, '');
            } else {
                $this->_sendResponseWithErrorCode($orderWfMgr->getLastError());
            }
        }
    }


    /**
     * CheckWorkflow
     * работа в 2 режимах
     * validate
     * checkTransition
     * Проверка на возможность запуска команды OWM на основе валидатора
     */
    public function actionCheckWorkflow()
    {
        $params = $this->_getRequestParams();

        if (!isset($params['operation'])) {
            $this->_sendResponseWithErrorCode(OrdersErrors::CHECKWORKFLOW_OPERATION_NOT_SET);
        }

        // чтобы было в любом регистре задание
        $params['operation'] = strtolower($params['operation']);

        $rval = [];

        // режим VALIDATE
        if ($params['operation'] == 'validate') {
            // проверка входных параметров
            if (!isset($params['action'])) {
                $this->_sendResponseWithErrorCode(OrdersErrors::ACTION_NOT_SET);
            }
            if (!isset($params['actionParams'])) {
                $this->_sendResponseWithErrorCode(OrdersErrors::ACTION_PARAM_NOT_SET);
            }

            $Event = EventRepository::getEventByCommand($params['action']);

            if (is_null($Event)) {
                $this->_sendResponseWithErrorCode(OrdersErrors::INCORRECT_OWM_ACTION);
            }

            // получаем объект Заявки
            $OrderModel = OrderModel::model()->findByPk($params['orderId']);

            if (is_null($OrderModel)) {
                $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_NOT_FOUND);
            }

            if (count($params['actionParams'])) {
                foreach ($params['actionParams'] as $actionParam) {
                    // инициализируем машину состояний
                    $OWM_FSM = new StateMachine($OrderModel);

                    // подготовим набор параметров для запуска экшена
                    $actionParam['userProfile'] = Yii::app()->user->getState('userProfile');
                    $actionParam['userPermissions'] = Yii::app()->user->getState('userPermissions');
                    $actionParam['orderId'] = $params['orderId'];

                    $rval[] = [
                        'action' => $params['action'],
                        'serviceId' => isset($actionParam['serviceId']) ? $actionParam['serviceId'] : null,
                        'validationResult' => $OWM_FSM->can($Event->getEvent(), $actionParam, EventManager::RUN_MODE_VALIDATE | EventManager::RUN_MODE_PREACTION)
                    ];
                }
            } else {
                $this->_sendResponseWithErrorCode(OrdersErrors::ACTION_PARAM_NOT_SET);
            }

            $this->_sendResponse(true, $rval, '');
        } elseif ($params['operation'] == 'checktransition') { // режим checkTransition
            $OrderModel = OrderModelRepository::getWithServices($params['orderId']);

            // если не нашли такую заявку
            if (is_null($OrderModel)) {
                $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_NOT_FOUND);
            }

            $OrdersServices = $OrderModel->getOrderServices();

            // если есть сервисы в заявке
            if ($OrdersServices && count($OrdersServices)) {
                // инициализируем машину состояний
                $OWM_FSM = new StateMachine($OrderModel);

                // соответствия делегатов к экшенам
                $OWMDelegatesToActions = $OWM_FSM->getCurrentState()->getAllDelegatesToActions();

                $rval = [
                    'services' => []
                ];

                // найдем все публичные действия из таблицы kt_events
                $PublicEvents = EventRepository::getAllPublicEvents();
                $OWMActionToCommand = [];

                // сформируем массив сопоставление Action => Event
                foreach ($PublicEvents as $PublicEvent) {
                    $OWMActionToCommand[$PublicEvent->getEvent()] = $PublicEvent->getCommand();
                }

                // переберем все услуги и найдем возможные комады OWM
                foreach ($OrdersServices as $OrdersService) {
                    // инициализация ответа
                    $controls = [];

                    // инициализация FSM
                    $SWM_FSM = new StateMachine($OrdersService);

                    // найдем все возможные переходы
                    $SWMTransitionNames = $SWM_FSM->getCurrentState()->getTransitionNames();

                    // создадим класс для работы с фильтрами checkWorkflow
                    try {
                        $CheckTransitionFilter = new CheckTransitionFilter();
                        $CheckTransitionFilter->setServiceType($OrdersService->getServiceType());
                    } catch (Exception $e) {
                        LogHelper::logExt(get_class($this), __METHOD__, '', $e->getMessage(), '', 'error', 'system.orderservice.error');
                        $this->_sendResponseWithErrorCode(OrdersErrors::CONFIGURATION_ERROR);
                    }

                    $additionalFilter = $CheckTransitionFilter->getFilterOWMTransitions();

                    // переберем все переходы
                    foreach ($SWMTransitionNames as $SWMTransitionName) {
                        // найдем имя делегата, который запускает SWM
                        $OWMDelegateName = DelegateFactory::getOWMDelegateNameBySWMAction($SWMTransitionName);

                        // если есть переход в списке соответствий делегатов к переходам
                        if ($OWMDelegateName && array_key_exists($OWMDelegateName, $OWMDelegatesToActions)) {
                            $OWMAction = $OWMDelegatesToActions[$OWMDelegateName];

                            // если есть такой переход в публичных
                            if (array_key_exists($OWMAction, $OWMActionToCommand)
                                && (empty($additionalFilter) || in_array($OWMAction, $additionalFilter))
                            ) {
                                $controls[] = $OWMActionToCommand[$OWMAction];
                            }
                        }
                    }

                    // запишем результат
                    $rval['services'][] = [
                        'serviceId' => $OrdersService->getServiceID(),
                        'controls' => $controls
                    ];

                    unset($SWM_FSM);
                }
            }

            $this->_sendResponse(true, $rval, '');
        } else {
            $this->_sendResponseWithErrorCode(OrdersErrors::CHECKWORKFLOW_OPERATION_INCORRECT);
        }
    }
}