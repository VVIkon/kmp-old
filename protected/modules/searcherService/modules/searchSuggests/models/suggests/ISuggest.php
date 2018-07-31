<?php

/**
 * Interface ISuggest
 * Интерфейс для доступа к методам поиска информации для подсказки
 */
interface ISuggest
{
    /**
     * Получить список подсказок
     * @return mixed
     */
    public function find($text, $lang);
}