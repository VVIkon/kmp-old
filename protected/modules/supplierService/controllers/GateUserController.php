<?php



/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 14.02.2017
 * Time: 11:40
 */
class GateUserController extends ServiceAuthController
//class GateUserController extends SecuredRestController
{


    /**
    * Команда проверяет корректность пары логин/пароль для пользователя шлюза
    * @param
     * {
            "gatewayId" : 5,        // Идентификатор шлюза из справочника шлюзов kt_ref_gateways
            "companyId" : "testcorp",    // Идентификатор компании в терминах кт из справочника kt_companies
            "login" : "o.karelin",  // Логин пользователя GPTS
            "password" : "123456"   // Пароль пользователя GPTS
        }
    * @returm
    */
    public function actionCheckGateUserPassword()
    {
        $params = $this->_getRequestParams();
        $module = Yii::app()->getModule('supplierService');
        // валидация параметров
        if (!isset($params['gatewayId']) || empty(UserGate::$supplierEngines[ $params['gatewayId']]) )
            $this->_sendResponseWithErrorCode(SupplierErrors::GATE_ID_NOT_SET);
        if (!isset($params['companyId']))
            $this->_sendResponseWithErrorCode(SupplierErrors::INVALID_COMPANY_ID);
        if (!isset($params['login']))
            $this->_sendResponseWithErrorCode(2);
        if (!isset($params['password']))
            $this->_sendResponseWithErrorCode(3);

        $ug = new UserGate($params);
        $response = $ug->getCredential();

        if ($response) {
            $this->_sendResponse(true, ['authenticate'=>'true'], '');
        } else {
            $this->_sendResponse(false, array(), $ug->getLastError(), $ug->getLastErrorCode());
        }

    }
}