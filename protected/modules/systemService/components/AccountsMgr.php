<?php

/**
 * Class AccountsMgr
 * Реализует функциональность аутентификации клиента
 */
class AccountsMgr
{
    /**
     * Код последней ошибки
     * @var int
     */
    private $_errorCode;

    /**
     * Идентифкатор сессии
     * @var string
     */
    private $_token;

    /**
     * Название модуля
     * @var string
     */
    private $_module;

    const ERROR_NONE = 0;
    const ERROR_LOGIN_INVALID = 2;
    const ERROR_PASSWORD_INVALID = 3;
    const ERROR_TOKEN_INVALID = 4;

    const DB_ERROR = 500;
    const ERROR_GETTING_USERINFO = 501;
    const CANNOT_GET_USER_TOKEN = 502;
    const ERROR_GETTING_USER_RIGHTS = 503;

    const USER_ROLE_NOT_DEFINED = 602;
    const PROFILE_FIELDS_NOT_DEFINED = 604;

    public function __construct($module)
    {
        $this->_module = $module;
    }

    /**
     * Начать сессию пользователя записав токен пользователя в php сессию
     * @param $userToken
     * @return bool
     */
    public function startSession($userToken)
    {
        $userProfile = $this->getUserProfileByToken($userToken);

        if (empty($userProfile)) {
            $this->_errorCode = self::ERROR_TOKEN_INVALID;
            return false;
        }

        // обновим последний логин пользователя
        Yii::app()->db->createCommand()->update('kt_users', ['LastLogin' => StdLib::getMysqlDateTime()], 'UserID = :UserID', ['UserID' => $userProfile['userId']]);

        Yii::app()->user->setState('userProfile', $userProfile);
        Yii::app()->user->setState('usertoken', $userToken);

        return true;
    }

    /**
     * Новая логика проверки указанного акаунта и запрос на формирование token'a пользователяю
     *
     * @return UserAccount component
     */
    public function authenticate($username, $password, $module)
    {
        $ua = new UserAccount($username, $password, $module);
        $ua->checkAndMake(); // Проверка и Создание аккаунта
        return $ua;
    }

    /**
     * смена пароля
     * @param $newPassword
     * @param $account
     * @return int
     */
    private function changingPassword($newPassword, $account)
    {
        $slt = KPasswordHelper::makeSalt();
        $psw = KPasswordHelper::hashPassword($newPassword, $slt);
        $account->Hash = $psw;
        $account->Salt = $slt;
        $flag = $account->save() ? 1 : 0;
        return $flag;
    }

    /**
     *  Управляющий смены пароля
     * @param $userToken
     * @param $password
     * @param $newPassword
     * @return int
     */
//    public function changePassword($userToken, $password, $newPassword)
    public function changePassword($login, $password, $newPassword)
    {
        $flag = 0; // 0 - ошибка
        $account = null;
        try {
            $accounts = AccountRepository::getAccountByLogin($login);
        } catch (Exception $e) {
            $this->_errorCode = SysSvcErrors::ERROR_LOGIN_INVALID;
            return $flag;
        }
        // Имеется ли аккаунт
        if (empty($accounts) || count($accounts) == 0) {
            $this->_errorCode = SysSvcErrors::ERROR_LOGIN_INVALID;
            return $flag;
        }
        // Aккаунт должен быть один!!!
        if (count($accounts) > 1) {
            $this->_errorCode = SysSvcErrors::ERROR_REGISTRATION_DATA;
            return $flag;
        }
        $account = $accounts[0];

//        $perm = $account->getPermissions();
//        if (!UserAccess::hasPermissions([0, 1, 2], $perm['PermissionsCode'])) {
//            $this->_sendResponseWithErrorCode(SysSvcErrors::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
//        }

        // Не проверять пароль. Хеш ведь должен быть пустым!!!
//        $oldPasswSalt = !empty($account->Salt) ? $password . '{' . $account->Salt . '}' : $password;
//        if (!KPasswordHelper::verifyPassword($oldPasswSalt, $account->Hash)) {
//            $this->_errorCode = SysSvcErrors::ERROR_PASSWORD_INVALID;
//            return $flag;
//        }
        if (empty($account->Hash) && empty($account->UserID_GP) && !empty($account->UserID_UTK)) {
            $flag = $this->changingPassword($newPassword, $account);
        } else {
            $this->_errorCode = SysSvcErrors::ERROR_REGISTRATION_DATA;
        }
        return $flag;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->_errorCode;
    }

    /**
     * Получение сформированного token'а после аутентификации
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * Установка нового token'а для указанного акаунта
     * @param $userId
     * @return mixed
     */
    private function setToken($userId)
    {

        try {
            $token = UserToken::getToken($userId);
        } catch (Exception $e) {
            $this->_errorCode = self::DB_ERROR;
            return false;
        }

        if ($token) {
            return $token;
        }
        $module = YII::app()->getModule('systemService');
        $tokenData = TokenHelper::generateToken(nvl($module->getConfig('token_expiration_seconds'), "2"));
//        $tokenData = TokenHelper::generateToken($this->_module->getConfig('token_expiration_seconds'));

        try {
            UserToken::setToken($userId, $tokenData['token'], $tokenData['expires']);
        } catch (Exception $e) {
            $this->_errorCode = self::DB_ERROR;
            return false;
        }

        return $tokenData['token'];
    }

    /**
     * Получить профиль пользователя по идентифкатору сессии
     * @param $token string
     * @return array|bool
     */
    public function getUserProfileByToken($token, $noCheckExpiry = true)
    {
        $userId = $this->getUserIdByToken($token, $noCheckExpiry);

        if (!$userId) {
            return [];
        }

        return $this->getUserProfile($userId);
    }

    /**
     * Получить идентифкатор пользователя по его токену
     * @param $token
     * @param $checkExpiry
     * @return bool
     */
    public function getUserIdByToken($token, $checkExpiry = true)
    {
        try {
            $tokenInfo = UserToken::model()->findByAttributes(array('token' => $token));
        } catch (Exception $e) {
            $this->_errorCode = self::DB_ERROR;
            return false;
        }

        if (is_null($tokenInfo)) {
            $this->_errorCode = self::ERROR_TOKEN_INVALID;
            return false;
        }

        $dateExpired = new DateTime($tokenInfo['expires']);
        $dateNow = new DateTime('now');

        if ($checkExpiry && $dateExpired < $dateNow) {
            $this->_errorCode = self::ERROR_TOKEN_INVALID;
            return false;
        }

        return $tokenInfo['UserID'];
    }

    /**
     * Получить профиль пользователя по идентифкатору пользователя
     * @param $userId integer
     * @return array|bool
     */
    public function getUserProfile($userId)
    {
        $userInfo = Account::getProfile($userId);

//todo включить привязку договора после заполнения им базы
        if (!$userInfo || /*empty($userInfo->contract) ||*/
            empty($userInfo->agent)
        ) {
            $this->_errorCode = self::ERROR_GETTING_USERINFO;
            return false;
        }

        $maxIndex = false;
        foreach ($userInfo->contract as $key => $contract) {
            if ((new DateTime($contract['ContractExpiry'])) >
                new DateTime($userInfo->contract[$maxIndex]['ContractExpiry'])
            ) {
                $maxIndex = $key;
            }
        }

        $userPermissions = $userInfo->permissions->getPermissionsCode();

        $profile = [
            'companyName' => $userInfo->agent['Name'],
            'companyID' => $userInfo->agent['AgentID'],
            'companyType' => $userInfo->agent['Type'],
            'companyTypeDesc' => (!Company::getCompanyTypeName($userInfo->agent['Type']))
                ? ''
                : Company::getCompanyTypeName($userInfo->agent['Type']),
// todo включить проверку окончания действия договора после добавления их в базу
            //'contractExpiry'    => $userInfo->contract[$maxIndex]['ContractExpiry'],
            'contractExpiry' => '2025-10-10 18:00:00',
            'userType' => $userInfo['RoleType'],
            'userTypeDesc' => Account::getUserTypeName($userInfo['RoleID']),
            'commission' => ($maxIndex !== false) ? $userInfo->contract[$maxIndex]->Commission : '',
            'subscribeChat' => $userInfo['subscribeChat'],
            'roleId' => $userInfo['RoleID'],
            'roleType' => $userInfo['RoleType'],
            //'token' => $accountsMgr->getUserToken($params['userId']),
            'aviaaccess' => UserAccess::hasPermissions([15, 16], $userPermissions),
            'hotelaccess' => UserAccess::hasPermissions([15, 17], $userPermissions),
            'trainaccess' => UserAccess::hasPermissions([15, 18], $userPermissions),
            'transferaccess' => UserAccess::hasPermissions([15, 19], $userPermissions)
        ];

        $profileFields = $this->_module->getConfig('userprofileFields');
        if (empty($profileFields) || count($profileFields) == 0) {
            $this->_errorCode = self::PROFILE_FIELDS_NOT_DEFINED;
            return false;
        }

        foreach ($profileFields as $profileField => $paramName) {
            if (array_key_exists($profileField, $userInfo->getAttributes())) {
                $profile[$paramName] = $userInfo->getAttribute($profileField);
            }
        }

        return $profile;
    }

    /**
     * Получение идентифкатора сессии
     * пользователя по идентифкатору пользователя
     * @param $userid string
     * @return bool | string
     */
    public function getUserToken($userid)
    {
        $token = UserToken::getToken($userid);

        if (!$token) {
            $this->_errorCode = self::CANNOT_GET_USER_TOKEN;
            return false;
        }

        return $token;
    }

    /**
     * Получить роль текущего пользователя
     * @return bool|int
     */
    public function getCurrentUserRole()
    {

        $accountInfo = $this->getCurrentUserProfile();

        if (empty($accountInfo)) {
            $this->_errorCode = $this->getLastError();
            return false;
        }

        if (empty($accountInfo['userType'])) {
            $this->_errorCode = self::USER_ROLE_NOT_DEFINED;
            return false;
        }

        return $accountInfo['userType'];
    }

    /**
     * Проверка, является ли пользователь сотрудником КМП
     * @param $userRole
     * @return bool
     */
    public function isUserKMPWorker($userRole)
    {
//todo изменить проверку после определения структуры битов в роле пользователя
        $KMP_USER = 1;
        if ($userRole == $KMP_USER) {
            return true;
        }

        return false;
    }

    /**
     * Получить профиль текущего пользователя
     * @return array|bool
     */
    public function getCurrentUserProfile()
    {

        $token = Yii::app()->user->getState('usertoken');

        if (empty($token)) {
            $this->_errorCode = self::ERROR_TOKEN_INVALID;
            return false;
        }

        $userProfile = $this->getUserProfileByToken($token);

        if (empty($userProfile)) {
            $this->_errorCode = self::ERROR_GETTING_USERINFO;
            return false;
        }

        return $userProfile;
    }

    /**
     * Получить права пользователя по указанному токену
     * @param $token
     * @return string
     */
    public function getUserRightsByToken($token)
    {

        $userId = $this->getUserIdByToken($token);

        if (empty($userId)) {
            return false;
        }

        return $this->getUserRights($userId);
    }

    /**
     * Получить права пользователя по его идентификатору
     * @param $userId
     */
    public function getUserRights($userId)
    {

        $rights = Account::getRights($userId);

        if (empty($rights)) {
            $this->_errorCode = self::ERROR_GETTING_USER_RIGHTS;
            return false;
        }

        return $rights;
    }

    /**
     * Проверка валидности идентифкатора сессии пользователя
     * @param $token string
     * @return bool
     */
    public function validateToken($token)
    {
        return UserToken::validateToken($token);
    }

    /**
     * Проверим имеет ли пользователь доступ к чату
     * @param $usertoken
     * @return bool
     */
    public function userHasAccessToChat($usertoken)
    {
        $profile = $this->getUserIdByToken($usertoken);
        if (!$profile) {
            return false;
        }
        $Account = AccountRepository::getAccountByToken($usertoken);
        if (is_null($Account)) {
            return false;
        }

        return $Account->hasSubscribeToChat();
    }

    /**
     * Обновление прав пользователя
     * @param $userId
     * @param $roleId
     * @param $roleType
     * @throws InvalidArgumentException
     * @return bool
     */
    public function setUserRole($userId, $roleId, $roleType)
    {
        $account = AccountRepository::getAccountById($userId);

        if (is_null($account)) {
            throw new InvalidArgumentException('Пользователя с таким userId не существует', SysSvcErrors::USER_NOT_FOUND);
        }

        $userProfile = Yii::app()->user->getState('userProfile');

        // PERMISSION_0 - редактирование и создание любого пользователя
        $permissionsToCheck = [0];

        // только (PERMISSION_1 OR PERMISSION_2) и редактирование и создание пользователей компании текущего пользователя
        if ($account->getCompany()->getId() == $userProfile['companyID']) {
            $permissionsToCheck[] = 1;
            $permissionsToCheck[] = 2;
        }

        // только PERMISSION_З  - редактирование данных текущего пользователя, создание запрещено
        if ($userId == $userProfile['userId']) {
            $permissionsToCheck[] = 3;
        }

        // проверим права доступа к операции
        if (!UserAccess::hasPermissions($permissionsToCheck)) {
            return false;
        }

        // установим тип пользака
        if (!$account->setRoleType($roleType)) {
            throw new InvalidArgumentException("Некорректный roleType - $roleType", SysSvcErrors::INCORRECT_USER_ROLE_TYPE);
        }

        // установим id набора прав
        if (!$account->setRoleId($roleId)) {
            throw new InvalidArgumentException("Некорректный roleId - $roleId", SysSvcErrors::INCORRECT_USER_ROLE_ID);
        }

        $account->save(false);

        return true;
    }
}