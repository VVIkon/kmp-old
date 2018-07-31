<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 06.04.17
 * Time: 16:04
 */
interface ResponseOfferInterface
{
    /**
     * @return TokenCache
     */
    public function getTokenCache();

    /**
     * @return array
     */
    public function getDescriptionAsMinimalPrice();
}