<?php

/**
 * Class GptsRoomOffer
 * Реализует функциональность для работы с данными предложения номера места размещения
 */
class GptsRoomOffer extends KFormModel
{
    /** @var string Идентификатор предложения номера */
    private $offerKey;
    /** @var bool Признак доступности предложения в текущий момент */
    private $available;
    /** @var string дата начала предложения */
    private $dateFrom;
    /** @var string дата окончания предложения */
    private $dateTo;
    /** @var string Тип комнаты */
    private $roomType;
    /** @var string Описание типа комнаты */
    private $roomTypeDescription;
    /** @var array Типы питания в номере */
    private $mealTypes;
    /** @var bool Специальное предложение */
    private $specialOffer;
    /** @var array Ценовые компоненты предложения */
    private $offerPrices;
    /** @var string название тарифа */
    private $fareName;
    /** @var string описание тарифа */
    private $fareDescription;
    /** @var bool флаг, означающий возможность заказа дополнительного питания */
    private $mealOptionsAvailable;
    /** @var int количество номеров по данному офферу */
    private $availableRooms;
    /** @var int количество взрослых, указанных в запросе для этой комнаты */
    private $adults;
    /** @var int количество детей, указанных в запросе для этой комнаты */
    private $children;

    private $roomServices;

    /**
     * @param $module object
     */
    public function __construct($offerInfo)
    {
        $this->offerKey = $offerInfo['offerKey'];

//        var_dump($offerInfo);
//        exit;

        foreach ($offerInfo['salesTerms'] as $saleTerm) {
            $offerPrice = new GptsRoomOfferPrice();
            $offerPrice->type = $saleTerm['type'];
            $offerPrice->currency = $saleTerm['price']['currency'];
//            $offerPrice->amountNetto = $saleTerm['price']['amount'];
            $offerPrice->amountBrutto = $saleTerm['price']['amount'];
            $offerPrice->originalCurrency = !empty($saleTerm['originalCurrency'])
                ? $saleTerm['originalCurrency']
                : $offerPrice->currency;

            // todo реализовать получение параметров комиссии после их добавления в GPTS
            $offerPrice->commissionCurrency = !empty($saleTerm['price']['commission']['currency'])
                ? $saleTerm['price']['commission']['currency']
                : $saleTerm['price']['currency'];
            $offerPrice->commissionAmount = hashtableval($saleTerm['price']['commission']['amount'], 0);
            $offerPrice->commissionPercent = hashtableval($saleTerm['price']['commission']['percent'], 0);

            if ($offerPrice->commissionAmount) {
                $offerPrice->amountNetto = $saleTerm['price']['amount'] - $offerPrice->commissionAmount;
            } elseif ($offerPrice->commissionPercent) {
                $offerPrice->amountNetto = $saleTerm['price']['amount'] - $saleTerm['price']['amount'] * $offerPrice->commissionPercent / 100;
            } else {
                $offerPrice->amountNetto = $saleTerm['price']['amount'];
            }

            // TaxAndFee
            $offerTaxes = StdLib::nvl($saleTerm['price']['taxesAndFees'], []);
            foreach ($offerTaxes as $taxAndFee) {
                $offerTax = new GptsRoomOfferPriceTax();
                $offerTax->setTaxParams($taxAndFee);
                $offerPrice->taxes[] = $offerTax;
            }
            $this->offerPrices[] = $offerPrice;
        }

        $this->available = $offerInfo['available'];
        $this->dateFrom = $offerInfo['dateFrom'];
        $this->dateTo = $offerInfo['dateTo'];
        $this->roomType = $offerInfo['info']['roomType'];
        $this->roomTypeDescription = isset($offerInfo['info']['roomTypeDescription']) ? $offerInfo['info']['roomTypeDescription'] : '';
        //$this->mealTypes = $offerInfo['info']['mealTypes'];
        $this->specialOffer = $offerInfo['specialOffer'];

        $this->mealTypes = [];
        foreach ($offerInfo['info']['mealTypes'] as $mealType) {
            $this->mealTypes[] = $mealType['standardCode'];
        }

        $this->roomServices = [];

        if (isset($offerInfo['roomServices'])) {
            foreach ($offerInfo['roomServices'] as $roomService) {
                $this->roomServices[] = $roomService;
            }
        }

        $this->fareName = hashtableval($offerInfo['info']['tariff'], null);
        $this->fareDescription = hashtableval($offerInfo['info']['tariffDescription'], null);
        $this->mealOptionsAvailable = hashtableval($offerInfo['info']['mealOptionsAvailable'], false);
        $this->availableRooms = hashtableval($offerInfo['availableRooms'], null);

        if (isset($offerInfo['guests'])) {
            $this->adults = (int)$offerInfo['guests']['adults'];
            $this->children = count($offerInfo['guests']['ages']);
        } else {
            $this->adults = null;
            $this->children = null;
        }
    }

    /**
     * Вывод параметров объекта в массив
     * @return array
     */
    public function toArray($curForm = null)
    {
        $offer = [];
        $offer['offerKey'] = $this->offerKey;

//        if (empty($curForm)) {
//            $curForm = new CurrencyForm();
//        }

        foreach ($this->offerPrices as $offerPrice) {
//            $offer['salesTerms'][] = $offerPrice->toArray($curForm);
            $offer['salesTerms'][] = $offerPrice->toArray();
        }

        $offer['available'] = $this->available;
        $offer['dateFrom'] = $this->dateFrom;
        $offer['dateTo'] = $this->dateTo;
        $offer['roomType'] = $this->roomType;
        $offer['roomTypeDescription'] = $this->roomTypeDescription;
        $offer['mealTypes'] = implode(',', $this->mealTypes);
        $offer['specialOffer'] = $this->specialOffer;
        $offer['fareName'] = $this->fareName;
        $offer['fareDescription'] = $this->fareDescription;
        $offer['mealOptionsAvailable'] = $this->mealOptionsAvailable;
        $offer['availableRooms'] = $this->availableRooms;
        $offer['adults'] = $this->adults;
        $offer['children'] = $this->children;
        $offer['roomServices'] = $this->roomServices;

        return $offer;
    }

}
