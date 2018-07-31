<?php

class AviaticketReport extends AbstractReport
{
    public function getEmailSubject()
    {
        return 'Отчет по авиабилетам';
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
            ]
        ];
        $criteria->addCondition('S.serviceType = 2');
        $criteria->addCondition('S.Status <> 0');

        $criteria->limit = 4000;

        $this->data = OrdersServicesRepository::getByCriteria($criteria);
    }

    /**
     * @param $params
     * @return mixed
     */
    protected function init($params)
    {
        $header = 'Отчет по авиабилетам';

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
            'Услуга',
            'Количество',
            'Сумма, руб.'
        ]);
    }

    /**
     * @return mixed
     */
    public function makeReport()
    {
        $totalCnt = 0;
        $totalSum = 0;

        $table = [];

        // группировки услуг по международности и авиакомпаниям
        foreach ($this->data as $service) {
            if ($service->isOffline()) {
                $isInternational = 'Оффлайн';
                $rowName = $service->getServiceName();
            } else {
                try {
                    $offer = $service->getOffer();
                } catch (Exception $e) {
                    continue;
                }

                // если в услуге есть авиабилеты
                if (!$offer->hasIssuedTickets()) {
                    continue;
                }

                $isInternational = ($offer->isInternationalFlight()) ? 'Международные перевозки' : 'Местные перевозки';
                $aviaCompany = $offer->getValidatingAviaCompany();

                if (is_null($aviaCompany)) {
                    continue;
                }

                $rowName = "Авиакомпания {$aviaCompany->getName()}";
            }

            $table[$isInternational][$rowName][] = $service;

            $totalCnt++;
            $price = $service->getMoney(CurrencyStorage::getById(643));
            $totalSum += $price->sum;
        }

        $this->SOReport->addTableHeader([
            'Авиабилет',
            $totalCnt,
            $totalSum
        ]);

        foreach ($table as $international => $aviaCompanyArr) {
            $soReportTableRowGroup = new SOReportTableRowGroup();

            $aviaCompanies = [];

            $internationalSum = 0;
            $internationalCnt = 0;

            foreach ($aviaCompanyArr as $aviaCompanyName => $services) {
                $aviaCompanySum = 0;
                $aviaCompanyCnt = 0;

                foreach ($services as $service) {
                    // посчитаем количество
                    $internationalCnt++;
                    $aviaCompanyCnt++;

                    // посчитаем сумму
                    $price = $service->getMoney(CurrencyStorage::getById(643));
                    $aviaCompanySum += $price->sum;
                    $internationalSum += $price->sum;
                }

                $aviaCompanies[$aviaCompanyName]['cnt'] = $aviaCompanyCnt;
                $aviaCompanies[$aviaCompanyName]['sum'] = $aviaCompanySum;
            }

            $soReportTableRowGroup->addGroupHeader([
                $international,
                $internationalCnt,
                $internationalSum
            ]);
            foreach ($aviaCompanies as $aviaCompanyName => $aviaCompanyData) {
                $soReportTableRowGroup->addRow([
                    $aviaCompanyName,
                    $aviaCompanyData['cnt'],
                    $aviaCompanyData['sum']
                ]);
            }

            $this->SOReport->addTableRowGroup($soReportTableRowGroup);
        }
    }
}