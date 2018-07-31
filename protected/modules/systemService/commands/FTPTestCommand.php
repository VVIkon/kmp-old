<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 20.12.16
 * Time: 10:35
 */
class FTPTestCommand extends CConsoleCommand
{
    public function init()
    {
        Yii::getLogger()->autoFlush = 1;
        Yii::getLogger()->autoDump = true;
    }

    public function run($args)
    {
        if (empty($args)) {
            exit('Не задан путь для проверки');
        }

        echo PHP_EOL;
        print_r("Проверка пути {$args[0]}");
        echo PHP_EOL;

        set_error_handler(function ($errno, $errstr) {
            throw new Exception("$errno - $errstr");
        }, E_WARNING);

        try {
            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            );

            $f = fopen($args[0], 'r', false, stream_context_create($arrContextOptions));
            restore_error_handler();

            if($f){
                exit('OK');
            }
        } catch (Exception $e) {
            restore_error_handler();
            exit($e->getMessage());
        }
    }
}