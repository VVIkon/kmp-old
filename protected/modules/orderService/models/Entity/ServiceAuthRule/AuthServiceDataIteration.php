<?php

/**
 * Class AuthServiceDataIteration
 *
 * @property $iterationId    int(11)    Шаг авторизации
 * @property $roundId    int(11)    Раунд авторизации
 * @property $userId    bigint(20) NULL    ID пользователя, выполнинившего авторизацию
 * @property $finishDateTime    datetime NULL    таймштамп автозавершения итерации
 * @property $termination    tinyint(4) NULL    1-успешное выполнение, 0-не успешное
 * @property $autoauth    tinyint(4) NULL    1, если авторизация выполнена по истечении времени
 * @property $autoTermination    tinyint(4) NULL    0 - не ограничивать во времени авторизацию,
 *                                              1-выполнить авторизацию успешно,
 *                                              2-завершить авторизацию не успешно
 *
 * @property Account $account
 * @property AuthRuleIteration $iterationRule
 * @property AuthRuleIterationUser $authIterationUsers
 */
class AuthServiceDataIteration extends CActiveRecord
{
    const AUTO_TERMINATION_DISABLED = 0;
    const AUTO_TERMINATION_AS_SUCCESS = 1;
    const AUTO_TERMINATION_AS_FAILURE = 2;

    private $SystemUserList = [33]; // Массив системных пользователей допустимых к авторизации

    public function tableName()
    {
        return 'kt_authIterationData';
    }

    public function relations()
    {
        return array(
            'account' => array(self::HAS_ONE, 'Account', 'userId'),
            'iterationRule' => array(self::HAS_ONE, 'AuthRuleIteration', 'iterationId'),
            'authIterationUsers' => array(self::HAS_MANY, 'AuthRuleIterationUser', 'iterationId', 'condition' => 'authIterationUsers.userType = 0'),
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function bindAuthIteration(AuthRuleIteration $iteration)
    {
        $this->iterationId = $iteration->getId();
    }

    public function setFinishDateTime(DateTime $dateTime)
    {
        $this->finishDateTime = $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * @return DateTime
     */
    public function getFinishDateTime()
    {
        return new DateTime($this->finishDateTime);
    }

    public function bindAuthServiceData(AuthServiceData $authServiceData)
    {
        $this->roundId = $authServiceData->getId();
    }

    public function setAutoTermination($autoTerminationType)
    {
        $this->autoTermination = $autoTerminationType;
    }

    /**
     * @return AuthRuleIterationUser
     */
    public function getAuthIterationUsers()
    {
        return $this->authIterationUsers;
    }

    /**
     * @return array
     */
    public function getAuthIterationUsersToArray()
    {
        $users = [];
        foreach ($this->authIterationUsers as $iterationUser){
            $users[] = $iterationUser->getUserId();
        }
        return array_merge($this->SystemUserList, $users);
    }

    /**
     * @return mixed
     */
    public function getAutoTermination()
    {
        return $this->autoTermination;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return mixed
     */
    public function getTermination()
    {
        return $this->termination;
    }

    /**
     * @param mixed $termination
     */
    public function setTermination($termination)
    {
        $this->termination = $termination;
    }

    /**
     * @return mixed
     */
    public function getAutoauth()
    {
        return $this->autoauth;
    }

    /**
     * @param mixed $autoauth
     */
    public function setAutoauth($autoauth)
    {
        $this->autoauth = $autoauth;
    }

    /**
     * @return Account[]
     */
    public function getAllIterationUsers()
    {
        return $this->iterationRule->getAccounts();
    }

    public function isActual()
    {
        return is_null($this->userId);
    }
}