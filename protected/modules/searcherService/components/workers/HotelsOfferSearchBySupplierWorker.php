<?php

/**
 * Class HotelsOfferSearchBySupplierWorker
 * Класс для выполнения процесса поиска предложений размещения по указанному провайдеру
 */
class HotelsOfferSearchBySupplierWorker extends OfferSearchWorker
{

    public function doSearch(GearmanJob $job)
    {

        $workload = json_decode($job->workload(), true);

        $module = YII::app()->getModule('searcherService');
        $config = $module->getConfig('gearman');


        $searchFunction = $config['workerPrefix'] . '_' . OfferSearchWorker::ACCOMMODATION_SEARCH_BY_SUPPLIER;

        LogHelper::logExt(get_class($this), __FUNCTION__,
            $module->getCxtName(get_class($this), __FUNCTION__),
            $module->getMessage(OfferSearchWorker::MESSAGE_SEARCH_START),
            ['token' => $workload['token'], 'agentId' => $workload['agentId'], 'searchType' => $searchFunction],
            LogHelper::MESSAGE_TYPE_INFO,
            $this->namespace. '.gearman.worker'
        );

        $handler = new HotelSearchHandler($module);
        $handler->searchBySupplier($workload['token'], $workload['agentId'], $workload['provider'],
        $workload['supplier'], $workload['percent']);

        LogHelper::logExt(get_class($this), __FUNCTION__,
            $module->getCxtName(get_class($this), __FUNCTION__),
            $module->getMessage(OfferSearchWorker::MESSAGE_SEARCH_STOP),
            ['token' => $workload['token'], 'agentId' => $workload['agentId'], 'searchType' => $searchFunction],
            LogHelper::MESSAGE_TYPE_INFO,
            $this->namespace. '.gearman.worker'
        );

        return true;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError() {
        return $this->errorCode;
    }
}
