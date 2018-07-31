<?php

/**
 * отчет по проживанию
 */
class HotelbookReport extends AbstractReport
{
    public function getEmailSubject()
    {
        return 'Отчет по проживанию';
    }

    protected function extractData($reportConstructType, $dateFrom, $dateTo, $companyIds)
    {
        // сформируем критерий, по которым искать заявки
        $criteria = new CDbCriteria();
        $criteria->alias = 'S';

        $orderCondition = '';

        if (isset($companyIds) ) {
            $orderCondition = "(O.AgentID in ($companyIds) )";
        }

        $criteria->addBetweenCondition('DATE(S.DateStart)', $dateFrom, $dateTo);

        $criteria->with = [
            'OrderModel' => [
                'alias' => 'O',
                'condition' => $orderCondition
            ],
            'city',
            'country',
            'RefService'
        ];
        $criteria->addCondition('S.serviceType = 1');
        $criteria->addCondition('S.Status >= 2');

        $criteria->limit = 4000;

        $this->data = OrdersServicesRepository::getByCriteria($criteria);

        $memoryPeak = (memory_get_peak_usage() / 1024 / 1024) . ' MB';

        LogHelper::logExt(
            __CLASS__,
            __METHOD__,
            'Формирование отчета',
            '',
            [
                'memory_get_peak_usage' => $memoryPeak
            ],
            'info',
            'system.orderservice.info'
        );

//        var_dump(count($this->data));
//        var_dump(round(memory_get_peak_usage() / 1024 / 1024) . ' MB');
//        exit;
    }

    protected function init($params)
    {
        $header = 'Отчет по проживанию';

        // если есть компания, то зададим ее
        if ($params['companyId']) {
            $company = CompanyRepository::getById($params['companyId']);
            $header .= ' для компании ' . $company->getName();
        }

        // заголовок отчета
        $this->SOReport->setMainHeader($header);

        ReportCommonHeader::addHeader($this->SOReport, $params);

        // заголовок таблицы
        $this->SOReport->addTableHeader([
            'Страна / Город / Отель',
            'Количество ночей',
            'Сумма, руб.'
        ]);
    }

    public function makeReport()
    {
        $table = [];

        // группировки услуг по стране, городу и отелю
        foreach ($this->data as $service) {
            if ($service->isOffline()) {
                $country = $service->getCountry();
                if ($country) {
                    $countryName = $country->getName();
                } else {
                    $countryName = self::NA;
                }

                $city = $service->getCity();
                if ($city) {
                    $cityName = $city->getName();
                } else {
                    $cityName = self::NA;
                }

                $hotelName = $service->getServiceName();
            } else {
                try {
                    $offer = $service->getOffer();
                } catch (Exception $e) {
                    continue;
                }
                $hotelInfo = $offer->getHotelInfo();

                $countryName = $hotelInfo->getCity()->getCountry()->getName();
                $cityName = $hotelInfo->getCity()->getName();
                $hotelName = $hotelInfo->getHotelName();
            }

            $table[$countryName][$cityName][$hotelName][] = $service;
        }

        foreach ($table as $countryName => $citiesArr) {
            $countryNights = 0;
            $countrySum = 0;
            $countryGroup = new SOReportTableRowGroup();

            foreach ($citiesArr as $cityName => $hotelArr) {
                $cityNights = 0;
                $citySum = 0;
                $cityGroup = new SOReportTableRowGroup();

                foreach ($hotelArr as $hotelName => $services) {
                    $hotelNights = 0;
                    $hotelSum = 0;

                    foreach ($services as $service) {
                        if ($service->isOffline()) {
                            $nights = $service->getTimeIntervalDays();
                        } else {
                            $nights = $service->getOffer()->getNights();
                        }

                        $hotelNights += $nights;
                        $cityNights += $nights;
                        $countryNights += $nights;

                        $price = $service->getMoney(CurrencyStorage::getById(643));

                        $hotelSum += $price->sum;
                        $citySum += $price->sum;
                        $countrySum += $price->sum;
                    }

                    $cityGroup->addRow([
                        $hotelName,
                        $hotelNights,
                        round($hotelSum, 2)
                    ]);
                }

                $cityGroup->addGroupHeader([
                    $cityName,
                    $cityNights,
                    round($citySum, 2)
                ]);
                $countryGroup->addRow($cityGroup);
            }

            $countryGroup->addGroupHeader([
                $countryName,
                $countryNights,
                round($countrySum, 2)
            ]);

            $this->SOReport->addTableRowGroup($countryGroup);
        }
    }
}