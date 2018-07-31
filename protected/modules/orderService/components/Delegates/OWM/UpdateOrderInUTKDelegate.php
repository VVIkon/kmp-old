<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/11/16
 * Time: 1:58 PM
 */
class UpdateOrderInUTKDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        // проверим - если статус не изменился, то не синхронизируем заявку
        if(isset($params['statusChanged']) && $params['statusChanged'] === false){
            $this->addLog('Статус не изменился, заявку в УТК не отправляем');
            return;
        }

        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        $utkClient = new UtkClient(Yii::app()->getModule('orderService')->getModule('utk'));

        try {
            $utkOrder = new UtkOrder();
            $utkOrder->load($OrderModel->getOrderId());
            $utkOrderParams = $utkOrder->toArray();

            $this->addLog('Обновление заявки в УТК', 'info', $utkOrderParams);

            $result = $utkClient->makeRestRequest('order', $utkOrderParams);
        } catch (KmpException $ke) {
            return;
        }

        $this->addLog('Обновление заявки в УТК - Результат', 'info', $result);
    }
}