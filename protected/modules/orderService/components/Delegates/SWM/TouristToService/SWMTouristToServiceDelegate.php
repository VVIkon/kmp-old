<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/29/16
 * Time: 1:08 PM
 */
class SWMTouristToServiceDelegate extends AbstractDelegate
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

        $result = [];

        // сначала всех отцепим
        foreach ($params['touristData'] as $touristData) {
            if (!$touristData['link']) {
                $this->addLog('Удаление туриста из услуги', 'info', $touristData);

                $linkingResult = [
                    'serviceId' => $OrdersServices->getServiceID(),
                    'link' => 0,
                    'touristId' => $touristData['touristId'],
                    'success' => 0,
                    'error' => '',
                    'errorCode' => ''
                ];

                try {
                    $OrdersServicesHistory = new OrdersServicesHistory();
                    $OrdersServicesHistory->setOrderData($OrderModel);
                    $OrdersServicesHistory->setObjectData($OrdersServices);

                    $Tourist = $OrdersServices->detachTourist($touristData['touristId']);

                    $OrdersServicesHistory->setCommentTpl("({{serviceName}}) {{157}} {{FIO}}");
                    $OrdersServicesHistory->setCommentParams([
                        'serviceName' => $OrdersServices->getServiceName(),
                        'FIO' => (string)$Tourist
                    ]);
                    $OrdersServicesHistory->setActionResult(0);
                    $this->addOrderAudit($OrdersServicesHistory);

                    $linkingResult['success'] = 1;
                } catch (ServiceTouristException $e) {
                    $linkingResult['error'] = ErrorHelper::getErrorDescription(Yii::app()->getModule('orderService'), $e->getMessage());
                    $linkingResult['errorCode'] = $e->getMessage();
                } catch (Exception $e) {
                    $linkingResult['error'] = ErrorHelper::getErrorDescription(Yii::app()->getModule('orderService'), $e->getMessage());
                    $linkingResult['errorCode'] = $e->getMessage();
                }

                $result[] = $linkingResult;
            }
        }

        // потом всех прицепим
        foreach ($params['touristData'] as $touristData) {
            if ($touristData['link']) {
                $this->addLog('Добавление туриста в услугу', 'info', $touristData);

                $linkingResult = [
                    'serviceId' => $OrdersServices->getServiceID(),
                    'link' => 1,
                    'touristId' => $touristData['touristId'],
                    'success' => 0,
                    'error' => '',
                    'errorCode' => ''
                ];

                // найдем туриста
                $OrderTourist = OrderTourist::model()->findByPk($touristData['touristId']);

                // если вообще нет такого туриста, то пропустим
                if (is_null($OrderTourist)) {
                    $linkingResult['error'] = ErrorHelper::getErrorDescription(Yii::app()->getModule('orderService'), OrdersErrors::INCORRECT_TOURIST_ID);
                    $linkingResult['errorCode'] = OrdersErrors::INCORRECT_TOURIST_ID;
                    $result[] = $linkingResult;
                    continue;
                } else {
                    $Tourist = $OrderTourist->getTourist();
                }

                // создадим аудит
                $OrdersServicesHistory = new OrdersServicesHistory();
                $OrdersServicesHistory->setOrderData($OrderModel);
                $OrdersServicesHistory->setObjectData($OrdersServices);

                $OrdersServicesHistory->setCommentParams([
                    'serviceName' => $OrdersServices->getServiceName(),
                    'FIO' => (string)$Tourist
                ]);

                // пробуем присоединить туриста
                try {
                    $OrdersServices->addTourist($OrderTourist, $touristData);
                    $OrdersServicesHistory->setCommentTpl("{{156}} {{FIO}}");
                    $linkingResult['success'] = 1;
                } catch (ServiceTouristException $e) {
                    $linkingResult['error'] = ErrorHelper::getErrorDescription(Yii::app()->getModule('orderService'), $e->getMessage());
                    $linkingResult['errorCode'] = $e->getMessage();
                    $OrdersServicesHistory->setCommentTpl("{{158}} ({{serviceName}}), {$linkingResult['error']}");
                } catch (Exception $e) {
                    $linkingResult['error'] = ErrorHelper::getErrorDescription(Yii::app()->getModule('orderService'), $e->getMessage());
                    $linkingResult['errorCode'] = $e->getMessage();
                    $OrdersServicesHistory->setCommentTpl("{{158}} ({{serviceName}}), {$linkingResult['error']}");
                }

                // если возникла ошибка - вытащим данные для аудита
                $OrdersServicesHistory->setActionResult(!$linkingResult['success']);

                $this->addOrderAudit($OrdersServicesHistory);

                $result[] = $linkingResult;
            }
        }

        $this->addResponse('result', $result);
    }
}