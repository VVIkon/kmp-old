<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 18.05.17
 * Time: 17:05
 */
interface ConnectionDecoratorInterface
{
    public function sendError($code, $closeConnection = false);
    public function sendResponse($type, array $data);
}