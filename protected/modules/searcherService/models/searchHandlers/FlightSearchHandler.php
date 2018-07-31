<?php

/**
 * Class FlightSearchHandler
 * Класс для поиска предложения по авиаперелёту
 */
class FlightSearchHandler extends SearchHandler
{
    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        parent::__construct($module);
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Запуск задания поиска
     * @param $token
     * @param int $agentId ID пользователя, совершившего запрос
     * @return bool
     */
    public function startSearch($token, $agentId)
    {
        $request = new FlightSearchRequest($this->module, SearchRequestsFactory::FLIGHT_REQUEST_TYPE);

        try {
            $request->loadFromCache($token);
        } catch (KmpDbException $kde) {
            LogHelper::logExt(
                $kde->class, $kde->method,
                $this->module->getCxtName($kde->class, $kde->method),
                $this->module->getError($kde->getCode()) . PHP_EOL . $kde->getDbMessage(),
                $kde->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            return false;
        }

        $request->agentId = $agentId;

        $progress = 0;
        OfferFinder::setSearchRequestTaskPercentComplete($token, $progress);
        $request->setProviders($this->getOfferProviders());
        //$request = $this->parsePostConditionRules($request);

        $offers = [];
        $existedOffers = [];
        $providersCount = count($request->providers);

        foreach ($request->providers as $providerType) {
            $provider = ProvidersFactory::createOfferProvider($providerType);

            if (!$provider) {
                continue;
            }

            $offersPortions = $this->getPortionsConfig($providerType);

            if (!empty($offersPortions['first']) && is_int($offersPortions['first'])) {
                $minimalRequest = clone $request;
                $minimalRequest->offerLimit = $offersPortions['first'];
                $existedOffers = $this->getMinimalOffersPortion($minimalRequest, $provider, $token);
                $progress = 100 / $providersCount * 0.3;
                OfferFinder::setSearchRequestTaskPercentComplete($token, $progress);
            }

            $providerOffers = $this->search($request, $provider);
            $offers = array_merge($offers, $providerOffers);
        }

        $offers = $this->excludeExistedOffers($offers, $existedOffers);
//        $offers = $this->parsePostConditionRules($offers);
        $this->bulkSaveOffers($offers, $token);

        $progress = ceil(100 / $providersCount) * 1;
        OfferFinder::setSearchRequestTaskPercentComplete($token, $progress);

        return true;
    }

    /**
     * Получить минимальную порцию результатов поиска
     * @param $request
     * @param $provider
     * @param $token
     * @return array
     */
    protected function getMinimalOffersPortion($request, $provider, $token)
    {
        $providerOffers = $this->search($request, $provider);

        $providerOffers = $this->parsePostConditionRules($providerOffers);

        if (empty($providerOffers)) {
            return [];
        }

        foreach ($providerOffers as $key => $offer) {
            $offer->toCache($token);
        }

        return $providerOffers;
    }

    /**
     * Запуск поиска на указанном провайдере
     * @param $provider
     * @param $request
     */
    protected function search($request, $provider)
    {

        try {
            $providerOffers = $provider->find($request);
            $this->validateOffers($providerOffers);

        } catch (KmpInvalidArgumentException $ke) {
            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
        }

        $offers = [];

        if (empty($providerOffers)) {
            return $offers;
        }

        foreach ($providerOffers as $providerOffer) {
            $offer = new FlightProviderOffer($this->module, SearchHandlersFactory::FLIGHT_OFFER_TYPE);
            $providerOffer['providerId'] = ProvidersFactory::getOfferProviderIdByClassName(get_class($provider));

            $providerOffer['supplierCode'] = $offer->getSupplierCode(
                $providerOffer['supplierCode'],
                $providerOffer['providerId']
            );

            $offer->initParams($providerOffer);
            $offers[] = $offer;
        }

        return $offers;
    }

    /**
     * Парсинг и применение предусловий
     * @param $request
     * @return mixed
     */
    protected function parsePreConditionRules($request)
    {
        $modifiedRequest = $request;
        $rules = $this->getSearchRules();

        foreach ($rules as $rule) {
            $modifiedRequest = $this->applySearchRule($rule, $modifiedRequest);
        }
        return $modifiedRequest;
    }

    /**
     * Парсинг и применение постусловий
     * @param $offers
     * @return mixed
     */
    protected function parsePostConditionRules($offers)
    {
        if (empty($offers)) {
            return false;
        }

        $modifiedOffers = [];
        foreach ($offers as $offer) {
            $modifiedOffers[] = clone $offer;
        }

        $rules = $this->getSearchRules();
        foreach ($modifiedOffers as $key => $modifiedOffer) {

            foreach ($rules as $rule) {
                $modifiedOffer = $this->applySearchRule($rule, $modifiedOffer);
            }

//          todo Убрать после реализации обработки результатов поиска с типом поставщика - multipleGDS
            if ($modifiedOffer->offerType = SearchRequestsFactory::FLIGHT_REQUEST_TYPE) {
                if ($modifiedOffer->supplierCode == 'multipleGDS') {
                    unset($modifiedOffers[$key]);
                    continue;
                }
            }
//

        }

        return $modifiedOffers;
    }

    /**
     * Применить правило предусловий
     * @param $rule
     * @param $params
     * @return mixed
     */
    protected function applySearchRule($rule, $request)
    {
        return $request;
    }

    protected function excludeExistedOffers($offers, $existedOffers)
    {

        $processedOffers = [];

        foreach ($offers as $key => $offer) {

            $found = false;
            foreach ($existedOffers as $key => $existedOffer) {
                if ($offer->isEqual($existedOffer)) {
                    unset($existedOffers[$key]);
                    $found = true;
                    break;
                }
            }

            if ($found == false) {
                $processedOffers[] = $offer;
            }

        }

        return $processedOffers;
    }

    /**
     * Массовое сохранение предложений
     * @param $offers
     */
    protected function bulkSaveOffers($offers, $token)    {
        if (empty($offers)) {
            return [];
        }

        foreach ($offers as $offer) {
            $offer->toCache($token);
        }
    }

    /**
     * Массовое сохранение объектов
     * @param $tableName
     * @param $arrayValues
     */
    protected function bulkSave($tableName, $arrayValues)
    {
        $builder = Yii::app()->db->schema->commandBuilder;

        $command = $builder->createMultipleInsertCommand($tableName, $arrayValues);
        $command->execute();
    }

    /**
     * Валидация предложений полученных от провайдера
     */
    protected function validateOffers()
    {

    }
}
