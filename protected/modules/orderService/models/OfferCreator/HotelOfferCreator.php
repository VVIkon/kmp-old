<?php

/**
 * Класс создатель отельного оффера
 */
class HotelOfferCreator
{
    /**
     * Создает набор сущностей оффера отеля по данным из GetCacheOffer
     * @param array $offerData
     * @return ServiceOfferInterface
     * @throws DomainException
     */
    public static function createFromArray(array $offerData)
    {
        $HotelOffer = new HotelOffer();
        $HotelOffer->fromArray($offerData);

        if (!$HotelOffer->save(false)) {
            throw new DomainException('Не удалось сохранить оффер Проживания');
        }

        if (count($offerData['salesTermsInfo'])) {
            // выберем из структуры salesTermsInfo только нужные цены по исходным валютам
            foreach ($offerData['salesTermsInfo'] as $currency => $priceTypeArray) {
                if ($currency == 'supplierCurrency') {
                    foreach ($priceTypeArray as $priceOfferType => $priceOfferArray) {
                        $HotelPriceOffer = new HotelPriceOffer();
                        $HotelPriceOffer->fromArray($priceOfferArray);
                        $HotelPriceOffer->setType($priceOfferType);
                        $HotelPriceOffer->setOfferId($HotelOffer->getOfferId());

                        if (!$HotelPriceOffer->save(false)) {
                            throw new DomainException('Не удалось сохранить ценовое предложение');
                        }

                        // Tax & Fees
                        foreach (StdLib::nvl($priceOfferArray['taxesAndFees'], []) as $tax) {
                            $hotelTaxOffer = new HotelTaxOffer();
                            $hotelTaxOffer->fromArray($tax, $HotelOffer->getOfferId());
                            if (!$hotelTaxOffer->save(false)) {
                                throw new DomainException('Не удалось сохранить налоги предложения');
                            }
                        }

                    }
                }
            }
        } else {
            throw new DomainException('Нет данных о ценовых предложениях');
        }

        /*
         * Сохраним roomService
         */
        if (count($offerData['roomServices'])) {
            foreach ($offerData['roomServices'] as $roomService) {
                $roomService['offerId'] = $HotelOffer->getOfferId();

                $HotelRoomServiceOffer = new HotelRoomService();
                $HotelRoomServiceOffer->setAttributes($roomService, false);
                if (!$HotelRoomServiceOffer->save(false)) {
                    throw new DomainException('Не удалось сохранить услугу в номере');
                }
            }
        }

        /**
         * TP
         */
        if (!isset($offerData['travelPolicy'])) {
            $offerData['travelPolicy'] = [];
        }
        if (!$HotelOffer->createTravelPolicyFromArray($offerData['travelPolicy'])) {
            throw new DomainException('Не удалось сохранить TP');
        }

        return $HotelOffer;
    }

    /**
     * Создание доп оффера по входящим параметрам
     * @param $data
     * @param HotelOffer $offer
     */
    public static function createAddOfferFromArray($data, HotelOffer $offer)
    {
        $addOffer = new HotelAddOffer();
        $addOffer->fromArray($data);
        $addOffer->bindHotelOffer($offer);

        if (!$addOffer->save(false)) {
            throw new DomainException('Не удалось сохранить оффер Проживания');
        }

        if (count($data['salesTermsInfo'])) {
            // выберем из структуры salesTermsInfo только нужные цены по исходным валютам
            foreach ($data['salesTermsInfo'] as $currency => $priceTypeArray) {
                if ($currency == 'supplierCurrency') {
                    foreach ($priceTypeArray as $priceOfferType => $priceOfferArray) {
                        $addOfferPrice = new HotelAddOfferPrice();
                        $addOfferPrice->fromArray($priceOfferArray);
                        $addOfferPrice->setType($priceOfferType);
                        $addOfferPrice->bindAddOffer($addOffer);

                        if (!$addOfferPrice->save(false)) {
                            throw new DomainException('Не удалось сохранить ценовое предложение');
                        }
                    }
                }
            }
        } else {
            throw new DomainException('Нет данных о ценовых предложениях');
        }
    }
}