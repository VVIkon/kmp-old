<?php

/**
 * Модель запиши кеша
 *
 * @property RefServices $RefService
 */
class TokenCache extends CActiveRecord
{
    protected $token;
    public $ServiceID;
    protected $StartDateTime;
    protected $success;

    public function tableName()
    {
        return 'token_cache';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'RefService' => array(self::BELONGS_TO, 'RefServices', 'ServiceID')
        );
    }

    public function getTokenLimitMinutes()
    {
        $module = YII::app()->getModule('searcherService');
        $time_diff = $module->getConfig('cacheClear');

        $DateTime = new DateTime($this->StartDateTime);
        $DateTime->add(new DateInterval($time_diff));

        $tokenlimit = floor(($DateTime->getTimestamp() - time()) / 60);

        if ($tokenlimit < 0) {
            $tokenlimit = 0;
        }

        return $tokenlimit;
    }

    public function getPercent()
    {
        return $this->success;
    }

    public function getServiceId()
    {
        return $this->ServiceID;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Получение названия класса репозитория офферов
     * @return string
     * @throws Exception
     */
    public function getOffersRepositoryClassName()
    {
        if ($this->RefService) {
            $modelClassName = $this->RefService->getModelName();

            $offersRepositoryClassName = $modelClassName . 'ResponseRepository';

            if (!class_exists($offersRepositoryClassName)) {
                throw new Exception("Offer repository class $offersRepositoryClassName not found");
            }
        } else {
            throw new Exception("Service Reference in table kt_ref_services not found");
        }

        return $offersRepositoryClassName;
    }
}