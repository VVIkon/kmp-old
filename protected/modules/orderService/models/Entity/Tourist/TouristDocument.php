<?php

/**
 * Модель документа туриста
 *
 * @property $TouristIDdoc
 * @property $TouristIDbase
 */
class TouristDocument extends AbstractDocument
{
    public function tableName()
    {
        return 'kt_tourists_doc';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param $Tourist Tourist
     * @return mixed
     */
    public function bindTourist(Tourist $Tourist)
    {
        return $this->TouristIDbase = $Tourist->getTouristIDbase();
    }

    /**
     * @return mixed
     */
    public function getTouristIDdoc()
    {
        return $this->TouristIDdoc;
    }
}