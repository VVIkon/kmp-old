<?php
/**
 * Маршруты модуля для подключения в основной конфиг
 */
return [
    'searcherService/<action:(authenticate)>'
    => ['searcherService/searcherServiceAuth/<action>', 'verb' => 'POST'],

    'searcherService/<action:(GetSuggestLocation|GetSuggestHotel|GetSchedule)>'
    => ['searcherService/Suggests/<action>', 'verb' => 'POST'],

    'searcherService/<action:(SearchStart|GetSearchResult|GetCacheOffer|ParseServiceFormConditions|GetHotelAdditionalService)>'
    => ['searcherService/offerSearch/<action>', 'verb' => 'POST']
];