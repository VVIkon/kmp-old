<?php

/**
 * Class RoomOfferPrice
 * Реализует функциональность для работы с ценовыми параметрами
 * номера для предложения размещения
 */
class RoomOfferPrice extends KFormModel
{
    const SUPPLIER_PRICE_TYPE = 1;
    const CLIENT_PRICE_TYPE = 2;

    /** @var string Идентификатор предложения */
    private $offerKey;
    /** @var int Тип назначения ценовых параметров */
    private $type;
    /** @var int Валюта предложения */
    private $currency;
    /** @var string Стоимость предложения нетто */
    private $amountNetto;
    /** @var string Стоимость предложения брутто */
    private $amountBrutto;
    /** @var int Валюта комиссии */
    private $commissionCurrency;
    /** @var string Размер комиссии в валюте комиссии */
    private $commissionAmount;
    /** @var string Коммиссия брутто цены в процентах */
    private $commissionPercent;
    /** @var int оригинальная валюта поставщика */
    private $originalCurrency;

    /**
     * @var RoomOfferPriceTax[]
     */
    private $taxes = [];

    /**
     * @param $module object
     */
    public function __construct($params)
    {
        $this->initParams($params);
    }

    public function getTaxes()
    {
        return $this->taxes;
    }

    /**
     * Инициализация параметров объектов
     * @param $params
     */
    public function initParams($params)
    {
        $this->currency = hashtableval($params['currency'], null);
        $this->amountNetto = hashtableval($params['amountNetto'], '');
        $this->amountBrutto = hashtableval($params['amountBrutto'], '');
        $this->originalCurrency = hashtableval($params['originalCurrency'], 0);

        $this->commissionCurrency = hashtableval($params['commission']['currency'], null);
        $this->commissionAmount = hashtableval($params['commission']['amount'], 0);
        $this->commissionPercent = hashtableval($params['commission']['percent'], 0);

        $this->type = $params['type'];
        $this->offerKey = isset($params['offerKey']) ? $params['offerKey'] : '';

        foreach (hashtableval($params['taxesAndFees'], []) as $taxAndFee) {
            $RoomOfferPriceTax = new RoomOfferPriceTax($taxAndFee, $this->type);
            $this->taxes[] = $RoomOfferPriceTax;
        }
    }

    /**
     * Получение данных по предложениям номеров из кэша
     * @param $offerKeys
     * @return array|CDbDataReader
     *
     * @deprecated удалить метод, вроде нигде не используется
     */
    public static function fromCache($offerKeys)
    {
        $command = Yii::app()->db->createCommand();

        $command->select(
            'offerKey, type, currency, amountBrutto, amountNetto, originalCurrency,
            commissionCurrency, commissionAmount, commissionPercent'
        );

        $command->from('ho_priceOffer');
        $command->where(['in', 'offerKey', $offerKeys]);
        $command->order('offerKey');

        try {
            $pricesInfo = $command->queryAll();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_ROOM_OFFER_PRICE,
                $command->getText(),
                $e
            );
        }
        return $pricesInfo;
    }

    /**
     * Возвращает массив цен найденных офферов
     * @param string $token токен поиска
     * @return array массив цен офферов [ 'offerKey' => [массив цен оффера]]
     */
    public static function getOffersPrices($token)
    {
        $offerPrices = [];

        $command = Yii::app()->db->createCommand()
            ->select('op.offerKey, op.type, op.currency, op.amountBrutto, op.amountNetto,
              op.originalCurrency, op.commissionCurrency, op.commissionAmount, op.commissionPercent')
            ->from('ho_priceOffer as op')
            ->join('ho_offers as o', 'o.offerKey = op.offerKey')
            ->where('o.token = :token', [':token' => $token])
            ->order('offerKey');

        try {
            $result = $command->query();

            foreach ($result as $op) {
                if (!isset($offerPrices[$op['offerKey']])) {
                    $offerPrices[$op['offerKey']] = [];
                }
                $offerPrices[$op['offerKey']][] = $op;
            }

            $result->close();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_ROOM_OFFER_PRICE,
                $command->getText(),
                $e
            );
        }

        return $offerPrices;
    }

    /**
     * Сохранить объект в БД
     */
    public function toCache($offerKey)
    {
//        return [
//            'offerKey' => $offerKey,
//            'type' => $this->type,
//            'currency' => $this->currency,
//            'amountBrutto' => $this->amountBrutto,
//            'amountNetto' => $this->amountNetto,
//            'originalCurrency' => $this->originalCurrency,
//            'commissionCurrency' => $this->commissionCurrency,
//            'commissionAmount' => $this->commissionAmount,
//            'commissionPercent' => $this->commissionPercent,
//        ];

        $command = Yii::app()->db->createCommand();

//        try {
            $res = $command->insert('ho_priceOffer', [
                'offerKey' => $offerKey,
                'type' => $this->type,
                'currency' => $this->currency,
                'amountBrutto' => $this->amountBrutto,
                'amountNetto' => $this->amountNetto,
                'originalCurrency' => $this->originalCurrency,
                'commissionCurrency' => $this->commissionCurrency,
                'commissionAmount' => $this->commissionAmount,
                'commissionPercent' => $this->commissionPercent,
            ]);

        $priceOfferId = $command->getConnection()->getLastInsertID();

//        } catch (Exception $e) {
//            throw new KmpDbException(
//                get_class(),
//                __FUNCTION__,
//                SearcherErrors::CANNOT_CREATE_ROOM_OFFER_PRICE,
//                $command->getText(),
//                $e
//            );
//        }

        foreach ($this->taxes as $tax) {
            $tax->toCache($priceOfferId);
        }
    }

    /**
     * Вывод параметров объекта в массив
     * @param $lang язык вывода
     * @param $currency валюта вывода
     * @param CurrencyRates $currencyForm
     * @return array
     */
    public function toArray($currency, &$currencyForm)
    {
        $inCurrency = (!empty($currency))
            ? $currency
            : $this->currency;

        $offerCurrency = $currencyForm->getCodeById($inCurrency);
        $originalCurrency = $currencyForm->getCodeById($this->originalCurrency);
        $commissionCurrency = $currencyForm->getCodeById($this->commissionCurrency);
        return [
            'amountNetto' => $currencyForm->calculateInCurrencyByIds($this->amountNetto, $this->currency, $inCurrency),
            'amountBrutto' => $currencyForm->calculateInCurrencyByIds($this->amountBrutto, $this->currency, $inCurrency),
            'currency' => !empty($offerCurrency) ? $offerCurrency : '',
            'originalCurrency' => !empty($originalCurrency) ? $originalCurrency : '',
            'commission' => [
                'currency' => !empty($commissionCurrency) ? $commissionCurrency : '',
                'amount' => ($this->commissionAmount == 0)
                    ? $this->commissionAmount
                    : $currencyForm->calculateInCurrencyByIds($this->commissionAmount, $this->commissionCurrency, $inCurrency),
                'percent' => $this->commissionPercent
            ]
        ];
    }

    /**
     * Получение свойств объекта
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'type' :
                return $this->type;
                break;
            case 'currency' :
                return $this->currency;
                break;
        }
    }

}
