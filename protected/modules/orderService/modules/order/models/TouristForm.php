<?php

/**
 * Class TouristForm
 * Реализует функциональность для работы с туристами заявки
 */
class TouristForm extends KFormModel
{

    const ADULT_AGE = 200;
    const CHILD_AGE = 12;
    const INFANT_AGE = 2;

    private $touristIdBase;

    /**
     * Базовая информация о туристе
     * @var object TouristBaseForm
     */
    public $touristBase;

    /**
     * Связь данных туриста и документа
     * @var object TouristDocMapperForm
     */
    public $touristDocMapper;

    /**
     * Информация о документе туриста
     * @var object TouristDocForm
     */
    public $touristDoc;

    /**
     * Связь ид туриста с заявкой
     * @var object TouristServiceMapperForm
     */
    public $touristServiceMapper;
     /**
     * Связь ид туриста с бонусными картами
     * @var object TouristServiceMapperForm
     */
    public $touristBonusCardArray;

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
        $this->touristDoc = new TouristDocForm($namespace);
        $this->touristDocMapper = new TouristDocMapperForm($namespace);
        $this->touristBase = new TouristBaseForm($namespace);
        $this->touristServiceMapper = new TouristServiceMapperForm($namespace);
        $this->touristBonusCardArray = [];

    }

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules()
    {
        return [
            ['touristBase', 'safe'],
        ];
    }

    /**
     * Получить туристов по ид услуги
     * @return bool|string
     */
    public static function getServiceTourists($serviceId)
    {
        if (empty($serviceId)) {
            return null;
        }

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_orders_services_tourists svc_tour')
            ->join('kt_tourists_order tourorder', 'tourorder.TouristID  = svc_tour.TouristID ')
            ->join('kt_tourists_base tourbase', 'tourbase.TouristIDBase = tourorder.TouristIDBase ')
            ->where('ServiceID = :serviceId', array(':serviceId' => $serviceId));

        $tourists = $command->queryAll();

        return $tourists;
    }

    /**
     * Получить всех туристов в указанных услугах
     * @param $servicesIds array идентифкаторы услуг
     * @return null
     */
    public static function getServicesTourists($servicesIds)
    {

        if (empty($servicesIds) || count($servicesIds) == 0) {
            return null;
        }

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_orders_services_tourists svc_tour')
            ->join('kt_tourists_order tourorder', 'tourorder.TouristID  = svc_tour.TouristID ')
            ->join('kt_tourists_base tourbase', 'tourbase.TouristIDBase = tourorder.TouristIDBase ')
            ->where(['in', 'ServiceID', $servicesIds]);

        $tourists = $command->queryAll();

        return $tourists;
    }

    /**
     * Получить информацию о туристе по его идентифкатору в УТК и ИД КТ услуги
     * @param $touristUtkId
     * @param $serviceId
     * @return null
     */
    public static function getTouristByUtkId($touristUtkId, $serviceId)
    {

        if (empty($touristUtkId)) {
            return null;
        }

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_tourists_base tbase')
            ->join('kt_tourists_order tourorder', 'tourorder.TouristIDbase  = tbase.TouristIDbase ')
            ->join('kt_orders_services_tourists tourservice', 'tourservice.TouristID  = tourorder.TouristID ')
            ->where('tbase.TouristID_UTK = :touristIdUtk and tourservice.ServiceID = :serviceId',
                array(':touristIdUtk' => $touristUtkId, ':serviceId' => $serviceId));

        $tourist = $command->queryRow();

        return $tourist;
    }

    /**
     * Получение информации о туристе по его идентификатору в УТК
     * @param $touristUtkId
     * @return CDbDataReader|mixed|null
     */
    public static function getTouristByUtkidBase($touristUtkId, $orderId = false)
    {

        if (empty($touristUtkId)) {
            return null;
        }

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_tourists_base tbase')
            ->join('kt_tourists_order tourorder', 'tourorder.TouristIDbase = tbase.TouristIDbase')
            ->where('tbase.TouristID_UTK = :touristIdUtk', array(':touristIdUtk' => $touristUtkId));

        /** если в базе наплодились туристы с одним УТК ID, это поможет взять нужного  */
        if ($orderId !== false) {
            $command->andWhere('tourorder.OrderID = :orderId', array(':orderId' => $orderId));
        }

        $tourist = $command->queryRow();

        return $tourist;
    }

    /**
     * Получение информации о туристе по его идентификатору в КТ
     * @param $touristUtkId
     * @return CDbDataReader|mixed|null
     */
    public static function getTouristByKtId($touristId)
    {
        if (empty($touristId)) {
            return null;
        }

        $command = Yii::app()->db->createCommand()
            ->select('torder.*,tbase.*,tdoc.TouristIDDoc,tdoc.TouristIDBase,tdoc.TouristIDBase,
			tdoc.Name NameByDoc, tdoc.MiddleName MiddleNameByDoc, tdoc.Surname SurNameByDoc,
			tdoc.DocTypeID, tdoc.DocSerial, tdoc.DocNumber, tdoc.DocValidFrom, tdoc.DocValidUntil,
			tdoc.IssueBy, tdoc.Address addressByDoc, tdoc.Citizenship, tdoc.UserDocId')
            ->from('kt_tourists_order torder')
            ->leftJoin('kt_tourists_base tbase', 'torder.TouristIDbase = tbase.TouristIDbase')
            ->leftJoin('kt_tourists_doc tdoc', 'torder.TouristIDdoc = tdoc.TouristIDDoc')
            ->where('torder.TouristID = :touristId', array(':touristId' => $touristId));

        try {
            $tourist = $command->queryRow();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(), __FUNCTION__,
                OrdersErrors::CANNOT_GET_TOURIST,
                $command->getText(),
                $e
            );
        }

        return $tourist;
    }

    /**
     * Сформировать объект класса TouristForm по данным из базы
     * @param $touristId идентифкатор туриста в КТ
     * @return bool
     */
    public function loadTouristByID($touristId)
    {
        $touristInfo = self::getTouristByKtId($touristId);

        if (empty($touristInfo)) {
            return false;
        }
        $this->touristIdBase = StdLib::nvl($touristInfo['TouristIDbase']);


        $touristInfo['document'] = [
            'touristBaseId' => $touristInfo['TouristIDbase'],
            'touristDocId' => $touristInfo['TouristIDdoc'],
            'name' => $touristInfo['NameByDoc'],
            'middleName' => $touristInfo['MiddleNameByDoc'],
            'surname' => $touristInfo['SurNameByDoc'],
            'docTypeId' => $touristInfo['DocTypeID'],
            'docSerial' => $touristInfo['DocSerial'],
            'docNumber' => $touristInfo['DocNumber'],
            'validFrom' => $touristInfo['DocValidFrom'],
            'validTill' => $touristInfo['DocValidUntil'],
            'issueBy' => $touristInfo['IssueBy'],
            'address' => $touristInfo['Address'],
            'citizenship' => $touristInfo['Citizenship'],
            'userDocId'=> $touristInfo['UserDocId']
//            'user_documentId'=> $touristInfo['UserDocId']
        ];

        $this->setAttributes([
            'touristId' => $touristInfo['TouristID'],
            'touristBaseId' => $touristInfo['TouristIDbase'],
            'touristDocId' => $touristInfo['TouristIDdoc'],
            'touristUtkId' => $touristInfo['TouristID_UTK'],
            'orderId' => $touristInfo['OrderID'],
            'userID'=> $touristInfo['userID'],
            'name' => $touristInfo['Name'],
            'middleName' => $touristInfo['MiddleName'],
            'surname' => $touristInfo['Surname'],
            'sex' => $touristInfo['MaleFemale'],
            'birthDate' => $touristInfo['Birthdate'],
            'email' => $touristInfo['Email'],
            'phone' => $touristInfo['Phone'],
            //'address' => $touristInfo['Address'],
            'tourLeader' => $touristInfo['TourLeader'],
            'needVisa' => $touristInfo['Visa'],
            'needInsurance' => $touristInfo['Insurance'],
            'document' => $touristInfo['document']
        ]);

        $this->touristServiceMapper->setAttributes([
            'touristId' => $this->touristDocMapper->touristId
        ]);
        $svcLinks = $this->touristServiceMapper->getTouristLinks();

        if (!empty($svcLinks)) {
            foreach ($svcLinks as $svcLink) {
                $this->touristServiceMapper->addServiceId($svcLink['ServiceID']);
            }
        }

        return true;
    }

    /**
     * Установка атрибутов класса
     * @param array $params
     * @param bool|true $safeOnly
     */
    public function setAttributes($params, $safeOnly = true)
    {
        $this->touristDocMapper->setAttributes($params, $safeOnly);

        if (!empty($params['document']) && count($params['document']) > 0) {
            $this->touristDoc->setAttributes($params['document'], $safeOnly);
        }

        $this->touristBase->setAttributes($params, $safeOnly);

        parent::setAttributes($params, $safeOnly);
    }

    /**
     * Установить соответствие названия полей услуги входящим значениям
     * @param $params
     * @return mixed
     */
    public function setParamsMapping($params)
    {

        $this->touristBase->setParamsMapping($params);
        $this->touristDocMapper->setParamsMapping($params);
        parent::setParamsMapping($params);
    }

    /**
     * Сохранить данные туриста в БД
     * @return bool|int|mixed|null
     */
    public function save()
    {

        $existedTourist = new TouristForm($this->_namespace);

        $existedTourist->loadTouristByID($this->touristId);

        if (empty($existedTourist->touristId)) {

            if (!$this->create()) {
                $this->_errorCode = OrdersErrors::CANNOT_CREATE_TOURIST;
                return false;
            }
        } else {

            if (!$this->update($existedTourist)) {

                $this->_errorCode = OrdersErrors::CANNOT_UPDATE_TOURIST;
                return false;
            }
        }
        return $this->touristId;
    }

    /**
     * Обновить данные туриста
     * @return bool
     */
    public function update($existedTourist)
    {

        $this->touristBase->touristBaseId = $existedTourist->touristDoc->touristBaseId;

        $this->touristDoc->touristDocId = $existedTourist->touristDoc->touristDocId;
        $this->touristDoc->touristBaseId = $existedTourist->touristDoc->touristBaseId;

        $this->touristDocMapper->touristDocId = $existedTourist->touristDoc->touristDocId;
        $this->touristDocMapper->touristBaseId = $existedTourist->touristDoc->touristBaseId;

        if (!$this->touristBase->save()) {
            return false;
        }

        if (!$this->touristDoc->save()) {
            return false;
        }

        if (!$this->touristDocMapper->save()) {
            return false;
        }

        $this->touristServiceMapper->setAttributes([
            'touristId' => $this->touristDocMapper->touristId
        ]);

        $this->touristServiceMapper->unmapServices();
        $this->touristServiceMapper->save();

        return $this->touristId;
    }

    /**
     * Запись в БД данных о туристе
     * и связей с услугой и документом
     * @return bool | int идентификатор созданной записи в бд
     */
    public function create()
    {

        if (!$this->touristBase->save()) {
            return false;
        }

        $this->touristDoc->touristBaseId = $this->touristBase->touristBaseId;
        if (!$this->touristDoc->save()) {
            return false;
        }

        $this->touristDocMapper->setAttributes([
            'touristBaseId' => $this->touristBase->touristBaseId,
            'touristDocId' => $this->touristDoc->touristDocId,
        ]);

        if (!$this->touristDocMapper->save()) {
            return false;
        }

        $this->touristServiceMapper->setAttributes([
            'touristId' => $this->touristDocMapper->touristId
        ]);

        $this->touristServiceMapper->save();

        return $this->touristDocMapper->touristId;
    }

    /**
     * Получить данные о туристах по идентифкаторам услуг
     * @param $servicesIds
     * @return array|CDbDataReader|null
     */
    public static function getTouristsServicesInfo($servicesIds)
    {

        if (empty($servicesIds) || count($servicesIds) == 0) {
            return null;
        }

        $command = Yii::app()->db->createCommand()
            ->select('tourbase.*, tourdoc.TouristIDdoc,
			tourdoc.DocTypeID, tourdoc.DocNumber,
			tourdoc.DocSerial, tourdoc.DocValidFrom, tourdoc.DocValidUntil,
			tourdoc.IssueBy, tourdoc.Address,tourdoc.Citizenship,
			tourdoc.Name NameByDoc, tourdoc.Surname SurnameByDoc, tourdoc.UserDocId,
			tourdoc.MiddleName MiddleNameByDoc,svc_tour.*')
            ->from('kt_orders_services_tourists svc_tour')
            ->join('kt_tourists_order tourorder', 'tourorder.TouristID  = svc_tour.TouristID ')
            ->join('kt_tourists_base tourbase', 'tourbase.TouristIDBase = tourorder.TouristIDBase')
            ->leftJoin('kt_tourists_doc tourdoc', 'tourorder.TouristIDDoc = tourdoc.TouristIDDoc')
            ->where(['in', 'ServiceID', $servicesIds]);

        $tourists = $command->queryAll();

        return $tourists;
    }

    /**
     * Получить туристов по указаному идентификатору заявки в КТ
     * @param $orderId
     */
    public static function getTouristsByOrderId($orderId)
    {

        if (empty($orderId)) {
            return null;
        }

        $command = Yii::app()->db->createCommand()
            ->select('tourbase.*, tourdoc.TouristIDdoc,
			tourdoc.DocTypeID, tourdoc.DocNumber,
			tourdoc.DocSerial, tourdoc.DocValidFrom, tourdoc.DocValidUntil,
			tourdoc.IssueBy, tourdoc.Address,tourdoc.Citizenship,
			tourdoc.Name NameByDoc, tourdoc.Surname SurnameByDoc, tourdoc.UserDocId,
			tourdoc.MiddleName MiddleNameByDoc,tourorder.TouristID, tourorder.TourLeader')
            ->from('kt_tourists_order tourorder')
            ->leftJoin('kt_orders_services_tourists svc_tour', 'tourorder.TouristID = svc_tour.TouristID ')
            ->join('kt_tourists_base tourbase', 'tourbase.TouristIDBase = tourorder.TouristIDBase')
            ->leftJoin('kt_tourists_doc tourdoc', 'tourorder.TouristIDDoc = tourdoc.TouristIDDoc')
            ->where(['in', 'OrderID', $orderId])
            ->group('TouristID');

        $tourists = $command->queryAll();

        return $tourists;
    }

    /**
     * Удаление привязок туриста к услугам
     */
    public function removeFromServices()
    {

        $touristId = $this->touristId;

        if (empty($touristId)) {
            return false;
        }

        $command = Yii::app()->db->createCommand();

        try {
            $res = $command->delete('kt_orders_services_tourists',
                'TouristID = :touristId', [':touristId' => $touristId]);
        } catch (Exception $e) {

            LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
                'Невозможно удалить привязку туриста к услугам '
                . ' турист ид ' . $touristId
                . ' Ид услуг ' . print_r($this->getTouristServicesIds(), 1)
                . print_r($e->getMessage(), 1), 'trace', $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Удаление привязки туриста к заявке
     */
    public function removeFromOrder()
    {

        $touristId = $this->touristId;

        if (empty($touristId)) {
            return false;
        }

        $command = Yii::app()->db->createCommand();

        try {
            $res = $command->delete('kt_tourists_order',
                'TouristID = :touristId', [':touristId' => $touristId]);
        } catch (Exception $e) {

            LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
                'Невозможно удалить привязку туриста к заявке ' . 'турист' . $touristId
                . ' Заявка ' . $this->orderId
                . print_r($e->getMessage(), 1), 'trace', $this->_namespace . '.errors');

            return false;
        }

        return true;
    }

    /**
     * Привязать объект документ туриста к объекту турист
     * @param $touristDoc
     */
    public function attachTouristDoc($touristDoc)
    {
        $this->touristDoc = $touristDoc;
    }

    /**
     * Привязать туриста к данным об услуге
     * @param $service array
     */
    public function linkToService($serviceId)
    {
        if (empty($serviceId) || !is_numeric($serviceId)) {
            return false;
        }

        return $this->touristServiceMapper->addServiceId($serviceId);
    }

    /**
     * Удалить привязку туриста к указанной услуге
     * @param $serviceId
     * @return bool
     */
    public function unlinkToService($serviceId)
    {

        if (empty($serviceId) || !is_numeric($serviceId)) {
            return false;
        }

        return $this->touristServiceMapper->removeServiceId($serviceId);
    }

    /**
     * Создание виртуальных свойств
     * @param string $name
     * @return int|mixed|null
     */
    public function __get($name)
    {
        if ($name == 'touristId') {
            return $this->touristDocMapper->touristId;
        }
        if ($name = 'orderId') {
            return $this->touristDocMapper->orderId;
        }

        return null;
    }

    /**
     * Необходимый метод для корректной проверки
     * существования виртуального свойства
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        if ($name == 'touristId') {
            return isset($this->touristDocMapper->touristId);
        }

        if ($name = 'orderId') {
            return isset($this->touristServiceMapper->orderId);
        }
    }

    /**
     * Получить базовую информацию о туристе
     * @return array
     */
    public function getBaseInfo()
    {
        $info = [];
        $info['touristId'] = $this->touristId;
        $info['userId'] = $this->touristBase->userID;
        $info['firstName'] = $this->touristBase->name;
        $info['surName'] = $this->touristBase->surname;
        $info['middleName'] = $this->touristBase->middleName;
        $info['email'] = $this->touristBase->email;
        $info['phone'] = $this->touristBase->phone;
        $info['sex'] = $this->touristBase->sex;
        $info['birthdate'] = $this->touristBase->birthDate;
        //	$info['address']		=  $this->touristBase->address;
        $info['isTourLeader'] = ($this->touristDocMapper->tourLeader) ? true : false;
        $info['needVisa'] = $this->touristDocMapper->needVisa;
        $info['needInsurance'] = $this->touristDocMapper->needInsurance;

        $info['bonusCards'] = $this->getTouristBonusCards();

        return $info;
    }

    /**
     * Получить информацию о документе туриста
     * @return array
     */
    public function getDocInfo()
    {
        $info = [];
        $info['serialNum'] = $this->touristDoc->docSerial;
        $info['user_documentId'] = $this->touristDoc->userDocId;
        $info['number'] = $this->touristDoc->docNumber;
        $info['firstName'] = $this->touristDoc->name;
        $info['surName'] = $this->touristDoc->surname;
        $info['middleName'] = $this->touristDoc->middleName;
        $info['issueDate'] = $this->touristDoc->validFrom;
        $info['expiryDate'] = $this->touristDoc->validTill;
        $info['issueDepartment'] = $this->touristDoc->issueBy;
        $info['documentType'] = $this->touristDoc->docTypeId;
        $info['citizenship'] = $this->touristDoc->citizenship;
        return $info;
    }


    /**
     * Получить возраст туриста
     * @param $dateOfBirth datetime дата рождения
     * @return bool|int количество лет
     */
    public static function getAge($dateOfBirth)
    {
        if (empty($dateOfBirth)) {
            return false;
        }

        $touristBirthYear = DateTime::createFromFormat('Y-m-d', $dateOfBirth)->format('Y');

        $years = date("Y") - $touristBirthYear;

        return $years;
    }

    /**
     * Получить информацию об услугах к которым привязан турист
     * @return array
     */
    public function getTouristServicesIds()
    {

        return $this->touristServiceMapper->getServicesIds();
    }

    public function getTouristBonusCards()
    {
        if (empty($this->touristIdBase)) {
            return null;
        }
        try {
            $command = Yii::app()->db->createCommand()
                ->select('id, bonusCardNumber as bonuscardNumber, loyalityProgramId as aviaLoyaltyProgramId')
                ->from('kt_touristBonusCard')
                ->where('TouristIdBase = :touristIdBase AND active = 1', array(':touristIdBase' => $this->touristIdBase));

            $bonusCards = $command->queryAll();
            $this->touristBonusCardArray = $bonusCards;
        }
        catch(Exception $e) {
            LogHelper::log(PHP_EOL . get_class(). '.' . __FUNCTION__. PHP_EOL.
                'Невозможно обновить данные по бонусным картам туриста ' . print_r($this->getAttributes(),1)
                . 'Ошибка' . PHP_EOL. print_r($e->getMessage(),1),'trace',
                $this->_namespace. '.errors');
            return false;
        }

        return $bonusCards;
    }

}
