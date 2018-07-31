<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 7/26/16
 * Time: 11:29 AM
 */
class DelegateFactory
{
    private static $OWMDelegateToSWMAction = [
        'AddServiceCreate' => 'SERVICECREATE',
        'RunSWMBookCancel' => 'SERVICEBOOKCANCEL',
        'RunSWMBookComplete' => 'SERVICEBOOKCOMPLETE',
        'RunSWMBookStart' => 'SERVICEBOOKSTART',
        'RunSWMManual' => 'SERVICEMANUAL',
        'RunSWMServiceCancel' => 'SERVICECANCEL',
        'RunSWMRemoveTourist' => 'SERVICEREMOVETOURIST',
        'RunSWMReprice' => 'SERVICEREPRICE',
        'RunSWMPayStart' => 'SERVICEPAYSTART',
        'RunSWMPayFinish' => 'SERVICEPAYFINISH',
        'RunSWMDone' => 'SERVICEDONE',
        'RunSWMInvoiceCancel' => 'SERVICEINVOICECANCEL',
        'RunSWMManualSetStatus' => 'SERVICEMANUALSETSTATUS',
        'RunSWMIssueTickets' => 'SERVICEISSUETICKETS',
        'RunSWMSetTickets' => 'SERVICESETTICKETS',
        'RunSWMSetReservation' => 'SERVICESETRESERVATION',
        'RunSWMSetServiceData' => 'SERVICESETSERVICEDATA',
        'RunSWMClose' => 'SERVICECLOSE',
        'RunSWMBookChange' => 'SERVICEBOOKCHANGE',
        'RunSWMAddExtraService' => 'SERVICEADDEXTRASERVICE',
        'RunSWMRemoveExtraService' => 'SERVICEREMOVEEXTRASERVICE',
        'RunSWMServiceSync' => 'SERVICESYNC'
    ];

    public static function getOWMDelegateNameBySWMAction($SWMActionName)
    {
        if (in_array($SWMActionName, self::$OWMDelegateToSWMAction)) {
            $SWMActionToOWMDelegateName = array_flip(self::$OWMDelegateToSWMAction);
            return $SWMActionToOWMDelegateName[$SWMActionName];
        } else {
            return false;
        }
    }

    /**
     * Возвращает объект Делегата по его имени
     * @param $delegate_name
     * @return AbstractDelegate|bool
     */
    public static function getDelegate($delegate_name)
    {
        $class_name = $delegate_name . 'Delegate';

        if (class_exists($class_name)) {
            return new $class_name();
        } else {
            return false;
        }
    }
}