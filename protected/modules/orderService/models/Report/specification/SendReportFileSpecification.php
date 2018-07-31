<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 03.10.17
 * Time: 12:05
 */
class SendReportFileSpecification extends AbstractReportSpecification
{

    protected $reportData;
    protected $subject;


    public function setReportParams($params)
    {
        // тип отчета
        if (isset($params['eventId'])) {
            $event = EventRepository::getByEventId($params['eventId']);

            if (is_null($event)) {
                throw new InvalidArgumentException("Event for reportType not found in kt_events", OrdersErrors::UNSUPPORTED_REPORT);
            }
            $this->event = $event;
            $this->reportName = $this->types[0]['reportName'];
            switch($params['eventId']){
                case 208:
                    $this->subject = 'Отчет списка сравнения предложений авиабилетов';
                    break;
                case 209:
                    $this->subject = 'Отчет истории заявки';
                    break;
                case 211:
                    $this->subject = 'Отчет списка сравнения предложений проживания';
                    break;
                default:
                    $this->subject = 'Информация из системы kmp.travel';
            }
        } else {
            throw new InvalidArgumentException("reportType unsupported or not set", OrdersErrors::UNSUPPORTED_REPORT);
        }

        $this->reportConstructType = 0;

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
        $this->format = AbstractReportSpecification::FORMAT_PDF;

        // email
        if (!empty($params['email'])) {
            $this->email = $params['email'];
        } else {
            throw new InvalidArgumentException("Email для отправки не задан", OrdersErrors::REPORT_EMAIL_NOT_SET);
        }

        $this->dateFrom = new DateTime();
        $this->dateTo = new DateTime();

        if (!empty($params['reportData'])) {
            $this->reportData = $params['reportData'];
        } else {
            throw new InvalidArgumentException("reportData для отправки не задан", OrdersErrors::REPORT_DATE_NOT_SET);
        }



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
            'reportData' => $this->reportData,
            'subject' => $this->subject
        ];
    }

}