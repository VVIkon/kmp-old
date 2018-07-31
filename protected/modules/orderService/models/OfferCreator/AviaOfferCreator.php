<?php

/**
 *
 */
class AviaOfferCreator
{
    /**
     * Создает набор сущностей оффера авиа по данным из GetCacheOffer
     * @param array $offerData
     * @return ServiceOfferInterface
     * @throws DomainException
     */
    public static function createFromArray(array $offerData)
    {
        $AviaOffer = new AviaOffer();
        $AviaOffer->fromArray($offerData);

        $prices = [];

        if (count($offerData['salesTermsInfo'])) {
            // выберем из структуры salesTermsInfo только нужные цены по исходным валютам
            foreach ($offerData['salesTermsInfo'] as $currency => $priceTypeArray) {
                if ($currency == 'supplierCurrency') {
                    foreach ($priceTypeArray as $priceOfferType => $priceOfferArray) {
                        $priceOffer = new AviaOfferPrice();
                        $priceOffer->setType($priceOfferType);

                        if ($priceOfferType == 'supplier') {
                            $AviaOffer->setSupplierSaleTerms($priceOfferArray);
                        } elseif ($priceOfferType == 'client') {
                            $AviaOffer->setClientSaleTerms($priceOfferArray);
                        }

                        $priceOffer->fromArray($priceOfferArray);
                        $prices[] = $priceOffer;
                    }
                }
            }
        } else {
            throw new DomainException('Нет данных о ценовых предложениях');
        }

        if (!$AviaOffer->save(false)) {
            throw new DomainException('Не удалось сохранить Авиа оффер');
        }

        foreach ($prices as $priceOffer) {
            $priceOffer->bindOffer($AviaOffer);

            if (!$priceOffer->save(false)) {
                throw new DomainException('Не удалось сохранить ценовое предложение авиа');
            }
        }

        if (count($offerData['itinerary'])) {
            foreach ($offerData['itinerary'] as $itinerary) {
                $AviaTrip = new AviaTrip();
                $AviaTrip->fromArray($itinerary);
                $AviaTrip->bindOffer($AviaOffer);

                if (!$AviaTrip->save(false)) {
                    throw new DomainException('Не удалось сохранить маршрут');
                }

                if (count($itinerary['segments'])) {
                    foreach ($itinerary['segments'] as $segment) {
                        $AviaOfferSegment = new AviaOfferSegment();
                        $AviaOfferSegment->fromArray($segment);
                        $AviaOfferSegment->bindTrip($AviaTrip);
                        $AviaOfferSegment->bindOffer($AviaOffer);

                        if (!$AviaOfferSegment->save(false)) {
                            throw new DomainException('Не удалось сохранить сегмент');
                        }
                    }
                } else {
                    throw new DomainException('Не заданы сегменты перелета');
                }
            }
        } else {
            throw new DomainException('Не задан маршрут перелета');
        }

        if(!isset($offerData['travelPolicy'])){
            $offerData['travelPolicy'] = [];
        }
        if (!$AviaOffer->createTravelPolicyFromArray($offerData['travelPolicy'])) {
            throw new DomainException('Не удалось сохранить TP');
        }

        return $AviaOffer;
    }
}