<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/3/16
 * Time: 4:36 PM
 */
class Services extends CActiveRecord
{
    public function tableName()
    {
        return 'kt_ref_services';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
}