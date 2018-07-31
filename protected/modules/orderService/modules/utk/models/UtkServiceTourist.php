<?php

/**
 * Class UtkServiceTourist
 * Реализует функциональность работы с данными туриста заявки для УТК
 */
class UtkServiceTourist extends KFormModel
{
    const STATUS_UNDEFINED = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_CLOSED = 2;
    const STATUS_ANNULED = 3;
    const STATUS_STAND_BY = 4;

    /** @var int Идентифкатор услуги в КТ */
    public $serviceId;

    /** @var int Идентифкатор услуги в УТК*/
    public $serviceIdUtk;

    /** @var string время обновления туриста*/
    public $personDateUpdate;

    /** @var string статус туриста */
    public $status;

    /** @var string Идентифкатор туриста в КТ*/
    public $personId;

    /** @var string Идентифкатор туриста в КТ*/
    public $personIdUtk;

    /** @var string Тип туриста*/
    public $personType;

    /** @var string Имя*/
    public $firstName;

    /** @var string Фамилия*/
    public $lastName;

    /** @var string Отчество*/
    public $middleName;

    /** @var string Имя национальное */
    public $nat_firstName;

    /** @var string Фамилия национальное */
    public $nat_lastName;

    /** @var string Отчество национальное */
    public $nat_middleName;

    /** @var string Дата рождения*/
    public $dateOfBirth;

    /** @var int Признак турлидера */
    public $isTourLead;

    /** @var string Пол туриста*/
    public $sex;

    protected $addData = [];

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules() {
        return array(
            [
                'serviceId,serviceIdUtk,personDateUpdate,status,personId,personIdUtk,personType,
                firstName,lastName,middleName,dateOfBirth,isTourLead,sex,nat_firstName,nat_lastName,nat_middleName', 'safe'
            ]
        );
    }

    /**
     * Инициализация свойств туриста услуги
     * @param $touristId integer идентифкатор туриста
     * @param $serviceId integer идентифкатор услуги, к которой привязан турист
     * @return bool
     */
    public function load($touristId, $serviceId)
    {

        $tourist = new TouristForm('');
        $tourist->loadTouristByID($touristId);

        if (!$tourist->loadTouristByID($touristId)) {
            throw new KmpException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::INCORRECT_TOURIST_STRUCTURE,
                [
                    'touristId' => $touristId
                ]
            );
        }

        $serviceInfo = $this->getService($serviceId);

        $this->setAttributes([
            'serviceId' => $serviceId,
            'serviceIdUtk' => $serviceInfo['ServiceID_UTK'],
            'personDateUpdate' => '',
            'status' => 1,
            'personId' => $tourist->touristBase->touristBaseId,
            'personIdUtk' => $tourist->touristBase->touristUtkId,
            'personType' => '' ,
            'firstName' => $tourist->touristDoc->name,
            'lastName' => $tourist->touristDoc->surname,
            'middleName' => $tourist->touristDoc->middleName,
            'dateOfBirth' => $tourist->touristBase->birthDate,
            'isTourLead' => $tourist->touristDocMapper->tourLeader,
            'nat_firstName' => $tourist->touristBase->name,
            'nat_lastName' => $tourist->touristBase->surname,
            'nat_middleName' => $tourist->touristBase->middleName,
            'sex' => $tourist->touristBase->sex
        ]);

        // доп поля
        $orderTourist = OrderTouristRepository::getByOrderTouristId($touristId);
        $touristAddFields = OrderAdditionalFieldRepository::getTouristFieldWithId($orderTourist);

        foreach ($touristAddFields as $touristAddField) {
            $this->addData[] = $touristAddField->getSOUTKOrderAdditionalData();
        }

        return true;
    }

    /**
     * Вывод свойств объекта в виде массива
     * @return array
     */
    public function toArray()
    {
        return [
            'serviceId' =>  $this->serviceId,
            'serviceIdUTK' =>  $this->serviceIdUtk,
            'personId' =>  $this->personId,
            'personIdUTK' =>  $this->personIdUtk,
            'personType' =>  '',
            'firstName' =>  $this->firstName,
            'middleName' =>  $this->middleName,
            'lastName' =>  $this->lastName,
            'nat_firstName' => $this->nat_firstName,
            'nat_lastName' => $this->nat_lastName,
            'nat_middleName' => $this->nat_middleName,
            'dateOfBirth' =>  UtkDateTime::getUtkDate($this->dateOfBirth),
            'isTourLead' =>  $this->isTourLead,
            'sex' =>  $this->sex,
            'citizenship' =>  'RU',
            'orderAdditionalData' => $this->addData
        ];
    }

    /**
     * Получение указанной услуги
     * @param $serviceId
     * @return null
     */
    private function getService($serviceId)
    {
        $service = ServicesForm::getServiceById($serviceId);

        if (empty($service)) {
            throw new KmpException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::SERVICE_NOT_FOUND,
                [
                    'serviceId' => $serviceId
                ]
            );
        }
        return $service;
    }

}
