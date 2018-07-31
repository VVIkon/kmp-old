<?php

class ReportCommonHeader
{
    public static function addHeader(SOReport $SOReport, $params)
    {
        // подзаголовок - таблица
        $periodStart = new DateTime($params['dateFrom']);
        $periodEnd = new DateTime($params['dateTo']);
        $account = AccountRepository::getAccountById($params['userId']);
        $creatorName = (string)$account;
        $nowTime = new DateTime();
        $clientName = '';

        if ($params['companyId']) {
            $company = CompanyRepository::getById($params['companyId']);
            $clientName = $company->getName();
        }

        $SOReport->addHeaderTextRow(['Наименование клиента: ', $clientName])
            ->addHeaderTextRow(['Начало периода: ', $periodStart->format('d/m/Y')])
            ->addHeaderTextRow(['Окончание периода: ', $periodEnd->format('d/m/Y')])
            ->addHeaderTextRow(['Автор отчета: ', $creatorName])
            ->addHeaderTextRow(['Дата и время выгрузки отчета: ', $nowTime->format('d/m/Y H:i')])
            ->addHeaderTextRow([""]);
    }
}