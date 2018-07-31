<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/4/16
 * Time: 3:23 PM
 */
class FlightBookData extends AbstractServiceBookData
{
    public function getGPTSServiceRef()
    {
        $serviceRefs = [];

        if (count($this->bookData['segments']['pnrData'])) {
            foreach ($this->bookData['segments']['pnrData'] as $pnrData) {
                $serviceRefs[] = $pnrData['engine']['data']['GPTS_service_ref'];
            }
        }

        return $serviceRefs[0];
    }

    public function getGPTSOrderRef()
    {
        $serviceRefs = StdLib::nvl($this->bookData['engineData']['data']['GPTS_order_ref'],0);
        return $serviceRefs;
    }

    public function getGPTSProcessID()
    {
        $serviceRefs = StdLib::nvl($this->bookData['engineData']['data']['GPTS_service_ref'],0);
        return $serviceRefs;
    }

}