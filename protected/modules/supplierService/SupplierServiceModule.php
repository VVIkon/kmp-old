<?php

class SupplierServiceModule extends KModule
{
    public function init()
    {
        $this->setImport(array(
            'supplierService.components.*',
            'supplierService.models.*',
            'supplierService.models.Entity.*',
            'supplierService.models.Entity.SupplierService.*',
            'supplierService.models.dataForms.*',

            'supplierService.modules.GPTSEngine.components.*',
            'supplierService.modules.GPTSEngine.components.workers.*'
        ));
    }

    public function beforeControllerAction($controller, $action)
    {
        if (parent::beforeControllerAction($controller, $action)) {
            return true;
        } else {
            return false;
        }
    }
}