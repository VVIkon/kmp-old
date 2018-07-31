<?php

/**
 * Class BookingPollWorker
 * Базовый класс для выполнения процесса поиска предложений
 */
class BookingPollWorker extends GearmanWorker
{
    private $module;

    private $namespace;

    const BOOKING_POLL_FUNCTION = 'bookingPoll';

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
                SupplierErrors::INCORRECT_GEARMAN_CONFIG,
                []
            );
        }

        if (empty($this->namespace)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                SupplierErrors::INCORRECT_GEARMAN_CONFIG,
                []
            );
        }

        $this->addServer($gearmanConfig['host'], $gearmanConfig['port']);

        return true;
    }

    /**
     * Выполнение опроса статуса брони
     * @param GearmanJob $job
     * @return bool
     */
    public function doPoll(GearmanJob $job)
    {
        $workload = json_decode($job->workload(), true);

        $module = YII::app()->getModule('supplierService');

        LogHelper::logExt(get_class($this), __FUNCTION__,
            'Старт опроса бронирования',
            '',
            $workload,
            LogHelper::MESSAGE_TYPE_INFO,
            $this->namespace . '.gearman.worker'
        );

        $engine = YII::app()->getModule('supplierService')->getModule('GPTSEngine')->getEngine();
        $engine->startBookingPoll($workload);

//        LogHelper::logExt(get_class($this), __FUNCTION__,
//            $module->getCxtName(get_class($this), __FUNCTION__),
//            $module->getMessage(BookingPollWorker::MESSAGE_SEARCH_STOP),
//            $workload,
//            LogHelper::MESSAGE_TYPE_INFO,
//            $this->namespace . '.gearman.worker'
//        );

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


    public function __get($name)
    {
        switch ($name) {
            case 'namespace' :
                return $this->namespace;
                break;
        }
    }
}
