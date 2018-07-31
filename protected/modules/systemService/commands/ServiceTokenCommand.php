<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/30/16
 * Time: 5:14 PM
 */
class ServiceTokenCommand extends CConsoleCommand
{
    public function init()
    {
        Yii::getLogger()->autoFlush = 1;
        Yii::getLogger()->autoDump = true;
    }

    public function actionUpdate()
    {
        $module = YII::app()->getModule('systemService');
        $tokenLifeTime = $module->getConfig('tokenLifetime');

        if (!$tokenLifeTime) {
            exit('Не найден параметр времени жизни токена');
        }
        ApiTokenRepository::updateTokens($tokenLifeTime);

        exit("Успех");
    }
}