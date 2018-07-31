<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/3/16
 * Time: 3:41 PM
 */
class AccomodationSupplierService implements SupplierServiceInterface
{
    protected $data;

    /**
     * @return mixed
     */
    public function getGateId()
    {
        return $this->data['gateId'];
    }

    /**
     * @param $ticket
     */
    public function initFromTicket($ticket)
    {
        $this->data = $ticket;
    }
}