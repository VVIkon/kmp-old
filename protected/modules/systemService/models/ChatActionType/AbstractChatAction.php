<?php

use Ratchet\ConnectionInterface;

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 20.02.17
 * Time: 17:38
 */
abstract class AbstractChatAction
{
    /**
     * @param $type
     * @return bool|AbstractChatAction
     */
    static public function getByType($type)
    {
        $className = 'ChatAction' . ucfirst($type);

        if (class_exists($className)) {
            return new $className();
        } else {
            return false;
        }
    }

    abstract public function process($from, array $data, SplObjectStorage $clients);
}