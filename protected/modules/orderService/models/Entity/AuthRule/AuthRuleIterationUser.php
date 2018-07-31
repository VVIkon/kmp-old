<?php

/**
 * Class AuthRuleIterationUser
 *
 * @property $iterationId	int(11)	Шаг авторизации
 * @property $userId	bigint(20)	ID пользователей имеющих право на авторизацию на данном шаге
 * @property $userType tinyint(4)	0- обычные пользователь 1- альтернативный пользователь,
 *                                  могущий выполнить авторизацию на данном шаге авторизации,
 *                                  если времени до истечения авторизации осталось менее timeLimitReserv
 *
 * @property Account $account
 */
class AuthRuleIterationUser extends CActiveRecord
{
    public function tableName()
    {
        return 'kt_authIterationUsers';
    }

    public function relations()
    {
        return array(
            'account' => array(self::BELONGS_TO, 'Account', 'userId'),
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function bindAccount(Account $account)
    {
        $this->userId = $account->getUserId();
    }

    public function bindIteration(AuthRuleIteration $iteration)
    {
        $this->iterationId = $iteration->getId();
    }

    public function makeForOrdinaryUser()
    {
        $this->userType = 0;
    }

    public function makeForAlternativeUser()
    {
        $this->userType = 1;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }
}