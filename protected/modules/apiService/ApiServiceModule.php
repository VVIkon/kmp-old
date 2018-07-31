<?php

/**
 * Class ApiServiceModule
 * Отвечает за вызов компонентов и контроллеров,
 * использующихся при реализации внешнего restapi
 */
class ApiServiceModule extends KModule
{
    public function init()
    {
        $this->setImport(array(
            'apiService.components.*',
        ));
    }




}