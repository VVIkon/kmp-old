<?php

/**
 * Модель правила тарифа
 * @property $tripId
 *
 */
class AviaFareRules extends CActiveRecord
{
    public function tableName()
    {
        return 'kt_service_fl_FareRule';
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
    public function saveFareRule(array $shortRule)
    {
        $this->offerId = $shortRule['offerId'];
        $this->tripId = $shortRule['tripId'];
        $this->flightSegmentName = $shortRule['flightSegmentName'];
        $this->refund_before_rule = $shortRule['refund_before_rule'];
        $this->refund_before_penalty = $shortRule['refund_before_penalty'];
        $this-> refund_before_penalty_perc= $shortRule['refund_before_penalty_perc'];
        $this-> refund_after_rule= $shortRule['refund_after_rule'];
        $this->refund_after_penalty = $shortRule['refund_after_penalty'];
        $this->refund_after_penalty_perc = $shortRule['refund_after_penalty_perc'];
        $this->change_before_rule = $shortRule['change_before_rule'];
        $this->change_before_penalty = $shortRule['change_before_penalty'];
        $this->change_before_penalty_perc = $shortRule['change_before_penalty_perc'];
        $this-> change_after_rule= $shortRule['change_after_rule'];
        $this->change_after_penalty = $shortRule['change_after_penalty'];
        $this->change_after_penalty_perc = $shortRule['change_after_penalty_perc'];
        $this->online_change = $shortRule['online_change'];
        $this->penalty_currency = $shortRule['penalty_currency'];
        if ($this->save()){
            return true;
        }
        return false;
    }

    public function toArray()
    {
        $shortRules['refund_before_rule'] = StdLib::dcl($this->refund_before_rule);
        $shortRules['refund_before_penalty'] = StdLib::dcl($this->refund_before_penalty);
        $shortRules['refund_before_penalty_perc'] = StdLib::dcl($this-> refund_before_penalty_perc);
        $shortRules['refund_after_rule'] = StdLib::dcl($this-> refund_after_rule);
        $shortRules['refund_after_penalty'] = StdLib::dcl($this->refund_after_penalty);
        $shortRules['refund_after_penalty_perc'] = StdLib::dcl($this->refund_after_penalty_perc);
        $shortRules['change_before_rule'] = StdLib::dcl($this->change_before_rule);
        $shortRules['change_before_penalty'] = StdLib::dcl($this->change_before_penalty);
        $shortRules['change_before_penalty_perc'] = StdLib::dcl($this->change_before_penalty_perc);
        $shortRules['change_after_rule'] = StdLib::dcl($this-> change_after_rule);
        $shortRules['change_after_penalty'] = StdLib::dcl($this->change_after_penalty);
        $shortRules['change_after_penalty_perc'] = StdLib::dcl($this->change_after_penalty_perc);
        $shortRules['online_change'] = StdLib::dcl($this->online_change);
        $shortRules['penalty_currency'] = StdLib::dcl($this->penalty_currency);
        return $shortRules;
    }

}