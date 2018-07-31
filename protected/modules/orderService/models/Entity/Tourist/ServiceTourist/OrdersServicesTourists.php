<?php

/**
 * @property $ServiceID
 * @property $TouristID
 * @property $loyalityProgramId
 * @property $mileCard
 * @property Tourist $Tourist
 *
 * @property LoyaltyProgram $loyaltyProgram
 * @property OrderTourist $orderTourist
 */
class OrdersServicesTourists extends CActiveRecord
{
    public function relations()
    {
        return array(
            'orderTourist' => array(self::BELONGS_TO, 'OrderTourist', 'TouristID'),
            'loyaltyProgram' => array(self::BELONGS_TO, 'LoyaltyProgram', 'loyalityProgramId')
        );
    }

    public function tableName()
    {
        return 'kt_orders_services_tourists';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return Tourist
     */
    public function getTourist()
    {
        return $this->Tourist;
    }

    /**
     * @param mixed $ServiceID
     */
    public function setServiceID($ServiceID)
    {
        $this->ServiceID = $ServiceID;
    }

    /**
     * @param mixed $TouristID
     */
    public function setTouristID($TouristID)
    {
        $this->TouristID = $TouristID;
    }

    /**
     * @return mixed
     */
    public function getTouristID()
    {
        return $this->TouristID;
    }

    public function save($runValidation = true, $attributes = null)
    {
        return parent::save(false);
    }

    /**
     * @return mixed
     */
    public function getLoyalityProgramId()
    {
        return $this->loyalityProgramId;
    }

    /**
     * @return LoyaltyProgram
     */
    public function getLoyaltyProgram()
    {
        return $this->loyaltyProgram;
    }

    /**
     * @param mixed $loyalityProgramId
     */
    public function setLoyalityProgramId($loyalityProgramId)
    {
        $this->loyalityProgramId = $loyalityProgramId;
    }

    /**
     * @return mixed
     */
    public function getMileCard()
    {
        return $this->mileCard;
    }

    /**
     * @return OrderTourist
     */
    public function getOrderTourist()
    {
        return $this->orderTourist;
    }

    /**
     *
     * @param mixed $mileCard
     * @return bool
     */
    public function setMileCard($mileCard)
    {
        if (!preg_match('/^[0-9A-z- ]{1,30}$/', $mileCard)) {
            return false;
        }

        $this->mileCard = $mileCard;
        return true;
    }
}