<?php

/**
 * Class AuthTokenTemp
 *
 * @property $token	varchar(16)	Token
 * @property $serviceId	bigint(20) NULL	id сервиса
 * @property $userId	bigint(20) NULL	id польхователя
 * @property $expireDateTime	datetime NULL	время истечения действия токена
 *
 */
class AuthTokenTemp extends CActiveRecord
{
    public function tableName()
    {
        return 'kt_authTokenTemp';
    }

//    public function relations()
//    {
//        return array(
//            'company' => array(self::BELONGS_TO, 'Company', 'companyId'),
//            'iterations' => array(self::HAS_MANY, 'AuthRuleIteration', 'authRuleId')
//        );
//    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function bindAccount(Account $account)
    {
        $this->userId = $account->getUserId();
    }

    public function bindService(OrdersServices $service)
    {
        $this->serviceId = $service->getServiceID();
    }

    public function setExpire(DateTime $dateTime)
    {
        $this->expireDateTime = $dateTime->format('Y-m-d H:i:s');
    }

    public function generateToken()
    {
        $this->token = TokenHelper::generateOnlyToken();
    }

    public function getToken()
    {
        return $this->token;
    }
}