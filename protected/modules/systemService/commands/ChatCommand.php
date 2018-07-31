<?php

use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;

use Workerman\Worker;

/**
 * Обработчик чата
 */
class ChatCommand extends CConsoleCommand
{
//    public function run()
//    {
//        $config = Yii::app()->getModule('systemService')->getConfig();
//
//        if(empty($config['chat']['port']) || empty($config['chat']['deactivationTime']) || empty($config['chat']['userGroups'])){
//            echo 'Не указаны настройки чата';
//            return;
//        }
//
//        $chatController = new ChatController();
//        $ws = new WsServer($chatController);
////        $ws->disableVersion(0); // old, bad, protocol version
//
//        // Make sure you're running this as root
//        $server = IoServer::factory(new HttpServer($ws), $config['chat']['port']);
//        $server->loop->addPeriodicTimer($config['chat']['deactivationTime'], function() use (&$chatController) {
//            $chatController->userDeactivationCb();
//        });
//        $server->run();
//    }

    public function run()
    {
        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'Чат', 'Старт',
            [
                'config' => 1
            ],
            LogHelper::MESSAGE_TYPE_INFO, 'system.systemservice.info'
        );

        $config = Yii::app()->getModule('systemService')->getConfig();

        if (empty($config['chat']['port']) || empty($config['chat']['deactivationTime']) || empty($config['chat']['userGroups'])) {
            echo 'Не указаны настройки чата';
            return;
        }

        $chat = new ChatWorkermanController($config['chat']);
        $chat->run();
    }
}