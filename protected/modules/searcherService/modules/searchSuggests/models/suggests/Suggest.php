<?php

/**
 * Class Suggest
 * Реализует функциональность управления
 * данными подсказок
 */
class Suggest extends KFormModel implements ISuggest
{
    /**
     * Поиск данных для подсказок
     * @param $text
     * @param $lang
     */
    public function find($text, $lang) {
        throw new KmpLogicException();
    }
}

