<?php

/**
 * Делегат логирования
 */
class LogDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_POST_ACTION;

    public function run(array $params)
    {
        $logData = $this->getLogArr();

        // проверим нужно ли что-то делать
        if ($logData) {
            $userId = 0;

            // добавим доп параметры для записи ID заявки и пользователя
            if (isset($params['userProfile']['userId'])) {
                $userId = $params['userProfile']['userId'];
            }

            $orderId = isset($params['orderId']) ? $params['orderId'] : 0;

            // если нашли структуру - запишем ее в БД
            foreach ($logData as $ss_Log) {
                $ss_Log['params']['userId'] = $userId;
                $ss_Log['params']['orderId'] = $orderId;

                LogHelper::logExt(
                    $ss_Log['class'],
                    $ss_Log['method'],
                    $ss_Log['actionDescr'],
                    $ss_Log['message'],
                    $ss_Log['params'],
                    $ss_Log['level'],
                    $ss_Log['category']
                );
            }
        }
    }
}