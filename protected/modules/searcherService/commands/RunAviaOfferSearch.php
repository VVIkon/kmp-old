<?php

/**
 * Class RunAviaOfferSearch
 * Класс для выполнения команды поиска предложений авиабилетов
 */
class RunAviaOfferSearch extends CConsoleCommand
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
    public function actionStartSearch($token, $agentId)
    {
        $module = YII::app()->getModule('searcherService');
        $handler = new FlightSearchHandler($module);

        $handler->startSearch($token, $agentId);

        return true;
    }

    /**
     * Старт воркера для поиска авиапредложений через gearman
     * @return bool
     */
    public function actionStartSearchGearman()
    {
        $module = YII::app()->getModule('searcherService');

        $worker = new AviaOfferSearchWorker($module);
        $config = $module->getConfig('gearman');

        $searchFunction = $config['workerPrefix'] . '_' . OfferSearchWorker::AVIA_SEARCH;

        $worker->addFunction($searchFunction, [$worker, 'doSearch']);

        while ($worker->work()) ;

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
