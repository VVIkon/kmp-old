<?php

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Сущность туриста
 * @property $TouristIDbase
 * @property $TouristID_UTK
 * @property $TouristID_GP
 * @property $Name
 * @property $Surname
 * @property $MiddleName
 * @property $Birthdate
 * @property $MaleFemale
 * @property $Email
 * @property $Phone
 * @property AbstractDocument [] $TouristDocuments
 */
class Tourist extends CActiveRecord implements LoggerInterface, Serializable
{
    protected $Name;
    protected $Surname;
    protected $MiddleName;
    protected $Birthdate;
    protected $Email;
    protected $Phone;
    protected $userID;

    public function tableName()
    {
        return 'kt_tourists_base';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'TouristDocuments' => array(self::HAS_MANY, 'TouristDocument', 'TouristIDbase')
        );
    }

    /**
     * Инициализация из параметров
     * @param array $params
     */
    public function fromArray(array $params)
    {
        $this->userID = StdLib::nvl($params['userId']);
        $this->Name = StdLib::nvl($params['firstName']);
        $this->Surname = StdLib::nvl($params['lastName']);
        $this->MiddleName = StdLib::nvl($params['middleName']);
        $this->Birthdate = StdLib::nvl($params['birthdate']);
        $this->MaleFemale = (isset($params['sex']) && $params['sex']) ? 1 : 0;
        $this->Email = StdLib::nvl($params['email']);
        $this->Phone = StdLib::nvl($params['phone']);
    }

    /**
     * @return mixed
     */
    public function getTouristIDbase()
    {
        return $this->TouristIDbase;
    }

    /**
    * @param string $utkTouristId
    */
    public function setUTKTouristID($utkTouristId) {
        $this->TouristID_UTK = $utkTouristId;
    }

    /**
    * @param int $gptsTouristId ID туриста из GPTS
    */
    public function setGPTSTouristID($gptsTouristId) {
        $this->TouristID_GP = $gptsTouristId;
    }

    /** @retrun int|null */
    public function getGPTSTouristID() {
        return $this->TouristID_GP;
    }

    /**
    * @return string
    */
    public function getUTKTouristID() {
        return $this->TouristID_UTK;
    }

    /**
     * @param mixed $TouristIDbase
     */
    public function setTouristIDbase($TouristIDbase)
    {
        $this->TouristIDbase = $TouristIDbase;
    }

    public function getSex()
    {
        return $this->MaleFemale;
    }

    public function getBirthdate()
    {
        return $this->Birthdate;
    }

    public function getSurname()
    {
        return $this->Surname;
    }
    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->Name;
    }

    /**
     * @return mixed
     */
    public function getMiddleName()
    {
        return $this->MiddleName;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->Email;
    }

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->Phone;
    }

    /**
     * Установка имени туриста
     * @param string $name
     * @param string $surname
     * @param string $middleName
     */
    public function setFIO($name = '', $surname = '', $middleName = '')
    {
        $this->Name = $name;
        $this->MiddleName = $middleName;
        $this->Surname = $surname;
    }

    /**
     * Установка имени туриста
     * @param string $name
     */
    public function setName($name)
    {
        $this->Name = $name;
    }
    /**
     * Установка отчества туриста
     * @param $middleName
     */
    public function setMiddleName($middleName)
    {
        $this->MiddleName = $middleName;
    }
    /**
     * Установка фамилии туриста
     * @param $surname
     */
    public function setSurname($surname)
    {
        $this->Surname = $surname;
    }

    /**
     * Установка пола туриста
     * @param $sex
     */
    public function setSex($sex)
    {
        switch ((string)$sex) {
            case 'Mr':
            case 1:
                $this->MaleFemale = 1;
                break;
            case 'Ms':
            case 0:
                $this->MaleFemale = 0;
                break;
        }
    }

    /**
    * @param string $birthdate
    */
    public function setBirthdate($birthdate) {
        $this->Birthdate = $birthdate;
    }

    /**
    * @param string $email
    */
    public function setEmail($email) {
        $this->Email = $email;
    }

    /**
    * @param string $phone
    */
    public function setPhone($phone) {
        $this->Phone = $phone;
    }

    public function __toString()
    {
        return implode(' ', [$this->Surname, $this->Name, $this->MiddleName]);
    }

    /**
     * Данные для лога
     * @return string
     */
    public function getLogData()
    {
        return 'Добавлен турист ' . $this->__toString();
    }

    /**
     * Получение возраста
     */
    public function getAge()
    {
        $FromDateTime = new DateTime($this->Birthdate);
        $ToDateTime = new DateTime('today');
        return $FromDateTime->diff($ToDateTime)->y;
    }

    /**
     * @return array
     */
    public function getSSTouristStructure()
    {
        $citizenship = 0;

        return [
            'touristId' => $this->TouristIDbase,
            'touristIdUTK' => $this->TouristID_UTK,
            'citizenshipId' => $citizenship,
            'maleFemale' => $this->MaleFemale,
            'firstName' => $this->Name,
            'middleName' => $this->MiddleName,
            'lastName' => $this->Surname,
            'dateOfBirth' => $this->Birthdate,
            'email' => $this->Email,
            'phone' => $this->Phone
        ];
    }

    /**
     * @return mixed
     */
    public function serialize()
    {
        return serialize([
            $this->TouristIDbase,
            $this->TouristID_UTK,
            $this->userID,
            $this->Name,
            $this->Surname,
            $this->MiddleName,
            $this->Birthdate,
            $this->MaleFemale,
            $this->Email,
            $this->Phone,
        ]);
    }

    /**
     * @param string $serialized
     * @return mixed
     */
    public function unserialize($serialized)
    {
        list(
            $this->TouristIDbase,
            $this->TouristID_UTK,
            $this->userID,
            $this->Name,
            $this->Surname,
            $this->MiddleName,
            $this->Birthdate,
            $this->MaleFemale,
            $this->Email,
            $this->Phone,
            ) = unserialize($serialized);
    }


    /**
     *
     */
    public function toArray()
    {
        $answer = [
            'id' => 100,                                  // (необязательный) Идентификатор туриста (для корпоратора)
            'onExtrabed' => false,                        // (пока не используется) Признак размещения туриста на доп. месте
            'onWithoutPlace' => false,                    // (пока не используется) Признак размещения туриста без предоставления доп.места
            'citizenshipId' => 1,                         // Идентификатор страны туриста (гражданство) (в терминах КТ)
            'sex' => $this->getSex(),                     // 1=М, 0=Ж

            'lastName' => $this->Surname,                 // Фамилия
            'firstName' => $this->Name,                   // Имя
            'birthdate' => $this->Birthdate               // Др
        ];

        return $answer;
    }

    public function save($runValidation = true, $attributes = null)
    {
        return parent::save(false);
    }

    /**
     * Валидационные общие метаданные
     * @param ClassMetadata $metadata
     */
    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('Name', new Assert\NotBlank(array('message' => OrdersErrors::INCORRECT_TOURIST_NAME)));
        $metadata->addPropertyConstraint('Surname', new Assert\NotBlank(array('message' => OrdersErrors::INCORRECT_TOURIST_SURNAME)));
        $metadata->addPropertyConstraint('Birthdate', new Assert\GreaterThan(array('value' => '1900-01-01', 'message' => OrdersErrors::INCORRECT_TOURIST_BIRTHDATE)));
        $metadata->addPropertyConstraint('Birthdate', new Assert\LessThan(array('value' => 'today', 'message' => OrdersErrors::INCORRECT_TOURIST_BIRTHDATE)));
    }

    /**
     * Метеданные для валидации для авиации
     * @param ClassMetadata $metadata
     */
    static public function loadAviaValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('Email', new Assert\NotBlank(array('message' => OrdersErrors::INCORRECT_TOURIST_EMAIL)));
        $metadata->addPropertyConstraint('Email', new Assert\Email(array('message' => OrdersErrors::INCORRECT_TOURIST_EMAIL)));
        $metadata->addPropertyConstraint('Phone', new Assert\NotBlank(array('message' => OrdersErrors::INCORRECT_TOURIST_PHONE)));
    }

    /**
     * Метеданные для валидации для проживания
     * @param ClassMetadata $metadata
     */
    static public function loadAccomodationValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('Name', new Assert\NotBlank(array('message' => OrdersErrors::INCORRECT_TOURIST_NAME)));
        $metadata->addPropertyConstraint('Surname', new Assert\NotBlank(array('message' => OrdersErrors::INCORRECT_TOURIST_SURNAME)));
        $metadata->addPropertyConstraint('Email', new Assert\NotBlank(array('message' => OrdersErrors::INCORRECT_TOURIST_EMAIL)));
        $metadata->addPropertyConstraint('Email', new Assert\Email(array('message' => OrdersErrors::INCORRECT_TOURIST_EMAIL)));
    }
}