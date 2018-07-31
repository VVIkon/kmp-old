<?php

/**
 * Class UtkOrderService
 * Реализует функциональность работы с данными услуги для УТК
 */
class UtkOrderService extends KFormModel
{
    const ACCMD_SERVICE_TYPE = 1;
    const FLIGHT_SERVICE_TYPE = 2;
    const TRANSFER_SERVICE_TYPE = 3;
    const VISA_SERVICE_TYPE = 4;
    const CAR_RENT_SERVICE_TYPE = 5;
    const TOUR_SERVICE_TYPE = 6;
    const RAILWAY_SERVICE_TYPE = 7;
    const PACKET_SERVICE_TYPE = 8;
    const INSURANCE_SERVICE_TYPE = 9;
    const GUIDE_SERVICE_TYPE = 10;
    const EXTRA_SERVICE_TYPE = 11;
    const EXCURSION_SERVICE_TYPE = 12;

    const ACTIVE_STATUS = 1;
    const ANNULED_STATUS = 2;


    /** @var int тип услуги */
    public $serviceType;

    /** @var int Идентифкатор услуги в КТ */
    public $serviceId;

    /** @var int Идентифкатор услуги в УТК */
    public $serviceIdUtk;

    /** @var int Идентифкатор услуги в GPTS */
    public $serviceIdGpts;

    /** @var string время обновления услуги */
    public $serviceDateUpdate;

    /** @var string статус услуги */
    public $status;

    /** @var string Дата начала оказания услуги */
    public $startDateTime;

    /** @var string Дата начала оказания услуги */
    public $endDateTime;

    /** @var string Дата начала оказания услуги */
    public $refNum;

    /** @var string Ид поставщика услуг в КТ */
    public $supplerId;

    /** @var string Ид поставщика услуг в УТК */
    public $supplerIdUtk;

    /** @var string Ид поставщика услуг в GPTS */
    public $supplerIdGpts;

    /** @var string Наименование поставщика */
    public $supplierCompanyName;

    /** @var float цена продажи одного экземпляра */
    public $salePrice;

    /** @var float себестоимость одного экземпляра */
    public $netPrice;

    /** @var float общая сумма продажи услуг */
    public $saleSum;

    /** @var float общая себестоимость продажи услуг */
    public $netSum;

    /** @var string валюта себестоимости */
    public $netCurrency;

    /** @var string валюта продажи */
    public $saleCurrency;

    /** @var float процент комиссии */
    public $commission;

    /** @var float процент комиссии */
    public $commissionSum;

    /** @var array детали конкретного типа услуги */
    public $serviceDetails;

    /** @var array детали конкретного типа услуги */
    public $servicesAdd;

    /** @var array налоги и сборы */
    public $taxesAndFees = [];

    /**
     * @var string Штрафы
     */
    public $SalePenalty;
    public $SalePenaltyCurr;
    public $NetPenalty;
    public $NetPenaltyCurr;

    protected $addData = [];

    protected $extraServices = [];

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules()
    {
        return array(
            ['serviceType, serviceId, serviceIdUtk, serviceIdGpts, serviceDateUpdate, status, startDateTime, endDateTime, refNum,
                supplerId, supplerIdUtk, supplerIdGpts, supplierCompanyName, salePrice, netPrice,saleSum, netSum,
                netCurrency, saleCurrency, commission, commissionSum, serviceDetails,
                servicesAdd, SalePenalty, SalePenaltyCurr, NetPenalty, NetPenaltyCurr', 'safe'
            ]
        );
    }

    /**
     * Инициализация свойств услуги заявки
     * @param $serviceId
     * @throws Exception
     */
    public function load($serviceId)
    {
        $serviceInfo = $this->getServiceInfo($serviceId);

        $OrdersServices = OrdersServicesRepository::findById($serviceId);

        $supplerId = '';
        $supplerIdUtk = '';
        $supplerIdGpts = '';
        $supplierCompanyName = '';

        $serviceDetails = [];

        /*
         * пытаемся отправить данные поставщика, но бывает так, что оффера нет, тк услуга офлайновая
         */
        try {
            $Supplier = $OrdersServices->getOffer()->getSupplier();

            $supplerId = $Supplier->getSupplierID();
            $supplerIdUtk = $Supplier->getSupplierIDUTK();
            $supplerIdGpts = $Supplier->getSupplierIDGPTS();
            $supplierCompanyName = $Supplier->getName();

            $serviceDetails = $OrdersServices->getOffer()->getUtkServiceDetails();
        } catch (Exception $e) {
            /*
                здесь не нашли оффер в услуге и в данном случае все нормально
            */
        }

        $CurrencyRates = CurrencyRates::getInstance();

        $this->setAttributes([
            'serviceType' => ServicesTypesMapperHelper::getUtkByKtServiceType($serviceInfo['ServiceType']),
            'serviceId' => $serviceInfo['ServiceID'],
            'serviceIdUtk' => $serviceInfo['ServiceID_UTK'],
            'serviceIdGpts' => $serviceInfo['ServiceID_GP'],
            'serviceDateUpdate' => $serviceInfo['DateOrdered'],
            'status' => StatusesMapperHelper::getUtkByKtStatus(
                $serviceInfo['Status'],
                StatusesMapperHelper::STATUS_TYPE_SERVICE
            ),
            'startDateTime' => $serviceInfo['DateStart'],
            'endDateTime' => $serviceInfo['DateFinish'],
            'refNum' => $serviceInfo['SupplierSvcID'],
            'supplerId' => $supplerId,
            'supplerIdUtk' => $supplerIdUtk,
            'supplerIdGpts' => $supplerIdGpts,
            'supplierCompanyName' => $supplierCompanyName,
            'salePrice' => $serviceInfo['KmpPrice'],
            'netPrice' => $serviceInfo['SupplierPrice'],
            'saleSum' => $serviceInfo['KmpPrice'],
            'netSum' => $serviceInfo['SupplierPrice'],
            'netCurrency' => $CurrencyRates->getCodeById($serviceInfo['SupplierCurrency']),
            'saleCurrency' => $CurrencyRates->getCodeById($serviceInfo['SaleCurrency']),
            'commission' => $serviceInfo['AgencyProfit'],
            'commissionSum' => $serviceInfo['AgencyProfit'],
            'serviceDetails' => $serviceDetails,
            'SalePenalty' => $serviceInfo['SalePenalty'],
            'SalePenaltyCurr' => $serviceInfo['SalePenaltyCurr'],
            'NetPenalty' => $serviceInfo['NetPenalty'],
            'NetPenaltyCurr' => $serviceInfo['NetPenaltyCurr'],
            'quantity' => 1,
            'city_id' => 0,
            'country_id' => 0,
            'servicesAdd' => []
        ]);

        // добавим доп поля
        $orderService = OrdersServicesRepository::findById($serviceInfo['ServiceID']);
        $serviceAddFields = OrderAdditionalFieldRepository::getServiceFieldWithId($orderService);

        foreach ($serviceAddFields as $serviceAddField) {
            $this->addData[] = $serviceAddField->getSOUTKOrderAdditionalData();
        }

        // добавим доп услуги
        $addServices = $orderService->getAddServices();

        foreach ($addServices as $addService) {
            $this->extraServices[] = $addService->toSOUTKAddService();
        }

        // налоги сборы
        $servicePrices = $orderService->getServicePrices();
        foreach ($servicePrices as $servicePrice) {
            if ($servicePrice->isClient()) {
                $taxes = $servicePrice->getTaxes();
                foreach ($taxes as $tax) {
                    $this->taxesAndFees[] = $tax->toArray();
                }
                break;
            }
        }


        // офлайновая доп услуга
        if ($orderService->getComment()) {
            $this->extraServices[] = [
                'idAddService' => null,      // id доп.услуги
                'serviceId' => null,            // id основной услуги (доп.услуга не может быть оформлена самостоятельно)
                'serviceSubType' => null,    // номер доп.услуги в справочнике доп.услуг.
                'status' => null,                  // Статус доп.услуги
                'salesTerms' => [],         // Ценовые компоненты услуги, структура ss_salesTermsInfo
                'tourists' => [],                           // массив с данными туриста. структура ss_tourist. Опционально, т.к. турист может заказать или не заказать эту доп.услугу
                'specParamAddService' => null,  //массив дополнительных параметров для доп.услуги. Опционально.
                'name' => null,
                'typeName' => null,
                'manualAddService' => $orderService->getComment()
            ];
        }
    }

    /**
     * Вывод свойств объекта в виде массива
     * @return array
     */
    public function toArray()
    {
        return [
            'serviceId' => $this->serviceId,
            'serviceIdUTK' => $this->serviceIdUtk,
            'GPTSserviceID' => $this->serviceIdGpts,
            'serviceDateUpdate' => UtkDateTime::getUtkDate($this->serviceDateUpdate),
            'serviceType' => $this->serviceType,
            'status' => $this->status,
            'startDateTime' => UtkDateTime::getUtkDate($this->startDateTime),
            'endDateTime' => UtkDateTime::getUtkDate($this->endDateTime),
            'refNum' => $this->refNum,
            'supplierCompanyName' => $this->supplierCompanyName,
            'supplierId' => $this->supplerId,
            'supplierIdUTK' => $this->supplerIdUtk,
            'supplierIdGPTS' => $this->supplerIdGpts,
            'SalePrice' => $this->salePrice + $this->commissionSum,
            'NetPrice' => $this->netPrice,
            'SaleSum' => $this->saleSum + $this->commissionSum,
            'NetSum' => $this->netSum,
            'NetCurrency' => $this->netCurrency,
            'SaleCurrency' => $this->saleCurrency,
            'Commission' => $this->commission,
            'CommissionSum' => $this->commissionSum,
            'ServiceDetails' => $this->serviceDetails,
            'taxesAndFees' => $this->taxesAndFees,
            'SalePenalty' => $this->SalePenalty,
            'SalePenaltyCurr' => $this->SalePenaltyCurr,
            'NetPenalty' => $this->NetPenalty,
            'NetPenaltyCurr' => $this->NetPenaltyCurr,
            'orderAdditionalData' => $this->addData,
            'extraServices' => $this->extraServices
        ];
    }

    /**
     * Получение предложение услуги
     * @param $serviceType
     * @param $offerId
     */
    private function getOffer($serviceType, $offerId)
    {
        $offer = OffersFactory::createOffer($serviceType);
        $offer->load($offerId);

        if ($serviceType == 2) {
            $offer->setSegmentsAirportCityName(LangForm::LANG_RU);
        }

        /*if (empty($offer)) {
            throw new KmpException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::OFFER_ID_NOT_EXISTENT,
                [
                    'serviceType' => $serviceType,
                    'offerId' => $offerId
                ]
            );
        }*/

        return $offer;
    }

    /**
     * Получение информации об услуге
     * @param $serviceId
     */
    private function getServiceInfo($serviceId)
    {
        $serviceInfo = ServicesForm::getServiceById($serviceId);

        if (empty($serviceInfo)) {
            throw new KmpException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_NOT_FOUND,
                [
                    'serviceId' => $serviceId
                ]
            );
        }
        return $serviceInfo;
    }

    /**
     * Получение информации о компании оказывающей услугу
     * @param $serviceId
     */
    private function getSupplier($supplierId)
    {
        $supplierInfo = SuppliersForm::getSupplierById($supplierId);

        if (empty($supplierInfo)) {
            throw new KmpException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SUPPLIER_COMPANY_NOT_FOUND,
                [
                    'supplierId' => $supplierId
                ]
            );
        }
        return $supplierInfo;
    }

    /**
     *  Конвертирует значения datetime в формат datetime УТК
     */
    private function convertServiceDetailsDates()
    {
        foreach ($this->serviceDetails as $key => $detail) {

            if (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $detail)) {
                $this->serviceDetails[$key] = str_replace(' ', '', $detail);
            }
        }
    }

}
