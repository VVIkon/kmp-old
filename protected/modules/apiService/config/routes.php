<?php
/**
 * Маршруты модуля для подключения в основной конфиг
 */
return  [
        'api/v10/apiService/<action:(ClientAuthenticate)>'          => ['apiService/ApiAuth/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientGetOrder)>'              => ['apiService/ApiAuth/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientGetOrderList)>'          => ['apiService/ApiAuth/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientGetSuggestLocation)>'    => ['apiService/ApiSearch/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientSearchStart)>'           => ['apiService/ApiSearch/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientGetHotelInfo)>'          => ['apiService/ApiSearch/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientGetSearchResult)>'       => ['apiService/ApiSearch/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientAddServiceToOrder)>'     => ['apiService/ApiService/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientSetTouristsToService)>'  => ['apiService/ApiService/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientBookService)>'           => ['apiService/ApiBook/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientCancelService)>'         => ['apiService/ApiBook/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientAddDocumentToOrder)>'    => ['apiService/ApiDocument/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientGetOrderDocuments)>'     => ['apiService/ApiDocument/<action>', 'verb' => 'POST'],
        'api/v10/apiService/<action:(ClientIssueTickets)>'          => ['apiService/ApiBook/<action>', 'verb' => 'POST'],

        'api/v15/apiService/<action:(ClientAddDataToService)>'      => ['apiService/v15/ApiOrder/<action>', 'verb' => 'POST'],
        'api/v15/apiService/<action:(ClientAddServiceToOrder)>'     => ['apiService/v15/ApiOrder/<action>', 'verb' => 'POST'],
        'api/v15/apiService/<action:(ClientSetTouristsToService)>'  => ['apiService/v15/ApiOrder/<action>', 'verb' => 'POST'],
        'api/v15/apiService/<action:(ClientGetOrder)>'              => ['apiService/v15/ApiOrder/<action>', 'verb' => 'POST'],
];

