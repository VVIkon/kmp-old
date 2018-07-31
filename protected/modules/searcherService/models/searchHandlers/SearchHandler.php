<?php

/**
 * Class SearchHandler
 * Базовый класс для поиска предложения
 */
class SearchHandler extends KFormModel
{
    /**
     * namespace для записи логов
     * @var string
     */
    protected $namespace;

    /**
     * Используется для хранения ссылки на текущий модуль
     * @var object
     */
    protected $module;

    /**
     * Токен для идентификации поискового запроса
     * @var
     */
    protected $requestToken;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        parent::__construct();
        $this->module = $module;
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    protected function getPortionsConfig($providerType) {

        $gateways = $this->module->getConfig('gateways');

        if (empty($gateways) || count($gateways) == 0) {
            throw new KmpInvalidSettingsException(
                get_class($this),
                __FUNCTION__,
                SearcherErrors::INCORRECT_GATEWAYS_CONFIG,
                get_class($this)
            );
        }

        if (isset($gateways[$providerType]) &&
            !empty($gateways[$providerType]['firstOffersPortion']) &&
            !empty($gateways[$providerType]['maxOffersPortion'])
        )
        {
            return [
                'first' => $gateways[$providerType]['firstOffersPortion'],
                'max' => $gateways[$providerType]['maxOffersPortion']
            ];
        }
    }

    /**
     * Запуск задания поиска
     * @param $token
     * @return bool
     */
    public function startSearch($token,$agentId)
    {
        return true;
    }

    /**
     * Парсинг предусловий предложения
     * @param $request
     */
    protected function parsePreCondition($request)
    {

    }

    /**
     * Получить правила выполнения поиска указанного типа предложения
     * @return array
     */
    protected function getSearchRules()
    {

        $offerType = OfferFindersFactory::getOfferTypeByClassName(get_class($this));
        return [];
    }

    /**
     * Применить поисковое правило к параметрам поиска
     * @param $rule
     * @param $request
     */
    protected function applySearchRule($rule, $request)
    {

    }

    /**
     * Получение провайдеров для поиска предложений
     * @return mixed
     */
    protected function getOfferProviders()
    {
        $gateways = $this->module->getConfig('gateways');
        $offerGateWays = $this->module->getConfig('offerGateWays');

        $offerType = SearchHandlersFactory::getSearchHandlerTypeByClassName(get_class($this));
        $offerGateways = $offerGateWays[$offerType];

        if (empty($offerGateways) || count($offerGateways) == 0) {
            throw new KmpInvalidSettingsException(
                get_class($this),
                __FUNCTION__,
                SearcherErrors::INCORRECT_GATEWAYS_CONFIG,
                get_class($this)
            );
        }
        return $offerGateways;
    }

    /**
     * Получение поставщиков для поиска предложений
     * @return mixed
     */
    protected function getOfferSuppliers()
    {
        $offersSuppliers = $this->module->getConfig('offerSuppliers');

        $offerType = SearchHandlersFactory::getSearchHandlerTypeByClassName(get_class($this));

        $suppliers = $offersSuppliers[$offerType];

        if (empty($suppliers) || count($suppliers) == 0) {
            throw new KmpInvalidSettingsException(
                get_class($this),
                __FUNCTION__,
                SearcherErrors::INCORRECT_GATEWAYS_CONFIG,
                get_class($this)
            );
        }
        return $suppliers;
    }
}
