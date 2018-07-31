<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11.09.17
 * Time: 16:12
 */
class ReportController extends SecuredRestController
{
    /**
     * @param CAction $action
     * @return bool
     */
    protected function beforeAction($action)
    {
        parent::beforeAction($action);
        return true;
    }

    public function actionCreateReport()
    {
        $profile = Yii::app()->user->getState('userProfile');
        $canUseHoldingCompany = 0;
        if ($profile['userType'] == 1) {
            $this->_sendErrorResponseIfNoPermissions(40);
        } elseif ($profile['userType'] == 3) {
            $this->_sendErrorResponseIfNoPermissions(41);
            $canUseHoldingCompany = (UserAccess::hasPermissions(42) == true) ? 1 : 0;
        } else {
            $this->_sendErrorResponseIfNoPermissions(43);
        }

        $params = $this->_getRequestParams();
        $params['canUseHoldingCompany'] = $canUseHoldingCompany;
        try {
            $reportTask = new ReportTask();
            $reportTask->runWith(new ReportSpecification($params));

            $this->_sendResponseData([
                'reported' => true
            ]);
        } catch (InvalidArgumentException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Постановка задачи формировани отчета', $e->getMessage(),
                $params,
                LogHelper::MESSAGE_TYPE_ERROR,
                'system.orderservice.error'
            );

            $this->_sendResponseWithErrorCode($e->getCode());
        }
    }

    public function actionSendReportFile()
    {
        $this->_sendErrorResponseIfNoPermissions(6);

        $params = $this->_getRequestParams();

        try {
            $reportTask = new ReportTask();
            $reportTask->runWith(new SendReportFileSpecification($params));

            $this->_sendResponseData([
                'reported' => true
            ]);
        } catch (InvalidArgumentException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Постановка задачи формировани отчета', $e->getMessage(),
                $params,
                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.error'
            );

            $this->_sendResponseWithErrorCode($e->getCode());
        }
    }
}