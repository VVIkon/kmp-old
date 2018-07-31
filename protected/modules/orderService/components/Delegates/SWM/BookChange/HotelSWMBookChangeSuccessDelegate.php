<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/18/16
 * Time: 12:20 PM
 */
class HotelSWMBookChangeSuccessDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        // заворачиваем весь код в отелях
        if ($OrdersServices->getServiceType() == 1) {
            if (isset($params['modifyResult']) && isset($params['bookData'])) {
                if ($params['modifyResult'] == 0) {
                    $OrdersServiceHistory = new OrdersServicesHistory();
                    $OrdersServiceHistory->setObjectData($OrdersServices);
                    $OrdersServiceHistory->setOrderData($OrderModel);

                    //Если при изменении брони есть HotelReservation, Voucher и/или OrderDoc
                    $Offer = $OrdersServices->getOffer();
                    $HotelReservation = $Offer->getHotelReservation();
                    if ($HotelReservation) {
                        $HotelReservation->setStatus($HotelReservation::STATUS_DISABLED);    //Переводим бронь в статус = Отмена
                        if ($HotelReservation->hasHotelVoucher()) {
                            $HotelVouchers = $HotelReservation->getHotelVouchers();
                            foreach ($HotelVouchers as $HotelVoucher) {
                                $HotelVoucher->setVoucherStatus($HotelVoucher::STATUS_CHANGED);     //Если есть ваучер, то присваиваем статус = "CHANGED"
                                $documentId = $HotelVoucher->getDocumentId();
                                if (isset($documentId) && $documentId > 0) {
                                    $orderDocument = OrderDocumentRepository::getOrderDocumentByDocumentId($documentId);
                                    if (isset($orderDocument)) {
                                        $orderDocument->setFileName("[ОБМЕНЯН] " . $orderDocument->getFileName());     //Если к ваучеру привязан документ, то вставляем префикс "[ОБМЕНЯН]" в название файла
                                        if (!$orderDocument->save()) {
                                            $this->setError(OrdersErrors::DB_ERROR);
                                            return null;
                                        }
                                    }
                                }
                                if (!$HotelVoucher->save(false)) {
                                    $this->setError(OrdersErrors::DB_ERROR);
                                    return null;
                                }
                            }
                        }
                        if (!$HotelReservation->save(false)) {
                            $this->setError(OrdersErrors::DB_ERROR);
                            return null;
                        }
                    }

                    // поработаем с ценами
                    if (isset($params['bookData']['newSalesTerms']) && is_array($params['bookData']['newSalesTerms'])) {
                        $oldKmpPrice = $OrdersServices->getKmpPrice();

                        try {
                            $OrdersServices->setNewSalesTermsFromSSSalesTerm($params['bookData']['newSalesTerms']);
                        } catch (InvalidArgumentException $e) {
                            $this->setError($e->getCode());
                            $this->addLog('Ошибка обновлении цен в услуге', 'error', $params['bookData']['newSalesTerms']);
                        }

                        if (($oldKmpPrice < $OrdersServices->getKmpPrice()) && ($params['userProfile']['userType'] == 2)) {
                            // новая цена больше, нужно выставить счет на разницу
                            $AsyncTask = new AsyncTask();
                            $AsyncTask->setModule(Yii::app()->getModule('orderService'));
                            $AsyncTask->setTaskParams('orderService', 'SetInvoice', [
                                'userId' => $params['userProfile']['userId'],
                                'orderId' => $OrderModel->getOrderId(),
                                'currency' => $OrdersServices->getSaleCurrency()->getCode(),
                                'Services' => [
                                    [
                                        'serviceId' => $OrdersServices->getServiceID(),
                                        'invoicePrice' => round($OrdersServices->getKmpPrice() - $oldKmpPrice, 2)
                                    ]
                                ],
                                'usertoken' => $params['usertoken']
                            ]);

                            $this->setObjectToContext($AsyncTask);
                        } elseif (($oldKmpPrice = $OrdersServices->getKmpPrice()) || ($params['userProfile']['userType'] == 3)) {
                            // Если цена не изменилась, или услуга заявки корпоратора
                            $AsyncTask = new AsyncTask();
                            $AsyncTask->setModule(Yii::app()->getModule('orderService'));

                            $PayFinishParams = [
                                'action' => 'PayFinish',
                                'orderId' => $OrderModel->getOrderId(),
                                'actionParams' => [
                                    'services' => [
                                        [
                                            'serviceId' => $OrdersServices->getServiceID(),
                                            'servicePaid' => 4
                                        ],
                                    ]
                                ],
                                'usertoken' => $params['usertoken']
                            ];
                            $AsyncTask->setTaskParams('orderService', 'OrderWorkflowManager', $PayFinishParams);
                            $this->setObjectToContext($AsyncTask);
                        }

                        // запишем аудит
                        $OrdersServiceHistory->setCommentTpl('{{148}} {{149}} {{dateStart}} {{150}} {{dateFinish}}. {{151}} {{newPrice}} {{newPriceCurrency}}');
                        $OrdersServiceHistory->setActionResult(0);
                        $OrdersServiceHistory->setCommentParams([
                            'dateStart' => StdLib::toRussianDate($params['serviceData']['orderService']['dateStart']),
                            'dateFinish' => StdLib::toRussianDate($params['serviceData']['orderService']['dateFinish']),
                            'newPrice' => round($OrdersServices->getKmpPrice(), 2),
                            'newPriceCurrency' => $OrdersServices->getSaleCurrency()->getCode()
                        ]);

                        $Offer = $OrdersServices->getOffer();
                        $Offer->addCurrency('local', CurrencyStorage::findByString(643));
                        $Offer->addCurrency('client', $OrderModel->getContract()->getCurrency());

                        $this->addResponse('newSalesTerms', $Offer->getSalesTerms()->getArray());
                    } else {
                        $OrdersServiceHistory->setCommentTpl('{{148}} {{149}} {{dateStart}} {{150}} {{dateFinish}}.');
                        $OrdersServiceHistory->setActionResult(0);
                        $OrdersServiceHistory->setCommentParams([
                            'dateStart' => StdLib::toRussianDate($params['serviceData']['orderService']['dateStart']),
                            'dateFinish' => StdLib::toRussianDate($params['serviceData']['orderService']['dateFinish']),
                        ]);
                    }

                    // сохраним данные услуги
                    $OrdersServices->setDateStart($params['serviceData']['orderService']['dateStart']);
                    $OrdersServices->setDateFinish($params['serviceData']['orderService']['dateFinish']);

                    $Offer = $OrdersServices->getOffer();
                    $Offer->setDateFrom($params['serviceData']['orderService']['dateStart']);
                    $Offer->setDateTo($params['serviceData']['orderService']['dateFinish']);
                    if (!$Offer->save(false)) {
                        $this->setError(OrdersErrors::DB_ERROR);
                        return null;
                    }

                    if (!$OrdersServices->save(false)) {
                        $this->setError(OrdersErrors::DB_ERROR);
                        return null;
                    }
                    $this->params['object'] = $OrdersServices->serialize();

                    $this->addOrderAudit($OrdersServiceHistory);
                }
            } else {
                $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
                $this->addLog('Некорректный запуск делегата или не определен параметр modifyResult или bookData', 'error');
                return null;
            }
        }
    }
}