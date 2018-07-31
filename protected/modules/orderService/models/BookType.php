<?php

/**
 * Class BookType
 * Реализует функциональность для работы с типами ответов от операций бронирования
 */
class BookType extends KFormModel
{
    const BOOK_RESULT_BOOKED = 0;
    const BOOK_RESULT_NOT_BOOKED = 2;
    const BOOK_RESULT_OFFLINE = 3;

    /**
     * @var array результаты операции бронирования
     */
    public static $bookResults = [
        self::BOOK_RESULT_BOOKED,
        self::BOOK_RESULT_NOT_BOOKED,
        self::BOOK_RESULT_OFFLINE
    ];

    /**
     * Проверка наличия указанного результата бронирования
     * @param $typeCode
     * @return bool
     */
    public static function checkTypeExists($typeCode)
    {
        if (in_array($typeCode,self::$bookResults)) {
            return true;
        }

        return false;
    }
}

