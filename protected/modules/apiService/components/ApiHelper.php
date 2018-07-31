<?php

/*
* Функция проверки значения. Функция написана для проверки ключа(ей) массива и имеющегося значения
* @param $val - ссылка на array[key], $retval - возвращаемое значение
* @returm может вернуть не только null...
*/
 function nvl(&$val, $retval = null){
    $a = isset($val) ? $val : $retval;
    return $a;
 }

