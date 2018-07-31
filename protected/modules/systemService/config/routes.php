<?php
/**
 * Маршруты модуля для подключения в основной конфиг
 */
return [
    'system/<action:(UserAuth|ChangePassword)>'
    => ['systemService/auth/<action>', 'verb' => 'POST'],

    'system/<action:(GetUser|UserAccess|GetClientUserSuggest|GetClientUser|SetUser|SetUserChat|SetUserRole|GetUserSuggest)>'
    => ['systemService/user/<action>', 'verb' => 'POST'],

    'system/<action:(authenticate)>'
    => ['systemService/systemServiceAuth/<action>', 'verb' => 'POST'],

    'system/<action:(UploadFile)>'
    => ['systemService/fileStore/<action>', 'verb' => 'POST'],

    'system/<action:(GetDictionary)>'
    => ['systemService/commonData/<action>', 'verb' => 'POST'],

    'system/<action:(SetAddFieldType|SetUserAddField)>'
    => ['systemService/addField/<action>', 'verb' => 'POST'],

    'system/<action:(GetTPlistForCompany|SetTPforCompany)>'
    => ['systemService/tp/<action>', 'verb' => 'POST'],

    'system/<action:(SetSchedule|GetSchedule|ScheduleManage)>'
    => ['systemService/schedule/<action>', 'verb' => 'POST'],
];