<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 19.04.17
 * Time: 18:40
 */
class ImplodeTplEngine
{
    public function render($tpl, $params)
    {
        $rows = [];
        foreach ($params as $name => $val) {
            $rows[] = $name . ': ' . $val;
        }
        return implode(', ', $rows);
    }
}