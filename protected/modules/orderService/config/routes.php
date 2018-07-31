<?php
/**
 * Маршруты модуля для подключения в основной конфиг
 */
return [
    'orderService/<action:(authenticate)>' => [
        'orderService/orderServiceAuth/<action>',
        'verb' => 'POST'
    ],

    'orderService/<action:(GetOrderList|GetOrder|GetOrderInvoices|GetOrderOffers|GetOrderDocuments|GetOrderHistory|AddDocumentToOrder|RemoveOrder|GetOrderManagers)>'
    => ['orderService/orders/<action>', 'verb' => 'POST'],

    'orderService/<action:(SetFareRules|ServiceCheckStatus||SendDocumentToUser)>'
    => ['orderService/orders/<action>', 'verb' => 'POST'],

    'orderService/<action:(OrderWorkflowManager|CheckWorkflow)>'
    => ['orderService/owm/<action>', 'verb' => 'POST'],

    'orderService/<action:(GetOrderTourists|SetTouristToOrder|RemoveTouristFromOrder)>'
    => ['orderService/tourists/<action>', 'verb' => 'POST'],

    'api/UtkService/<action:(order|invoice|payment)>'
    => ['orderService/utkHandler/<action>', 'verb' => 'POST'],

    'orderService/<action:(OrderView|OrderList|GetClientsList|SetInvoice|SetInvoiceCancel|SetDiscount|OrderToUTK)>'
    => ['orderService/UtkClient/<action>', 'verb' => 'POST'],

    'orderService/<action:(CreateReport|SendReportFile)>'
    => ['orderService/Report/<action>', 'verb' => 'POST'],

    'orderService/<action:(SetAuthorizationRule|GetAuthorizationRule)>'
    => ['orderService/AuthorizationRule/<action>', 'verb' => 'POST'],
];

