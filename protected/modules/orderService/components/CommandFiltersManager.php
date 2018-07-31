<?php

/**
 * Class CommandFiltersManager
 * Класс для установки фильтров для выполняемых команд в зависимости от прав пользователя
 */
class CommandFiltersManager
{
    /**
     * Код ошибки
     * @var int
     */
    private $_errorCode;

    /**
     * namespace для записи логов
     * @var string
     */
    private $namespace;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct()
    {
        $this->namespace = 'system.orderservice';
    }

    /**
     * Фильтрация параметров для принудительного выставления ид агенства пользователя
     * @param $params
     * @return bool
     */
    public function applyOrdersListFilters($params)
    {
        $accountsmodule = YII::app()->getModule('systemService')->getModule('account');
        $accountsMgr = $accountsmodule->AccountsMgr($accountsmodule);

        $role = $accountsMgr->getCurrentUserRole();
        if (!$accountsMgr->isUserKMPWorker($role)) {
            $account = $accountsMgr->getCurrentUserProfile();
            if (UserAccess::hasPermissions([41])) {
                $params['agencyId'] = $account['companyID'];
            } else {
                $params['agencyId'] = $account['companyID'];
                $params['userId'] = $account['userId'];         //KT-2551
            }
        }
        return $params;
    }

    /**
     * Фильтрация параметров, удалить признак турлидера
     * если турлидер уже существует в заявке
     * @param $params
     */
    public function applySetTouristToOrderFilters($params)
    {

        if (empty($params['tourist']['isTourLeader'])) {
            return $params;
        }

        $tourists = TouristForm::getTouristsByOrderId($params['orderId']);
        foreach ($tourists as $tourist) {
            if ($tourist['TourLeader'] == 1 && $tourist['TouristID'] != $params['tourist']['touristId']) {
                $params['tourist']['isTourLeader'] = 0;
            }
        }

        return $params;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->_errorCode;
    }

}

