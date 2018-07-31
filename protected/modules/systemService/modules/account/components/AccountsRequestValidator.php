<?php

/**
 * Class AccountsRequestValidator
 * Класс для проверки корректности значений
 * в сервис аутентификации и авторизации
 */
class AccountsRequestValidator
{

    const ERROR_NONE = 0;
    const ERROR_LOGIN_INVALID = 2;
    const ERROR_PASSWORD_INVALID = 3;
    const ERROR_TOKEN_INVALID = 4;
    const ERROR_USER_ID_INVALID = 6;

    /**
     * Код ошибки
     * @var int
     */
    private $_errorCode;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module) {
        $this->_module = $module;
    }

    /**
     * Проверка входных параметров для авторизации
     * @param $params array входные параметры
     * @return bool|null
     */
    public function checkUserAuthParams($params) {

        if (empty($params['login'])) {
            $this->_errorCode = self::ERROR_LOGIN_INVALID;
            return false;
        }

        if (empty($params['password'])) {
            $this->_errorCode = self::ERROR_PASSWORD_INVALID;
            return false;
        }

        return true;
    }
    /**
     * Проверка логина для авторизации
     * @param $params array входные параметры
     * @return bool|null
     */
    public function checkUserLoginParams($params) {

        if (empty($params['login'])) {
            $this->_errorCode = self::ERROR_LOGIN_INVALID;
            return false;
        }

        return true;
    }

    /**
     * Проверка входных параметров
     * для получения данных пользователя
     * @param $params
     * @return bool
     */
    public function checkGetUserParams($params)
    {
        if (empty($params['userId'])) {
            $this->_errorCode = self::ERROR_USER_ID_INVALID;
            return false;
        }

        if (empty($params['token'])) {
            $this->_errorCode = self::ERROR_TOKEN_INVALID;
            return false;
        }
        return true;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError() {
        return $this->_errorCode;
    }

}