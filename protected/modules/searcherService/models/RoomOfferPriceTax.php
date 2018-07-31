<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 31.07.17
 * Time: 11:19
 */
class RoomOfferPriceTax extends KFormModel
{
    /** @var string Идентификатор предложения */
//    private $offerKey;

    /** @var string Идентификатор предложения */
    private $amount;

    /** @var string Идентификатор предложения */
    private $code;

    /** @var string Идентификатор предложения */
    private $currency;
    /**
     * @var тип
     */
    private $type;

//    const SUPPLIER_PRICE_TYPE = 1;
//    const CLIENT_PRICE_TYPE = 2;

    /**
     * RoomOfferPriceTax constructor.
     * @param string $params
     * @param $type
     */
    public function __construct($params, $type)
    {
        $this->initParams($params, $type);
    }

    /**
     * Инициализация параметров объектов
     * @param $params
     */
    public function initParams($params, $type)
    {
        $this->type = hashtableval($type, '');
        $this->code = hashtableval($params['code'], '');
        $this->amount = hashtableval($params['amount'], 0);
        $this->currency = hashtableval($params['currency'], '');
    }

    public function toCache($offerPriceId)
    {
        $command = Yii::app()->db->createCommand();

        $res = $command->insert('ho_taxOffer', [
            'priceOfferId' => $offerPriceId,
            'code' => $this->code,
            'amount' => $this->amount,
            'currency' => $this->currency,
        ]);

//        $tax['code'] = $this->code;
//        $tax['amount'] = $this->amount;
//        $tax['currency'] = $this->currency;
//
//        return $tax;
    }
}