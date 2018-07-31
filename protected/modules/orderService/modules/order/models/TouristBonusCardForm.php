<?php

/**
 * Реализует функциональность для работы с данными бонусными карты туриста
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 23.06.17
 * Time: 11:55
 */
class TouristBonusCardForm extends KFormModel
{
    /**
     * id
     * @var int
     */
    public $Id;
    /**
     * Идентификатор туриста
     * @var int
     */
    public $touristId;
    /**
     * @var int
     */
    public $bonusCardNumber;
    /**
     * @var string
     */
    public $loyalityProgramId;
    /**
     * @var int
     */
    public $active;

    /**
     * namespace для записи логов
     * @var string
     */
    private $_namespace;

    /**
     * Конструктор объекта
     * @param array $values
     */
    public function __construct($namespace)
    {
        $this->_namespace = $namespace;
    }

    public function rules()
    {
        return [
            ['touristId, bonusCardNumber, loyalityProgramId, active']
        ];
    }

    /**
     * Создание|обновление бонусных карт туриста
     * @return bool|void
     */
    public function save($id)
    {
        if (empty($id)) {
            return $this->create();
        } else {
            return $this->update($id);
        }

    }

    /**
     * Создание записи бонусной карты туриста в БД
     * @return int ид вставленной записи
     */
    public function create()
    {
        $command = Yii::app()->db->createCommand();
        $res = $command->insert('kt_touristBonusCard', [
            'touristId'         => $this->touristId,
            'bonusCardNumber'   => $this->bonusCardNumber,
            'loyalityProgramId' => $this->loyalityProgramId,
            'active'    		=> $this->active,
        ]);
        $this->Id = Yii::app()->db->lastInsertID;
        return $this->Id;
    }

    /**
     * Обновление записи бонусной карты
     * @return bool
     */
    private function update($id)
    {
        $command = Yii::app()->db->createCommand();
        try {
            $res = $command->update('kt_touristBonusCard', [
                'touristId'         => $this->touristId,
                'bonusCardNumber'   => $this->bonusCardNumber,
                'loyalityProgramId' => $this->loyalityProgramId,
                'active'    		=> $this->active,
            ], 'id = :id', [':id' => $id]);
        } catch(Exception $e) {
            LogHelper::log(PHP_EOL . get_class(). '.' . __FUNCTION__. PHP_EOL.
                'Невозможно обновить данные бонусной карты туриста ' . print_r($this->getAttributes(),1)
                . 'Ошибка' . PHP_EOL. print_r($e->getMessage(),1),'trace',
                $this->_namespace. '.errors');
            return false;
        }

        return true;
    }

}