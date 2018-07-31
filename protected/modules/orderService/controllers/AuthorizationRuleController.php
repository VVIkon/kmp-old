<?php

/**
 * Class AuthorizationRuleController
 *
 */
class AuthorizationRuleController extends SecuredRestController
{
    /**
     * Операция выполняет сохранение данных КП для указанной компании
     */
    public function actionSetAuthorizationRule()
    {
        $this->_sendErrorResponseIfNoPermissions(60);

        $params = $this->_getRequestParams();

        if (empty($params['rule'])) {
            $this->_sendResponseWithErrorCode(OrdersErrors::INPUT_PARAMS_ERROR);
        }

        $authRuleMgr = new AuthRuleMgr();

        try {
            $soAuthRule = $authRuleMgr->setAuthRule($params['rule']);
        } catch (AuthRuleMgrException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Запись правила авторизации', $e->getMessage(),
                $params,
                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.error'
            );
            $this->_sendResponseWithErrorCode($e->getCode());
        } catch (InvalidArgumentException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Запись правила авторизации', $e->getMessage(),
                $params,
                LogHelper::MESSAGE_TYPE_WARNING,
                'system.orderservice.warning'
            );
            $this->_sendResponseWithErrorCode($e->getCode());
        }

        $this->_sendResponseData([
            'success' => true,
            'rule' => $soAuthRule
        ]);
    }

    /**
     * Операция выполняет чтение правила авторизации
     */
    public function actionGetAuthorizationRule()
    {
        $this->_sendErrorResponseIfNoPermissions(60);

        $params = $this->_getRequestParams();

        if (!isset($params['companyId'])) {
            $params['companyId'] = null;
        }

        $authRuleMgr = new AuthRuleMgr();

        try {
            $rules = $authRuleMgr->getRulesByCompanyId($params['companyId']);
        } catch (AuthRuleMgrException $e) {
            $this->_sendResponseWithErrorCode($e->getCode());
        }

        $this->_sendResponseData($rules);
    }
}