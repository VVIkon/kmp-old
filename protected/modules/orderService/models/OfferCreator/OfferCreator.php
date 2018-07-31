<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 15.02.17
 * Time: 13:01
 */
class OfferCreator
{
    /**
     * @param array $offerData
     * @param $offerClassName
     * @return ServiceOfferInterface
     * @throws DomainException
     */
    public static function createFromArray(array $offerData, $offerClassName)
    {
        $offerCreatorClassName = $offerClassName . 'OfferCreator';

        if (class_exists($offerCreatorClassName)) {
            return $offerCreatorClassName::createFromArray($offerData);
        } else {
            throw new DomainException("Unknown offer class: $offerClassName");
        }
    }
}