<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 01.03.2017
 * Time: 17:33
 */
class FareRuleMgr
{
    /**
     * Код ошибки
     * @var int
     */
    private $errorCode;

    private $module;
    /**
     * namespace для записи логов
     * @var
     */
    private $namespace;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module) {
        $this->module = $module;
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    public function addFareRule($bodyRules, $params)
    {
        $flag= false;
        if (!isset($bodyRules) || !isset($params) ){
            $this->errorCode =  OrdersErrors::INCORRECT_INPUT_PARAM;
            return false;
        }
        $transaction = Yii::app()->db->beginTransaction();
        try {
            foreach ($bodyRules as $bodyRule) {
                $shortRules = StdLib::nvl($bodyRule['aviaFareRule']['shortRules'],[]);
                $shortRules['flightSegmentName']= StdLib::nvl($bodyRule['segment']['flightSegmentName']);
                $shortRules['offerId']=StdLib::nvl($params['offerId']);
                $shortRules['tripId']=StdLib::nvl($params['tripId']);
                $rules = StdLib::nvl($bodyRule['aviaFareRule']['rules'], []);
                // Сохранение правила тарифа
                $afr = new AviaFareRules();
                $afr->saveFareRule($shortRules);
                // сохранение текста тарифа
                foreach ($rules as $rule){
                    $rule['offerId'] = StdLib::nvl($params['offerId']);
                    $rule['tripId'] = StdLib::nvl($params['tripId']);
                    $rule['flightSegmentName']= StdLib::nvl($bodyRule['segment']['flightSegmentName']);

                    $atr = new AviaTextRules();
                    $atr->saveTextRule($rule);
                }
            }
        } catch (Exception $e) {
            $transaction->rollback();
            $this->errorCode = OrdersErrors::DB_ERROR;
            return false;
        }
        $transaction->commit();
        $flag = true;

        return $flag;
    }

    public function getLastError()
    {
       return $this->errorCode;
    }

}