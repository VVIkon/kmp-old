<?php

/**
 * Отчет по бронированиям
 */
class BookReport extends AbstractReport
{
    /**
     * Доп поля
     * @var array
     */
    private $addFieldTypes = [];

    /**
     * Количество колонок в заголовке
     * @var int
     */
    private $headerClmnsNum;

    private $addFieldHeaders = [];

    public function getEmailSubject()
    {
        return 'Реестровый отчет';
    }

    protected function extractData($reportConstructType, $dateFrom, $dateTo, $companyId)
    {
        // сформируем критерий, по которым искать заявки
        $criteria = new CDbCriteria();
        $criteria->alias = 'O';

        $serviceCondition = 'S.Status >= 2';

        if ($companyId) {
            $criteria->addCondition("O.AgentID = $companyId");
        }

        if ($reportConstructType == 1) {
            $criteria->addBetweenCondition('DATE(O.OrderDate)', $dateFrom, $dateTo);
        } else {
            $serviceCondition .= " AND (DATE(S.DateStart) BETWEEN '$dateFrom' AND '$dateTo')";
        }

        $criteria->with = [
            'OrdersServices' => [
                'alias' => 'S',
                'condition' => $serviceCondition,
                'with' => [
                    'RefService' => [
                        'alias' => 'ST'
                    ]
                ]
            ],
            'Company',
            'creator',
            'companyManager',
            'managerKMP'
        ];

        $criteria->limit = 4000;

        $this->data = OrderModelRepository::getByCriteria($criteria);
    }

    protected function init($params)
    {
        $header = 'Реестровый отчет';

        $tableHeader = [
            'Название компании',
            'Ответственный менеджер заявки от клиента',
            'Ответственный менеджер заявки от КМП',
            'Создатель заявки',
            'ФИО',
            'Вид обслуживания',
            'Тип услуги',
            'Количество человек',
            'Направление перевозки',
            'Класс обслуживания',
            'Код бронирования',
            'Тип маршрута',
            'Статус услуги',
            'Маршрут',
            'Дата вылета / Дата заезда',
            'Дата прилета / Дата выезда',
            'Время в пути, (мин)',
            'Перевозчик',
            'Город и страна отправления',
            'Город и страна прибытия/проживания',
            '№ билета',
            'Название отеля',
            'Категория отеля',
            'Тип размещения',
            'Количество ночей',
            'Стоимость в валюте поставщика',
            'Стоимость, RUB',
        ];

        // если есть компания, то зададим ее
        if ($params['companyId']) {
            $company = CompanyRepository::getById($params['companyId']);
            $header .= ' для компании ' . $company->getName();

            $serviceAddFields = AdditionalFieldTypeRepository::getServiceFieldsForCompany($company);
            foreach ($serviceAddFields as $serviceAddField) {
                if ($serviceAddField->isMinimalPrice()) {
                    continue;
                }
                $this->addFieldHeaders[] = [$serviceAddField->getName(), 'tag' => 'additionalFields'];
                $this->addFieldTypes[] = $serviceAddField;
            }

            $touristAddFields = AdditionalFieldTypeRepository::getTouristFieldsForCompany($company);
            foreach ($touristAddFields as $touristAddField) {
                $this->addFieldHeaders[] = [$touristAddField->getName(), 'tag' => 'additionalFields'];
                $this->addFieldTypes[] = $touristAddField;
            }
        }

        // заголовок отчета
        $this->SOReport->setMainHeader($header);

        ReportCommonHeader::addHeader($this->SOReport, $params);

        array_splice($tableHeader, 5, 0, $this->addFieldHeaders);

        // формирование заголовка таблицы
        $this->SOReport->addTableHeader($tableHeader);

        $this->headerClmnsNum = count($tableHeader);
        foreach ($this->addFieldHeaders as &$addFieldHeader) {
            $addFieldHeader[0] = '';
        }
    }

    public function makeReport()
    {
        $totalSumRUB = 0;

        // перебор заявок
        foreach ($this->data as $order) {
            $servicesSumRUB = 0;

            $orderGroup = new SOReportTableRowGroup();

            // заголовок группы
            $groupHeaderRow = $this->createEmptyRow($this->headerClmnsNum, ["Заявка №{$order->getOrderNumber()}"]);
            array_splice($groupHeaderRow, 5, count($this->addFieldHeaders), $this->addFieldHeaders);
            $orderGroup->addGroupHeader($groupHeaderRow);

            $writeGroup = false;

            // перебор услуг
            $services = $order->getOrderServices();
            foreach ($services as $service) {
                $serviceGroup = new SOReportTableRowGroup();
                $groupHeaderRow = $this->createEmptyRow($this->headerClmnsNum, ["Услуга №{$service->getServiceID()}"]);
                array_splice($groupHeaderRow, 5, count($this->addFieldHeaders), $this->addFieldHeaders);
                $serviceGroup->addGroupHeader($groupHeaderRow);

                // если оффлайн - разговор короткий
                if ($service->isOffline()) {
                    $offlineGroup = new SOReportTableRowGroup();
                    $groupHeaderRow = $this->createEmptyRow($this->headerClmnsNum, ['Офлайн']);
                    array_splice($groupHeaderRow, 5, count($this->addFieldHeaders), $this->addFieldHeaders);
                    $offlineGroup->addGroupHeader($groupHeaderRow);

                    $row = new BookReportRow($this->addFieldTypes);
                    $row->setOrderData($service->getOrderModel());
                    $row->setServiceData($service);

                    $offlineGroup->addRow($row->toArray());
                    $serviceGroup->addRow($offlineGroup);

                    $money = $service->getMoney(CurrencyStorage::getById(643));
                    $servicesSumRUB += $money->sum;
                    $writeGroup = true;
                } else {
                    try {
                        $offer = $service->getOffer();
                    } catch (Exception $e) {
                        continue;
                    }

                    if ($offer->canBeBooked()) {
                        continue;
                    }

                    switch ($service->getServiceType()) {
                        case 1: // вывод строки для отелей
                            $reservationGroup = new SOReportTableRowGroup();
                            $groupHeaderRow = $this->createEmptyRow($this->headerClmnsNum, ["Бронь №{$offer->getReservationNumber()}"]);
                            array_splice($groupHeaderRow, 5, count($this->addFieldHeaders), $this->addFieldHeaders);
                            $reservationGroup->addGroupHeader($groupHeaderRow);

                            $row = new BookReportRow($this->addFieldTypes);
                            $row->setOrderData($service->getOrderModel());
                            $row->setServiceData($service);
                            $row->setHotelOffer($offer);
                            $row->setTouristFIOs($service->getServiceTourists());

                            $reservationGroup->addRow($row->toArray());
                            $serviceGroup->addRow($reservationGroup);
                            $writeGroup = true;

                            $money = $service->getMoney(CurrencyStorage::getById(643));
                            $servicesSumRUB += $money->sum;
                            break;
                        case 2:
                            // найдем билеты
                            $pnrs = $offer->getPNRs();

                            foreach ($pnrs as $pnr) {
                                $tickets = $pnr->getAviaTickets();

                                if (count($tickets)) {
                                    foreach ($tickets as $ticket) {
                                        $pnrGroup = new SOReportTableRowGroup();
                                        $groupHeaderRow = $this->createEmptyRow($this->headerClmnsNum, ["PNR: {$pnr->getPNR()}"]);
                                        array_splice($groupHeaderRow, 5, count($this->addFieldHeaders), $this->addFieldHeaders);
                                        $pnrGroup->addGroupHeader($groupHeaderRow);

                                        $row = new BookReportRow($this->addFieldTypes);
                                        $row->setOrderData($service->getOrderModel());
                                        $row->setServiceData($service);
                                        $row->setAviaOffer($offer);
                                        $row->setTicket($ticket);

                                        $pnrGroup->addRow($row->toArray());
                                        $serviceGroup->addRow($pnrGroup);
                                        $writeGroup = true;

                                        $money = $service->getMoney(CurrencyStorage::getById(643));
                                        $servicesSumRUB += $money->sum;
                                    }
                                } else {
                                    $pnrGroup = new SOReportTableRowGroup();
                                    $groupHeaderRow = $this->createEmptyRow($this->headerClmnsNum, ["PNR: {$pnr->getPNR()}"]);
                                    array_splice($groupHeaderRow, 5, count($this->addFieldHeaders), $this->addFieldHeaders);
                                    $pnrGroup->addGroupHeader($groupHeaderRow);

                                    $row = new BookReportRow($this->addFieldTypes);
                                    $row->setOrderData($service->getOrderModel());
                                    $row->setServiceData($service);
                                    $row->setAviaOffer($offer);
                                    $row->setTouristFIOs($service->getServiceTourists());

                                    $pnrGroup->addRow($row->toArray());
                                    $serviceGroup->addRow($pnrGroup);
                                    $writeGroup = true;
                                    $money = $service->getMoney(CurrencyStorage::getById(643));
                                    $servicesSumRUB += $money->sum;
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }

                $orderGroup->addRow($serviceGroup);
            } // END OF SERVICES

            if ($writeGroup) {
                $servicesSumRUB = round($servicesSumRUB, 2);

                $totalSumRUB += $servicesSumRUB;

                $orderGroupFooter = $this->createEmptyRow($this->headerClmnsNum, ["Итого"], ["$servicesSumRUB RUB"]);
                array_splice($orderGroupFooter, 5, count($this->addFieldHeaders), $this->addFieldHeaders);
                $orderGroup->addGroupFooter($orderGroupFooter);

                $this->SOReport->addTableRowGroup($orderGroup);
            }
        } // END OF ORDERS

        $totalSumRUB = round($totalSumRUB, 2);

        $tableFooter = $this->createEmptyRow($this->headerClmnsNum, ['Итого'], ["$totalSumRUB RUB"]);
        array_splice($tableFooter, 5, count($this->addFieldHeaders), $this->addFieldHeaders);
        $this->SOReport->addTableFooter($tableFooter);
    }
}