<?php

//Подключение настроек зависящих от среды выполнения
$environmentConfig = require(dirname(__FILE__) . '/envconfig.php');

return CMap::mergeArray($environmentConfig, [
    'log_namespace' => "system.systemservice",
    'error_descriptions' => [
        'undefined_error' => 'Неизвестная ошибка',
        '0' => 'Неверный логин или пароль сервиса',
        '1' => 'Неправильный формат JSON тела запроса',
        '2' => 'Неверный сервисный токен',
        '3' => 'Срок действия токена истек',
        '4' => 'Неверный токен пользователя',
        '5' => 'Неверно указан CONTENT_TYPE',

        '6' => 'Неверный идентификатор пользователя',
        '11' => 'Не указано отображаемое имя файла',
        '12' => 'Некорректное имя файла',
        '13' => 'Некорректные правила валидации',
        '14' => 'Не указан номер заявки',
        '15' => 'Не указан тип бизнес объекта',
        '16' => 'Некорректный тип бизнес объекта',
        '17' => 'Не указан идентификатор бизнес объекта',
        '18' => 'Не указан адрес распололжения файла',
        '19' => 'Некорректные параметры сохранения файла',
        '20' => 'Не найден указанный файл',
        '21' => 'Не указан тип справочных данных',
        '22' => 'Некорректный тип справочных данных',
        '23' => 'Не указан параметр фильтрации справочных данных',
        '24' => 'Не указан код языка',
        '25' => 'Некорректный код языка',
        '26' => 'Не указан тип услуги',
        '27' => 'Некорректный тип услуги',
        '28' => 'Некорректное число возвращаемых записей',
        '29' => 'Некорректное наименование возвращаемого поля',
        '30' => 'Не указан ID пользователя',
        '31' => 'Пользователь с таким ID не найден',
        '32' => 'Права пользователя не заданы',
        '33' => 'Ошибка входных параметров',
        '34' => 'Корпоративный сотрудник не найден',
        '35' => 'Не задан документ пользователя',
        '36' => 'Неверный токен пользователя',
        '37' => 'Невалидный пользователь',
        '38' => 'Невалидная структура сообщения',
        '39' => 'Неизвестный тип действия',
        '40' => 'Не указан получатель сообщения',
        '41' => 'Пользователь не подписан на чат',
        '42' => 'Недостаточно прав для отправки сообщения в заявку',
        '43' => 'Некорректная дата рождения',
        '44' => 'Некорректная фамилия',
        '45' => 'Некорректное имя',
        '46' => 'Некорректный пол',
        '47' => 'Некорректная компания',
        '48' => 'Некорректный документ',
        '49' => 'Некорректный номер программы лояльности',
        '50' => 'Некорректный тип пользователя',
        '51' => 'Некорректные права пользователя',
        '52' => 'Некорректный ID поля',
        '53' => 'Некорректная категория поля',
        '54' => 'Некорректный шаблон',
        '55' => 'Некорректный список возможных значений',
        '56' => 'Невалидное значение доп поля',
        '57' => 'Некорректный поставщик',
        '58' => 'Некорректный тип правила',
        '59' => 'Некорректные условия',
        '60' => 'Некорректные действия',
        '61' => 'Правило не найдено',

        '300' => 'Отчеты: неизвестная ошибка',
        '301' => 'Отчеты: не указано наименование задачи',
        '302' => 'Отчеты: не указан период выполнения задачи',
        '303' => 'Отчеты: не указана детализация периода выполнения задачи',
        '304' => 'Отчеты: не указана операция запуска команды',
        '305' => 'Отчеты: не указано название сервиса',
        '306' => 'Отчеты: не указана JSON структура параметров запуска',
        '307' => 'Отчеты: не указан ID компании',


        '500' => 'Ошибка базы данных',
        '501' => 'Невозможно получить данные пользователя',
        '502' => 'Невозможно получить идентифкатор сессии пользователя',
        '510' => 'Невозможно получить данные типа услуги',
        '511' => 'Невозможно получить наименования полей таблицы поставщиков',
        '512' => 'Невозможно получить данные о поставщиках',
        '513' => 'Невозможно получить список отельных сетей',
        '514' => 'Невозможно получить список альянсов авиакомпаний',

        '601' => 'Невозможно сохранить файл',
        '602' => 'Некорректный путь к хранилищу файлов',
        '603' => 'Невозможно загрузить файл',
        '604' => 'Метод не реализован',
        '605' => 'Невозможно получить значение подстановки для указанного поля',

        '701' => 'Недостаточно прав для выполнения операции',
        '702' => 'Превышена максимальная длинна пароля (50 сим.)',

        '802' => 'Невозможно получить файл по указанному URL',
        '803' => 'Невозможно привязать загруженный файл к указаной заявке',

        // Ошибки авторизации в UserAuth
        '2000' => 'Необходимо сменить пароль',
        '2001' => 'Необходимо сменить логин',
        '2002' => 'Ошибка авторизации',
        '2003' => 'Ошибка: Имеются аккаунты в кот. присутствует одинаковые хеши',
        '2004' => 'Ошибка: В БД присутствует логически неправильная регистрационная запись',
        '2005' => 'Ошибка получении аккаунта пользователя',
        '2006' => 'Ошибка авторизации в GPTS',
        '2007' => 'Ошибка логин не имеет аккаунта',
    ],
]);
