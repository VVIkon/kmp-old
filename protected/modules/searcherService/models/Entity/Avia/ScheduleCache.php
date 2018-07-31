<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 19.06.17
 * Time: 12:37
 *
 * @property $supplierCode
 * @property $airlineCode
 * @property $route
 * @property $actualDate
 * @property $scheduleData
 */
class ScheduleCache extends CActiveRecord
{
    public function tableName()
    {
        return 'kt_schedule';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function getScheduleData()
    {
        return $this->scheduleData;
    }




}