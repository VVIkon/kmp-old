<?php

/**
 * Управление ТП
 */
class TpController extends SecuredRestController
{
    /**
     * Операция возвращает справочник корпоративных политик для указанной компании
     */
    public function actionGetTPlistForCompany()
    {
        // проверим права пользователя
        $userProfile = Yii::app()->user->getState('userProfile');

        if ($userProfile['roleType'] == 1) {
            $this->_sendErrorResponseIfNoPermissions(62);
        } elseif ($userProfile['roleType'] == 3) {
            if (!UserAccess::hasPermissions(62) && !UserAccess::hasPermissions(63)) {
                $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
            }
        } else {
            $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
        }

        $params = $this->_getRequestParams();

        if (!isset($params['companyId'])) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::INVALID_COMPANY);
        }

        $company = CompanyRepository::getById($params['companyId']);

        if (is_null($company)) {
            $tps = TravelPolicyRuleGroupRepository::findAllUniversal();
        } else {
            $tps = TravelPolicyRuleGroupRepository::findAllForCompany($company);
        }

        $answer = [];

        foreach ($tps as $tp) {
            $answer[] = $tp->toTravelPolicyEditRule();
        }

        $this->_sendResponseData($answer);
    }

    /**
     * Операция выполняет сохранение данных КП для указанной компании
     */
    public function actionSetTPforCompany()
    {
        // проверим права пользователя
        $userProfile = Yii::app()->user->getState('userProfile');

        if ($userProfile['roleType'] == 1) {
            $this->_sendErrorResponseIfNoPermissions(56);
        } elseif ($userProfile['roleType'] == 3) {
            if (!UserAccess::hasPermissions(56) && !UserAccess::hasPermissions(63)) {
                $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
            }
        } else {
            $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
        }

        $params = $this->_getRequestParams();

        if (!isset($params['travelPolicyRules'])) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::INPUT_PARAMS_ERROR);
        }

        $TPMgr = new TPMgr();

        try {
            $tpRule = $TPMgr->setTPforCompany($params['travelPolicyRules']);
        } catch (InvalidArgumentException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Создание TP', $e->getMessage(),
                $params,
                LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.errors'
            );
            $this->_sendResponseWithErrorCode($e->getCode());
        }

        $this->_sendResponseData($tpRule->toTravelPolicyEditRule());
    }
}