<?php

/**
 * Class AgentForm
 * Реализует функциональность для работы с данными агентств
 */
class AgentForm extends KFormModel
{
    /**
     * Операция создания агентства
     */
    const OPERATION_CREATE = 1;

    /**
     * Операция обновления агентства
     */
    const OPERATION_UPDATE = 2;

    /**
     * Идентификатор агентства в КТ
     * @var int
     */
    public $agentId;

    /**
     * Идентификатор агентства в ГПТС
     * @var string
     */
    public $agentGPTSId;

    /**
     * Идентификатор агентства в УТК
     * @var string
     */
    public $agentUTKId;

    /**
     * Наименование агентства
     * @var string
     */
    public $agentName;

    /**
     * Тип агентства
     * @var int
     */
    public $agentType;

    /**
     * ИНН агентства
     * @var string
     */
    public $INN;

    /**
     * КПП агентства
     * @var string
     */
    public $KPP;

    /**
     * Пользователи, работающие от имени агентства
     * @var array AgencyUserForm
     */
    public $agencyUsers;

    /**
     * Договоры заключённые с агентством
     * @var array AgencyContractForm
     */
    public $agencyContracts;

    /**
     * Namespace выполнения
     * @var string
     */
    private $_namespace;

    /**
     * Конструктор объекта
     * @param string $namespace
     */
    public function __construct($namespace)
    {
        $this->_namespace = $namespace;
    }

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules()
    {
        return [
            [
                'agentId,agentGPTSId,agentUTKId,agentName,agentType,INN,KPP', 'safe'
            ]
        ];
    }

    /**
     * Получить данные агентства по ид агентства из КТ
     * @param $agencyId
     * @return bool
     */
    public function getAgencyByID($agencyId)
    {

        if (empty($agencyId)) {
            return false;
        }

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_companies')
            ->where('kt_companies.AgentID = :agentId', array(':agentId' => $agencyId));

        return $command->queryRow();

    }

    /**
     * Получить ид агентства по ид агентства из УТК
     * @param $agentUtkId
     * @return bool
     */
    public function getAgencyIdByUtkID($agencyUtkId)
    {

        $agency = $this->getAgencyByUtkID($agencyUtkId);

        if (empty($agency) || !isset($agency['AgentID'])) {
            return false;
        }

        return $agency['AgentID'];
    }

    /**
     * Получить ид агентства по ид агентства из ГПТС
     * @param $agencyGptsId
     * @return bool
     */
    public function getAgencyIdByGptsID($agencyGptsId)
    {
        $agency = $this->getAgencyByGptsID($agencyGptsId);

        if (empty($agency) || !isset($agency['AgentID'])) {
            return false;
        }

        return $agency['AgentID'];
    }

    /**
     * Получить агентство по ид ГПТС
     * @param $agencyGptsId
     * @return bool
     */
    public function getAgencyByGptsID($agencyGptsId)
    {

        if (empty($agencyGptsId)) {
            return false;
        }

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_companies')
            ->where('kt_companies.AgentID_GP = :agentGptsId', array(':agentGptsId' => $agencyGptsId));

        return $command->queryRow();
    }

    /**
     * Получить данные агентства по ид агентства из УТК
     * @param $agentUtkId
     * @return bool
     */
    public function getAgencyByUtkID($agencyUtkId)
    {

        if (empty($agencyUtkId)) {
            return false;
        }

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_companies')
            ->where('kt_companies.AgentID_UTK = :agentUtkId', array(':agentUtkId' => $agencyUtkId));

        return $command->queryRow();
    }

    /**
     * Получить ид менеджера агентства
     * по ид менеджера агентства из УТК
     * @param $userUtkId
     */
    public function getAgentUserIdByUtkId($userUtkId)
    {

        $user = $this->getAgencyUserByUtkId($userUtkId);

        if (empty($user) || !isset($user['UserID'])) {
            return false;
        }

        return $user['UserID'];
    }

    /**
     * Получение данные менеджера агентства
     * по его ид из УТК
     * @param $userUtkId
     */
    public function getAgencyUserByUtkId($userUtkId)
    {

        if (empty($userUtkId)) {
            return false;
        }

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_users')
            ->where('kt_users.UserID_UTK = :agentUtkId', array(':agentUtkId' => $userUtkId));

        return $command->queryRow();
    }

    /**
     * Получение агентской комиссии по договору
     * @param $agencyId
     * @return bool
     */
    public function getAgencyContractCommission($agencyId, $contractId = null)
    {

        if (empty($agencyId)) {
            return false;
        }

        $agencyContracts = $this->getAgencyContracts($agencyId);

        if (empty($agencyContracts) || !is_array($agencyContracts)) {
            return false;
        }

        if (!$contractId) {
            return (!empty($agencyContracts[0]['Commission'])) ? $agencyContracts[0]['Commission'] : 0;
        } else {

            foreach ($agencyContracts as $contract) {
                if ($contract['ContractID'] == $contractId) {
                    return (!empty($contract['Commission'])) ? $contract['Commission'] : 0;
                }
            }

            return false;
        }

    }

    /**
     * Получение списка агентских контрактов
     * @param $agencyId
     * @return bool
     */
    public function getAgencyContracts($agencyId)
    {

        if (empty($agencyId)) {
            return false;
        }

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_contracts')
            ->where('AgentID = :agentid and active = :active', [
                ':agentid' => $agencyId,
                ':active' => 1
            ]);

        return $command->queryAll();

    }

    /**
     * Получение агентского договора по его УТК ИД
     * @param $agencyId
     * @return bool
     */
    public function getAgencyContractByUtkId($contractUtkId)
    {

        if (empty($contractUtkId)) {
            return false;
        }
        try {
            $command = Yii::app()->db->createCommand()
                ->select('*')
                ->from('kt_contracts')
                ->where('ContractID_UTK = :contractIdUtk', array(':contractIdUtk' => $contractUtkId));
        } catch (Exception $e) {
            LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
                'Невозможно получить данные договора агентства по УТК ид:' . $contractUtkId . ' ' .
                print_r($e->getMessage(), 1), 'trace', $this->_namespace . '.errors');
        }

        $contractInfo = $command->queryRow();

        if (empty($contractInfo) || empty($contractInfo['ContractID'])) {

            LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
                'Невозможно получить данные договора агентства по ид' . $contractUtkId . ' '
                , 'trace', $this->_namespace . '.errors');
            return false;
        }

        return $contractInfo['ContractID'];
    }

    /**
     * Проверка актуальности хотя бы одного из агентских договоров
     * если не указан параметр date, проверка актуальности производиться
     * на текущую дату
     * @param $agentInfo
     * @param null $date
     * @return bool
     */
    public function isActiveAgent($agentInfo, $date = null)
    {

        $limitlessDate = '0001-01-01';


        if (empty($agentInfo) || empty($agentInfo['AgentID'])) {
            return false;
        }

        if ($date == null) {
            $date = time();
        }

        $contract = $this->getActiveAgencyContractId($agentInfo['AgentID'], $date);

        if (empty($contract)) {
            return false;
        }

        return true;
    }

    /**
     * Получить любой активный договор агентства
     * @param $agencyId
     * @param null $date
     * @return bool
     */
    public function getActiveAgencyContractId($agencyId, $date = null)
    {
        if (empty($agencyId)) {
            return false;
        }

        $contracts = $this->getAgencyContracts($agencyId);

        if (empty($contracts)) {
            return false;
        }

        if ($date == null) {
            $date = new DateTime();
        }

        foreach ($contracts as $contract) {
            if (empty($contract['ContractExpiry'])) {
                continue;
            }

            //Проверка если контракт бессрочный то дата его окончания 0001-01-01
            if ($contract['ContractExpiry'] == '0001-01-01') {
                return $contract['ContractID'];
            }

            if (!strtotime($contract['ContractExpiry'] . ' 23:59:59')) {
                continue;
            }

            if (strtotime($contract['ContractExpiry'] . ' 23:59:59') > $date->getTimestamp()) {

                return $contract['ContractID'];
            }
        }

        return false;
    }

    /**
     * Добавить к объекту агентства объект агентского договора
     * @param $contract object
     * @return bool
     */
    public function addContractItem($contract)
    {

        if (empty($contract)) {
            return false;
        }

        $this->agencyContracts[] = $contract;
    }

    /**
     * Добавить к объекту агентства объект пользователя агентства
     * @param $contract object
     * @return bool
     */
    public function addUserItem($user)
    {

        if (empty($user)) {
            return false;
        }

        $this->agencyUsers[] = $user;
    }

    /**
     * Установить значения агентства его договоров
     * и пользователей из информации от УТК
     * @param $agencyInfo
     * @return bool
     */
    public function setAttributesFromUtk($agencyInfo)
    {

        if (empty($agencyInfo)) {
            return false;
        }

        $this->setParamsMapping([
            'clientId' => 'agentId',
            'client_GPTS_ID' => 'agentGPTSId',
            'clientID_UTK' => 'agentUTKId',
            'clientName' => 'agentName'
        ]);

        $this->setAttributes($agencyInfo);

        if (!empty($agencyInfo['contracts']) && is_array($agencyInfo['contracts'])) {
            $CurrencyRates = CurrencyRates::getInstance();

            foreach ($agencyInfo['contracts'] as $contract) {

                $agencyContract = new AgencyContractForm($this->_namespace);

                $agencyContract->setParamsMapping([
                    'contract' => 'contract',
                    'contractDate' => 'contractDate',
                    'contractExpiry' => 'contractExpiry',
                    'contractId' => 'contractId',
                    'contractIdUTK' => 'contractUTKId',
                    'commission' => 'commission',
                ]);

                $agencyContract->setAttributes($contract);
                $agencyContract->currencyId = $CurrencyRates->getIdByCode($contract['currency']);

                $this->addContractItem($agencyContract);
            }
        }

        if (!empty($agencyInfo['clientUsers']) && count($agencyInfo['clientUsers']) > 0) {

            foreach ($agencyInfo['clientUsers'] as $user) {

                $agencyUser = new AgencyUserForm($this->_namespace);

                $agencyUser->setParamsMapping([
                    'UTK_id' => 'userUTKId',
                    'gpts_i' => 'userGPTSId',
                    'email' => 'email',
                    'name' => 'name',
                    'surName' => 'surName',
                    'sndName' => 'sndName',
                ]);

                $agencyUser->setAttributes($user);

                $this->addUserItem($agencyUser);
            }
        }
        return true;
    }

    /**
     * Создание|изменение данных агентства в БД
     * @return bool
     */
    public function save()
    {

        if (empty($this->agentUTKId) && empty($this->agentGPTSId)) {
            return false;
        }

        if (!empty($this->agentUTKId)) {
            $existedAgency = AgentForm::getAgencyByUtkID($this->agentUTKId);
        }

        if (empty($existedAgency)) {
            $existedAgency = AgentForm::getAgencyByGptsID($this->agentGPTSId);
        }

        if (!$existedAgency) {

            $operation = self::OPERATION_CREATE;
            $result = $this->createAgency();
        } else {

            $operation = self::OPERATION_UPDATE;
            $result = $this->updateAgency($existedAgency);
        }

        if ($result) {
            $result = [
                'operation' => $operation,
                'agentId' => $result['agencyId'],
                'agencyUsers' => $result['agencyUsers'],
                'agencyContracts' => $result['agencyContracts'],
            ];
        }

        return $result;
    }

    /**
     * Создание информации об агентстве,
     * его пользователях и договорах в БД (сериализация)
     * @return bool|int
     */
    public function createAgency()
    {

        $contracts = [];
        $users = [];

        if (empty($this->agentUTKId) && empty($this->agentGPTSId)) {
            return false;
        }

        if (!$this->createAgencyData()) {
            return false;
        }

        foreach ($this->agencyContracts as $contract) {
            $contract->setAgencyId($this->agentId);
        }
        $contracts = $this->processAgencyContracts();
        if (!$contracts) {
            return false;
        }

        if (!empty($this->agencyUsers)) {
            foreach ($this->agencyUsers as $user) {
                $user->setAgencyId($this->agentId);
            }

            $users = $this->processAgencyUsers();
            if (!$users) {
                return false;
            }
        }

        return [
            'agencyId' => $this->agentId,
            'agencyUsers' => $users,
            'agencyContracts' => $contracts
        ];
    }

    /**
     * Обновление всех данных агентства в БД
     * @param $existedAgencyData
     * @return bool
     */
    public function updateAgency($existedAgencyData)
    {

        $contracts = [];
        $users = [];

        if (empty($this->agentUTKId) && empty($this->agentGPTSId)) {
            return false;
        }

        $this->setParamsMapping([
            'AgentID' => 'agentId',
            'AgentID_GP' => 'agentGPTSId',
            'AgentID_UTK' => 'agentUTKId',
            'Name' => 'agentName',
            'Type' => 'agentType'
        ]);

        $this->setAttributesIfNull($existedAgencyData);

        if (!$this->updateAgencyData()) {
            return false;
        }

        foreach ($this->agencyContracts as $contract) {
            $contract->setAgencyId($this->agentId);
        }

        $contracts = $this->processAgencyContracts();
        if (!$contracts) {
            return false;
        }

        if (!empty($this->agencyUsers)) {

            foreach ($this->agencyUsers as $user) {
                $user->setAgencyId($this->agentId);
            }

            $users = $this->processAgencyUsers();
            if (!$users) {
                return false;
            }
        }

        return [
            'agencyId' => $this->agentId,
            'agencyContracts' => $contracts,
            'agencyUsers' => $users
        ];
    }

    /**
     * Создание данных агентства в БД
     * @return int
     */
    protected function createAgencyData()
    {

        $command = Yii::app()->db->createCommand();

        try {
            $res = $command->insert('kt_companies', [
                'AgentID_GP' => $this->agentGPTSId,
                'AgentID_UTK' => $this->agentUTKId,
                'Name' => $this->agentName,
                'Type' => empty($this->agentType) ? 2 : $this->agentType,
                'CountryID' => null,
                'CityID' => null,
                'Prefix' => null,
                'FirstName' => null,
                'Middlename' => null,
                'Lastname' => null,
                'Phone' => null,
                'email' => null,
                'url' => null,
                'active' => null,
                'OfficialCompanyName' => null,
            ]);
        } catch (Exception $e) {

            LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
                'Невозможно записать данные агентства ' . print_r($e->getMessage(), 1), 'trace',
                $this->_namespace . '.errors');
            return false;
        }

        $this->agentId = Yii::app()->db->lastInsertID;

        return $this->agentId;
    }

    /**
     * Обновление данных об агентстве в БД
     * @return bool
     */
    protected function updateAgencyData()
    {

        $command = Yii::app()->db->createCommand();

        try {
            $res = $command->update('kt_companies', [

                'AgentID_GP' => $this->agentGPTSId,
                'AgentID_UTK' => $this->agentUTKId,
                'Name' => $this->agentName,
                'Type' => empty($this->agentType) ? 2 : $this->agentType,
                /*'CountryID' => null,
                'CityID' => null,
                'Prefix' => null,
                'FirstName' => null,
                'Middlename' => null,
                'Lastname' => null,
                'Phone' => null,
                'email' => null,
                'url' => null,
                'active' => null,
                'OfficialCompanyName' => null,*/
            ], 'AgentID = :agentId', [':agentId' => $this->agentId]);
        } catch (Exception $e) {

            LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
                'Невозможно записать данные агентства ' . print_r($e->getMessage(), 1), 'trace',
                $this->_namespace . '.errors');
            return false;
        }
        return true;
    }

    /**
     * Создание|обновление данных о договорах агентства в БД
     * @return bool
     */
    protected function processAgencyContracts()
    {

        if (empty($this->agentId)) {
            return false;
        }

        foreach ($this->agencyContracts as $key => $contract) {

            $result = $contract->save();
            if (!$result) {
                return false;
            } else {
                $contracts[] = $result;
            }
        }
        return $contracts;
    }

    /**
     * Создание|обновление данных пользователей агентства в БД
     * @return bool
     */
    protected function processAgencyUsers()
    {
        $users = [];
        if (empty($this->agentId)) {
            return false;
        }

        foreach ($this->agencyUsers as $user) {

            $result = $user->save();
            if (!$result) {
                return false;
            } else {
                $users[] = $result;
            }
        }

        return $users;
    }

}
