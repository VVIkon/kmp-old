<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/29/16
 * Time: 4:52 PM
 */
class AviaCancelPenalty extends AbstractCancelPenalty
{
    public function tableName()
    {
        return 'kt_service_fl_cancelPenalties';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
}