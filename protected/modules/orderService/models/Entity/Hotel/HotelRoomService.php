<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/18/16
 * Time: 2:37 PM
 */
class HotelRoomService extends AbstractRoomService
{
    public function tableName()
    {
        return 'kt_service_ho_roomService';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
}