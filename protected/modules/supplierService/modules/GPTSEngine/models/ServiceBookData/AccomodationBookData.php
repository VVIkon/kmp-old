<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/4/16
 * Time: 3:23 PM
 */
class AccomodationBookData extends AbstractServiceBookData
{
    public function getGPTSOrderId()
    {
        $orderIds = [];

        if (count($this->bookData['hotelReservation']['engineData'])) {
            foreach ($this->bookData['hotelReservation']['engineData'] as $engineData) {
                $orderIds[] = $engineData['data']['GPTS_order_ref'];
            }
        }

        return $orderIds[0];
    }

    public function getGPTSServiceRef()
    {
        $serviceRefs = [];

        if (count($this->bookData['hotelReservation']['engineData'])) {
            foreach ($this->bookData['hotelReservation']['engineData'] as $engineData) {
                $serviceRefs[] = $engineData['data']['GPTS_service_ref'];
            }
        }

        return $serviceRefs[0];
    }

    /**
     * @return array
     */
    public function getClientCancelPenalties()
    {
        $cancelPenalties = [];

        if (count($this->bookData['cancelPenalties'])) {
            foreach ($this->bookData['cancelPenalties']['client'] as $cancelPenalty) {
                $cancelPenalties[] = $cancelPenalty;
            }
        }

        return $cancelPenalties;
    }

    public function getGPTSOrderRef()
    {
        $serviceRefs = StdLib::nvl($this->bookData['engineData']['data']['GPTS_order_ref'], 0);
        return $serviceRefs;
    }

    public function getGPTSProcessID()
    {
        $serviceRefs = StdLib::nvl($this->bookData['engineData']['data']['GPTS_service_ref'], 0);
        return $serviceRefs;
    }

}