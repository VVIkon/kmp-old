<?php

/**
 * Class BookStartType
 * Реализует функциональность для работы с результами запросов операций бронирования
 */
class BookStartType extends KFormModel
{
    const BOOK_START_RESULT_BOOKED = 0;
    const BOOK_START_RESULT_RUN = 1;
    const BOOK_START_RESULT_ERROR = 2;

    /**
     * @var array результаты запросов операции бронирования
     */
    public static $bookStartResults = [
        self::BOOK_START_RESULT_BOOKED,
        self::BOOK_START_RESULT_RUN,
        self::BOOK_START_RESULT_ERROR
    ];

    /**
     * Проверка наличия указанного результата запроса
     * @param $typeCode
     * @return bool
     */
    public static function checkTypeExists($typeCode)
    {
        if (in_array($typeCode, self::$bookStartResults)) {
            return true;
        }

        return false;
    }

}

