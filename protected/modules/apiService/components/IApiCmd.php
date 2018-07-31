<?php


interface IApiCmd
{
    function loadParams(array $par, array $serAct, $template, $module);
    function onCommand(array $param, $module);
}