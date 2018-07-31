<?php

return [
//    'applog',
//    'project',
    'serviceAuth' => [
        'class'=>'kmpfrwk.modules.serviceAuth.ServiceAuthModule',
        'components' => array(
            'AccountsMgr' => array(
                'class' => 'serviceAuth.AccountsMgr',
            )
        )
    ],
    'apiService' => [
    ],
    'orderService' => [
        'modules' => [
            'order' => [
                'components' => array(
                    'OrdersMgr' => array(
                        'class' => 'OrdersMgrMgr',
                    )
                )
            ],
            'utk' => [
                'components' => array(
                    'UtkClient' => array (
                        'class' => 'UtkClient',
                    )
                )
            ]
        ]
    ],
    'systemService' => [
        'modules' => [
            'account' => [
                'components' => array(
                    'AccountsMgr' => array(
                        'class' => 'AccountsMgr',
                    )
                )
            ],
            'file' => [
                'components' => array(
                    'FileMgr' => array(
                        'class' => 'FileMgr',
                    )
                )
            ],
        ]
    ],
    'searcherService' => [
        'components' => [
            'SearchManager' => array(
                'class' => 'SearchManager',
            )
        ],
        'modules' => [
            'searchSuggests' => [
                'components' => array(
                )
            ],
            'searchEngineGPTS' => [
                'components' => array(
                )
            ]
        ]
    ],
    'supplierService' => [
      'modules' => [
        'GPTSEngine' => [
          'components' => array(
          )
        ],
        'Dictionaries' => [
          'components' => array(
          )
        ]
      ]
    ],
    'cabinetUI',
    'UI',
    'adminUI',
    
    //-----------------------------------------Не выкладывать на prod
//    'gii'=>array(
//        'class'=>'system.gii.GiiModule',
//        'password'=>'kmpgiigenerator',
//        'ipFilters'=>array('127.0.0.1','::1'),
//    )
    //---------------------------------------------------------------
];
