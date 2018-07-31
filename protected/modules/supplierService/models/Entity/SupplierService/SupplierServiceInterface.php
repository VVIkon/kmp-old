<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/3/16
 * Time: 3:36 PM
 */
interface SupplierServiceInterface
{
    public function getGateId();
    public function initFromTicket($ticket);
}