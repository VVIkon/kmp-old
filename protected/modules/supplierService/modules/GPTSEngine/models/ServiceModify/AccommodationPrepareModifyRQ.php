<?php

/**
 * Модель для модификации брони отеля
 */
class AccommodationPrepareModifyRQ
{
    protected $ModifyTouristRQs = [];
    protected $processId;
    protected $startDate;
    protected $endDate;

    /**
     *
     * @param array $params
     * @return bool
     */
    public function init(array $params)
    {
        if (isset($params['engineData']['data']['GPTS_service_ref'])) {
            $this->processId = preg_replace('/\/[^\/]*\//', '', $params['engineData']['data']['GPTS_service_ref']);
        } else {
            return false;
        }

        if (isset($params['orderService'])) {
            $this->startDate = $params['orderService']['dateStart'];
            $this->endDate = $params['orderService']['dateFinish'];
        } else {
            return false;
        }

        foreach ($params['tourists'] as $tourist) {
            $ModifyTouristRQ = new ModifyTouristRQ($tourist);

            if ($ModifyTouristRQ->init($tourist)) {
                $this->ModifyTouristRQs[] = $ModifyTouristRQ;
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     *
     * @return array
     */
    public function toArray()
    {
        $tourists = [];

        foreach ($this->ModifyTouristRQs as $ModifyTouristRQ) {
            $tourists[] = $ModifyTouristRQ->toArray();
        }

        return [
            'processId' => $this->processId,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'tourists' => $tourists
        ];
    }
}