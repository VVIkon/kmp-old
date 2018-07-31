<?php

/**
 * Репозиторий отельных офферов поиска
 */
class AviaOfferResponseRepository implements OfferResponseRepositoryInterface
{
    /**
     * @param $offerId
     * @return mixed
     */
    public static function getByOfferId($offerId)
    {
        return AviaOfferResponse::model()
            ->with('trips.segments.arrivalAirport', 'trips.segments.departureAirport')
            ->findByAttributes(array('id' => $offerId));
    }

    /**
     * @param $token
     * @param int $maxId
     * @param int $limit
     * @return mixed
     */
    public static function getOffersByToken($token, $maxId = 0, $limit = 0)
    {
        $Criteria = new CDbCriteria();

        if ($limit > 0) {
            $Criteria->limit = $limit;
        } else {
            $Criteria->limit = 500;
        }

        if ($maxId > 0) {
            $Criteria->compare('t.id', '>' . $maxId);
        }

        $Criteria->with = array(
            'trips.segments',
            'AviaSearchRequest',
            'offerValue'
        );
        $Criteria->compare('t.token', $token);
        $Criteria->order = 't.id';

        return AviaOfferResponse::model()->findAll($Criteria);
    }

    public static function decorateForGetSearchResult($offers, Currency $currency, $lang)
    {
        $offerIds = [];
        $offerInfo = [];

        if (count($offers)) {
            $offerInfo = [];
            $module = YII::app()->getModule('searcherService');

            foreach ($offers as $offer) {
                $offer->addCurrency('view', $currency);
                $offer->addCurrency('local', CurrencyStorage::findByString(643));
                $offer->addCurrency('client', CurrencyStorage::findByString(643));
                $offer->setLang($lang);
                $offer->setConfig($module->getConfig());
                $offerInfo[] = $offer->toArray();
                $offerIds[] = $offer->getId();
            }
        }

        return [$offerInfo, $offerIds];
    }

    public static function getSearchRequestByToken(TokenCache $tokenCache)
    {
        return AviaSearchRequest::model()->findByPk($tokenCache->getToken());
    }

    /**
     * @param TokenCache $tokenCache
     * @return mixed
     */
    public static function getOfferIdsWithMinimalPriceByToken(TokenCache $tokenCache)
    {
        $resp = Yii::app()->db->createCommand()
            ->select('FLO.id, FLO.amountBrutto * (IFNULL(CUR.RateCBR, 1) / IF(CUR.Nominal = 0, 1, CUR.Nominal)) AS amount')
            ->from('fl_Offer AS FLO')
            ->join('kt_ref_currencies REF', 'REF.CurrencyCode = FLO.currencyBrutto')
            ->join('kt_currencies_rates CUR', 'CUR.CurrencyID = REF.CurrencyID and CUR.RateDate = date(now())')
            ->andWhere("FLO.amountBrutto = (SELECT MIN(amountBrutto) FROM fl_Offer WHERE token = '{$tokenCache->getToken()}')")
            ->queryAll();

        $answer = [];

        if(count($resp)){
            foreach ($resp as $item) {
                $answer[] = $item['id'];
            }
        }

        return $answer;
    }


}