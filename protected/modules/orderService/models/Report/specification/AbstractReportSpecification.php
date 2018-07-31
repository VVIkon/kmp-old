<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 03.10.17
 * Time: 11:55
 */
abstract class AbstractReportSpecification
{
    /**
     * Типы отчетов
     */
    protected $types = [
        0 => [
            'eventName' => 'GENERATEFILEOFFERS',
            'reportName' => 'dummy',
            'reportConstructTypes' => []
        ],
        1 => [
            'eventName' => 'GENERATEBOOKREPORT',
            'reportName' => 'book',
            'reportConstructTypes' => [1, 2]
        ],
//        2 => [
//            'eventName' => 'GENERATETPREPORT',
//            'reportName' => 'tp'
//        ],
        4 => [
            'eventName' => 'GENERATEMSRREPORT',
            'reportName' => 'msr',
            'reportConstructTypes' => [1, 2]
        ],

        6 => [
            'eventName' => 'GENERATEHOTELBOOKREPORT',
            'reportName' => 'hotelbook',
            'reportConstructTypes' => 2
        ],
        7 => [
            'eventName' => 'GENERATEAVIATICKETREPORT',
            'reportName' => 'aviaticket',
            'reportConstructTypes' => 2
        ],

//        10 => [
//            'eventName' => 'GENERATEBOOKREPORT',
//            'reportName' => 'book_manager'
//        ],
//        11 => [
//            'eventName' => 'GENERATETPREPORT',
//            'reportName' => 'tp_manager'
//        ]
    ];

    /**
     * Форматы вывода
     */
    const FORMAT_PDF = 'pdf';
    const FORMAT_XLSX = 'xlsx';

    protected $formats = [
        self::FORMAT_PDF,
        self::FORMAT_XLSX
    ];

    protected $reportConstructType;
    protected $company;
    protected $companyId;
    protected $format;
    protected $email;
    /**
     * @var DateTime
     */
    protected $dateFrom;
    /**
     * @var DateTime
     */
    protected $dateTo;
    protected $event;
    protected $reportName;

    protected $holdingCompaniesIds;

    abstract public function setReportParams($params);
    abstract public function getTaskData();

    public function __construct($params)
    {
        $this->setReportParams($params);
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @return Events
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Если пермишины позволяют, то загрузить id компаний холдинга
     * @param $canUseHoldingCompany
     * @return string
     */
    public function getHoldingCompanies($canUseHoldingCompany)
    {
        $companyIds = [];
        if ($canUseHoldingCompany){
            $companies = CompanyRepository::findAllHoldingCompanies($this->companyId);
            foreach ($companies as $company){
                $companyIds[] = $company->getId();
            }
        }else{
            $companyIds[] = $this->companyId;
        }
        return implode(',', $companyIds);
    }


}