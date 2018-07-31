<?php

/**
 * Модуль движка поставщика GPTS
 */
class GPTSEngineModule extends KModule
{
    public function init()
    {
        $this->setImport(array(
            'GPTSEngine.commands.*',
            'GPTSEngine.components.*',
            'GPTSEngine.components.serviceManagers.*',
            'GPTSEngine.components.serviceBookingPoll.*',
            'GPTSEngine.components.serviceValidators.*',
            'GPTSEngine.components.workers.*',
            'GPTSEngine.models.*',
            'GPTSEngine.models.GPTSApi.*',
            'GPTSEngine.models.GPTSApi.Listeners.*',
            'GPTSEngine.models.GPTSResponse.*',
            'GPTSEngine.models.Response.*',
            'GPTSEngine.models.Exception.*',
            'GPTSEngine.models.ServiceBookData.*',
            'GPTSEngine.models.ServiceModify.*'
        ));
    }

    /**
     * Отдает объект класса поставщика
     * @return GPTSSupplierEngine
     */
    public function getEngine()
    {
        return new GPTSSupplierEngine($this);
    }

    /**
     * Запуск асинхронной задачи опроса статуса бронирования оффера
     * @param int $supplierId ID поставщика
     * @param mixed[] $params параметры, необходимые для запуска процесса бронирования
     */
    public function runBookingPollTask($params)
    {
        // укажем количество попыток
        $params['bookingPollAttempts'] = YII::app()->getModule('supplierService')->getModule('GPTSEngine')->getConfig('bookingPollAttempts');

        // укажем время на 1 попытку
        $params['bookingPollAttemptTime'] = YII::app()->getModule('supplierService')->getModule('GPTSEngine')->getConfig('bookingPollAttemptTime');

        if (!$params['bookingPollAttempts']) {
            throw new Exception('Не указан параметр bookingPollAttempts в конфигурации GPTSEngine');
        }

        if (!$params['bookingPollAttemptTime']) {
            throw new Exception('Не указан параметр bookingPollAttemptTime в конфигурации GPTSEngine');
        }

        try {
            if (EnvironmentHelper::getEnvironmentType() == EnvironmentHelper::LOCAL) {
                $this->runBookingPollTaskAsConsoleCommand($params);
            } else {
                $this->runBookingPollTaskAsGearmanJob($params);
            }
        } catch (Exception $e) {
            throw new KmpException(
                get_class(), __FUNCTION__,
                SupplierErrors::CANNOT_START_BOOKING_POLL_TASK,
                ['exception' => $e->getMessage()]
            );
        }

        return true;
    }

    /**
     * Запуск задачи опроса статуса бронирования оффера как консольной команды
     * @param mixed[] $params параметры, необходимые для запуска процесса бронирования
     * @return bool
     */
    private function runBookingPollTaskAsConsoleCommand($params)
    {
        $orderId = $params['orderId'];
        $serviceId = $params['serviceId'];
        $gptsOrderId = $params['gptsOrderId'];

        $command = 'GPTSBookingPoll';

        if (PHP_OS == 'WINNT') {
            $cmd = YII::app()->basePath . "/yiic.bat $command startbookingpoll --orderId=$orderId --serviceId=$serviceId --gptsOrderId=$gptsOrderId";
            pclose(popen("start /B " . $cmd, "r"));
        } else {
            $cmd = YII::app()->basePath . "/yiic $command startbookingpoll --orderId=$orderId --serviceId=$serviceId --gptsOrderId=$gptsOrderId";
            exec($cmd . " > /dev/null &");
        }
    }

    /**
     * Запуск задачи опроса статуса бронирования оффера как задания gearman
     * @param mixed[] $params параметры, необходимые для запуска процесса бронирования
     */
    private function runBookingPollTaskAsGearmanJob($params)
    {
        if (!extension_loaded('gearman')) {
            throw new KmpException(get_class(), __FUNCTION__,
                SupplierErrors::CANNOT_CREATE_GEARMAN_CLIENT, ['params' => json_encode($params)]);
        }
        $gearmanClient = new GearmanClient();

        $config = YII::app()->getModule('supplierService')->getConfig('gearman');
        if (!$config) {
            throw new KmpInvalidArgumentException(get_class(), __FUNCTION__,
                SupplierErrors::INCORRECT_GEARMAN_CONFIG, ['params' => json_encode($params)]);
        }

        $gearmanClient->addServer($config['host'], $config['port']);

        $bookingPollFunction = $config['workerPrefix'] . '_' . BookingPollWorker::BOOKING_POLL_FUNCTION;

        $job = $gearmanClient->doBackground(
            $bookingPollFunction,
            json_encode($params)
        );

        if (empty($job)) {
            throw new KmpException(get_class(), __FUNCTION__,
                SupplierErrors::CANNOT_START_BOOKING_POLL_TASK, ['params' => json_encode($params)]);
        }

        LogHelper::logExt(
            get_class($this),
            __FUNCTION__,
            $this->getCxtName(get_class($this), __FUNCTION__),
            '',
            [
                'jobHandle' => $job,
                'function' => $bookingPollFunction,
                'params' => $params
            ],
            LogHelper::MESSAGE_TYPE_INFO,
            $this->getConfig('log_namespace') . '.gearman.client'
        );

    }


}

?>
