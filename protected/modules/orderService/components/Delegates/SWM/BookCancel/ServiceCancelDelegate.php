<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/4/16
 * Time: 4:21 PM
 */
class ServiceCancelDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        // восстановим объект сервиса
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        $OrdersServicesHistory = new OrdersServicesHistory();
        $OrdersServicesHistory->setOrderData($OrderModel);
        $OrdersServicesHistory->setObjectData($OrdersService);

        $Offer = $OrdersService->getOffer();

        // если поставщик поддерживает отмену
        if ($Offer->hasCancelAbility()) {
            $serviceCancelParams = [
                'serviceType' => $OrdersService->getServiceType(),
                'gateId' => $OrdersService->getSupplierID(),
                'bookData' => $OrdersService->getOffer()->getBookData(),
                'usertoken' => $params['usertoken']
            ];

            // сделаем запрос сервис букинг
            $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
            $serviceCancelResponse = json_decode($apiClient->makeRestRequest('supplierService', 'SupplierServiceCancel', $serviceCancelParams), true);

            $this->addLog('Ответ сервиса поставщиков на отмену услуги', 'info', $serviceCancelResponse);

            if ($serviceCancelResponse['status']) { // если ошибка ответа
                $this->params['serviceCancelled'] = false;
                $OrdersServicesHistory->setCommentTpl('{{139}}');
                $OrdersServicesHistory->setActionResult(1);
                $this->addLog('Ошибка ответа, услуга не отменена');
            } elseif (isset($serviceCancelResponse['body'])) { // если успех
                // если упешная отмена
                if ($serviceCancelResponse['body']['cancelResult'] == 0) {
                    $this->params['serviceCancelled'] = true;
                    $OrdersServicesHistory->setCommentTpl('{{138}}');
                    $OrdersServicesHistory->setActionResult(0);
                    $this->addLog('Бронирование отменено');
                } elseif ($serviceCancelResponse['body']['cancelResult'] == 1) { // если пришли новые штрафы
                    $this->addLog('Пришли новые штрафы в услугу ' . $OrdersService->getServiceName(), 'info', $serviceCancelResponse['body']['newPenalties']);

                    $this->params['serviceCancelled'] = false;
                    $OrdersServicesHistory->setActionResult(1);
                    $this->params['newPenalties'] = $serviceCancelResponse['body']['newPenalties'];
                }
            }
        } else {
            $account = AccountRepository::getAccountById($params['userProfile']['userId']);

            $this->addNotificationTemplate('manager', [
                'comment' => 'Пользователь ' . (string)$account . ' запросил отмену брони. 
                                        Оператор должен выполнить отмену брони вручную т.к. поставщик не поддерживает онлайн-отмену. 
                                        Счет на возможный штраф выставляется оператором вручную.'
            ]);

            $this->addResponse('runOWMManual', true);
            $this->breakProcess();
        }

        $OrdersServicesHistory->setCommentParams([]);
        $this->addOrderAudit($OrdersServicesHistory);
    }
}