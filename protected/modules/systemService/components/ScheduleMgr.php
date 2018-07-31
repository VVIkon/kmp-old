<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 21.09.17
 * Time: 13:00
 */
class ScheduleMgr
{
    private $_errorCode;
    private $_module;


    public function __construct($module)
    {
        $this->_module = $module;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->_errorCode;
    }

    public function saveSchedule($params)
    {
        if ( StdLib::nvl($params['DeleteTaskId'],0) ){
            $result = ScheduledTaskRepository::deleteScheduledTask($params);
        }else {
            $result = ScheduledTaskRepository::newScheduledTask($params);
        }
        return $result;
    }

    public function getScheduleTask($companyId)
    {
        $result = [];
        $scheduleTasks = ScheduledTaskRepository::getTaskByCompany($companyId);
        foreach ($scheduleTasks as $scheduleTask) {
            $task['taskId'] = $scheduleTask->Id;
            $task['period'] = $scheduleTask->period;
            $task['periodDetail'] = $scheduleTask->periodDetail;
            $task['taskName'] = $scheduleTask->taskName;
            $task['taskParams'] = json_decode($scheduleTask->taskParams);
            $result[]=$task;
        }
        return $result;
    }

    private function runCreateReport($servName, $action, $params)
    {
        //return ['status'=> 0];
        $apiClient = new ApiClient($this->_module);
        $serviceResponse = $apiClient->makeRestRequest($servName, $action, $params);
        $ServArr = json_decode($serviceResponse, true);
        return $ServArr;
    }

    private function checkRunDate($scheduleTask)
    {
        $period = $scheduleTask->period;
        $periodDetail = $scheduleTask->periodDetail;
        if (!is_null($scheduleTask->lastRunDate)){
            $lastRunDate = new DateTime($scheduleTask->lastRunDate);
        }else{
            $lastRunDate = new DateTime($this->generateLastRunDate($period, $periodDetail));
        }

        $toDay = new DateTime();
        switch($period){
            case 1:
                // если в $lastRunDate стоит сегодня то false
                $result = $toDay->diff($lastRunDate)->format('%a') == 0 ? false : true;
                break;
            case 2:
                // если в $lastRunDate стоит сегодня то false
                $differ = $toDay->diff($lastRunDate)->format('%a') == 0 ? false : true;
                // и день недели совпадает с $periodDetail
                $result = ($toDay->format('N') === $periodDetail && $differ);
                break;
            case 3:
                // если в $lastRunDate стоит сегодня то false
                $differ = $toDay->diff($lastRunDate)->format('%a') == 0 ? false : true;
                // и день месяца совпадает с $periodDetail
                $jn = $toDay->format('j');
                $result = ( $jn === $periodDetail && $differ);
                break;
            case 4:
                // если в $lastRunDate стоит сегодня то false
                $differ = $toDay->diff($lastRunDate)->format('%a') == 0 ? false : true;
                // и день.номер месяца в квартале совпадает с $periodDetail <15.2>
                $dayPointManth[0] = (int) $toDay->format('d');
                $dayPointManth[1] = intval(($toDay->format('m')-1)/3)+1; // номер месяца в квартале
                $transformDate = implode('.', $dayPointManth);

                $result = ($transformDate === $periodDetail && $differ);
                break;
            case 5:
                // если в $lastRunDate стоит сегодня то false
                $differ = $toDay->diff($lastRunDate)->format('%a') == 0 ? false : true;
                // и "день.месяц" совпадает с $periodDetail (23.11)
                $jn = $toDay->format('j.n');
                $result = ($jn === $periodDetail && $differ);
                break;
        }
        return $result;
    }


    private function generateLastRunDate($period, $periodDetail)
    {
        $toDay = new DateTime();
        switch($period){
            case 1:
                $strDays = '-1 day';
                break;
            case 2:
                $strDays = "-7 day";
                break;
            case 3:
                $strDays = "-1 month";
                break;
            case 4:
                $strDays = "-3 month";
                break;
            case 5:
                $strDays = "-1 year";
                break;
        }
        $interval = DateInterval::createfromdatestring($strDays);
        $toDay->add($interval);

        return $toDay->format('Y-m-d');
    }
    //{
    //  "reportType":4,
    //  "reportConstructType":1,
    //  "companyId":1,
    //  "outFormat":"xlsx",
    //  "email":"test@gmail.com",
    //  "dateFrom":"2017-06-16",
    //  "dateTo":"2017-07-01",
    //  "companyId":null
    //}
    private function taskParamToArray($scheduleTask, $usertoken)
    {
        $params = json_decode(StdLib::nvl($scheduleTask->taskParams));
        if (is_null($params)){
            return null;
        }
        $toDay = new DateTime();
        $res = [];
        $res['reportType'] = StdLib::nvl($params->reportType);
        $res['reportConstructType'] = StdLib::nvl($params->reportConstructType);
		$res['companyId'] = StdLib::nvl($params->companyId);
		$res['outFormat'] = StdLib::nvl($params->outFormat);
		$res['email'] = StdLib::nvl($params->email);
		$res['dateFrom'] = is_null($scheduleTask->lastRunDate) ? $this->generateLastRunDate($scheduleTask->period, $scheduleTask->periodDetail) :$scheduleTask->lastRunDate;
        $res['dateTo'] = $toDay->format('Y-m-d');
        $res['usertoken'] =  $usertoken;
        return $res;
    }

    public function runScheduler($usertoken)
    {
        $toDay = date("Y-m-d");

        $scheduleTasks = ScheduledTaskRepository::getTasksByPeriod($toDay, $toDay, $toDay);
        foreach ($scheduleTasks as $scheduleTask) {
            $rightDate = $this->checkRunDate($scheduleTask);
            if ($rightDate) {
                $taskOperation = $scheduleTask->taskOperation;
                $taskService = $scheduleTask->taskService;
                $taskParams = $this->taskParamToArray($scheduleTask, $usertoken);
                if (is_null($taskParams)) {
                    LogHelper::logExt(__CLASS__, __METHOD__, 'Менеджер Создания отчетов', '', ['taskParams' => $taskParams, 'Result' => null], LogHelper::MESSAGE_TYPE_INFO, 'system.systemservice.requests');
                    continue;
                }
                $result = $this->runCreateReport($taskService, $taskOperation, $taskParams);
                if ($result['status'] == 1) {
                    LogHelper::logExt(__CLASS__, __METHOD__, 'Менеджер Создания отчетов', '', ['taskParams' => $taskParams, 'Result' => $result], LogHelper::MESSAGE_TYPE_INFO, 'system.systemservice.requests');
                } else {
                    ScheduledTaskRepository::setLastRun($scheduleTask->Id);
                }
            }
        }
        return ['finish'=>true];
    }

}