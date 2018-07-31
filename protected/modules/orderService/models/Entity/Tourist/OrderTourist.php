<?php

use Symfony\Component\Validator\Validation;

/**
 *
 * @property $TouristID
 * @property $OrderID
 * @property $TourLeader
 * @property $Visa
 * @property $Insurance
 * @property $TouristIDbase
 * @property $TouristIDdoc
 *
 * @property OrderModel $OrderModel
 * @property Tourist $Tourist
 * @property TouristDocument $TouristDocument
 * @property TouristBonusCard[] $TouristBonusCards
 */
class OrderTourist extends CActiveRecord
{

    protected $TouristBonusCards;


    public function tableName()
    {
        return 'kt_tourists_order';
    }

    public function relations()
    {
        return array(
            'Tourist' => array(self::BELONGS_TO, 'Tourist', 'TouristIDbase'),
            'TouristDocument' => array(self::BELONGS_TO, 'TouristDocument', 'TouristIDdoc'),
            'OrderModel' => array(self::BELONGS_TO, 'OrderModel', 'OrderID'),
            'TouristBonusCards' => array(self::HAS_MANY, 'TouristBonusCard', 'touristId'),
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Инициализация из массива
     * @param $params
     * @throws Exception
     */
    public function fromArray(array $params)
    {
        // создание валидатора
        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();

        // создаем туриста из параметров
        $Tourist = $this->getTourist();

        if (is_null($Tourist)) {
            $Tourist = new Tourist();
        }

        // сохраним туриста
        $this->Tourist = $Tourist;

        $Tourist->fromArray($params);
        $violations = $validator->validate($Tourist);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                throw new TouristException($violation->getMessage());
            }
        } else {
            if (!$Tourist->save()) {
                throw new Exception(OrdersErrors::DB_ERROR);
            }
        }
        // сохраним бонусные карты туриста
        if (isset($params['bonusCards'])) {
            if (is_null($this->TouristBonusCards) ) {

                $touristIdBase = $Tourist->getTouristIDbase();
                foreach ($params['bonusCards'] as $bonusCard) {
                    if (isset($bonusCard['id']) ) {
                        $TouristBonusCard = TouristBonusCard::model()->findByPk($bonusCard['id']);
                    }else{
                        $TouristBonusCard = new TouristBonusCard();
                    }
                    $modified = $TouristBonusCard->fromParams($bonusCard, $touristIdBase);
                    // Если бонусная карта не изменялась, не сохранять
                    if (!$modified){
                        continue;
                    }
                    if (!$TouristBonusCard->save()) {
                        throw new Exception(OrdersErrors::DB_ERROR);
                    }
                }
            }
        }

        // создадим документ из параметров
        if (isset($params['document'])) {
            if ($this->isNewRecord && isset($params['document'])) {
                $TouristDocument = new TouristDocument();
            } else {
                $TouristDocument = $this->getDocument();
            }

            if (is_null($TouristDocument)) {
                throw new DocumentException(OrdersErrors::INCORRECT_TOURIST_DOCUMENT_TYPE);
            }

            $TouristDocument->fromArray($params['document']);
            $TouristDocument->bindTourist($Tourist);

            $violations = $validator->validate($TouristDocument);

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    throw new TouristException($violation->getMessage());
                }
            } else {
                if (!$TouristDocument->save()) {
                    throw new Exception(OrdersErrors::DB_ERROR);
                }
            }

            $this->TouristIDdoc = $TouristDocument->getTouristIDdoc();
        }

        // пока не определена данная логика, поэтому всегда false
        $this->setTourLeader(false);

        // вытащим все данные
        $this->TouristIDbase = $Tourist->getTouristIDbase();
    }

    /**
     * Если турлидер
     * @return bool
     */
    public function isTourLeader()
    {
        return $this->TourLeader == 1;
    }

    /**
     * @param mixed $TourLeader
     */
    public function setTourLeader($TourLeader)
    {
        $this->TourLeader = ($TourLeader) ? 1 : 0;
    }

    public function makeTourLeader()
    {
        $this->TourLeader = 1;
    }

    /**
     * @param mixed $OrderID
     */
    public function setOrderID($OrderID)
    {
        $this->OrderID = $OrderID;
    }

    /**
     * @return mixed
     */
    public function getTouristID()
    {
        return $this->TouristID;
    }

    /**
     * Возвращает данные туриста
     * @return null|Tourist
     */
    public function getTourist()
    {
        return $this->Tourist;
    }

    /**
     * Привязка сущности туриста
     * @param Tourist $Tourist
     */
    public function bindTourist(Tourist $Tourist)
    {
        $this->TouristIDbase = $Tourist->getTouristIDbase();
        $documents = $Tourist->TouristDocuments;
        if (!empty($documents)) {
            $this->TouristIDdoc = $documents[0]->getTouristIDdoc();
        }
    }

    /**
     *
     * @return TouristDocument
     */
    public function getDocument()
    {
        return $this->TouristDocument;
    }

    /**
     *
     * @return TouristBonusCard[]
     */
    public function getBonusCards()
    {
        return $this->TouristBonusCards;
    }
}