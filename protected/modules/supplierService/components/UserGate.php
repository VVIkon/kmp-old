<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 14.02.2017
 * Time: 12:55
 */
class UserGate
{
    const UTK_ENGINE = 4;
    const GPTS_ENGINE = 5;
    const KT_ENGINE = 6;

    public static $supplierEngines = [
        self::UTK_ENGINE => '',
        self::GPTS_ENGINE => 'GPTSEngine',
        self::KT_ENGINE => '',
    ];

    private $module;
    private $currentEngin;
    private $envConfig;
    private $currConfig;
    private $apiClient;

    private $lastError;
    private $lastErrorCode;


    public function __construct(array $params)
    {
        if( !empty(self::$supplierEngines[ $params['gatewayId'] ]) ){
            $this->currentEngin = self::$supplierEngines[ $params['gatewayId'] ];

            $this->module = YII::app()->getModule('supplierService')->getModule($this->currentEngin);
            $this->envConfig['test_api'] = $this->module->getConfig('test_api');
            $this->envConfig['prod_api'] = $this->module->getConfig('prod_api');

            $cfg['authInfo']['companyCodeOrAlias'] = $params['companyId'];
            $cfg['authInfo']['login'] = $params['login'];
            $cfg['authInfo']['password'] = $params['password'];

            if (EnvironmentHelper::getEnvironmentType() == EnvironmentHelper::PRODUCTION) {
                $cfg['url'] = $this->envConfig['prod_api']['url'];
                $cfg['authInfo']['key'] = $this->envConfig['prod_api']['authInfo']['key'];

                $this->currConfig['prod_api'] = $cfg;
            } else {
                $cfg['url'] = $this->envConfig['test_api']['url'];
                $cfg['authInfo']['key'] = $this->envConfig['test_api']['authInfo']['key'];

                $this->currConfig['test_api'] = $cfg;
            }
        }

    }

    public function getCredential()
    {
        try{
            $this->apiClient = new GPTSApiClient($this->currConfig, $this->module);
            $token = $this->apiClient->getToken();
            return isset($token);
        } catch (Exception $ex) {
            $this->lastError = 'Unauthorized';
            $this->lastErrorCode = '401';
            LogHelper::logExt(get_class($this), __METHOD__, 'Unauthorized', $this->lastErrorCode, $this->currConfig, 'error', 'system.supplierservice.error');
            return false;
        }
    }

    public function getLastError()
    {
        return $this->lastError;
    }
    public function getLastErrorCode()
    {
        return $this->lastErrorCode;
    }

}