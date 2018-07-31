<?php

/**
 *
 */
class AsyncTask implements Serializable
{
    protected $serviceName;
    protected $action;
    protected $params = [];
    protected $gearmanConf = [];

    public function setModule($yiiModule)
    {
        if (!$yiiModule) {
            throw new AsyncTaskException('Неверный запуск AsyncTask, не задан сервис');
        }
        $serviceConfig = $yiiModule->getConfig();

        if (!empty($serviceConfig['gearman'])) {
            $this->gearmanConf = $serviceConfig['gearman'];
        } else {
            throw new AsyncTaskException('Не настроен gearman');
        }
    }

    public function setTaskParams($serviceName, $action, $params = [])
    {
        $this->serviceName = $serviceName;
        $this->action = $action;
        $this->params = $params;
    }

    /**
     * @return mixed
     */
    public function serialize()
    {
        return serialize([
            $this->serviceName,
            $this->action,
            $this->params,
            $this->gearmanConf
        ]);
    }

    /**
     * @param string $serialized
     * @return mixed
     */
    public function unserialize($serialized)
    {
        list(
            $this->serviceName,
            $this->action,
            $this->params,
            $this->gearmanConf
            ) = unserialize($serialized);
    }

    public function run()
    {
        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'Запуск асинхронной задачи', '',
            [
                'serviceName' => $this->serviceName,
                'action' => $this->action,
                'params' => $this->params,
            ],
            LogHelper::MESSAGE_TYPE_INFO, 'system.orderservice.info'
        );

        // создадим GearmanClient и запустим
        $GearmanClient = new GearmanClient();
        $GearmanClient->addServer($this->gearmanConf['host'], $this->gearmanConf['port']);
        $GearmanClient->doBackground("{$this->gearmanConf['workerPrefix']}_asyncTask", json_encode([
            'serviceName' => $this->serviceName,
            'action' => $this->action,
            'params' => $this->params,
        ]));
    }
}