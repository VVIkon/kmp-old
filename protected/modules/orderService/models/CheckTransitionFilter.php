<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/7/16
 * Time: 1:23 PM
 */
class CheckTransitionFilter
{
    protected $filters;
    protected $userType;
    protected $companyWorkflowScheme;
    protected $serviceType;

    public function __construct()
    {
        // настройки фильтров
        $filters = Yii::app()->getModule('orderService')->getConfig('workFlowFilters');
        if (empty($filters)) {
            throw new Exception('Не настроены фильтры workflow в orderService/config/envconfig.php');
        }
        $this->filters = $filters;

        // пользователь
        $userProfile = Yii::app()->user->getState('userProfile');

        $Company = CompanyRepository::getById($userProfile['companyID']);
        if (is_null($Company)) {
            throw new Exception('Не найдена компания');
        }
        $this->companyWorkflowScheme = $Company->getWorkFlowScheme();
        $this->userType = $userProfile['userType'];
    }

    public function isAllowedEvent(Events $event)
    {
        $allowedActions = $this->getFilterOWMTransitions();
        return in_array($event->getEvent(), $allowedActions);
    }

    /**
     * @param mixed $serviceType
     */
    public function setServiceType($serviceType)
    {
        $this->serviceType = $serviceType;
    }

    /**
     * @return array
     */
    public function getFilterOWMTransitions()
    {
        foreach ($this->filters as $filter) {
            if ($filter['workFlowScheme'] == $this->companyWorkflowScheme && in_array($this->userType, $filter['userTypes'])) {
                if (is_null($this->serviceType) || in_array($this->serviceType, $filter['serviceTypes'])) {
                    return $filter['OWMActions'];
                }
            }
        }

        return [];
    }
}