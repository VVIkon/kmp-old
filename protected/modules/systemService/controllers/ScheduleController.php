<?php
//use Symfony\Component\Validator\Validation;

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 21.09.17
 * Time: 15:40
 */


class ScheduleController extends SecuredRestController
{

    /**
     * Команда создания или удаления расписания
     */
    public function actionSetSchedule()
    {
        $params = $this->_getRequestParams();

        $module = YII::app()->getModule('systemService');
        $paramValidator = $module->ScheduleRequestValidator($module);
        if(StdLib::nvl($params['DeleteTaskId'],0) > 0){
            $valid = $paramValidator->checkDeleteSchedule($params);
        }else{
            $valid = $paramValidator->checkSetScheduleParams($params);
        }

        if (!$valid) {
            $err = $paramValidator->getLastError();
            $this->_sendResponseWithErrorCode($err);
        }

        $scheduleMgr = $module->ScheduleMgr($module);

        $response = $scheduleMgr->saveSchedule($params);
        if ($response){
            $this->_sendResponseData(['success' => true]);
        }else {
            $this->_sendResponseWithErrorCode(SysSvcErrors::DB_ERROR);
        }
    }

    /**
     * Команда получения списка растисания по одной компании
     */
    public function actionGetSchedule()
    {
        $module = YII::app()->getModule('systemService');
        $paramValidator = $module->ScheduleRequestValidator($module);

        $params = $this->_getRequestParams();

        $valid = $paramValidator->checkGetScheduleParams($params);

        if (!$valid) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($paramValidator->getLastError()),
                $paramValidator->getLastError()
            );
        }

        $scheduleMgr = $module->ScheduleMgr($module);

        $response = $scheduleMgr->getScheduleTask($params['companyId']);

        $this->_sendResponseData($response);
    }

    public function actionScheduleManage()
    {
        $params = $this->_getRequestParams();

        $module = YII::app()->getModule('systemService');
        $scheduleMgr = $module->ScheduleMgr($module);

        $response = $scheduleMgr->runScheduler($params['usertoken']);
        if ($response){
            $this->_sendResponseData($response);
        }else {
            $this->_sendResponseWithErrorCode(SysSvcErrors::FATAL_ERROR);
        }
    }
}