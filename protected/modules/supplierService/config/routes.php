<?php
/**
 * Маршруты модуля для подключения в основной конфиг
 */
return [
    'supplierService/<action:(authenticate)>'
    => ['supplierService/supplierServiceAuth/<action>', 'verb' => 'POST'],

    'supplierService/<action:(GetOffer|ServiceBooking|IssueTickets|GetEtickets|GetCancelRules|SupplierServiceCancel|SupplierModifyService|SupplierGetServiceStatus)>'
    => ['supplierService/offerInfo/<action>', 'verb' => 'POST'],

    'supplierService/<action:(SupplierGetOrder|SetServiceData)>'
    => ['supplierService/supplier/<action>', 'verb' => 'POST'],

    'supplierService/<action:(GetFareRule)>'
    => ['supplierService/offerControl/<action>', 'verb' => 'POST'],

    'supplierService/<action:(GetHotelInfo|CreateHotelFromSearch)>'
    => ['supplierService/dictionary/<action>', 'verb' => 'POST'],

    'supplierService/<action:(CheckGateUserPassword)>'
    => ['supplierService/gateUser/<action>', 'verb' => 'POST'],
];
