<?php

/**
 * Class TouristsValidator
 * Класс для проверки корректности значений при работе с данными туристов
 */
class TouristsValidator extends Validator
{
    const LOWER_BIRTHDATE_LIMIT = '1915-01-01';

    /**
     * Код ошибки
     * @var int
     */
    private $_errorCode;

    /**
     * namespace для записи логов
     * @var
     */
    private $_namespace;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        $this->module = $module;
        $this->_namespace = "system.orderservice";
    }

    /**
     * Проверка параметров команды добавления/обновления туристов
     * @param $params
     * @return bool
     */
    public function checkAddTouristsCommonParams($params)
    {
        $this->validateComplex($params, [
            ['orderId', 'required', 'message' => OrdersErrors::ORDER_ID_NOT_SET],
            ['orderId', 'checkOrderOnline', 'message' => OrdersErrors::CANNOT_ADD_TOURIST_TO_OFFLINE_ORDER],
            ['tourist', 'required', 'message' => OrdersErrors::INCORRECT_TOURIST_STRUCTURE],
            ['tourist', 'checkTouristsStructure', 'message' => OrdersErrors::INCORRECT_TOURIST_STRUCTURE],
        ]);

        return true;
    }

    /**
     * Проверка признака онлайновой заявки
     * @todo черновая реализация, метод добавления туристов в принципе должен быть в OWM
     */
    public function checkOrderOnline($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        $orderId = $values[$attribute];

        $command = Yii::app()->db->createCommand()
            ->select('count(*)')
            ->from('kt_orders_services')
            ->where('OrderID = :orderId and Offline=1', array(':orderId' => $orderId));

        $offlineServices = $command->queryScalar();

        if ((int)$offlineServices > 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Проверка структуры данных для добавления туристов
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkTouristsStructure($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (empty($values[$attribute]) || count($values[$attribute]) == 0) {

            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                $params['message'],
                []
            );
        }

        foreach ($values[$attribute] as $touristInfo) {

            if (empty($touristInfo) || !is_array($touristInfo) || count($touristInfo) == 0) {

                throw new KmpInvalidArgumentException(
                    get_class($this),
                    __FUNCTION__,
                    $params['message'],
                    ['touristInfo' => $touristInfo]
                );
            }

            $touristInfo['orderId'] = $values['orderId'];

            if (!$this->checkTouristCreationParams($touristInfo)) {
                throw new KmpInvalidArgumentException(
                    get_class($this),
                    __FUNCTION__,
                    $this->getLastError(),
                    ['touristInfo' => $touristInfo]
                );
            }
        }

        return true;
    }

    /**
     * Проверка параметров для создания туриста
     * @param $params array входные параметры
     * @return bool|null
     */
    public function checkTouristCreationParams($params)
    {
        if (empty($params['orderId'])) {
            $this->_errorCode = OrdersErrors::ORDER_ID_NOT_SET;
        }

        $cxtName = $this->module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->module;

        if (empty($params['birthdate'])) {
            $this->_errorCode = OrdersErrors::TOURIST_BIRTHDATE_NOT_SET;
        } else {
            if (!$this->validateDate($params['birthdate']) || $this->isDateAfter($params['birthdate'],
                    (new DateTime())->format('Y-m-d'))
                || $this->isDateBefore($params['birthdate'], self::LOWER_BIRTHDATE_LIMIT)
            ) {
                $this->_errorCode = OrdersErrors::INCORRECT_TOURIST_BIRTHDATE;
            }
        }

        if (empty($params['email']) || !filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
            $this->_errorCode = OrdersErrors::INCORRECT_TOURIST_EMAIL;
        }

        if (!isset($params['isTourLeader'])) {
            $this->_errorCode = OrdersErrors::TOURLEADER_NOT_FOUND;
        }

        if (empty($params['document']['documentType'])) {
            $this->_errorCode = OrdersErrors::TOURIST_DOCUMENT_TYPE_NOT_SET;
        } else {
            if (!TouristDocTypeForm::GetDocType($params['document']['documentType']) ||
                !TouristDocTypeForm::isDocTypeValid($params['document']['documentType'])
            ) {
                $this->_errorCode = OrdersErrors::INCORRECT_TOURIST_DOCUMENT_TYPE;
            }
        }

        if (!$this->_errorCode) {
            if (!isset($params['document']['serialNum']) ||
                TouristDocTypeForm
                    ::getDocSerialLength(
                        $params['document']['documentType']) != mb_strlen($params['document']['serialNum'])
            ) {

                $this->_errorCode = OrdersErrors::INCORRECT_TOURIST_DOCUMENT_SERIAL;
            }

            if (empty($params['document']['number']) ||
                TouristDocTypeForm
                    ::getDocNumberLength(
                        $params['document']['documentType']) != mb_strlen($params['document']['number'])
            ) {

                $this->_errorCode = OrdersErrors::INCORRECT_TOURIST_DOCUMENT_NUMBER;
            }

            if (empty($params['document']['expiryDate'])) {
                $this->_errorCode = OrdersErrors::INCORRECT_DOCUMENT_EXPIRY_DATE;
            } else {
                $expiryTime = strtotime($params['document']['expiryDate']);

                if (!$expiryTime || $expiryTime < time()) {
                    $this->_errorCode = OrdersErrors::INCORRECT_DOCUMENT_EXPIRY_DATE;
                }
            }
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    public function checkTouristLinkedServices($params)
    {

        $cxtName = $this->module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->module;

        $orderServicesIds = 0;

        if (empty($params['services']) || count($params['services']) == 0) {
            return true;
        }


        foreach ($params['services'] as $linkedService) {

            if (empty($touristOrderServices)) {

                $order = OrderForm::getOrderByServiceId($linkedService['serviceId']);
                if (empty($order) || empty($order['OrderID'])) {
                    $this->_errorCode = OrdersErrors::TOURISTS_LINKS_TO_SERVICES_INCORRECT;
                    return false;
                }

                $orderSearch = OrderSearchForm::createInstance();
                $touristOrderServices = $orderSearch->getOrdersServices($order['OrderID']);

                if (empty($touristOrderServices) || count($touristOrderServices) == 0) {
                    $this->_errorCode = OrdersErrors::TOURISTS_LINKS_TO_SERVICES_INCORRECT;
                    return false;
                }

                $orderServicesIds = [];
                foreach ($touristOrderServices as $orderService) {
                    $orderServicesIds[] = $orderService['serviceID'];
                }
            }

            if (!in_array($linkedService['serviceId'], $orderServicesIds)) {
                $this->_errorCode = OrdersErrors::SERVICES_IDS_FROM_DIFFERENT_ORDERS;
                return false;
            }

        }

        return true;
    }

    /**
     * Проверка состояния услуг, для которых удаляется связь с туристом
     * @param $params
     * @return bool
     */
    public function checkTouristUnlinkedServices($params)
    {
        if (empty($params['detachServices']) || count($params['detachServices']) == 0) {
            return true;
        }

        foreach ($params['detachServices'] as $detachService) {

            if (empty($touristOrderServices)) {

                $order = OrderForm::getOrderByServiceId($detachService['serviceId']);

                if (empty($order) || empty($order['OrderID'])) {

                    throw new KmpInvalidArgumentException(
                        get_class($this),
                        __FUNCTION__,
                        OrdersErrors::TOURISTS_LINKS_TO_SERVICES_INCORRECT,
                        ['detachServiceId' => $detachService['serviceId']]
                    );
                }

                $orderSearch = OrderSearchForm::createInstance();
                $touristOrderServices = $orderSearch->getOrdersServices($order['OrderID']);

                if (empty($touristOrderServices) || count($touristOrderServices) == 0) {

                    throw new KmpInvalidArgumentException(
                        get_class($this),
                        __FUNCTION__,
                        OrdersErrors::SERVICE_NOT_FOUND,
                        ['detachServiceId' => $detachService['serviceId']]
                    );
                }

                $orderServicesIds = [];
                foreach ($touristOrderServices as $orderService) {
                    $orderServicesIds[] = $orderService['serviceID'];
                }
            }

            if (!in_array($detachService['serviceId'], $orderServicesIds)) {
                throw new KmpInvalidArgumentException(
                    get_class($this),
                    __FUNCTION__,
                    OrdersErrors::SERVICES_IDS_FROM_DIFFERENT_ORDERS,
                    ['detachServiceId' => $detachService['serviceId']]
                );
            }

            foreach ($touristOrderServices as $orderService) {
                if ($detachService['serviceId'] == $orderService['serviceID']) {
                    if ($orderService['status'] != ServicesForm::SERVICE_STATUS_NEW &&
                        $orderService['status'] != ServicesForm::SERVICE_STATUS_BOOKED &&
                        $orderService['status'] != ServicesForm::SERVICE_STATUS_MANUAL &&
                        $orderService['status'] != ServicesForm::SERVICE_STATUS_PAID &&
                        $orderService['status'] != ServicesForm::SERVICE_STATUS_W_PAID
                    ) {
                        throw new KmpInvalidArgumentException(
                            get_class($this),
                            __FUNCTION__,
                            OrdersErrors::SERVICE_STATUS_IS_BLOCKING_DETACHING_TURIST,
                            [
                                'detachServiceId' => $detachService['serviceId'],
//                                'tourist' => [
//                                    'touristId' => $params['touristId'],
//                                    'touristName' => $params['firstName'],
//                                    'touristSurName' => $params['surName'],
//                                    'touristMiddleName' => $params['middleName']
//                                ]
                            ]
                        );
                    }
                }
            }
        }
        return true;
    }

    /**
     * Проверка, что турист связан только с заявкой укзаной в команде
     */
    public function checkTouristLinkedToOrder($params)
    {

        $cxtName = $this->module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->module;

        //Если турист новый не искать связи
        if (empty($params['tourist']['touristId'])) {
            return true;
        }

        $tourist = new TouristForm($this->_namespace);
        $tourist->loadTouristByID($params['tourist']['touristId']);

        if (empty($tourist->touristId)) {
            return true;
        }

        if ($params['orderId'] == $tourist->orderId) {
            return true;
        }
        $this->_errorCode = OrdersErrors::TOURIST_ALREADY_LINKED_TO_ANOTHER_ORDER;

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

    }

    /**
     * Проверка параметров получения туристов заявки
     * @param $params array
     * @return bool
     */
    public function checkGetOrderTourists($params)
    {

        $cxtName = $this->module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->module;

        if (empty($params['orderId'])) {
            $this->_errorCode = OrdersErrors::ORDER_ID_NOT_SET;
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Проверка параметров для удаления туриста из заявки
     * @param $params
     */
    public function checkTouristDeletingParams($params)
    {

        $cxtName = $this->module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->module;

        if (empty($params['orderId'])) {
            $this->_errorCode = OrdersErrors::ORDER_ID_NOT_SET;
        } else {

            if (!is_numeric($params['orderId'])) {
                $this->_errorCode = OrdersErrors::ORDER_ID_INCORRECT;
            }
        }

        if (empty($params['touristId'])) {
            $this->_errorCode = OrdersErrors::TOURIST_ID_NOT_SET;
        } else {

            if (!is_numeric($params['touristId'])) {
                $this->_errorCode = OrdersErrors::INCORRECT_TOURIST_ID;
            }
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Проверка условий удаления туриста из заявки
     * @param $params
     */
    public function checkTouristInOrder($params)
    {

        $cxtName = $this->module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->module;

        $orderForm = OrderSearchForm::createInstance();
        $orderForm->orderId = $params['orderId'];

        $order = $orderForm->getOrderById();
        if (empty($order)) {
            $this->_errorCode = OrdersErrors::ORDER_NOT_FOUND;
        }

        $tourist = TouristForm::getTouristByKtId($params['touristId']);
        if (empty($tourist)) {
            $this->_errorCode = OrdersErrors::TOURIST_NOT_FOUND;
        }

        $orderTourists = TouristForm::getTouristsByOrderId($order['OrderID']);

        $isTouristInOrder = false;
        if (!$this->_errorCode && !empty($orderTourists) && count($orderTourists) > 0) {

            foreach ($orderTourists as $orderTourist) {

                if ($orderTourist['TouristID'] == $tourist['TouristID']) {
                    $isTouristInOrder = true;
                }
            }

            if (!$isTouristInOrder) {
                $this->_errorCode = OrdersErrors::TOURIST_NOT_FOUND;
            }
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Проверка возможности удаления связи между туристом и услугами
     * @param $params array
     * @return bool
     */
    public function checkCanRemoveTouristFromOrderService($params)
    {

        $cxtName = $this->module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->module;

        $tourist = new TouristForm($this->_namespace);
        $tourist->loadTouristByID($params['touristId']);

        if (empty($tourist->touristId)) {
            $this->_errorCode = OrdersErrors::TOURIST_NOT_FOUND;
        } else {

            $services = OrderSearchForm::createInstance()->getOrdersServices($tourist->orderId);

            $touristServicesIds = $tourist->getTouristServicesIds();

            foreach ($services as $service) {

                if (in_array($service['serviceID'], $touristServicesIds)
                    && $service['status'] != ServicesForm::SERVICE_STATUS_NEW
                ) {
                    $this->_errorCode = OrdersErrors::REMOVE_TOURIST_FROM_SERVICE_IS_PROHIBITED;
                }
            }
        }

        if ($this->_errorCode != OrdersErrors::ERROR_NONE) {

            LogHelper::logExt(get_class($this), __FUNCTION__, $cxtName,
                $err->getError($this->_errorCode), $params,
                LogHelper::MESSAGE_TYPE_ERROR, $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->_errorCode;
    }

}
