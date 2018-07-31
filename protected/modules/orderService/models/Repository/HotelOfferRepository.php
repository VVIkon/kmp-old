<?php

/**
 * Репозиторий для отельных офферов
 */
class HotelOfferRepository
{
    public static function findByOfferId($offerId)
    {
        return HotelOffer::model()->with(
            'roomServices',
////            'priceOffers.PriceOfferCurrency',
//            'priceOffers',
            'hotelReservation',
            'hotelCancelPenalties',
            'HotelInfo.HotelChain',
            'HotelInfo.HotelImages'
//            'HotelInfo.HotelServices.HotelServiceGroup'
//            'HotelInfo.HotelDescriptions.HotelDescriptionType'
        )->findByPk($offerId);
    }
}