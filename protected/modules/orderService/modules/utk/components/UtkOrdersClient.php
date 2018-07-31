<?php

/**
 * Class UtkOrdersClient
 * Класс для реализации запросов к УТК, связанных с заявками
 */
class UtkOrdersClient extends UtkClient
{
    const REQUEST_ORDER_LIST = 'orderlist';
    const REQUEST_ORDER_VIEW = 'orderview';
    const REQUEST_DO_INVOICE = 'invoice';

    const OP_REQUEST_KT_UPDATE_SUCCESS = 'requestKtUpdate';
    const OP_REQUEST_KT_UPDATE_FAIL = 'requestKtFail';

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module) {
        parent::__construct($module);
    }

    /**
     * Запросить обновление списка в КТ со стороны УТК
     * @param $action
     * @param $params
     * @return array|bool
     */
    public function makeOrderListRequest($action, $params) {

        $result = [];

        $params['datestart'] = $params['dateStart'];
        $params['dateend'] = $params['dateEnd'];

        $response = parent::makeRestRequest($action, $params);

        if (empty($response['result']['orderList'])) {
            return false;
        }

        $ordersIdList = $response['result']['orderList'];

        foreach ($ordersIdList as $orderId) {

            if (empty($orderId['orderId'])) {
                continue;
            }

            $response = $this->makeOrderViewRequest(self::REQUEST_ORDER_VIEW, $orderId);

            if (empty($response) || empty($response['status'])) {
                $action = self::OP_REQUEST_KT_UPDATE_FAIL;
            }

            if ($response['status'] == 0) {
                $action = self::OP_REQUEST_KT_UPDATE_SUCCESS;
            } else {
                $action = self::OP_REQUEST_KT_UPDATE_FAIL;
            }

            $result[] = [
                    'orderId' => $orderId,
                   'action' => $action
            ];

        }

        return $result;
    }

    /**
     * Запросить обновление заявки в КТ со стороны УТК
     * @param $action 
     * @param $params
     * @return bool|mixed
     */
    public function makeOrderViewRequest($action, $params) {

        $response = parent::makeRestRequest($action, $params);
        return $response;
    }
}