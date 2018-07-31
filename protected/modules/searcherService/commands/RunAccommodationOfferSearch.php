<?php

/**
 * Class RunAccommodationOfferSearch
 * Класс для выполнения команды поиска предложений размещения
 */
class RunAccommodationOfferSearch extends CConsoleCommand
{
    public function init()
    {
        Yii::getLogger()->autoFlush = 1;
        Yii::getLogger()->autoDump = true;
    }

    /**
     * Запуск процесса поиска предложений из командной строки
     * @param $token
     * @return bool
     */
    public function actionStartSearch($token,$agentId)
    {
        $module = YII::app()->getModule('searcherService');
        $handler = new HotelSearchHandler($module);
        $handler->startSearch($token,$agentId);

        return true;
    }

    /**
     * Старт воркера для поиска авиапредложений через gearman
     * @return bool
     */
    public function actionStartSearchGearman()
    {
        $module = YII::app()->getModule('searcherService');

        $worker = new HotelsOfferSearchWorker($module);
        $config = $module->getConfig('gearman');

        $searchFunction = $config['workerPrefix']. '_' . OfferSearchWorker::ACCOMMODATION_SEARCH;

        $worker->addFunction($searchFunction, [$worker, 'doSearch']);

        while ($worker->work());

        return true;
    }

    /**
     * Запуск процесса поиска предложений из командной строки по указанному поставщику и шлюзу
     * @param $token
     * @return bool
     */
    public function actionStartSearchBySupplier($token, $agentId, $provider, $supplier, $percent)
    {
        $module = YII::app()->getModule('searcherService');
        $handler = new HotelSearchHandler($module);
        $handler->searchBySupplier($token, $agentId, $provider, $supplier, $percent);

        return true;
    }

    /**
     * Запуск процесса поиска предложений из командной строки по указанному поставщику и шлюзу
     * @param $token
     * @return bool
     */
    public function actionStartSearchBySupplierGearman()
    {
        $module = YII::app()->getModule('searcherService');

        $worker = new HotelsOfferSearchBySupplierWorker($module);
        $config = $module->getConfig('gearman');

        $searchFunction = $config['workerPrefix']. '_' . OfferSearchWorker::ACCOMMODATION_SEARCH_BY_SUPPLIER;

        $worker->addFunction($searchFunction, [$worker, 'doSearch']);

        while ($worker->work());

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
