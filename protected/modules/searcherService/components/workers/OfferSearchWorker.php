<?php

/**
 * Class OfferSearchWorker
 * Базовый класс для выполнения процесса поиска предложений
 */
class OfferSearchWorker extends GearmanWorker
{
    private $module;

    private $namespace;

    const AVIA_SEARCH = 'aviaSearcher';
    const ACCOMMODATION_SEARCH = 'accommodationSearcher';
    const ACCOMMODATION_SEARCH_BY_SUPPLIER = 'accommodationSearcherBySupplier';

    const MESSAGE_SEARCH_START = 20001;
    const MESSAGE_SEARCH_STOP = 20002;

    public function __construct($module)
    {
        parent::__construct();

        $this->init($module);
    }

    public function init($module)
    {
        $this->module = $module;
        $this->namespace = $module->getConfig('log_namespace');
        $gearmanConfig = $module->getConfig('gearman');

        if (empty($gearmanConfig)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                SearcherErrors::INCORRECT_GEARMAN_CONFIG
            );
        }

        if (empty($this->namespace)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                SearcherErrors::INCORRECT_GEARMAN_CONFIG //здесь было CON, но нет такой ошибки =)
            );
        }

        $this->addServer($gearmanConfig['host'], $gearmanConfig['port']);

        return true;
    }

    /**
     * Выполнение поиска
     * @param GearmanJob $job
     * @return bool
     */
    public function doSearch(GearmanJob $job)
    {
        return false;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->errorCode;
    }

    /**
     * Получить получить название функции ворекра по типу запрашиваемых предложений
     * @param $offerType int
     * @param $prefix string
     * @return string
     */
    public static function getSearchFunctionByTypeOffer($offerType, $prefix = '')
    {
        switch ($offerType) {
            case SearchRequestsFactory::FLIGHT_REQUEST_TYPE:
                return $prefix . '_' . self::AVIA_SEARCH;
                break;

            case SearchRequestsFactory::ACCOMMODATION_REQUEST_TYPE:
                return $prefix . '_' . self::ACCOMMODATION_SEARCH;
                break;
        }
    }

    public function __get($name)
    {
        switch ($name) {
            case 'namespace' :
                return $this->namespace;
                break;
        }
    }
}
