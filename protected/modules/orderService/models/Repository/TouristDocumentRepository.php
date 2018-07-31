<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 1:25 PM
 */
class TouristDocumentRepository
{
    /**
     * @param $touristId
     * @return TouristDocument|null
     */
    public static function getTouristDocumentByTouristId($touristId)
    {
        return TouristDocument::model()->findByAttributes(['TouristIDbase' => $touristId]);
    }
}