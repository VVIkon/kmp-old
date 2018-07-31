<?php

class TouristsManager
{
    /**
     * Ссылка на объект модуля
     * @var object
     */
    private $module;

    /**
     * Код ошибки
     * @var int
     */
    private $errorCode;
    /**
     * namespace для записи логов
     * @var string
     */
    private $namespace;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        if (empty($module)) {
            return false;
        }

        $this->module = $module;
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Обёртка для получения списка заявок в развёрнутом виде
     * @param $params array фильтры
     * @return array заявки
     */
    public function addTourists($params)
    {
//        if (!$this->checkAddTouristsParams($params)) {
//            return false;
//        }

        foreach ($params['tourist'] as $tourist) {
            $tourist['orderId'] = $params['orderId'];
            if (!$this->checkAddTouristConditions($tourist)) {
                return false;
            }
        }

        $results = [];
        foreach ($params['tourist'] as $tourist) {
            $tourist['orderId'] = $params['orderId'];
            $addResult = $this->addTourist($tourist);
            if (!$addResult) {
                return false;
            } else {
                $results[] = $addResult;
            }

        }

        return $results;
    }

    /**
     * Добавление нового туриста в заявку
     * или изменение данных существующего туриста
     * @param $params
     * @return bool
     */
    public function addTourist($params)
    {
        $cxtName = $this->module->getCxtName(get_class($this), __FUNCTION__);

        $err = $this->module;

        $ordersMgr = $this->module->OrdersMgr($this->module);

        $filtersManager = new CommandFiltersManager();
        $params = $filtersManager->applySetTouristToOrderFilters($params);

        $result = $ordersMgr->setOrderTourist($params);

        if (!$result) {
            $this->errorCode = $ordersMgr->getLastError();
            return false;
        }

        return $result;
    }

    /**
     * Проверка условий добавления туриста
     * @param $params
     * @return bool
     */
    private function checkAddTouristConditions($params)
    {
        $touristValidator = $this->module->TouristsValidator($this->module);

        try {
            if(isset($params['touristId']) && !$params['touristId']){
                if (!$touristValidator->checkTouristCreationParams($params)) {
                    $this->errorCode = $touristValidator->getLastError();
                    return false;
                }
            }

            if (!$touristValidator->checkTouristLinkedToOrder($params)) {
                $this->errorCode = $touristValidator->getLastError();
                return false;
            }

            if (!$touristValidator->checkTouristLinkedServices($params)) {
                $this->errorCode = $touristValidator->getLastError();
                return false;
            }

            if (!$touristValidator->checkTouristUnlinkedServices($params)) {
                $this->errorCode = $touristValidator->getLastError();
                return false;
            }
        } catch (KmpException $ke) {
            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $ke->getCode();
            return false;
        }

        return true;
    }

    /**
     * Проверка общих параметров запроса добавления туристов
     * @param $params
     */
    private function checkAddTouristsParams($params)
    {
        $touristValidator = $this->module->TouristsValidator($this->module);

        try {
            $touristValidator->checkAddTouristsCommonParams($params);
        } catch (KmpInvalidArgumentException $kae) {
            LogHelper::logExt(
                $kae->class,
                $kae->method,
                $this->module->getCxtName($kae->class, $kae->method),
                $this->module->getError($kae->getCode()),
                $kae->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $kae->getCode();
            return false;
        }
        return true;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->errorCode;
    }

}
