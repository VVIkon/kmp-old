<?php

/**
 * класс управляющий аккаунтами пользователя. Пользователь, в данном случае,
 * это набор записей объединённых одним login
 * User: v.ikonnikov
 * Date: 17.02.2017
 * Time: 9:27
 *
 * Ошибки авторизации:
 * ERROR_MAST_CHANGE_PASSWORD = 2000; // Пользователь должен сменить пароль
 * ERROR_MAST_CHANGE_LOGIN = 2001;    // Пользователь должен сменить логин
 * ERROR_AUTHENTICATE = 2002;         // Ошибка авторизации
 * ERROR_DOUBLE_HASH = 2003;          // Ошибка: Имеются аккаунты в кот. присутствует одинаковые хеши
 * ERROR_REGISTRATION_DATA = 2004;    // Ошибка: В БД присутствует логически неправильная регистрационная запись
 * ERROR_GETTING_USERINFO = 2005;     // Ошибка получении аккаунта пользователя
 * ERROR_AUTHENTICATE_GPTS = 2006;    // Ошибка авторизации в GPTS
 * ERROR_NOT_ACCOUNT = 2007;          // Ошибка логин не имеет аккаунта
 *
 */

require_once('SystemHelper.php');

class UserAccount
{
    private $module;
    private $accountModel;
    private $errorCode;

    private $accounts = [];     // Массив аккаунтов одного активного логина
    private $stat = [];         // Массив статистики по выбранным аккаунтам
    private $login;
    private $password;
    private $retAccount;        // аккаунт прошедший авторизацию


    public function __construct($inLogin, $inPassword, $module)
    {
        $this->errorCode = SysSvcErrors::ERROR_NONE;
        $this->module = $module;
        $this->login = $inLogin;
        $this->password = $inPassword;
        $this->getAccount($inLogin);
    }

    /**
     * Окаунт по логину
     * @param $login
     * @returm bool
     */
    private function getAccount($login)
    {
        try {
            $this->accountModel = Account::model()->findAllByAttributes(['Login' => $login, 'active' => '1']);
            if (empty($this->accountModel)) {
                $this->errorCode = SysSvcErrors::ERROR_GETTING_USERINFO;
                return false;
            }
            $a = 0;
            $b = 0;
            $c = 0;
            foreach ($this->accountModel as $account) {
                $acc['UserID'] = $account->UserID;
                $acc['UserID_UTK'] = $account->UserID_UTK;
                $acc['UserID_GP'] = $account->UserID_GP;
                $acc['Hash'] = $account->Hash;
                $acc['Salt'] = $account->Salt;
                $acc['AgentID'] = $account->getAgentID();
                $acc['token'] = $account->getUserToken($account->UserID);
                $acc['tokenExpiry'] = $account->getUserTokenExpiry($account->UserID);
                $acc['GPTScompanyCode'] = $account->getCompanyCode($account->UserID);

                if (!empty($account->UserID_UTK))
                    $a++;
                if (!empty($account->UserID_GP))
                    $b++;
                if (!empty($account->Hash))
                    $c++;

                $this->accounts[] = $acc;
            }
            $this->stat['UTK'] = $a;    // Статистика: кол-во ID УТК в наборе аккаунтов
            $this->stat['GP'] = $b;     // Статистика: кол-во ID GPTS в наборе аккаунтов
            $this->stat['HASH'] = $c;   // Статистика: кол-во заполненных хешей в наборе аккаунтов
            $this->stat['HASH-TWINS'] = $this->checkTwins($this->accountModel, 'Hash');     // Статистика: кол-во одинаковых хешей в наборе аккаунтов
            $this->stat['CNT'] = count(nvl($this->accountModel, []));      // Статистика: кол-во аккаунтов в наборе

            return true;
        } catch (Exception $e) {
            $this->errorCode = SysSvcErrors::ERROR_GETTING_USERINFO;
            return false;
        }

    }

    /**
     * Проверка имеется ли в массиве $arrs совпадения по $keyName
     * 1: совпадения имеются
     * @param
     * @returm int
     */
    private function checkTwins($arrs, $keyName)
    {
        $ch = [];
        foreach ($arrs as $arr) {
            if (!empty($arr[$keyName]) && array_key_exists($arr[$keyName], $ch)) {
                return 1;
            } elseif (!empty($arr[$keyName])) {
                $ch[$arr[$keyName]] = 1;
            }
        }
        return 0;
    }

    /**
     * Проверка при НЕ переданном в UserAuth password
     * @param
     * @returm bool
     */
    private function emptyPasswordOperation()
    {
        if ($this->stat['UTK'] > 0) {
            if ($this->stat['CNT'] == 1 && $this->stat['GP'] == 0 && $this->stat['HASH'] == 0) {                        //один аккаунт, ID GPTS - нет, хеша - нет
                $this->errorCode = SysSvcErrors::ERROR_MAST_CHANGE_PASSWORD;   //
            } elseif ($this->stat['CNT'] == 1 && $this->stat['GP'] == 1 && $this->stat['HASH'] == 0) {                   //один аккаунт, ID GPTS - есть, хеша - нет
                $this->errorCode = SysSvcErrors::ERROR_AUTHENTICATE;
            } elseif ($this->stat['CNT'] > 1 && $this->stat['GP'] == 0 && $this->stat['HASH'] == 0) {                   //несколько аккаунтов, ID GPTS - нет, хеша - нет
                $this->errorCode = SysSvcErrors::ERROR_MAST_CHANGE_PASSWORD;
            } elseif ($this->stat['CNT'] > 1 && $this->stat['GP'] < $this->stat['CNT'] && $this->stat['HASH'] == 0) {   //несколько аккаунтов, ID GPTS - несколько, хеша - нет
                $this->errorCode = SysSvcErrors::ERROR_MAST_CHANGE_LOGIN;
            } elseif ($this->stat['CNT'] > 1 && $this->stat['GP'] == $this->stat['CNT'] && $this->stat['HASH'] == 0) {  //несколько аккаунтов, ID GPTS - есть, хеша - нет
                $this->errorCode = SysSvcErrors::ERROR_AUTHENTICATE;
            } else {
                $this->errorCode = SysSvcErrors::ERROR_AUTHENTICATE;
            }
        } else {
            $this->errorCode = SysSvcErrors::ERROR_AUTHENTICATE;
        }
        return false;
    }

    /**
     * Операции при переданном в UserAuth password и заполненном Hesh
     * @param
     * @returm bool
     */
    private function filledPasswordOperation_WithHash()
    {   //ХЕШ заполнен
        if ($this->stat['CNT'] == 1 && $this->stat['GP'] == 1) { //(1)(7) Для одной записи
            $salt = nvl($this->accounts[0]['Salt']);
            $hash = nvl($this->accounts[0]['Hash']);
            if (!$this->basicAuth($salt, $hash)) {  // Штатная авторизация
                $this->errorCode = SysSvcErrors::ERROR_AUTHENTICATE;
                return false;
            } else {
                $this->retAccount = $this->accounts[0];
                return true;
            }
        } elseif ($this->stat['CNT'] == 1 && $this->stat['GP'] == 0) { //(5) Для нескольких записей ID GP - нет
            $salt = nvl($this->accounts[0]['Salt']);
            $hash = nvl($this->accounts[0]['Hash']);
            if (!$this->basicAuth($salt, $hash)) {  // Штатная авторизация
                $this->errorCode = SysSvcErrors::ERROR_AUTHENTICATE;
                return false;
            } else {
                $this->retAccount = $this->accounts[0];
                return true;
            }
        } elseif ($this->stat['CNT'] > 1 && $this->stat['GP'] == $this->stat['CNT']) { //(2)(8) Для нескольких записей ID GP - есть у всех
            if ($this->stat['HASH-TWINS'] == 1) { // близнецы - есть
                $this->errorCode = SysSvcErrors::ERROR_DOUBLE_HASH;
                return false;
            } else {
                foreach ($this->accounts as $account) {
                    $salt = nvl($account['Salt']);
                    $hash = nvl($account['Hash']);
                    if ($this->basicAuth($salt, $hash)) {  // Штатная авторизация
                        $this->retAccount = $account;
                        $this->errorCode = SysSvcErrors::ERROR_NONE;
                        return true;  // Авторизация успешна
                    }
                }
                $this->errorCode = SysSvcErrors::ERROR_AUTHENTICATE;
                return false; // Ниодин из аккаунтов не прошёл авторизацию
            }
        } elseif ($this->stat['CNT'] > 1 && $this->stat['GP'] < $this->stat['CNT']) { //(6) Для нескольких записей ID GP - встречается, но не у всех
            if ($this->stat['HASH-TWINS'] == 1) { // близнецы - есть
                $this->errorCode = SysSvcErrors::ERROR_DOUBLE_HASH;
                return false;
            } else {
                foreach ($this->accounts as $account) {
                    $salt = nvl($account['Salt']);
                    $hash = nvl($account['Hash']);
                    if ($this->basicAuth($salt, $hash)) {  // Штатная авторизация
                        $this->retAccount = $account;
                        $this->errorCode = SysSvcErrors::ERROR_NONE;
                        return true;  // Авторизация успешна
                    }
                }
                $this->errorCode = SysSvcErrors::ERROR_AUTHENTICATE;
                return false; // Ниодин из аккаунтов не прошёл авторизацию
            }

        } else {
            return false;
        }
    }

    /**
     * Операции при переданном в UserAuth password и пустом Hesh
     * @param
     * @returm bool
     */
    private function filledPasswordOperation_WithOutHash()
    {
        // Хеша - НЕТ - Проверка пользователя по ГПТС
        if ($this->stat['CNT'] == 1 && $this->stat['GP'] == 1) { //(10) Для одной записи

            if (!$this->gptsAuth($this->login, $this->password, $this->accounts[0]['GPTScompanyCode'])) { // Аутентификация не  прошла
                return false;
            } else { // Аутент прошла
                $acc = nvl($this->accountModel[0]); // текущий акаунт
                if (!$this->enterPassword($this->password, $acc)) {  // Штатная авторизация
                    $this->errorCode = SysSvcErrors::ERROR_AUTHENTICATE;
                    return false;
                } else {
                    $this->retAccount = $this->accounts[0];
                    return true;
                }
            }
        } elseif ($this->stat['CNT'] == 1 && $this->stat['GP'] == 0) { //(3) Для одной записи
            $this->errorCode = SysSvcErrors::ERROR_REGISTRATION_DATA;
            return false;
        } elseif ($this->stat['CNT'] > 1 && $this->stat['GP'] < $this->stat['CNT']) { //(4) Для одной записи

            if ($this->stat['HASH-TWINS'] == 1) { // близнецы - есть!!!
                $this->errorCode = SysSvcErrors::ERROR_DOUBLE_HASH;
                return false;
            } else {
                foreach ($this->accountModel as $account) {
                    if (!empty($account->UserID_GP)) { // Только для аккаунта в которых есть idGPTS
                        if ($this->gptsAuth($this->login, $this->password, $account['GPTScompanyCode'])) { // Аутент прошла !!!
                            if (!$this->enterPassword($this->password, $account)) {  // Штатная авторизация
                                return false;
                            } else {
                                $this->retAccount = $account;
                                return true;
                            }
                        }
                    }
                }
                return false;
            }
        } elseif ($this->stat['CNT'] > 1 && $this->stat['GP'] == $this->stat['CNT']) { //(9) Для одной записи

            if ($this->stat['HASH-TWINS'] == 1) { // близнецы - есть!!!
                $this->errorCode = SysSvcErrors::ERROR_DOUBLE_HASH;
                return false;
            } else {
//                foreach ($this->accountModel as $account) {
                foreach ($this->accounts as $account) {
                    if (!$this->gptsAuth($this->login, $this->password, $account['GPTScompanyCode'])) { // Аутент не  прошла
                        return false;
                    } else { // Аутент прошла
                        if (!$this->enterPassword($this->password, $account)) {  // Штатная авторизация
                            $this->errorCode = SysSvcErrors::ERROR_AUTHENTICATE;
                            return false;
                        } else {
                            $this->retAccount = $account;
                            return true;
                        }
                    }
                }
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Штатная авторизация
     * @param
     * @returm bool
     */
    private function basicAuth($salt, $hash)
    {
        $passandsalt = !empty($salt) ? $this->password . '{' . $salt . '}' : $this->password;
        if (KPasswordHelper::verifyPassword($passandsalt, $hash)) {
            return true;
        } else {
            $this->errorCode = SysSvcErrors::ERROR_AUTHENTICATE;
            return false;
        }
    }

    /**
     * Сервис проверки авторизации в GPTS
     * @param $login , $password
     * @returm bool
     */
    private function gptsAuth($login, $password, $GPTSCompahyCode)
    {
        $params = [
            'gatewayId' => 5,
            'companyId' => $GPTSCompahyCode,
            'login' => $login,
            'password' => $password
        ];
        $apiClient = new ApiClient($this->module);
        $serviceResponse = $apiClient->makeRestRequest('supplierService', 'CheckGateUserPassword', $params);
        $ServArr = json_decode($serviceResponse, true);
        $result = nvl($ServArr['body']['authenticate'], false);// "true"
        if ($result == false) {
            $this->errorCode = SysSvcErrors::ERROR_AUTHENTICATE_GPTS;
        }
        return $result;
    }

    /**
     * Cохранение пароля
     * @param $login , $password
     * @returm bool
     */
    private function enterPassword($newPassword, $account)
    {
        $flag = false;
        if (isset($newPassword) && isset($account)) {
            $slt = KPasswordHelper::makeSalt();
            $psw = KPasswordHelper::hashPassword($newPassword, $slt);
            $account->Hash = $psw;
            $account->Salt = $slt;
            $flag = $account->save() ? true : false;
        }
        return $flag;
    }

    /** Смена токена возвращаемому retAccount
     *  В systemService/config/envconfig:
     * если токен протух - токен и дата меняются.
     * флаг change_if_expired = true - позволяет не изменять токен пока он не протухнет
     * если флага change_if_expired в envconfig НЕТ, это равно change_if_expired = false - токен будет изменён
     *
     * @return bool;
     */
    private function changeToken()
    {
        $module = YII::app()->getModule('systemService');
        $tokenExpiry = nvl($module->getConfig('token_expiration_seconds'), "2");

        if (date('Y-m-d H:i:s') >= $this->retAccount['tokenExpiry']) { // Если существующий токен пользователя протух
            $changeIt = false;
        } else { // Если ещё не протух, то смотрим config:
            $changeIt = nvl($module->getConfig('change_if_expired'), false);//
        }
        if ($changeIt == false) { // токен менять при любой смене логина
            $tokenData = TokenHelper::generateToken($tokenExpiry);
            try {
                UserToken::setToken($this->retAccount['UserID'], $tokenData['token'], $tokenData['expires']);
            } catch (Exception $e) {
                $this->_errorCode = SysSvcErrors::DB_ERROR;
                return false;
            }
            $this->retAccount['token'] = nvl($tokenData['token']);
            $this->retAccount['tokenExpiry'] = date('Y-m-d H:i:s', nvl($tokenData['expires']));
        }
        return true;
    }

    /**
     * Проверка и Создание аккаунта
     * @param
     * @returm bool
     */
    public function checkAndMake()
    {
        $flag = false;
        // Если аккаунты пользователя найдены
        if (nvl($this->stat['CNT'], 0) > 0) {
            if (empty($this->password)) {
                $flag = $this->emptyPasswordOperation();
            } else {
                if ($this->stat['HASH'] > 0) {
                    $flag = $this->filledPasswordOperation_WithHash();// Операции при переданном в UserAuth password и заполненном Hash
                } else {
                    $flag = $this->filledPasswordOperation_WithOutHash();// Операции при переданном в UserAuth password и пустом Hash
                }
            }
        } else {
            $this->errorCode = SysSvcErrors::ERROR_NOT_ACCOUNT;
        }

        // Если прошла авторизация - обновляется токен
        if ($flag) {
            $this->changeToken();
        }
        return $flag;
    }

    /**
     * Геттер возвращает ошибки объекта
     * @param
     * @returm int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Геттер возвращает набор аккаунтов пользователя
     * @param
     * @returm array
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * Геттер возвращает набор статистики по аккаунтам пользователя
     * @param
     * @returm array
     */
    public function getStat()
    {
        return $this->stat;
    }

    /**
     * Геттер возвращает аккаунт прошедший авторизацию
     * @param
     * @returm array
     */
    public function getRetAccount()
    {
        return $this->retAccount;
    }
}