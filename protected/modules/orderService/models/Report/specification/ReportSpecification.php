<?php

/**
 * Спецификация для генерации отчета
 */
class ReportSpecification extends AbstractReportSpecification
{
    public function setReportParams($params)
    {
        // тип отчета
        if (isset($params['reportType']) && array_key_exists($params['reportType'], $this->types)) {
            $event = EventRepository::getByEventName($this->types[$params['reportType']]['eventName']);

            if (is_null($event)) {
                throw new InvalidArgumentException("Event for reportType not found in kt_events", OrdersErrors::UNSUPPORTED_REPORT);
            }

            // определим reportConstructType
            if(is_array($this->types[$params['reportType']]['reportConstructTypes'])){
                // тип даты для формирования
                if (isset($params['reportConstructType']) && in_array($params['reportConstructType'], $this->types[$params['reportType']]['reportConstructTypes'])) {
                    $this->reportConstructType = $params['reportConstructType'];
                } else {
                    throw new InvalidArgumentException('reportConstructType not set', OrdersErrors::UNSUPPORTED_REPORT_CONSTRUCT_TYPE);
                }
            } else {
                $this->reportConstructType = $this->types[$params['reportType']]['reportConstructTypes'];
            }

            $this->event = $event;
            $this->reportName = $this->types[$params['reportType']]['reportName'];
        } else {
            throw new InvalidArgumentException("reportType unsupported or not set", OrdersErrors::UNSUPPORTED_REPORT);
        }

        // компания
        if (isset($params['companyId'])) {
            $company = CompanyRepository::getById($params['companyId']);

            if (is_null($company)) {
                throw new InvalidArgumentException("Компания с ID {$params['companyId']} не найдена", OrdersErrors::AGENT_NOT_FOUND);
            } else {
                $this->company = $company;
                $this->companyId = $company->getId();
            }
        }

        // выходной формат
        if (isset($params['outFormat'])) {
            $outFormat = strtolower($params['outFormat']);

            if (in_array($outFormat, $this->formats)) {
                $this->format = $outFormat;
            } else {
                throw new InvalidArgumentException("Формат не поддерживается", OrdersErrors::REPORT_OUT_FORMAT_NOT_SET);
            }
        } else {
            throw new InvalidArgumentException("Выходной формат не задан", OrdersErrors::REPORT_OUT_FORMAT_NOT_SET);
        }

        // email
        if (!empty($params['email'])) {
            $this->email = $params['email'];
        } else {
            throw new InvalidArgumentException("Email для отправки не задан", OrdersErrors::REPORT_EMAIL_NOT_SET);
        }

        // dateFrom
        if (isset($params['dateFrom'])) {
            $this->dateFrom = new DateTime($params['dateFrom']);
        } else {
            throw new InvalidArgumentException("Дата начала временного периода отчета не задана", OrdersErrors::REPORT_DATE_FROM_NOT_SET);
        }

        // dateTo
        if (isset($params['dateTo'])) {
            $this->dateTo = new DateTime($params['dateTo']);
        } else {
            throw new InvalidArgumentException("Дата завершения временного периода отчета не задана", OrdersErrors::REPORT_DATE_TO_NOT_SET);
        }
        // Если пермишины позволяют, то загрузить id компаний холдинга
        $this->holdingCompaniesIds = $this->getHoldingCompanies(StdLib::nvl($params['canUseHoldingCompany'],0));
    }


    public function getTaskData()
    {
        $userProfile = Yii::app()->user->getState('userProfile');

        return [
            'dateTo' => $this->dateTo->format('Y-m-d'),
            'dateFrom' => $this->dateFrom->format('Y-m-d'),
            'email' => $this->email,
            'format' => $this->format,
            'reportName' => $this->reportName,
            'companyId' => $this->companyId,
            'reportConstructType' => $this->reportConstructType,
            'userId' => $userProfile['userId'],
            'holdingCompaniesIds'=>$this->holdingCompaniesIds
        ];
    }
}