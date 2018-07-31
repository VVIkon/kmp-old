<?php

class AuthController extends RestController
{
    /**
    * Аутентифкация и создание сессии пользователя по логину и паролю
    *
    * @param
     *  {
            "login": "api",
            "password": "12345",
            "token": "fe27b68acc59770"
        }
    * @returm
     * {
            "status": 0,
            "errors": "",
            "body": {
                "companyName": "Corporate",
                "companyID": 22057,
                "companyType": 3,
                "companyTypeDesc": "Корпоративный клиент",
                "contractExpiry": "2025-10-10 18:00:00",
                "userType": 3,
                "userTypeDesc": false,
                "commission": "",
                "userId": 4737,
                "userName": "Иван",
                "userLastName": "Иванов",
                "userMName": "Иванович",
                "userEmail": "ivanov@test.com",
                "token": "8b58df758cf0b79a",
                "tokenExpiry": "2027-01-18 12:52:46"
            }
        }
    */
    public function actionUserAuth()
    {
        $module = YII::app()->getModule('systemService')->getModule('account');
        $identityValidator = $module->AccountsRequestValidator($module);
        $params = $this->_getRequestParams();

        $response = $identityValidator->checkUserLoginParams($params);
        if (!$response) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($identityValidator->getLastError()),
                $identityValidator->getLastError()
            );
        }

        $accountsMgr = $module->AccountsMgr($module);
        $response = $accountsMgr->authenticate($params['login'], $params['password'], $module);

        if ($response->getErrorCode() == 0) {
            $acc = $response->getRetAccount();
            $profileInfo = $accountsMgr->getUserProfileByToken($acc['token'], false);

            if (!$profileInfo) {
                LogHelper::logExt( __CLASS__, __METHOD__, 'Ошибка авторизации', '', $profileInfo, LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.requests');
                $this->_sendResponse(false, array(),
                    $this->getErrorDescription($accountsMgr->getLastError()),
                    $accountsMgr->getLastError()
                );
            }

            $profileInfo['token'] = $acc['token'];
            $profileInfo['tokenExpiry'] = $acc['tokenExpiry'];
            // Флаги доступа

            LogHelper::logExt( __CLASS__, __METHOD__, 'Авторизация пользователя', '', $profileInfo, LogHelper::MESSAGE_TYPE_INFO, 'system.systemservice.requests');

            $this->_sendResponse(true, $profileInfo, '');
        } else {
            LogHelper::logExt( __CLASS__, __METHOD__, 'Ошибка авторизации', $this->getErrorDescription($response->getErrorCode()),
                [$params, $response->getAccounts(), $response->getStat()], LogHelper::MESSAGE_TYPE_ERROR,'system.systemservice.requests'
            );

            $this->_sendResponse(false, array(), $this->getErrorDescription($response->getErrorCode()), $response->getErrorCode());
        }
    }

    public function actionChangePassword()
    {
        $params = $this->_getRequestParams();
        if ( empty($params['newpassword'])) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::INPUT_PARAMS_ERROR);
        }
        if ( strlen($params['newpassword']) >50) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::MAX_LENGHT_PASSWORD);
        }

        $module = YII::app()->getModule('systemService')->getModule('account');
        $accountsMgr = $module->AccountsMgr($module);
        $response = $accountsMgr->changePassword($params['login'], $params['password'], $params['newpassword']);
        if ($response) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Пароль пользователя сменён', '', $params, 'info', 'system.systemservice.info');
            $this->_sendResponseData(['changed' => $response]);
        }else{
            $errCode = $accountsMgr->getLastError();
            LogHelper::logExt(get_class($this), __METHOD__, 'Ошбка смены пароля', $errCode, $params, 'error', 'system.systemservice.errors');
            $this->_sendResponseWithErrorCode($errCode);
        }
    }
}