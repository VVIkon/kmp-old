<?php

/** класс-валидатор действий Supplier Manager'а */
class SupplierManagerValidator extends Validator
{
    /**
     * переопределен конструктор, т.к. в базовом классе (Validator) передается ссылка на домуль, которая не нужна
     * @todo убрать переопределение ,если будет убран параметр модуля в базовом классе
     */
    public function __construct()
    {
    }

    /**
     * Проверка общих параметров действия getOffer
     * @param mixed[] $params Параметры команды
     * @return bool результат проверки
     * @throws KmpInvalidArgumentException ошибка проверки параметров
     */
    public function checkGetOffer($params)
    {
        $this->validateComplex($params, [
            ['serviceType', 'required', 'message' => SupplierErrors::SERVICE_TYPE_NOT_SET],
            ['supplierId', 'required', 'message' => SupplierErrors::SUPPLIER_ID_NOT_SET],
            ['supplierOfferData', 'required', 'message' => SupplierErrors::COMMAND_DETAILS_NOT_SET],
        ]);

        return true;
    }

    /**
     * Проверка общих параметров действия serviceBooking
     * @param mixed[] $params Параметры команды
     * @return bool результат проверки
     * @throws KmpInvalidArgumentException ошибка проверки параметров
     */
    public function checkServiceBooking($params)
    {
        $this->validateComplex($params, [
            ['orderId', 'required', 'message' => SupplierErrors::ORDER_ID_NOT_SET],
            ['serviceId', 'required', 'message' => SupplierErrors::SERVICE_ID_NOT_SET],
            ['serviceType', 'required', 'message' => SupplierErrors::SERVICE_TYPE_NOT_SET],
            ['supplierId', 'required', 'message' => SupplierErrors::SUPPLIER_ID_NOT_SET],
            ['supplierOfferData', 'required', 'message' => SupplierErrors::COMMAND_DETAILS_NOT_SET],
        ]);

        return true;
    }

    /**
     * Проверка общих параметров команды выписки билетов
     * @param $params
     * @return bool
     */
    public function checkIssueTickets($params)
    {

        $this->validateComplex($params, [
            ['serviceType', 'required', 'message' => SupplierErrors::SERVICE_TYPE_NOT_SET],
            ['serviceType', 'checkServiceType', 'message' => SupplierErrors::SERVICE_TYPE_NOT_DETERMINED],
            ['bookData', 'required', 'message' => SupplierErrors::INCORRECT_PNR_DATA],
            ['bookData', 'checkBookDataStruct', 'message' => SupplierErrors::INCORRECT_PNR_DATA]
        ]);

        return true;
    }

    /**
     * Проверка общих параметров команды получения маршрутной квитанции
     * @param $params
     * @return bool
     */
    public function checkGetEtickets($params)
    {
        $this->validateComplex($params, [
            ['tickets', 'required', 'message' => SupplierErrors::TICKETS_NOT_SET],
            ['serviceType', 'required', 'message' => SupplierErrors::SERVICE_TYPE_NOT_SET],
//            ['tickets', 'checkTicketDataStruct', 'message' => SupplierErrors::INCORRECT_PNR_DATA]
        ]);

        return true;
    }

    /**
    * Проверка общих параметров команды получения правил тарифов
     * @param array $params
     * @return bool
    */
    public function checkGetFareRule($params) {
        $this->validateComplex($params, [
            ['offerKey', 'required', 'message' => SupplierErrors::OFFER_KEY_NOT_SET]
        ]);

        return true;
    }
    /**
    * Проверка общих параметров команды получения правил тарифов
     * @param array $params
     * @return bool
    */
    public function checkSupplierGetOrder($params) {
        $this->validateComplex($params, [
             ['services', 'required', 'message' => SupplierErrors::INCORRECT_VALIDATION_RULES]
        ]);
        return true;
    }

    /**
     * Проверка параметров поставщика для EnginData
     * @param $values
     * @param $attribute
     * @param $params
     */
    public function checkEngineData($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class(), __FUNCTION__, SupplierErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        $this->validateComplex($values[$attribute], [
            ['gateId', 'required', 'message' => SupplierErrors::GATE_ID_NOT_SET],
            //['GPTS_service_ref', 'required', 'message' => SupplierErrors::SERVICE_ID_NOT_SET],
            //['GPTS_order_ref', 'required', 'message' => SupplierErrors::ORDER_ID_NOT_SET],
        ]);

        return true;
    }

    /**
     * Проверка статуса
     * @param $values
     * @param $attribute
     * @param $params
     */
    public function checkStatus($values, $attribute, $params){
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class(), __FUNCTION__, SupplierErrors::INCORRECT_VALIDATION_RULES, []);
        }
        if (!in_array($values[$attribute],[1,2,3,4,5,6,7,8,9,10]) ){
            throw new KmpException(get_class(), __FUNCTION__, SupplierErrors::SERVICE_STATUS_NOT_CORRECT, []);
        }

    }
    /**
     * Проверка параметров orderService
     * @param $values
     * @param $attribute
     * @param $params
     */
    public function checkOrderService($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class(), __FUNCTION__, SupplierErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        $this->validateComplex($values[$attribute], [
            ['serviceType', 'required', 'message' => SupplierErrors::SERVICE_TYPE_NOT_SET],
            ['status', 'required', 'message' => SupplierErrors::SERVICE_STATUS_NOT_SET],
            ['status', 'checkStatus', 'message' => SupplierErrors::SERVICE_STATUS_NOT_SET]
        ]);

        return true;
    }

    public function checkSetServiceDate($params) {
        $this->validateComplex($params, [
                    ['engineData', 'required', 'message' => SupplierErrors::ENGINE_MODULE_NOT_FOUND],
                    ['engineData', 'checkEngineData', 'message' => SupplierErrors::ENGINE_MODULE_NOT_FOUND],
                    ['orderService', 'required', 'message' => SupplierErrors::SERVICE_ID_NOT_SET],
                    ['orderService', 'checkOrderService', 'message' => SupplierErrors::INCORRECT_COMMAND_PARAMS],
            ]);

        return true;
    }

    /**
     * Проверка типа услуги
     * @param $values
     * @param $attribute
     * @param $params
     */
    public function checkServiceType($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, $values);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        if (!SupplierServices::isServiceTypeExist($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка структуры параметров для выписки билета
     * @param $values
     * @param $attribute
     * @param $params
     */
    public function checkBookDataStruct($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class(), __FUNCTION__, SupplierErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (!empty($values[$attribute]['pnrData'])) {
            $this->validateComplex($values[$attribute]['pnrData'], [
                ['engine', 'required', 'message' => SupplierErrors::INCORRECT_PNR_DATA],
                ['engine', 'checkSupplierParams', 'message' => SupplierErrors::INCORRECT_PNR_DATA],
                ['supplierCode', 'required', 'message' => SupplierErrors::INCORRECT_PNR_DATA],
                ['PNR', 'required', 'message' => SupplierErrors::INCORRECT_PNR_DATA],
            ]);
            return true;

        } else {
            if (empty($values[$attribute]['pnrData'])) {
                throw new KmpInvalidArgumentException(
                    get_class($this),
                    __FUNCTION__,
                    SupplierErrors::INCORRECT_SEGMENTS_DATA,
                    $values
                );
            }

            $this->validateComplex($values[$attribute]['segments'], [
//                todo после добавления сегментов перелёта реализовать их валидацию
                ['segment', 'checkSegmentsStruct', 'message' => SupplierErrors::INCORRECT_SEGMENTS_DATA],

            ]);
        }

        return true;
    }

    /**
     * Проверка структуры параметров для получения маршрутной квитанции
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkTicketDataStruct($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class(), __FUNCTION__, SupplierErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                SupplierErrors::INCORRECT_TICKET_DATA,
                $values
            );
        }

        foreach ($values[$attribute] as $ticket) {

            $this->validateComplex($ticket['ticket']['pnrData'], [
                ['engine', 'required', 'message' => SupplierErrors::INCORRECT_PNR_DATA],
                ['engine', 'checkSupplierParams', 'message' => SupplierErrors::INCORRECT_PNR_DATA],
                ['supplierCode', 'required', 'message' => SupplierErrors::INCORRECT_PNR_DATA],
                ['PNR', 'required', 'message' => SupplierErrors::INCORRECT_PNR_DATA],
            ]);

            $this->validateComplex($ticket['ticket'], [
                ['number', 'required', 'message' => SupplierErrors::INCORRECT_TICKET_DATA]
            ]);
        }

        return true;
    }

    /**
     * Проверка параметров поставщика для функции выписки билетов
     * @param $values
     * @param $attribute
     * @param $params
     */
    public function checkSupplierParams($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class(), __FUNCTION__, SupplierErrors::INCORRECT_VALIDATION_RULES, []);
        }

        if (empty($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        $this->validateComplex($values[$attribute], [
            ['type', 'required', 'message' => SupplierErrors::SUPPLIER_ID_NOT_SET],
            ['GPTS_service_ref', 'required', 'message' => SupplierErrors::INCORRECT_PNR_DATA],
            ['GPTS_order_ref', 'required', 'message' => SupplierErrors::INCORRECT_PNR_DATA],

        ]);

        return true;
    }

    /**
     * Проверка параметров для получения данных штрафов
     * @param $params
     * @return bool
     */
    public function checkGetCancelRules($params)
    {
        $this->validateComplex($params, [
            ['gateId', 'required', 'message' => SupplierErrors::GATE_ID_NOT_SET],
            ['offerKey', 'required', 'message' => SupplierErrors::OFFER_KEY_NOT_SET],
            ['serviceType', 'required', 'message' => SupplierErrors::SERVICE_TYPE_NOT_SET],
            ['viewCurrency', 'required', 'message' => SupplierErrors::VIEWCURRENCY_NOT_DEFINED],
        ]);

        return true;
    }

    /**
     * Проверка параметров для модификации брони
     * @param $params
     * @return bool
     */
    public function checkModifyService($params)
    {
        $this->validateComplex($params, [
            ['gateId', 'required', 'message' => SupplierErrors::GATE_ID_NOT_SET],
            ['serviceData', 'required', 'message' => SupplierErrors::SERVICE_DATA_NOT_SET],
            ['supplierId', 'required', 'message' => SupplierErrors::SUPPLIER_ID_NOT_SET],
            ['serviceType', 'required', 'message' => SupplierErrors::SERVICE_TYPE_NOT_SET],
        ]);

        return true;
    }

    /**
     * Проверка параметров для отмены брони
     * @param $params
     * @return bool
     */
    public function checkCancelService($params)
    {
        $this->validateComplex($params, [
            ['gateId', 'required', 'message' => SupplierErrors::GATE_ID_NOT_SET],
            ['bookData', 'required', 'message' => SupplierErrors::BOOK_DATA_NOT_SET],
            ['serviceType', 'required', 'message' => SupplierErrors::SERVICE_TYPE_NOT_SET],
        ]);

        return true;
    }

    /**
     * Проверка параметров для отмены брони
     * @param $params
     * @return bool
     */
    public function checkGetServiceStatus($params)
    {
        $this->validateComplex($params, [
            ['gateId', 'required', 'message' => SupplierErrors::GATE_ID_NOT_SET],
            ['inServiceData', 'required', 'message' => SupplierErrors::SERVICE_DATA_NOT_SET],
        ]);

        return true;
    }
}
