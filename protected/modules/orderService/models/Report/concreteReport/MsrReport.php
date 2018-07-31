<?php

/**
 * Формирование данных для отчета MSR
 */
class MsrReport extends AbstractReport
{
    public function getEmailSubject()
    {
        return 'Отчет по упущенной выгоде';
    }

    protected function extractData($reportConstructType, $dateFrom, $dateTo, $companyIds)
    {
        // сформируем критерий, по которым искать заявки
        $criteria = new CDbCriteria();
        $criteria->alias = 'S';

        $orderCondition = '1';

        if (isset($companyIds) ) {
            $orderCondition = "(O.AgentID in ($companyIds) )";
        }

        if ($reportConstructType == 1) {
            $orderCondition .= " AND (DATE(O.OrderDate) BETWEEN '$dateFrom' AND '$dateTo')";
        } else {
            $criteria->addBetweenCondition('DATE(S.DateStart)', $dateFrom, $dateTo);
        }

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
     * MSRReport constructor.
     * @param $params
     */
    public function init($params)
    {
        $header = 'Отчет по упущенной выгоде';

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
            ['Общие данные', 'margin' => 9],
            ['Данные выбранного тарифа', 'margin' => 8],
            ['Данные минимального тарифа', 'margin' => 7]
        ])->addTableHeader([
            // 'Общие данные',
            'Тип сервиса',
            '№ заявки',
            'Дата создания заявки',
            'Пользователь (создатель)',
            'Даты поездки  (с - по)',
            'Направление',
            'Travel policy (нарушено/не нарушено)',
            'Код нарушения TP',
            'Сумма упущенной выгоды по услуге',
            // 'Данные выбранного тарифа',
            'Стоимость услуги',
            'Тип перелета (RT/OW/MC)',
            'Маркетинговая А/К',
            'Класс перелета',
            'Багаж включен в стоимость (да/нет)',
            'Время вылета',
            'Время в пути, (мин)',
            'Кол-во пересадок',
            // 'Данные минимального тарифа'
            'Стоимость услуги',
            'Маркетинговая А/К',
            'Класс перелета',
            'Багаж включен в стоимость (да/нет)',
            'Время вылета',
            'Время в пути, (мин)',
            'Кол-во пересадок',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function makeReport()
    {
        $dateFormat = 'd/m/Y';

        $soReportTableRowGroup = new SOReportTableRowGroup();
        // переберем заявки
        foreach ($this->data as $service) {
            // если услуга не авиа или не имеет минимальной цены - пропустим
            if (!$service->getMinimalPrice()) {
                continue;
            }
            try {
                $offer = $service->getOffer();
            } catch (Exception $e) {
                continue;
            }
            if (!$offer->getReservationNumber()) {
                continue;
            }

            // достанем услуги
            $order = $service->getOrderModel();

            // даты поездки
            $dateStart = new DateTime($service->getDateStart());
            $dateEnd = new DateTime($service->getDateEnd());

            // багаж
            $baggage = (!empty($offer->getFirstTrip()->getFirstSegment()->getBaggageData())) ? 'Да' : 'Нет';

            // нарушения ТП
            $tpViolations = ($service->hasTPViolations()) ? 'Да' : 'Нет';
            $tpFailCodes = $service->getTPViolations();

            // время вылета
            $departureTime = new DateTime($offer->getFirstTrip()->getFirstSegment()->getDepartureDate());

            // минимальная цена авиа
            $minimalPrice = $service->getMinimalPrice();

            // сумма упущенной выгоды по услуге
            $lostPrice = new Money($service->getKmpPrice(), $service->getSaleCurrency());
            $lostPrice->substract($minimalPrice->getPrice());
            // формирование отчета
            $soReportTableRowGroup->addRow([
                // 'Общие данные',
                $service->getRefService()->getName(),   // тип сервиса
                $order->getOrderNumber(),               // № заявки
                $order->getOrderDate()->format($dateFormat), // Дата создания заявки
                $order->getCreator()->getFI(),          // пользователь (создатель)
                $dateStart->format($dateFormat) . ' - ' . $dateEnd->format($dateFormat), // даты поездки  (с - по)
                $offer->getFullDescription(),                      // направление
                $tpViolations,                          // Travel policy (нарушено/не нарушено)
                implode(', ', $tpFailCodes),            // Код нарушения TP
                (string)$lostPrice,      // сумма упущенной выгоды по услуге
                // 'Данные выбранного тарифа',
                $service->getKmpPrice() . ' ' . $service->getSaleCurrency()->getCode(),   // стоимость услуги
                $offer->getFlightType(),  // тип перелета (RT/OW/MC)
                $offer->getMarketingAirline(),   // маркетинговая А/К
                $offer->getClassType(),   // класс перелета
                $baggage,                                     // багаж включен в стоимость (да/нет)
                $departureTime->format('d/m/Y H:i'),  // время вылета
                $offer->getTimeInRoad(),  // время в пути (по первому трипу RT/MC, или всему перелету OW)
                $offer->countTransfers(),  // кол-во пересадок
                // 'Данные минимального тарифа'
                (string)$minimalPrice->getPrice(),      // Стоимость услуги
                self::NA,                                     // Маркетинговая А/К
                $minimalPrice->getClass(),              // Класс перелета
                self::NA,                                      // Багаж включен в стоимость (да/нет)
                $minimalPrice->getDepartureTime()->format('d/m/Y H:i'),   // Время вылета
                $minimalPrice->getDuration(),                                        // Время в пути, мин
                $minimalPrice->getChanges()                // Кол-во пересадок
            ]);
        }
        $this->SOReport->addTableRowGroup($soReportTableRowGroup);
    }
}