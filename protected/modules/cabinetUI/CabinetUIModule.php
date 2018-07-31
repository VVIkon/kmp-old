<?php

/**
 * Class CabinetUIModule
 * Отвечает за вызов компонентов и контроллеров,
 * использующихся при реализации клиента к API OrderService
 */
class CabinetUIModule extends CWebModule
{
    public $layout = 'main';

    public function init()
    {

        $this->setImport(array(
            'cabinetUI.components.*',
        ));
    }

    public function beforeControllerAction($controller, $action)
    {
        if (parent::beforeControllerAction($controller, $action)) {
            return true;
        } else
            return false;
    }

    /**
     * Получение параметров конфигурации модуля
     * @param string $paramName имя параметра
     * @return mixed значение указанного параметра
     */
    public function getConfig($paramName = '')
    {
        $config = require(dirname(__FILE__) . '/config/config.php');
        return empty($paramName) ? $config : $config[$paramName];
    }
}
