<?php

return [
    'gearman' => [
        'host' => '127.0.0.1',
        'port' => '4730',
        'workerPrefix' => 'dev'
    ],

    'authdata' => [
        'login' => 'testcl',
        'pass' => '123'
    ],

    'baseUrl' => 'http://dev-kmp.travel',

    /**
     * Конфигурирование воркфлоу
     */
    'workFlowFilters' => [
        //тестовая схема фильтрации воркфлоу
        [
            'serviceTypes' => [1, 2],          // 1-проживание, 2-авиа
            'userTypes' => [1, 2, 3],           // 1 - сотрудник KMP
            'workFlowScheme' => 'testWorkflowScheme',
            'OWMActions' => [
                // действия стандартного воркфлоу
                'NEW',
                'TEST',
                'ORDERSETADDITIONALDATA',
                'ADDSERVICE',
                'ORDERBOOKSTART',
                'ORDERBOOKCOMPLETE',
                'ORDERBOOKCANCEL',
                'ORDERSERVICECANCEL',
                'ORDERBOOKCHANGE',
                'ADDTOURIST',
                'ORDERTOURISTTOSERVICE',
                'REMOVETOURIST',
                'SAVETOURIST',
                'ORDERPAYSTART',
                'ORDERPAYFINISH',
                'ORDERISSUETICKETS',
                'ORDERADDEXTRASERVICE',
                'ORDERREMOVEEXTRASERVICE',
                'ORDERSYNC',
                'ORDERAUTHORIZATION',

                // действия оператора
                'SETCLIENTCOMPANY',
                'ORDERREPRICE',
                'ORDERDONE',
                'ORDERCLOSED',
                'ORDERMANUAL',
                'ORDERINVOICECANCEL',
                'ORDERMANUALSETSTATUS',
                'ORDERSETTICKET',
                'ORDERSETRESERVATION',
                'ORDERSETSERVICEDATA',
            ]
        ],
        [
            'serviceTypes' => [1, 2],          // 1-проживание, 2-авиа
            'userTypes' => [1, 2, 3],           // 1 - сотрудник KMP
            'workFlowScheme' => 'pavelWorkflowScheme',
            'OWMActions' => [
                // действия стандартного воркфлоу
                'NEW',
                'ORDERSETADDITIONALDATA',
                'ADDSERVICE',
                'ORDERBOOKSTART',
                'ORDERBOOKCOMPLETE',
                'ORDERBOOKCANCEL',
                'ORDERSERVICECANCEL',
                'ORDERBOOKCHANGE',
                'ADDTOURIST',
                'ORDERTOURISTTOSERVICE',
                'REMOVETOURIST',
                'SAVETOURIST',
                'ORDERPAYSTART',
                'ORDERISSUETICKETS',
                'ORDERADDEXTRASERVICE',

                // действия оператора
                'SETCLIENTCOMPANY',
                'ORDERREPRICE',
                'ORDERDONE',
                'ORDERCLOSED',
                'ORDERMANUAL',
                'ORDERINVOICECANCEL',
                'ORDERMANUALSETSTATUS',
                'ORDERSETTICKET',
                'ORDERSETRESERVATION',
                'ORDERSETSERVICEDATA',
            ]
        ],
        [
            'serviceTypes' => [1, 2],          // 1-проживание, 2-авиа
            'userTypes' => [1],           // 1 - сотрудник KMP
            'workFlowScheme' => 'kmpWorkflowScheme',
            'OWMActions' => [
                // действия стандартного воркфлоу
                'ADDSERVICE', 'ORDERBOOKSTART', 'ORDERBOOKCANCEL', 'ORDERSERVICECANCEL', 'ORDERBOOKCHANGE', 'ORDERBOOKCOMPLETE',
                'ADDTOURIST', 'ORDERTOURISTTOSERVICE', 'REMOVETOURIST', 'SAVETOURIST',
                'ORDERISSUETICKETS',
                'ORDERSETADDITIONALDATA',
                'ORDERADDEXTRASERVICE', 'ORDERREMOVEEXTRASERVICE',
                'ORDERDONE',

                // действия оператора
                'SETCLIENTCOMPANY',
                'ORDERREPRICE',
                'ORDERCLOSED',
                'ORDERMANUAL',
                'ORDERINVOICECANCEL',
                'ORDERMANUALSETSTATUS',
                'ORDERSETTICKET',
                'ORDERSETRESERVATION',
                'ORDERSETSERVICEDATA',
                'ORDERSYNC',
                'ORDERAUTHORIZATION'
            ]
        ]
    ],

    /**
     *
     *  Конфигурация OWM
     *  STATES - статусы заявки и ружимы машины состояний
     *  TRANSITIONS -  возможнные переходы из состояний
     *
     */
    'OWM' =>
        [
            'STATES' => [
                OrderModel::STATUS_NEW,
                OrderModel::STATUS_MANUAL,
                OrderModel::STATUS_PAID,
                OrderModel::STATUS_CLOSED,
                OrderModel::STATUS_ANNULED,
                OrderModel::STATUS_W_PAID,
                OrderModel::STATUS_DONE,
                OrderModel::STATUS_BOOKED
            ],
            'TRANSITIONS' => [
                'TEST' => [
                    'from' => [],
                    'delegates' => [
                        'UpdateOrderInUTK',
                        'Log'
                    ],
                    'permissions' => []
                ],
                'NEW' => [
                    'from' => [],
                    'delegates' => [
                        'CreateOrder',
                        'Audit',
                        'Log'
                    ],
                    'permissions' => [20]
                ],
                'ADDSERVICE' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_PAID,
                        OrderModel::STATUS_DONE,
                        OrderModel::STATUS_ANNULED,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'CreateOrder',
                        'Permissions',
                        'AddServiceCreate',
                        'AggregateStatus',
//                        'UpdateOrderInUTK',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => []
                ],
                'ORDERADDEXTRASERVICE' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'ValidateOWMAddExtraService',
                        'Permissions',
                        'RunSWMAddExtraService',
//                      'UpdateOrderInUTK',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => []
                ],
                'ORDERREMOVEEXTRASERVICE' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'ValidateOWMRemoveExtraService',
                        'Permissions',
                        'RunSWMRemoveExtraService',
//                      'UpdateOrderInUTK',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => []
                ],
                'ADDTOURIST' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_PAID,
                        OrderModel::STATUS_DONE,
                        OrderModel::STATUS_ANNULED,
                        OrderModel::STATUS_MANUAL
                    ]
                    , 'delegates' => [
                        'AddTouristPermissions',
                        'ValidateOWMAddTourist',
                        'CreateOrder',
                        'OWMAddTourist',
//                        'UpdateOrderInUTK',
                        'Log',
                        'Audit',
                        'Notification'
                    ],
                    'permissions' => []
                ],
                'ORDERBOOKSTART' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'BookStartPreAction',
                        'BookPermissions',
                        'OWMValidateBookStart',
                        'ValidateRequiredOrderAddFields',
                        'RunSWMBookStart',
                        'BookStartRunSWMManual',
//                        'UpdateOrderInUTK',
                        'RunOWMPayStartForCorporate',
                        'AggregateStatus',
                        'OrderBookStartOutput',
                        'Log',
                        'Audit',
                        'AsyncTask'
                    ],
                    'permissions' => []
                ],
                'ORDERBOOKCOMPLETE' => [
                    'from' => [],
                    'delegates' => [
                        'BookDataToContext',
                        'BookStartRunSWMManual',
                        'RunSWMBookComplete',
                        'RunOWMPayStartForCorporate',
//                        'UpdateOrderInUTK',
                        'AggregateStatus',
                        'OrderBookStartOutput',
                        'Log',
                        'Audit',
                        'AsyncTask'
                    ],
                    'permissions' => []
                ],
                'ORDERBOOKCANCEL' => [
                    'from' => [
                        OrderModel::STATUS_MANUAL,
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_PAID,
                        OrderModel::STATUS_DONE
                    ],
                    'delegates' => [
                        'BookCancelPreAction',
                        'BookPermissions',
                        'ValidateBookCancel',
                        'RunSWMBookCancel',
                        'AggregateStatus',
//                        'UpdateOrderInUTK',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => []
                ],
                'ORDERBOOKCHANGE' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_MANUAL,
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_PAID,
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_DONE,
                    ],
                    'delegates' => [
                        'BookChangePermissions',
                        'ValidateOWMBookChange',
                        'RunOWMAddTourist',
                        'RunSWMBookChange',
                        'BookChangeOwnHotel',
                        'BookChangeNotOwnHotel',
                        'AggregateStatus',
                        'AsyncTask',
//                        'UpdateOrderInUTK',
                        'BookChangeSetOutput',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => []
                ],
                'ORDERSERVICECANCEL' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_PAID,
                        OrderModel::STATUS_DONE,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'RunSWMServiceCancel'
                    ],
                    'permissions' => []
                ],
                'REMOVETOURIST' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'RunSWMRemoveTourist'
                    ],
                    'permissions' => []
                ],
                'ORDERREPRICE' => [
                    'from' => [
                    ],
                    'delegates' => [
                        'RunSWMReprice'
                    ],
                    'permissions' => []
                ],
                'ORDERPAYSTART' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_MANUAL,
                        OrderModel::STATUS_DONE,
                        OrderModel::STATUS_ANNULED
                    ],
                    'delegates' => [
                        'PayStartPermissions',
                        'ValidateOWMPayStart',
                        'OWMCreateInvoice',
                        'RunSWMPayStart',
                        'SendInvoiceToUTK',
                        'SendInvoiceToUTKSuccess',
                        'SendInvoiceToUTKFail',
                        'AggregateStatus',
                        'PayStartSetOutput',
                        'Log',
                        'Audit',
                        'Notification',
                        'AsyncTask'
                    ],
                    'permissions' => []
                ],
                'ORDERPAYFINISH' => [
                    'from' => [
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_DONE,
                    ],
                    'delegates' => [
                        'ValidateOWMPayFinish',
                        'RunSWMPayFinish',
                        'AggregateStatus',
                        'Log',
                        'Audit',
                        'Notification'
                    ],
                    'permissions' => []
                ],
                'ORDERDONE' => [
                    'from' => [
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_PAID,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'DonePermissions',
                        'ValidateOWMDone',
                        'RunSWMDone',
                        'OWMDone',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => []
                ],
                'ORDERCLOSE' => [
                    'from' => [
                        OrderModel::STATUS_DONE,
                        OrderModel::STATUS_ANNULED,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [],
                    'permissions' => []
                ],
                'ORDERMANUAL' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_PAID,
                        OrderModel::STATUS_CLOSED,
                        OrderModel::STATUS_ANNULED,
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_DONE,
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'OWMManualPermissions',
                        'RunSWMManual',
                        'AggregateStatus',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => []
                ],
                'ORDERINVOICECANCEL' => [
                    'from' => [
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_PAID,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'RunSWMInvoiceCancel',
                        'Notification'
                    ],
                    'permissions' => []
                ],
                'ORDERMANUALSETSTATUS' => [
                    'from' => [
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'ValidateOWMManualSetStatus',
                        'RunSWMManualSetStatus',
                        'AggregateStatus',
                        'ManualSetStatusOutput',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => [42, 45, 46, 47, 48, 49, 50, 51, 52]
                ],
                'ORDERISSUETICKETS' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_PAID,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'IssueTicketsPermissions',
                        'ValidateOWMIssueTicketsBooked',
                        'ValidateOWMIssueTickets',
                        'RunSWMIssueTickets',
                        'RunAsyncOWMManual',
                        'RunAsyncOWMDone',
                        'AggregateStatus',
                        'AsyncTask',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => []
                ],
                'ORDERSETADDITIONALDATA' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_PAID,
                        OrderModel::STATUS_DONE,
                        OrderModel::STATUS_ANNULED,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'OWMSetAdditionalDataPermissions',
                        'ValidateOWMSetAdditionalData',
                        'OWMSetAdditionalData',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => []
                ],
                'ORDERSETTICKET' => [
                    'from' => [
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'SetTicketsPermissions',
                        'ValidateOWMSetTickets',
                        'RunSWMSetTickets',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => [42, 45, 46, 47, 48, 49, 50, 51, 52]
                ],
                'ORDERSETRESERVATION' => [
                    'from' => [
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'OWMSetReservationPermissions',
                        'ValidateOWMSetReservation',
                        'RunSWMSetReservation',
//                        'UpdateOrderInUTK',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => [42, 45, 46, 47, 48, 49, 50, 51, 52]
                ],
                'ORDERSETSERVICEDATA' => [
                    'from' => [
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'ValidateOWMSetServiceData',
                        'RunSWMSetServiceData',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => [42, 45, 46, 47, 48, 49, 50, 51, 52]
                ],
                'ORDERTOURISTTOSERVICE' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_PAID,
                        OrderModel::STATUS_DONE,
                        OrderModel::STATUS_ANNULED,
                        OrderModel::STATUS_MANUAL
                    ],
                    'delegates' => [
                        'SetTouristToServicePermissions',
                        'RunSWMTouristToService',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => []
                ],
                'ORDERIMPORT' => [
                    'from' => [],
                    'delegates' => [
                        'ImportOrder',
                        'AggregateStatus',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => []
                ],
                'ORDERSYNC' => [
                    'from' => [],
                    'delegates' => [
                        'RunSWMServiceSync',
                        'AggregateStatus',
//                        'UpdateOrderInUTK',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => [40]
                ],
                'ORDERAUTHORIZATION' => [
                    'from' => [
                        OrderModel::STATUS_NEW,
                        OrderModel::STATUS_MANUAL,
                        OrderModel::STATUS_BOOKED,
                        OrderModel::STATUS_W_PAID,
                        OrderModel::STATUS_PAID,
                        OrderModel::STATUS_DONE,
                        OrderModel::STATUS_ANNULED,
                    ],
                    'delegates' => [
                        'RunSWMServiceAuthorization',
                        'Log',
                        'Audit'
                    ],
                    'permissions' => [61]
                ],
            ]
        ],

    /**
     *
     *  Конфигурация SWM
     *  STATES - статусы заявки и ружимы машины состояний
     *  TRANSITIONS -  возможнные переходы из состояний
     *
     */
    'SWM' => [
        'STATES' => [
            OrdersServices::STATUS_NEW,
            OrdersServices::STATUS_W_BOOKED,
            OrdersServices::STATUS_BOOKED,
            OrdersServices::STATUS_W_PAID,
            OrdersServices::STATUS_P_PAID,
            OrdersServices::STATUS_PAID,
            OrdersServices::STATUS_CANCELLED,
            OrdersServices::STATUS_VOIDED,
            OrdersServices::STATUS_DONE,
            OrdersServices::STATUS_MANUAL
        ],

        'TRANSITIONS' => [
            'SERVICECREATE' => [
                'from' => [],
                'delegates' => [
                    'ValidateCreate',
                    'ParseServiceFormConditions',
                    'CreateGetCacheOffer',
                    'CreateAddInfo',
                    'CreateSetFareRules',   // Правила тарифов
                    'CreateGetCancelRules',
                    'SWMCreateTP',
                    'CreateAddOffers',

                    'AsyncTask',            // Асинхронно
                    'Log',
                    'Audit',
                    'Notification'
                ],
                'permissions' => []
            ],

            'SERVICEADDEXTRASERVICE' => [
                'from' => [
                    OrdersServices::STATUS_NEW,
                    OrdersServices::STATUS_MANUAL
                ],
                'delegates' => [
                    'ValidateSWMAddExtraService',
                    'AddExtraService',
                    'Log',
                    'Audit',
                    'Notification'
                ],
                'permissions' => []
            ],

            'SERVICEREMOVEEXTRASERVICE' => [
                'from' => [
                    OrdersServices::STATUS_NEW,
                    OrdersServices::STATUS_MANUAL
                ],
                'delegates' => [
                    'ValidateSWMRemoveExtraService',
                    'RemoveExtraService',
                    'Log',
                    'Audit',
                    'Notification'
                ],
                'permissions' => []
            ],

            'SERVICEBOOKSTART' => [
                'from' => [
                    OrdersServices::STATUS_NEW,
                    OrdersServices::STATUS_MANUAL
                ],
                'delegates' => [
                    'SWMValidateServiceIsOnline',
                    'ValidateBookStart',
                    'ValidateRequiredServiceAndTouristAddFields',
                    'SWMValidateBookStartTP',
                    'SetWBOOKEDStatus',
                    'SetAgreementSet',
                    'BookStart',
                    'SaveOfferBookData',
                    'SetBOOKEDStatus',
                    'NewSalesTermsNewStatus',
                    'NewSalesTermsNewTerms',
                    'Log',
                    'Audit',
                    'Notification'
                ],
                'permissions' => []
            ],
            'SERVICEBOOKCOMPLETE' => [
                'from' => [
                    OrdersServices::STATUS_W_BOOKED
                ]
                , 'delegates' => [
                    'ValidateSWMBookComplete',
                    'NewSalesTermsNewTerms',
                    'SaveOfferBookData',
                    'SetBOOKEDStatus',
                    //todo создание ваучера для услуги
                    'Log',
                    'Audit',
                    'Notification'
                ],
                'permissions' => []
            ],
            'SERVICEBOOKCANCEL' => [
                'from' => [
                    OrdersServices::STATUS_W_BOOKED,
                    OrdersServices::STATUS_W_PAID,
                    OrdersServices::STATUS_P_PAID,
                    OrdersServices::STATUS_PAID,
                    OrdersServices::STATUS_CANCELLED,
                    OrdersServices::STATUS_VOIDED,
                    OrdersServices::STATUS_DONE,
                    OrdersServices::STATUS_MANUAL,
                    OrdersServices::STATUS_BOOKED
                ],
                'delegates' => [
                    'ValidateServiceBookCancel',
                    'ValidateAviaServiceBookCancel',
                    'ValidateHotelServiceBookCancel',
                    'ServiceCancel',
                    'ServiceCancelFail',
                    'AviaServiceCancel',
                    'HotelServiceCancel',
                    'HotelServiceCancelFail',
                    'BookCancelSetInvoice',
                    'ServiceBookCancelGetReturnData',
                    'Log',
                    'Audit',
                    'Notification',
                    'AsyncTask'
                ],
                'permissions' => []
            ],
            'SERVICEBOOKCHANGE' => [
                'from' => [
                    OrdersServices::STATUS_W_BOOKED,
                    OrdersServices::STATUS_W_PAID,
                    OrdersServices::STATUS_P_PAID,
                    OrdersServices::STATUS_PAID,
                    OrdersServices::STATUS_CANCELLED,
                    OrdersServices::STATUS_VOIDED,
                    OrdersServices::STATUS_DONE,
                    OrdersServices::STATUS_MANUAL,
                    OrdersServices::STATUS_BOOKED
                ],
                'delegates' => [
                    'ValidateSWMBookChange',
                    'RunSupplierModifyService',
                    'HotelSWMBookChangeSuccess',
                    'HotelSWMBookChangeFailure',
                    'SWMSetOutput',
                    'AsyncTask',
                    'Log',
                    'Audit',
                    'Notification'
                ],
                'permissions' => []
            ],
            'SERVICEMANUAL' => [
                'from' => [
                    OrdersServices::STATUS_NEW,
                    OrdersServices::STATUS_W_BOOKED,
                    OrdersServices::STATUS_BOOKED,
                    OrdersServices::STATUS_W_PAID,
                    OrdersServices::STATUS_P_PAID,
                    OrdersServices::STATUS_PAID,
                    OrdersServices::STATUS_CANCELLED,
                    OrdersServices::STATUS_VOIDED,
                    OrdersServices::STATUS_DONE,
                ],
                'delegates' => [
                    'SetMANUALStatus',
                    'SetSupplierServiceData',
                    'Log',
                    'Audit',
                    'AsyncTask',
                    'Notification'
                ],
                'permissions' => []
            ],
            'SERVICEDONE' => [
                'from' => [
                    OrdersServices::STATUS_PAID,
                    OrdersServices::STATUS_MANUAL,
                    OrdersServices::STATUS_BOOKED
                ],
                'delegates' => [
                    'ValidateAviaSWMDone',
                    'ValidateHotelSWMDone',
                    'SWMDone',
                    'Log',
                    'Audit',
                    'Notification'
                ],
                'permissions' => []
            ],
            'SERVICECANCEL' => [
                'from' => [
                    OrdersServices::STATUS_NEW,
                    OrdersServices::STATUS_MANUAL
                ],
                'delegates' => [],
                'permissions' => []
            ],
            'SERVICEREMOVETOURIST' => [
                'from' => [
                    OrdersServices::STATUS_NEW,
                    OrdersServices::STATUS_MANUAL
                ],
                'delegates' => [],
                'permissions' => []
            ],
            'SERVICEREPRICE' => [
                'from' => [
                    OrdersServices::STATUS_NEW,
                    OrdersServices::STATUS_W_PAID,
                    OrdersServices::STATUS_P_PAID,
                    OrdersServices::STATUS_MANUAL
                ],
                'delegates' => [],
                'permissions' => []
            ],
            'SERVICEPAYSTART' => [
                'from' => [
                    OrdersServices::STATUS_W_PAID,
                    OrdersServices::STATUS_P_PAID,
                    OrdersServices::STATUS_PAID,
                    OrdersServices::STATUS_MANUAL,
                    OrdersServices::STATUS_BOOKED,
                    OrdersServices::STATUS_DONE,
                    OrdersServices::STATUS_CANCELLED,
                    OrdersServices::STATUS_VOIDED
                ],
                'delegates' => [
                    'ValidateSWMPayStart',
                    'SWMPayStart',
                    'Audit',
                    'Log',
                    'Notification'
                ],
                'permissions' => []
            ],
            'SERVICEPAYFINISH' => [
                'from' => [
                    OrdersServices::STATUS_W_PAID,
                    OrdersServices::STATUS_P_PAID,
                    OrdersServices::STATUS_MANUAL,
                    OrdersServices::STATUS_BOOKED,
                    OrdersServices::STATUS_DONE
                ],
                'delegates' => [
                    'ValidateSWMPayFinish',
                    'SWMPayFinish',
                    'AsyncTask',
                    'Log',
                    'Audit',
                    'Notification'
                ],
                'permissions' => []
            ],
            'SERVICEINVOICECANCEL' => [
                'from' => [
                    OrdersServices::STATUS_W_PAID,
                    OrdersServices::STATUS_P_PAID,
                    OrdersServices::STATUS_PAID,
                    OrdersServices::STATUS_CANCELLED,
                    OrdersServices::STATUS_VOIDED,
                    OrdersServices::STATUS_MANUAL,
                    OrdersServices::STATUS_BOOKED
                ],
                'delegates' => [],
                'permissions' => []
            ],
            'SERVICEMANUALSETSTATUS' => [
                'from' => [
                    OrdersServices::STATUS_MANUAL
                ],
                'delegates' => [
                    'ValidateSWMManualSetStatus',
                    'ServiceStatusSet',
                    'SetOnlineOffline',
                    'SetSupplierServiceData',
                    'Audit',
                    'Log',
                    'Notification',
                    'AsyncTask'
                ],
                'permissions' => []
            ],
            'SERVICEISSUETICKETS' => [
                'from' => [
                    OrdersServices::STATUS_W_PAID,
                    OrdersServices::STATUS_P_PAID,
                    OrdersServices::STATUS_PAID,
                    OrdersServices::STATUS_MANUAL,
                    OrdersServices::STATUS_BOOKED
                ],
                'delegates' => [
                    'ValidateSWMIssueTickets',
                    'AviaSWMIssueTickets',
                    'AviaSWMGetEtickets',
                    'SWMIssueTickets',
                    'SetIssueTicketsError',
                    'Log',
                    'Audit',
                    'Notification'
                ],
                'permissions' => []
            ],
            'SERVICESETADDITIONALDATA' => [
                'from' => [],
                'delegates' => [
                    'SWMSetAdditionalData',
                    'Log',
                    'Audit'
                ],
                'permissions' => []
            ],
            'SERVICESETRESERVATION' => [
                'from' => [
                    OrdersServices::STATUS_MANUAL
                ],
                'delegates' => [
                    'ValidateSWMSetReservation',
                    'SetReservationData',
                    'SetSupplierServiceData',
                    'Audit',
                    'Log',
                    'Notification',
                    'AsyncTask'
                ],
                'permissions' => []
            ],
            'SERVICESETSERVICEDATA' => [
                'from' => [
                    OrdersServices::STATUS_MANUAL
                ],
                'delegates' => [
                    'ValidateSWMSetServiceData',
                    'SWMSetServiceData',
                    'SetSupplierServiceData',
                    'Log',
                    'Audit',
                    'AsyncTask'
                ],
                'permissions' => []
            ],
            'SERVICESETTICKETS' => [
                'from' => [
                    OrdersServices::STATUS_MANUAL
                ],
                'delegates' => [
                    'ValidateSWMSetTickets',
                    'SWMSetTickets',
                    'SetSupplierServiceData',
                    'Log',
                    'Audit',
                    'Notification',
                    'AsyncTask'
                ],
                'permissions' => []
            ],
            'SERVICETOURISTTOSERVICE' => [
                'from' => [
//                    OrdersServices::STATUS_NEW,
//                    OrdersServices::STATUS_MANUAL
                ],
                'delegates' => [
                    'SWMTouristToService',
                    'AuthorizationPostAction',
                    'Log',
                    'Audit'
                ],
                'permissions' => []
            ],
            'SERVICESYNC' => [
                'from' => [],
                'delegates' => [
                    'RunSupplierGetServiceStatus',
                    'ServiceSyncStatusSet',
                    'Log',
                    'Audit',
                    'Notification',
                    'AsyncTask'
                ],
                'permissions' => []
            ],
            'SERVICEAUTHORIZATION' => [
                'from' => [
                    OrdersServices::STATUS_NEW,
                    OrdersServices::STATUS_W_BOOKED,
                    OrdersServices::STATUS_BOOKED,
                    OrdersServices::STATUS_W_PAID,
                    OrdersServices::STATUS_P_PAID,
                    OrdersServices::STATUS_PAID,
                    OrdersServices::STATUS_MANUAL
                ],
                'delegates' => [
                    'ServiceAuthorization',
                    'Log',
                    'Audit',
                    'Notification'
                ],
                'permissions' => []
            ],

        ]
    ]
];
