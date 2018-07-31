<?php

/**
 *
 *
 * @property $adult
 * @property $child
 * @property $infant
 * @property $AgentID
 *
 * @property Company $company
 */
class AviaSearchRequest extends CActiveRecord
{
    public function tableName()
    {
        return 'fl_searchRequest';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'company' => array(self::BELONGS_TO, 'Company', 'AgentID'),
        );
    }

    /**
     * @return mixed
     */
    public function getAdult()
    {
        return $this->adult;
    }

    /**
     * @return mixed
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @return mixed
     */
    public function getInfant()
    {
        return $this->infant;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }
}