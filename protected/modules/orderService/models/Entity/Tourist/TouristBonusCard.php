<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 26.06.17
 * Time: 16:33
 */


class TouristBonusCard extends CActiveRecord
{
    protected $id;
    protected $TouristIDbase;
    protected $bonusCardNumber;
    protected $loyalityProgramId;
    protected $active;



    public function tableName()
    {
        return 'kt_touristBonusCard';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function fromParams(array $params, $touristIdBase)
    {
        $modified = false;

        if ($this->id != StdLib::nvl($params['id'])){
            $this->id = StdLib::nvl($params['id']);
            $modified = true;
        } ;
        if ($this->TouristIDbase != StdLib::nvl($touristIdBase)){
            $this->TouristIDbase = StdLib::nvl($touristIdBase);
            $modified = true;
        }
        if ($this->bonusCardNumber != StdLib::nvl($params['bonuscardNumber'])) {
            $this->bonusCardNumber = StdLib::nvl($params['bonuscardNumber']);
            $modified = true;
        }
        if ($this->loyalityProgramId != StdLib::nvl($params['aviaLoyaltyProgramId'])) {
            $this->loyalityProgramId = StdLib::nvl($params['aviaLoyaltyProgramId']);
            $modified = true;
        }
        if ($this->active != StdLib::nvl($params['active'], 1)){
            $this->active = StdLib::nvl($params['active'], 1);
            $modified = true;
        }

        return $modified;
    }

    public function save($runValidation = true, $attributes = null)
    {
        return parent::save(false);
    }


}