<?php

/**
 * Запрос на поиск отелей
 *
 * @property Company $company
 */
class HotelSearchRequest extends CActiveRecord
{
    public function tableName()
    {
        return 'ho_searchRequest';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'company' => array(self::BELONGS_TO, 'Company', 'agentId'),
        );
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }
}