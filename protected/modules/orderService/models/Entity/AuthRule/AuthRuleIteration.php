<?php

/**
 * Class AuthRuleIteration
 *
 * @property $iterationId    int(11)    Шаг авторизации
 * @property $authRuleId    int(11) NULL    id условия авторизации
 * @property $timelimit    int(11) NULL    таймлимит на выполнение шага авторизации в минутах
 * @property $termination 1-выполнить авторизацию успешно, 2-завершить авторизацию не успешно /после истечения таймлимита
 * @property $timeLimitReserv int(11) NULL    резервное время оставшееся до истечения таймлимита в минутах, не может быть больше чем timelimit
 *
 * @property Account[] $accounts
 * @property Account[] $alterAccounts
 * @property AuthRuleIterationUser[] $authIterationUsers
 */
class AuthRuleIteration extends CActiveRecord
{
    /**
     * @var AuthRuleIterationUser[]
     */
    private $authIterationUsers = [];

    /**
     * @var AuthRuleIterationUser[]
     */
    private $authAlterIterationUsers = [];

    public function tableName()
    {
        return 'kt_authIteration';
    }

    public function relations()
    {
        return array(
            'authIterationUsers' => array(self::HAS_MANY, 'AuthRuleIterationUser', 'iterationId', 'condition' => 'authIterationUsers.userType = 0'),
            'authAlterIterationUsers' => array(self::HAS_MANY, 'AuthRuleIterationUser', 'iterationId', 'condition' => 'authAlterIterationUsers.userType = 1'),

            'accounts' => array(self::HAS_MANY, 'Account', ['userId' => 'UserId'], 'through' => 'authIterationUsers'),
            'alterAccounts' => array(self::HAS_MANY, 'Account', ['userId' => 'UserId'], 'through' => 'authAlterIterationUsers')
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function fromArray($so_authRegulation)
    {
        if (isset($so_authRegulation['timelimit'])) {
            $this->timelimit = $so_authRegulation['timelimit'];
        } else {
            throw new InvalidArgumentException('Time limit not set', OrdersErrors::AUTH_INCORRECT_TIME_LIMIT);
        }

        if (isset($so_authRegulation['timeLimitReserv']) && $this->timelimit >= $so_authRegulation['timeLimitReserv']) {
            $this->timeLimitReserv = $so_authRegulation['timeLimitReserv'];
        } else {
            throw new InvalidArgumentException('Time limit reserv not set or greater than time limit', OrdersErrors::AUTH_INCORRECT_TIME_LIMIT);
        }

        if (isset($so_authRegulation['termination']) && in_array($so_authRegulation['termination'], [1, 2])) {
            $this->termination = $so_authRegulation['termination'];
        } else {
            throw new InvalidArgumentException('Termination type not set', OrdersErrors::AUTH_INCORRECT_TERMINATION);
        }

        // обычная авторизация
        if (isset($so_authRegulation['authUsers']) && count($so_authRegulation['authUsers'])) {
            foreach ($so_authRegulation['authUsers'] as $authUser) {
                if (!isset($authUser['userId'])) {
                    throw new InvalidArgumentException('userId not set', OrdersErrors::AUTH_INCORRECT_USERS);
                }

                $account = AccountRepository::getAccountById($authUser['userId']);
                if (UserAccess::hasPermissions(61, $account->getPermissions()->getPermissionsCode())) {
                    $authIterationUser = new AuthRuleIterationUser();
                    $authIterationUser->bindAccount($account);
                    $authIterationUser->makeForOrdinaryUser();

                    $this->authIterationUsers[] = $authIterationUser;
                }
            }
        } else {
            throw new InvalidArgumentException('authUsers not set', OrdersErrors::AUTH_INCORRECT_USERS);
        }

        // альтернативная авторизация
        if (isset($so_authRegulation['authAlterUsers']) && count($so_authRegulation['authAlterUsers'])) {
            foreach ($so_authRegulation['authAlterUsers'] as $authAlterUsers) {
                if (!isset($authAlterUsers['userId'])) {
                    throw new InvalidArgumentException('userId not set', OrdersErrors::AUTH_INCORRECT_USERS);
                }

                $account = AccountRepository::getAccountById($authAlterUsers['userId']);
                if (UserAccess::hasPermissions(61, $account->getPermissions()->getPermissionsCode())) {
                    $authAlterIterationUser = new AuthRuleIterationUser();
                    $authAlterIterationUser->bindAccount($account);
                    $authAlterIterationUser->makeForAlternativeUser();

                    $this->authAlterIterationUsers[] = $authAlterIterationUser;
                }
            }
        }
    }

    public function getId()
    {
        return $this->iterationId;
    }

    public function toSOAuthRegulation()
    {
        $users = [];
        foreach ($this->accounts as $account) {
            $users[] = [
                'userId' => $account->getUserId(),
                'userFIO' => $account->getShortFIO()
            ];
        }

        $alterUsers = [];
        foreach ($this->alterAccounts as $alterAccount) {
            $alterUsers[] = [
                'userId' => $alterAccount->getUserId(),
                'userFIO' => $alterAccount->getShortFIO()
            ];
        }

        return [
            'authUsers' => $users,
            'authAlterUsers' => $alterUsers,
            'timelimit' => $this->timelimit,
            'timeLimitReserv' => $this->timeLimitReserv,
            'termination' => $this->termination
        ];
    }

    public function bindAuthRule(AuthRule $authRule)
    {
        $this->authRuleId = $authRule->getId();
    }

    public function saveAll()
    {
        $this->save(false);

        AuthRuleIterationUser::model()->deleteAll("iterationId = {$this->getId()}");

        foreach ($this->authIterationUsers as $authIterationUser) {
            $authIterationUser->bindIteration($this);
            $authIterationUser->save(false);
        }

        foreach ($this->authAlterIterationUsers as $authAlterIterationUser) {
            $authAlterIterationUser->bindIteration($this);
            $authAlterIterationUser->save(false);
        }
    }

    public function getTimeLimit()
    {
        return $this->timelimit;
    }

    public function getTermination()
    {
        return $this->termination;
    }

    /**
     *
     * @return Account[]
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * @return AuthRuleIterationUser[]
     */
    public function getAuthIterationUsers()
    {
        return $this->authIterationUsers;
    }
}