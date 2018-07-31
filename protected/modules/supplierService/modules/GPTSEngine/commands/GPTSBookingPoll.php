<?php

class GPTSBookingPoll extends CConsoleCommand
{

    public function init()
    {
        Yii::getLogger()->autoFlush = 1;
        Yii::getLogger()->autoDump = true;
    }

    /**
     * Запуск процесса проверки статуса бронирования из командной строки
     * @param string $jsprm параметры команды в JSON
     * @return bool
     */
    public function actionStartBookingPoll($orderId, $serviceId, $gptsOrderId)
    {
        $params = [
            'orderId' => $orderId,
            'serviceId' => $serviceId,
            'gptsOrderId' => $gptsOrderId
        ];

        YII::app()->getModule('supplierService')->getModule('GPTSEngine')->getEngine()->startBookingPoll($params);

        return true;
    }

    /**
     * Старт воркера для проверки статуса бронирования через gearman
     * @return bool
     */
    public function actionStartBookingPollGearman()
    {
        $module = YII::app()->getModule('supplierService');

        $worker = new BookingPollWorker($module);

        $config = $module->getConfig('gearman');

        $bookingPollFunction = $config['workerPrefix'] . '_' . BookingPollWorker::BOOKING_POLL_FUNCTION;

        $worker->addFunction($bookingPollFunction, [$worker, 'doPoll']);

        $worker->work();
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