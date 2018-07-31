<?php

/**
 * Модель текста правил тарифа
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 01.03.2017
 * Time: 16:46
 */
class AviaTextRules extends CActiveRecord
{
    public function tableName()
    {
        return 'kt_service_fl_TextRule';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
    /**
    * Сохранение данных
    * @param array
    * @returm bool
    */
    public function saveTextRule(array $textRule)
    {
        $this->offerId = $textRule['offerId'];
        $this->tripId = $textRule['tripId'];
        $this->flightSegmentName = $textRule['flightSegmentName'];
        $this->nameRule = $textRule['name'];
        $this->textRule = $textRule['text'];
        if ($this->save()) {
            return true;
        }
        return false;
    }

    public function toArray()
    {
        $textRule['name'] = $this->nameRule;
        $textRule['text'] = $this->textRule;
        return $textRule;
    }

}