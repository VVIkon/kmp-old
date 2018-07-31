<?php

/**
 * Class ServicesValidator
 * Класс для проверки корректности значений при работе с услугами заявки
 */
class ServicesValidator extends Validator
{
    /**
     * Код ошибки
     * @var int
     */
    private $errorCode;

    private $namespace;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        parent::__construct($module);

        $this->namespace = $this->module->getConfig('log_namespace');
    }


    public function checkServiceCommonParams($params)
    {

        $this->validateComplex($params, [
            ['serviceType', 'required', 'message' => OrdersErrors::SERVICE_TYPE_NOT_SET],
            ['serviceType', 'checkServiceType', 'message' => OrdersErrors::UNKNOWN_SERVICE_TYPE],
        ]);

        return true;
    }

    /**
     * Проверка параметров создания услуги
     * @param $params array
     * @return bool
     */
    public function checkServiceCreateParams($params)
    {

        $this->validateComplex($params, [
            ['saleCurrency', 'checkSaleCurrency', 'message' => OrdersErrors::CURRENCY_INCORRECT],
        ]);

        return true;
    }

    /**
     * Проверка параметров бронирования услуги
     * @param $params array
     * @return bool
     */
    public function checkBookStartCommonParams($params)
    {

        $this->validateComplex($params, [
            ['serviceId', 'required', 'message' => OrdersErrors::SERVICE_ID_NOT_SET],
            [
                'serviceId',
                'checkServiceToStartBooking',
                'message' => OrdersErrors::SERVICE_STATUS_IS_BLOCKING_BOOKING
            ],
            [
                'serviceId',
                'checkTouristsCountEqual',
                'message' => OrdersErrors::TOURISTS_INFO_IN_SERVICE_NOT_EQUAL_TO_OFFER
            ],

        ]);

        return true;
    }

    public function checkBookCompleteParams($params)
    {

        $this->validateComplex($params, [
            ['serviceId', 'required', 'message' => OrdersErrors::SERVICE_ID_NOT_SET],
            [
                'serviceId',
                'checkServiceToFinishBooking',
                'message' => OrdersErrors::SERVICE_STATUS_IS_BLOCKING_BOOKING
            ]
        ]);

        return true;
    }

    /**
     * Проверка параметров для выставления счёта
     * @param $params
     * @return bool
     */
    public function checkPayStartParams($params)
    {

        $this->validateComplex($params, [
            ['serviceId', 'required', 'message' => OrdersErrors::SERVICE_ID_NOT_SET],
            [
                'serviceId',
                'checkServiceToSetInvoice',
                'message' => OrdersErrors::SERVICE_STATUS_IS_BLOCKING_SET_PAYMENT
            ],
            [
                'invoiceId',
                'checkInvoiceSum',
                'message' => OrdersErrors::INCORRECT_INVOICE_AMOUNT
            ]
        ]);

        return true;
    }

    /**
     * Проверка параметров при оплате счёта
     * @param $params
     * @return bool
     */
    public function checkPayFinishParams($params)
    {

        $this->validateComplex($params, [
            ['serviceId', 'required', 'message' => OrdersErrors::SERVICE_ID_NOT_SET],
            [
                'serviceId',
                'checkServiceToSetPayment',
                'message' => OrdersErrors::SERVICE_STATUS_IS_BLOCKING_SET_PAYMENT
            ],
        ]);

        return true;
    }

    /**
     * Проверка параметров для выполнения операции выписки билетов
     * @param $params
     * @return bool
     */
    public function checkIssueTicketsParams($params)
    {

        $this->validateComplex($params, [
            ['serviceId', 'required', 'message' => OrdersErrors::SERVICE_ID_NOT_SET],
            [
                'serviceId',
                'checkServiceToIssueTickets',
                'message' => OrdersErrors::SERVICE_STATUS_IS_BLOCKING_ISSUE_TICKETS
            ],
        ]);

        return true;
    }

    /**
     * Проверка параметров для завершения оформления услуги
     * @param $params
     * @return bool
     */
    public function checkDoneParams($params)
    {

        $this->validateComplex($params, [
            ['serviceId', 'required', 'message' => OrdersErrors::SERVICE_ID_NOT_SET],
            [
                'serviceId',
                'checkServiceToDone',
                'message' => OrdersErrors::SERVICE_STATUS_IS_BLOCKING_SET_STATUS_DONE
            ],
        ]);

        return true;
    }

    /**
     * Проверка статуса и дат услуги для бронирования
     * @param $values
     * @param $attribute
     * @param $params
     */
    public function checkServiceToStartBooking($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        $service = ServicesForm::getServiceById($values[$attribute]);

        if (empty($service)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_NOT_FOUND,
                ['serviceId' => $values[$attribute]]
            );
        }

        if (empty($service['DateStart']) || strtotime($service['DateStart']) < time()) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_DATE_START_INCORRECT,
                ['serviceId' => $values[$attribute]]
            );
        }

        if (empty($service['DateFinish'])) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_DATE_END_INCORRECT,
                ['serviceId' => $values[$attribute]]
            );
        }


        $maxDateFinish = DateTime::createFromFormat('Y-m-d H:i:s', $service['DateFinish']);

        $maxDateFinish->add(new DateInterval('P1Y'));
        if (strtotime($service['DateFinish']) > $maxDateFinish->getTimestamp()) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_DATE_END_INCORRECT,
                ['serviceId' => $values[$attribute]]
            );
        }

        return true;
    }

    /**
     * Провекра состояния услугия для завершения процесса бронирования
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkServiceToFinishBooking($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        $service = ServicesForm::getServiceById($values[$attribute]);

        if (empty($service)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_NOT_FOUND,
                ['serviceId' => $values[$attribute]]
            );
        }

        if (
            $service['Status'] != ServicesForm::SERVICE_STATUS_W_BOOKED &&
            $service['Status'] != ServicesForm::SERVICE_STATUS_NEW &&
            $service['Status'] != ServicesForm::SERVICE_STATUS_MANUAL
        ) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_STATUS_IS_BLOCKING_BOOKING,
                [
                    'serviceId' => $values[$attribute],
                    'serviceStatus' => $service['Status']
                ]
            );
        }

        return true;
    }

    /**
     * Проверка статуса услуги для выставления счёта
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkServiceToSetInvoice($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        $service = ServicesForm::getServiceById($values[$attribute]);

        if (empty($service)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_NOT_FOUND,
                ['serviceId' => $values[$attribute]]
            );
        }

        if (
            $service['Status'] != ServicesForm::SERVICE_STATUS_BOOKED &&
            $service['Status'] != ServicesForm::SERVICE_STATUS_W_PAID &&
            $service['Status'] != ServicesForm::SERVICE_STATUS_P_PAID
        ) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                $params['message'],
                [
                    'serviceId' => $values[$attribute],
                    'serviceStatus' => $service['Status']
                ]
            );
        }

        $currencyForm = CurrencyRates::getInstance();
        $invoices = InvoiceServiceForm::getServiceInvoices($values[$attribute]);
        $serviceInvoiceSum = 0;
        foreach ($invoices as $invoice) {
            $serviceInvoiceSum += $currencyForm->calculateInCurrencyByIds($invoice['servicePrice'], $invoice['currencyId'], CurrencyRates::RUSSIAN_RUBLE_CURRENCY_ID);
        }

        $priceInCurrency = $currencyForm->calculateInCurrencyByIds($service['KmpPrice'], $service['SaleCurrency'], CurrencyRates::RUSSIAN_RUBLE_CURRENCY_ID);
        $priceInCurrency += $currencyForm->calculateInCurrencyByIds($service['AgencyProfit'], $service['SaleCurrency'], CurrencyRates::RUSSIAN_RUBLE_CURRENCY_ID);

        $priceInCurrency = number_format($priceInCurrency, 2, '.', '');
        $serviceInvoiceSum = number_format($serviceInvoiceSum, 2, '.', '');

        if ($priceInCurrency < $serviceInvoiceSum) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_COST_LOWER_INVOICES_SUM,
                [
                    'serviceId' => $values[$attribute],
                    'serviceStatus' => $service['Status']
                ]
            );
        }

        return true;
    }

    /**
     * Проверка состояния услуги для выставления оплаты
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkServiceToSetPayment($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        $service = ServicesForm::getServiceById($values[$attribute]);

        if (empty($service)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_NOT_FOUND,
                ['serviceId' => $values[$attribute]]
            );
        }

        if (
            $service['Status'] != ServicesForm::SERVICE_STATUS_PAID &&
            $service['Status'] != ServicesForm::SERVICE_STATUS_W_PAID &&
            $service['Status'] != ServicesForm::SERVICE_STATUS_P_PAID
        ) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                $params['message'],
                [
                    'serviceId' => $values[$attribute],
                    'serviceStatus' => $service['Status']
                ]
            );
        }

        return true;
    }

    /**
     * Проверка состояния услуги для выполнения операции выписки билетов
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkServiceToIssueTickets($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        $service = ServicesForm::getServiceById($values[$attribute]);

        if (empty($service)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_NOT_FOUND,
                ['serviceId' => $values[$attribute]]
            );
        }

        $userProfile = Yii::app()->user->getState('userProfile');

        switch ($service['Status']) {
            case ServicesForm::SERVICE_STATUS_W_PAID:
                if (isset($userProfile['userType']) && $userProfile['userType'] != 1) {
                    throw new KmpInvalidArgumentException(
                        get_class($this),
                        __FUNCTION__,
                        $params['message'],
                        [
                            'serviceId' => $values[$attribute],
                            'serviceStatus' => $service['Status']
                        ]
                    );
                }
                break;
            case ServicesForm::SERVICE_STATUS_BOOKED:
                if (isset($userProfile['userType']) && $userProfile['userType'] == 2) {
                    throw new KmpInvalidArgumentException(
                        get_class($this),
                        __FUNCTION__,
                        $params['message'],
                        [
                            'serviceId' => $values[$attribute],
                            'serviceStatus' => $service['Status']
                        ]
                    );
                }
                break;
            case ServicesForm::SERVICE_STATUS_NEW:
            case ServicesForm::SERVICE_STATUS_W_BOOKED:
            case ServicesForm::SERVICE_STATUS_CANCELLED:
            case ServicesForm::SERVICE_STATUS_VOIDED:
            case ServicesForm::SERVICE_STATUS_DONE:
                throw new KmpInvalidArgumentException(
                    get_class($this),
                    __FUNCTION__,
                    $params['message'],
                    [
                        'serviceId' => $values[$attribute],
                        'serviceStatus' => $service['Status']
                    ]
                );
                break;
            case ServicesForm::SERVICE_STATUS_PAID:
            case ServicesForm::SERVICE_STATUS_MANUAL:
                // здесь все в порядке
                break;
            default:
                throw new KmpInvalidArgumentException(
                    get_class($this),
                    __FUNCTION__,
                    $params['message'],
                    [
                        'serviceId' => $values[$attribute],
                        'serviceStatus' => $service['Status']
                    ]
                );
                break;
        }

        return true;
    }

    /**
     * Проверка статуса услуги для завершения её оформления
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkServiceToDone($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        $service = ServicesForm::getServiceById($values[$attribute]);

        if (empty($service)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_NOT_FOUND,
                ['serviceId' => $values[$attribute]]
            );
        }

        if (
            $service['Status'] != ServicesForm::SERVICE_STATUS_PAID
        ) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                $params['message'],
                [
                    'serviceId' => $values[$attribute],
                    'serviceStatus' => $service['Status']
                ]
            );
        }
        return true;
    }

    /**
     * Проверка суммы счёта
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkInvoiceSum($values, $attribute, $params)
    {

        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException(get_class($this), __FUNCTION__, OrdersErrors::INCORRECT_VALIDATION_RULES, []);
        }

        $invoice = InvoicesForm::getInvoiceById($values[$attribute]);

        if (empty($invoice)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::INVOICE_NOT_FOUND,
                ['serviceId' => $values[$attribute]]
            );
        }

        if (
            $invoice['InvoiceAmount'] <= 0
        ) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                $params['message'],
                [
                    'invoiceId' => $invoice['InvoiceID'],
                ]
            );
        }


        return true;
    }

    /**
     * Проверка соответствия количества и возрастов туристов
     * в оффере услуги и привязанных к услуге
     * @param $values
     * @param $attribute
     * @param $params
     * @return bool
     */
    public function checkTouristsCountEqual($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        $service = ServicesForm::getServiceById($values[$attribute]);

        if (empty($service)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_NOT_FOUND,
                ['serviceId' => $values[$attribute]]
            );
        }

        if (empty($service['OfferID'])) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::OFFER_ID_NOT_SET,
                ['serviceId' => $values[$attribute]]
            );
        }

        $offer = OffersFactory::createOffer($service['ServiceType']);

        if (!method_exists($offer, 'load')) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::OFFER_ID_INCORRECT,
                ['serviceId' => $values[$attribute]]
            );
        }

        try {
            $offer->load($service['OfferID']);
        } catch (KmpDbException $kde) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::OFFER_ID_INCORRECT,
                ['serviceId' => $values[$attribute]]
            );
        }

        $touristsInfo = $offer->getOfferTouristsInfo();
        $svcTouristsInfo = TouristForm::getServiceTourists($service['ServiceID']);

        $svcTourists = [
            'adult' => 0,
            'child' => 0,
            'infant' => 0
        ];

        foreach ($svcTouristsInfo as $svcTouristInfo) {

            if (empty($svcTouristInfo['Birthdate'])) {
                $svcTourists['adult'] += 1;
                continue;
            }

            $touristAge = TouristForm::getAge($svcTouristInfo['Birthdate']);

            if ($touristAge >= TouristForm::CHILD_AGE) {
                $svcTourists['adult'] += 1;
            } else if ($touristAge >= TouristForm::INFANT_AGE) {
                $svcTourists['child'] += 1;
            } else {
                $svcTourists['infant'] += 1;
            }
        }

        foreach ($touristsInfo as $key => $touristCategory) {

            if ($touristsInfo[$key] != $svcTourists[$key]) {

                throw new KmpInvalidArgumentException(
                    get_class($this),
                    __FUNCTION__,
                    OrdersErrors::TOURISTS_INFO_IN_SERVICE_NOT_EQUAL_TO_OFFER,
                    [
                        'serviceId' => $values[$attribute],
                        'serviceTourists' => print_r($svcTouristsInfo, 1),
                        'offerTourists' => print_r($touristsInfo, 1)
                    ]
                );
            }
        }

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
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute])) {
            return false;
        }

        if (!ServicesFactory::isServiceTypeExist($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Проверка значения параметра валюты поставщика
     * @param $values
     * @param $attribute
     * @param $params
     */
    public function checkSaleCurrency($values, $attribute, $params)
    {
        if (empty($attribute) || empty($values) || empty($params)) {
            throw new KmpException('', OrdersErrors::INCORRECT_VALIDATION_RULES);
        }

        if (empty($values[$attribute]) && empty($values['kmpPrice'])) {
            return true;
        }

        $CurrencyRates = CurrencyRates::getInstance();

        if (!$CurrencyRates->isCurrencyExists($values[$attribute])) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__, $params['message'], $values);
        }

        return true;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->errorCode;
    }

}
