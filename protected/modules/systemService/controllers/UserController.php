<?php

use Symfony\Component\Validator\Validation;

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 10/3/16
 * Time: 5:34 PM
 */
class UserController extends SecuredRestController
{
    /**
     * Получение данных профиля указанного пользователя
     */
    public function actionGetUser()
    {
        $module = YII::app()->getModule('systemService')->getModule('account');
        $identityValidator = $module->AccountsRequestValidator($module);

        $params = $this->_getRequestParams();

        $response = $identityValidator->checkGetUserParams($params);

        if (!$response) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($identityValidator->getLastError()),
                $identityValidator->getLastError()
            );
        }

        $accountsMgr = $module->AccountsMgr($module);

        $profileInfo = $accountsMgr->getUserProfile($params['userId']);

        if (!$profileInfo) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($accountsMgr->getLastError()),
                $accountsMgr->getLastError());
        }

        $profileInfo['token'] = $accountsMgr->getUserToken($params['userId']);

        //$this->_sendResponse(true, array('profile' => $profileInfo), '');
        $this->_sendResponse(true, $profileInfo, '');
    }

    /**
     * Проверка прав пользователя в системе
     */
    public function actionUserAccess()
    {
        $params = $this->_getRequestParams();

        if (empty($params['permissions'])) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::USER_PERMISSIONS_NOT_SET);
        }

        // вытащим профиль пользователя
        $userProfile = Yii::app()->user->getState('userProfile');

        // поищем пользователя с таким ID с подгрузкой прав доступа
        $Account = AccountRepository::getAccountWithPermissionsById($userProfile['userId']);

        $answer = [
            'hasAccess' => UserAccess::hasPermissions($params['permissions']),
            'permissions' => null
        ];

        if ($answer['hasAccess']) {
            $answer['permissions'] = $Account->getPermissions()->getPermissionsCode();
        }

        // отправим ответ
        $this->_sendResponseData($answer);
    }

    /**
     * GetClientUserSuggest
     * Операция выполняет поиск по подстроке ФИО сотрудкников компании и возвращает список подстановки
     */
    public function actionGetClientUserSuggest()
    {
        $params = $this->_getRequestParams();

        if (empty($params['companyId'])) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::INPUT_PARAMS_ERROR);
        }

        $results = [];
        $Accounts = [];
        $userProfile = Yii::app()->user->getState('userProfile');
        $userId = $userProfile['userId'];

        // искать среди всех пользователей
        if (UserAccess::hasPermissions(0)) {
            $Accounts = AccountRepository::getSuggest($params['substringFIO'], $params['companyId']);
        } elseif (UserAccess::hasPermissions(8) && UserAccess::hasPermissions(9) ) { // искать всех пользователей холдинга (ГО + дочерние компании)
            $Accounts = AccountRepository::getSuggest($params['substringFIO'], $userProfile['companyID'], AccountRepository::ACCOUNT_WITH_HEADQUARTERS_AND_ALL_CHILD_COMPANIES);
        } elseif (UserAccess::hasPermissions(8)) { // из ГО компаний холдинга и своей компания
            $Accounts = AccountRepository::getSuggest($params['substringFIO'], $params['companyId'], AccountRepository::ACCOUNT_WITH_HEADQUARTERS_AND_OWN);
        } elseif (UserAccess::hasPermissions(9)) { // из всех компаний холдинга но без профилей ГО
            $Accounts = AccountRepository::getSuggest($params['substringFIO'], $params['companyId'], AccountRepository::ACCOUNT_FOR_ALL_WITHOUT_HEADQUARTERS);
        } elseif (UserAccess::hasPermissions([1, 2])) { // искать среди пользователей компании  текущего пользователя
            $Accounts = AccountRepository::getSuggest($params['substringFIO'], $userProfile['companyID']);
        } elseif (UserAccess::hasPermissions(3)) { // вернуть только данные текущего пользователя
            $Accounts = AccountRepository::getSuggest($params['substringFIO'], $userProfile['companyID']);
//            if ($Account = AccountRepository::getAccountById($userId)) {
//                $Accounts[] = $Account;
//            }
        } else {
            $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
        }

        if (count($Accounts)) {
            foreach ($Accounts as $Account) {
                $UserDocuments = $Account->getUserDocuments();

                if (count($UserDocuments)) {
                    foreach ($UserDocuments as $UserDocument) {
                        $results[] = $UserDocument->getUserSuggestArray();
                    }
                }
            }
        }

        $this->_sendResponseData($results);
    }

    /**
     * UserSuggest
     * Операция выполняет поиск по подстроке ФИО сотрудкников компании и возвращает список пользователей
     */
    public function actionGetUserSuggest()
    {
        $params = $this->_getRequestParams();

        if (empty($params['companyId']) || !isset($params['onlyChatSubscribers'])) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::INPUT_PARAMS_ERROR);
        }

        $params['substringFIO'] = isset($params['substringFIO']) ? $params['substringFIO'] : null;

        $results = [];
        $Accounts = [];
        $userProfile = Yii::app()->user->getState('userProfile');

        // искать среди всех пользователей
        if (UserAccess::hasPermissions(0)) {
            $Accounts = AccountRepository::getSuggest($params['substringFIO'], $params['companyId']);
        } elseif (UserAccess::hasPermissions([1, 2])) { // искать среди пользователей компании  текущего пользователя
            $Accounts = AccountRepository::getSuggest($params['substringFIO'], $userProfile['companyID']);
        } elseif (UserAccess::hasPermissions(3)) { // вернуть только данные текущего пользователя
            if ($Account = AccountRepository::getAccountById($userProfile['userId'])) {
                $Accounts[] = $Account;
            }
        } else {
            $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
        }

        foreach ($Accounts as $Account) {
            // если выводим только подписчиков чата
            if ($params['onlyChatSubscribers'] && !$Account->hasSubscribeToChat()) {
                continue;
            }

            $results[] = $Account->getSkUser();
        }

        $this->_sendResponseData($results);
    }

    /**
     * GetClientUserSuggest
     * Операция возвращает данные корпоративного сотрудника
     */
    public function actionGetClientUser()
    {
        $params = $this->_getRequestParams();

        // валидация входных параметров
        if (empty($params['docId'])) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::INPUT_PARAMS_ERROR);
        }

        // сразу проверим все права и отсечем негодяев
        if (!UserAccess::hasPermissions([0, 1, 2, 3])) {
            $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
        }

        // достанем профиль пользователя для проверки других прав
        $userProfile = Yii::app()->user->getState('userProfile');

        // поищем заветный документ
        $Account = AccountRepository::getClientUserByDocId($params['docId']);

        if (!$Account) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::CLIENT_USER_NOT_FOUND);
        }

        // проверим другие ограничения прав
        if (!UserAccess::hasPermissions(0)) {
            if (UserAccess::hasPermissions([1, 2])) { // DocID принадлежит пользователям компании текущего пользователя
                if ($Account->getAgentID() != $userProfile['companyID']) {
                    $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
                }
            } elseif (UserAccess::hasPermissions(3)) { // текущий пользователь имеет документ DocID
                if ($Account->getUserId() != $userProfile['userId']) {
                    $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
                }
            }
        }

        // выведем результат
        $this->_sendResponseData($Account->getClientUserArray());
    }

    /**
     * SetUser
     */
    public function actionSetUser()
    {
        $params = $this->_getRequestParams();

        if (empty($params['user']) || empty($params['document']) || empty($params['user']['clientId'])) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::INPUT_PARAMS_ERROR);
        }

        // проверим все права
        if (!UserAccess::hasPermissions([0, 1, 2, 3])) {
            $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
        }

        // достанем профиль пользователя для проверки других прав
        $userProfile = Yii::app()->user->getState('userProfile');

        // если не задан ID пользователя, то попробуем его поискать
        if (is_null($params['user']['userId'])) {
            $Account = AccountRepository::getByFIOAndCompanyId($params['user']);

            if(is_null($Account)){
                $Account = new Account();
            }
        } else {
            $Account = AccountRepository::getAccountById($params['user']['userId']);

            // если не нашли - ошибка
            if (is_null($Account)) {
                $this->_sendResponseWithErrorCode(SysSvcErrors::USER_NOT_FOUND);
            }
        }

        // инициализация пользователя
        $Account->fromArray($params['user']);
        $Account->setRoles();

        // проверим кого мы пытаемся создать и есть ли на это права
        if (!UserAccess::hasPermissions(0)) {
            if (UserAccess::hasPermissions([1, 2])) { //  редактирование и создание пользователей компании текущего пользователя
                if ($Account->getAgentID() != $userProfile['companyID']) {
                    $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
                }
            } elseif (UserAccess::hasPermissions(3)) { //  редактирование данных текущего пользователя, создание запрещено
                if ($Account->getUserId() != $userProfile['userId'] || $Account->getIsNewRecord()) {
                    $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
                }
            }
        }

        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadRequiredFieldsMetadata')
            ->getValidator();

        $violations = $validator->validate($Account);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $this->_sendResponseWithErrorCode($violation->getMessage());
            }
        }

        // ищем по ID
        if (!empty($params['document']['userDocId'])) {
            $UserDocument = UserDocumentRepository::getById($params['document']['userDocId']);

            if (is_null($UserDocument)) {
                $this->_sendResponseWithErrorCode(SysSvcErrors::DOCUMENT_NOT_FOUND);
            }
        } elseif (!empty($params['document']['docSerial']) && !empty($params['document']['docNumber'])) { // ищем по номеру и серии
            $UserDocument = UserDocumentRepository::getUserDocBySerialAndNum($params['document']['docSerial'], $params['document']['docNumber'], $Account->getUserId());

            // если по номеру и серии документ не найден, то создадим новый
            if (is_null($UserDocument)) {
                $UserDocument = new UserDocument();
            }
        } else {
            $UserDocument = new UserDocument();
        }

        $UserDocument->fromSkUserDocumentArray($params['document']);

        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadRequiredFieldsMetadata')
            ->getValidator();

        $violations = $validator->validate($UserDocument);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $this->_sendResponseWithErrorCode($violation->getMessage());
            }
        }

        // бонусные карты
        $accountBonusCards = [];

        if (isset($params['user']['bonusCards']) && count($params['user']['bonusCards'])) {
            if ($Account->getUserId()) {
                $Account->deleteBonusCards();
            }

            foreach ($params['user']['bonusCards'] as $bonusCard) {
                $loyaltyProgramId = isset($bonusCard['aviaLoyaltyProgramId']) ? $bonusCard['aviaLoyaltyProgramId'] : 0;

                $loyaltyProgram = LoyaltyProgramRepository::getById($loyaltyProgramId);
                if (is_null($loyaltyProgram)) {
                    $this->_sendResponseWithErrorCode(SysSvcErrors::INVALID_LOYALTY_PROGRAM);
                }

                $accountBonusCard = new AccountBonusCard();
                $accountBonusCard->fromArray($bonusCard);
                $accountBonusCard->bindLoyaltyProgram($loyaltyProgram);
                $accountBonusCard->save(false);

                $accountBonusCards[] = $accountBonusCard;
            }
        }

        try {
            $transaction = Yii::app()->db->beginTransaction();

            if (!$Account->save(false)) {
                $this->_sendResponseWithErrorCode(SysSvcErrors::FATAL_ERROR);
            }
            $UserDocument->bindUser($Account);
            if (!$UserDocument->save(false)) {
                $this->_sendResponseWithErrorCode(SysSvcErrors::FATAL_ERROR);
            }

            foreach ($accountBonusCards as $accountBonusCardToSave) {
                $accountBonusCardToSave->bindAccount($Account);
                $accountBonusCardToSave->save(false);
            }

            $transaction->commit();
        } catch (CDbException $e) {
            $transaction->rollback();
            $this->_sendResponseWithErrorCode(SysSvcErrors::FATAL_ERROR);
        }

        $this->_sendResponseData([
            'userId' => $Account->getUserId(),
            'userDocId' => $UserDocument->getUserDocId()
        ]);
    }

    /**
     * Команда смены пароля
     * @param
     *  {
     * "usertoken": "{{userToken}}",
     * "password": "12345",
     * "newpassword":"12345",
     * "token":"fe27b68acc59770"
     * }
     * @returm
     * {
     * "status": 0,
     * "errors": "",
     * "body": {
     * "changed": "1"
     * }
     * }
     */
//    public function actionChangePassword()
//    {
//        $params = $this->_getRequestParams();
//        if ( empty($params['newpassword']) || !array_key_exists('password', $params)) {
//            $this->_sendResponseWithErrorCode(SysSvcErrors::INPUT_PARAMS_ERROR);
//        }
//        if ( strlen($params['newpassword']) >50) {
//            $this->_sendResponseWithErrorCode(self::MAX_LENGHT_PASSWORD);
//        }
//
//        if (!UserAccess::hasPermissions([0, 1, 2])) {
//            $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
//        }
//        $module = YII::app()->getModule('systemService')->getModule('account');
//        $accountsMgr = $module->AccountsMgr($module);
//        $response = $accountsMgr->changePassword($params['usertoken'], $params['password'], $params['newpassword']);
//        if ($response) {
//            LogHelper::logExt(get_class($this), __METHOD__, 'Пароль пользователя сменён', '', $params, 'info', 'system.systemservice.info');
//            $this->_sendResponseData(['changed' => $response]);
//        }else{
//            $errCode = $accountsMgr->getLastError();
//            LogHelper::logExt(get_class($this), __METHOD__, 'Ошбка смены пароля', $errCode, $params, 'error', 'system.systemservice.errors');
//            $this->_sendResponseWithErrorCode($errCode);
//        }
//    }

    /**
     * Команда предназначена для привязки роли пользователя и типа клиента к пользователю
     */
    public function actionSetUserRole()
    {
        $params = $this->_getRequestParams();

        if (empty($params['userId']) || empty($params['roleId']) || empty($params['roleType'])) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::INPUT_PARAMS_ERROR);
        }

        $accountManager = new AccountsMgr(YII::app()->getModule('systemService')->getModule('account'));

        try {
            $res = $accountManager->setUserRole($params['userId'], $params['roleId'], $params['roleType']);

            if (!$res) {
                $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
            }
        } catch (InvalidArgumentException $e) {
            $this->_sendResponseWithErrorCode($e->getCode());
        }

        $this->_sendResponseData([
            'success' => true
        ]);
    }

    /**
     * Операция оформляет или отменяет подписку пользователя на чат
     */
    public function actionSetUserChat()
    {
        $params = $this->_getRequestParams();

        if (!isset($params['mode'])) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::INPUT_PARAMS_ERROR);
        }

        $userProfile = Yii::app()->user->getState('userProfile');

        $Account = AccountRepository::getAccountById($userProfile['userId']);

        if ($params['mode']) {
            $Account->subscribeToChat();
        } else {
            $Account->unSubscribeToChat();
        }

        $Account->save(false);

        $this->_sendResponseData([
            'success' => true
        ]);
    }
}
