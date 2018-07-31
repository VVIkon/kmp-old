<?php

/**
 * Шаблон класса движка поставщика
 */
abstract class SupplierEngine
{
    abstract public function getOffer($params);
    abstract public function serviceCancel($params);
    abstract public function getCancelRules($params);
    abstract public function serviceBooking($params);
    abstract public function getServiceNameByType($type);
    abstract public function serviceModify($params);
    abstract public function getServiceStatus($params);
    abstract public function getSupplierGetOrder($params);
    abstract public function setServiceData($params);
}