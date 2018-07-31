<?php

/**
 * Модель доп услуги отельного оффера
 *
 * @property $offerId
 *
 * @property HotelOfferResponse $hotelOffer
 * @property HotelAddOfferResponsePrice[] $addOfferPrices
 */
class HotelAddOfferResponse extends AbstractHotelAddOffer
{
    public function tableName()
    {
        return 'ho_addOffers';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'hotelOffer' => array(self::BELONGS_TO, 'HotelOfferResponse', 'offerId'),
            'addOfferPrices' => array(self::HAS_MANY, 'HotelAddOfferResponsePrice', 'addOfferId'),
        );
    }

    /**
     *
     * @param HotelOfferResponse $offer
     */
    public function bindHotelOfferResponse(HotelOfferResponse $offer)
    {
        $this->offerId = $offer->getId();
    }

    /**
     * Создание пулла записей о доп услуге с ценами из данных ГПТС
     * @param HotelOfferResponse $offer
     * @param $data array
     * @param AbstractRefSubService $refSubServiceConcreteClass
     * @return bool
     */
    public static function createFromGPTSData(HotelOfferResponse $offer, $data, AbstractRefSubService $refSubServiceConcreteClass)
    {
        // создадим саму доп услугу
        $addOfferResponse = new HotelAddOfferResponse();
        $addOfferResponse->bindHotelOfferResponse($offer);
        $addOfferResponse->setSubServiceId($refSubServiceConcreteClass->getRefSubServiceId());
        $addOfferResponse->setEngineData([
            'GPTS_service_ref' => $data['serviceRPH']
        ]);
        $addOfferResponse->setName($refSubServiceConcreteClass->getName());
        $addOfferResponse->setSpecParamAddService($refSubServiceConcreteClass->getSpecParams());

        // цена поставщика
        $addOfferResponsePriceSupplier = new HotelAddOfferResponsePrice();
        $addOfferResponsePriceSupplier->setType('supplier');
        $addOfferResponsePriceSupplier->setAmountBrutto($data['originalSupplierPrice']['amount']);
        $addOfferResponsePriceSupplier->setAmountNetto($data['originalSupplierPrice']['amount']);
        $addOfferResponsePriceSupplier->setCurrency($data['originalSupplierPrice']['currency']);

        // цена клиента
        $addOfferResponsePriceClient = new HotelAddOfferResponsePrice();
        $addOfferResponsePriceClient->setType('client');
        $addOfferResponsePriceClient->setAmountBrutto($data['price']['amount']);
        $addOfferResponsePriceClient->setAmountNetto($data['price']['amount']);
        $addOfferResponsePriceClient->setCurrency($data['price']['currency']);

        // сохраним все в транзакции
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $addOfferResponse->save(false);
            $addOfferResponsePriceSupplier->bindAddOffer($addOfferResponse);
            $addOfferResponsePriceClient->bindAddOffer($addOfferResponse);
            $addOfferResponsePriceSupplier->save(false);
            $addOfferResponsePriceClient->save(false);

            $transaction->commit();
        } catch (Exception $e){
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Ошибка при сохранении доп услуг', $e->getMessage(),
                [],
                'error', 'system.searcherservice.errors'
            );
            $transaction->rollback();
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getOfferId()
    {
        return $this->offerId;
    }

    /**
     * @return HotelAddOfferResponsePrice[]
     */
    public function getAddOfferPrices()
    {
        return $this->addOfferPrices;
    }

    /**
     * @return mixed
     */
    public function getSubService()
    {
        // TODO: Implement getSubService() method.
    }
}