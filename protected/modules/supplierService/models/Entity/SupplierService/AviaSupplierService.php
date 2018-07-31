<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/3/16
 * Time: 3:42 PM
 */
class AviaSupplierService implements SupplierServiceInterface
{
    protected $data;

    /**
     * @return mixed
     */
    public function getGateId()
    {
        return $this->data['ticket']['pnrData']['engine']['type'];
    }

    /**
     * @param $ticket
     */
    public function initFromTicket($ticket)
    {
        $this->data = $ticket;
    }


}