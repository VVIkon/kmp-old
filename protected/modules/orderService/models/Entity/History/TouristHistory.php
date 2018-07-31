<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/26/16
 * Time: 3:44 PM
 */
class TouristHistory extends History
{
    public function setObjectData(Tourist $Tourist)
    {
        $this->setObjectId($Tourist->getTouristIDbase());
        $this->setObjectType('tourist');
        $this->setObjectStatus(0);
    }
}