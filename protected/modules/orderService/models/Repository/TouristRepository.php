<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/5/16
 * Time: 3:26 PM
 */
class TouristRepository
{
    /**
     * @return Tourist
     */
    public static function getTouristById($id)
    {
        return Tourist::model()->findByPk($id);
    }
    
    /**
    * @param string $utkTouristId
    * @return Tourist|null
    */
    public static function getTouristByUTKId($utkTouristId) {
        return Tourist::model()->findByAttributes(['TouristID_UTK' => $utkTouristId]);
    }
    
    /**
    * @param string $gptsTouristId
    * @return Tourist|null
    */
    public static function getTouristByGPTSId($gptsTouristId) {
        return Tourist::model()->findByAttributes(['TouristID_GP' => $gptsTouristId]);
    }
}