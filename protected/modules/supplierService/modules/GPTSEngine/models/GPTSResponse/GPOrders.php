<?php

/**
 * класс для работы с информацией по заявкам GP,
 * полученным командой Orders (get)
 */
class GPOrders
{
    /**
     * Соответствие статусов услуги GPTS - КТ для Hotel
     * @param
     * @returm
     */
    private static $statusGPTSSubService = [
        'PENDING_CONFIRMATION' => 1,    // W_BOOKED
        'CONFIRMED' => 2,               // BOOKED HOTEL
        'REJECTED' => 7,                // VOIDED
    ];

    /** @var mixed[] ответ команды GPTS orders[get] */
    private $cdata;

    /**
     * @param string $cdata - ответ команды в json
     */
    public function __construct($cdata)
    {
        $this->cdata = $cdata;
    }

    /**
     * Маппинг статуса GPTS -> KT для доп услуг
     * @param $gptsStatus
     * @return mixed
     */
    private function calculateStatusGPTSSubService($gptsStatus)
    {
        $status = strtoupper(StdLib::nvl($gptsStatus, ''));
        if (isset(self::$statusGPTSSubService[$status])) {
            return self::$statusGPTSSubService[$status];
        } else {
            return null;
        }
    }

    /**
     * Получить Service status по processId
     * @param $processId
     * @return bool|string
     */
    public function getServiceStatusByProcessId($processId)
    {
        foreach ($this->cdata[0]['services'] as $service) {
            if (!empty($service['processId']) && $service['processId'] == $processId) {
                return $service['status'];
            }
        }
        return false;
    }


    /**
     * Получение номер брони (PNR для авиа)
     * @return string
     */
    public function getRefNumByProcessId($processId)
    {
        foreach ($this->cdata[0]['services'] as $service) {

            if (!empty($service['processId']) && $service['processId'] == $processId) {
                return $service['refNum'];
            }
        }
        return false;
    }

    /**
     * Получить Passenger Name Record(PNR) указанной услуги
     * @param $pnr
     * @return bool|string
     */
    public function getServiceIdByPnr($pnr)
    {
        foreach ($this->cdata[0]['services'] as $service) {

            if (!empty($service['refNum']) && $service['refNum'] == $pnr) {
                return $service['serviceId'];
            }
        }
        return false;
    }

    /**
     * Получить ServiceId по processId
     * @param $processId
     * @return bool|string
     */
    public function getServiceIdByProcessId($processId)
    {
        foreach ($this->cdata[0]['services'] as $service) {
            if (!empty($service['processId']) && $service['processId'] == $processId) {
                return $service['serviceId'];
            }
        }
        return false;
    }

    /**
     * Получить LastTicketingDate по serviceId
     * @param $serviceId
     * @return bool|string
     */
    public function getLastTicketingDateByServiceId($serviceId)
    {
        foreach ($this->cdata[0]['services'] as $service) {
            if ($service['serviceId'] == $serviceId) {
                return $service['serviceDetails'][0]['lastTicketingDate'];
            }
        }
        return '';
    }

    /**
     * Получение идентификатора GDS (для услуги FLIGHT, предполагается, что в ответе одна заявка с одной услугой)
     * @return string идентификатор GDS
     */
    public function getSupplierCode()
    {
        return $this->cdata[0]['services'][0]['supplierCode'];
    }

    /**
     * Получить данные указанной услуги
     * @param $serviceId
     * @return mixed
     */
    public function getServiceInfo($serviceId)
    {
        foreach ($this->cdata[0]['services'] as $service) {
            if ($service['serviceId'] == $serviceId) {
                return $service;
            }
        }

    }

    /**
     * Получение идентифкаторов услуг заявки
     * @return array
     */
    public function getServicesIds()
    {
        $servicesIds = [];
        foreach ($this->cdata[0]['services'] as $service) {
            $servicesIds[] = $service['serviceId'];
        }

        return $servicesIds;
    }

    /**
     * Получить данные билетов указанной услуги
     * @param $serviceId
     * @return mixed
     */
    public function getServiceTickets($serviceId)
    {
        $tickets = [];
        foreach ($this->cdata[0]['services'] as $service) {
            if ($service['serviceId'] == $serviceId) {
                foreach ($service['serviceDetails'][0]['tickets'] as $ticket) {
                    $tickets[] = $ticket;
                }
            }
        }
        return $tickets;
    }

    /**
     * Получить данные штрафов указанной услуги
     * @param $processId
     * @return array
     */
    public function getServiceClientCancelPenalties($processId)
    {
        $penalties = [];

        foreach ($this->cdata[0]['services'] as $service) {
            if ($service['processId'] == $processId) {
                foreach ($service['salesTerms'] as $salesTerm) {
                    if ($salesTerm['type'] == 'CLIENT') {
                        foreach ($salesTerm['cancelPenalty'] as $cancelPenalty) {
                            $penalties[] = $this->toSSCancelPenalty($cancelPenalty);
                        }
                    }
                }
            }
        }
        return $penalties;
    }

    /**
     * Получить данные штрафов указанной услуги
     * @param $processId
     * @return array
     */
    public function getServiceSupplierCancelPenalties($processId)
    {
        $penalties = [];

        foreach ($this->cdata[0]['services'] as $service) {
            if ($service['processId'] == $processId) {
                foreach ($service['salesTerms'] as $salesTerm) {
                    if ($salesTerm['type'] == 'SUPPLIER') {
                        foreach ($salesTerm['cancelPenalty'] as $cancelPenalty) {
                            $penalties[] = $this->toSSCancelPenalty($cancelPenalty);
                        }
                    }
                }
            }
        }
        return $penalties;
    }

    /**
     * Получение туристов указанной услуги
     * @param $serviceId
     * @return bool
     */
    public function getServiceTourists($serviceId)
    {

        foreach ($this->cdata[0]['services'] as $service) {

            if ($service['serviceId'] == $serviceId) {
                return $service['travelers'];
            }
        }

        return false;
    }

    /**
     * Вся инфа о заказе
     * @return mixed[]|string
     */
    public function getInfo()
    {
        return $this->cdata;
    }

    /**
     * получение статуса доп услуги по id доп услуги
     * @param $subServiceId
     * @param $processId
     * @return integer
     */
    public function getAddServiceStatusBySubServiceId($processId, $subServiceId)
    {
        foreach ($this->cdata[0]['services'] as $service) {
            if (!empty($service['processId']) && $service['processId'] == $processId) {
                // проверим наличие и статусы доп услуг
                if (isset($service['serviceDetails'][0]['earlyCheckIn']) && $subServiceId == 2) {
                    return $this->calculateStatusGPTSSubService($service['serviceDetails'][0]['earlyCheckIn']['status']);
                }

                if (isset($service['serviceDetails'][0]['lateCheckOut']) && $subServiceId == 3) {
                    return $this->calculateStatusGPTSSubService($service['serviceDetails'][0]['lateCheckOut']['status']);
                }
            }
        }
    }

    /**
     * Преобразование штрафов ГПТС к виду ssCancelPenalty
     * @param $gpCancelPenalty
     * @return array
     */
    private function toSSCancelPenalty($gpCancelPenalty)
    {
        return [
            'dateFrom' => isset($gpCancelPenalty['startDateTime']) ? $gpCancelPenalty['startDateTime'] : '',     // Начало действия периода штрафа
            'dateTo' => isset($gpCancelPenalty['endDateTime']) ? $gpCancelPenalty['endDateTime'] : '',        // Конец действия периода штрафа
            'description' => '',      // Текст условий отмены
            'penalty' => [
                'currency' => isset($gpCancelPenalty['currency']) ? $gpCancelPenalty['currency'] : '',              // Валюта штрафа
                'amount' => isset($gpCancelPenalty['amount']) ? $gpCancelPenalty['amount'] : '',                   // Сумма штрафа
            ]
        ];
    }
}