<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 05.04.17
 * Time: 13:19
 */
class TokenCacheRepository
{
    /**
     * @param $searchToken
     * @return TokenCache|null
     */
    public static function getByToken($searchToken)
    {
        return TokenCache::model()->findByPk($searchToken);
    }
}