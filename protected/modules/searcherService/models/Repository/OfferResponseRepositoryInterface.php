<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/20/16
 * Time: 3:45 PM
 */
interface OfferResponseRepositoryInterface
{
    public static function getByOfferId($offerId);

    public static function getOffersByToken($token, $maxId = 0, $limit = 0);

    public static function decorateForGetSearchResult($offers, Currency $currency, $lang);

    public static function getSearchRequestByToken(TokenCache $tokenCache);

    public static function getOfferIdsWithMinimalPriceByToken(TokenCache $tokenCache);
}