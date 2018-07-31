<?php

/**
 * Class AuthServiceData
 *
 * @property $roundId    int(11) Auto Increment    раунд авторизации
 * @property $serviceId    int(11) NULL    id сервиса
 * @property $reason    text NULL    событие бизнес-процесса приведшее к выполнению авторизации
 * @property $reasonComment    varchar(45) NULL    Описание причины авторизации
 * @property $dateTime    datetime NULL    таймштамп инициации раунда авторизации
 * @property $termination    varchar(45) NULL    1-успешное выполнение, 0-не успешное / для всего раунда
 * @property $completed    varchar(45) NULL    статус выполнения раунда авторизации
 * @property $price    text NULL
 * @property $tabuComment    varchar(1000) NULL    комментарий к запрету в авторизации от пользователяи
 *
 * @property AuthRuleIteration $authIteration
 * @property AuthRuleIteration[] $iterations
 * @property AuthServiceDataIteration[] $authServiceDataIterations
 */
class AuthServiceData extends CActiveRecord
{


    protected $authRuleIterationUsers = [];

    /**
     * @var AuthServiceDataIteration[]
     */
    private $iterationsToSave;


    public function tableName()
    {
        return 'kt_authServiceData';
    }

    public function relations()
    {
        return array(
            'authIteration' => array(self::HAS_ONE, 'AuthRuleIteration', 'iterationId'),
            'iterations' => array(self::HAS_MANY, 'AuthRuleIteration', 'authRuleId'),
            'authServiceDataIterations' => array(self::HAS_MANY, 'AuthServiceDataIteration', 'roundId')
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function bindService(OrdersServices $service)
    {
        $this->serviceId = $service->getServiceID();
    }

    public function isCompleted()
    {
        return $this->completed == 1;
    }

    public function fromAuthRule(AuthRule $rule)
    {
        // datetime - текущим штампом времени authServiceDataIteration
        $this->dateTime = StdLib::getMysqlDateTime();
        // completed: false
        $this->completed = 0;

        $iterations = $rule->getIterations();

        foreach ($iterations as $iterationNum => $iteration) {
            $serviceAuthIteration = new AuthServiceDataIteration();
            $serviceAuthIteration->bindAuthIteration($iteration);

            // Для первой активной итерации установить finishDatetime, если so_authRegulation.
            // timelimit != 0 прибавив timelimit к текущему штампу времени.
            if ($iterationNum == 0) {
                $iterationTimeLimit = $iteration->getTimeLimit();

                if ($iterationTimeLimit > 0) {
                    $intervalStr = "PT{$iterationTimeLimit}M";

                    $timeLimit = new DateTime();
                    $timeLimit->add(new DateInterval($intervalStr));

                    $serviceAuthIteration->setFinishDateTime($timeLimit);
                }
            }

            // Для всех итераций установить autotermination
            // в соответствии с данными в регламентом авторизации для этого шага
            $serviceAuthIteration->setAutoTermination($iteration->getTermination());

            $this->iterationsToSave[] = $serviceAuthIteration;
        }
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    public function getId()
    {
        return $this->roundId;
    }

    public function saveAll()
    {
        $this->save(false);

        foreach ($this->iterationsToSave as $iteration) {
            $iteration->bindAuthServiceData($this);
            $iteration->save(false);
        }
    }
    /**
     * @return mixed
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @return mixed
     */
    public function getRoundId()
    {
        return $this->roundId;
    }

    /**
     * @return AuthServiceDataIteration
     */
    public function getCurrentIteration()
    {
        foreach ($this->authServiceDataIterations as $iteration) {
            if ($iteration->isActual()) {
                return $iteration;
            }
        }
    }

    public function getAuthServiceDataIteration()
    {
        return $this->authServiceDataIterations;
    }


    /**
     * Проверка и сохранение шага авторизации
     * @param $AuthDataIterations
     * @param $userId
     * @param $termination
     * @param $autoAuth
     * @return bool
     */
    private function checkAndSave($AuthDataIterations, $params)
    {
        if (empty($AuthDataIterations)){
            return false;
        }
        $userId = StdLib::nvl($params['userId'],0);
        $termination =StdLib::nvl($params['termination']);
        $autoAuth = StdLib::nvl($params['autoAuth']);

        foreach ($AuthDataIterations as $AuthDataIteration) {
            $autoTermination = $AuthDataIteration->getAutoTermination();
            $userTermination = $AuthDataIteration->getUserId();
            $this->authRuleIterationUsers = $AuthDataIteration->getAuthIterationUsersToArray();

            if ($autoTermination > 0 && is_null($userTermination) && in_array($userId, $this->authRuleIterationUsers)) {
                $AuthDataIteration->setUserId($userId);
                $AuthDataIteration->setTermination($termination);
                $AuthDataIteration->setAutoauth($autoAuth);
                $AuthDataIteration->save(false);
                if ($autoAuth == 0) {  // 0: Команда запускается НЕ консольно
                    return true;
                }
            }
        }
        return true;    // Сюда дойдет только консольный запуск, поэтому true
    }

    /** Проверка степени готовности (подписи) шагов раунда
     * @param $AuthDataIterations
     * @return bool
     */
    private function checkStatus($AuthDataIterations)
    {
        $resultArray=[];
        foreach ($AuthDataIterations as $AuthDataIteration){
            $userId = $AuthDataIteration->getUserId();
            $termination = $AuthDataIteration->getTermination();
            if (!is_null($userId ) && $termination == 1) {
                $resultArray[] = 1;
            }else{
                $resultArray[] = 0;
            }
        }

        return !in_array('0', $resultArray);
    }

    /**
     * Операция подписи следующего шага
     * @param $userId
     * @param $termination
     * @param $autoAuth
     * @return int (//0: нет раундов или шагов для проверки; 1-все шаги роаунда выполнены; 2-не все шаги раунда выполнены)
     */
    public function operationAutorizationIteration($params)
    {
        $resStatus =0; //0: нет раундов или шагов для проверки; 1-все шаги роаунда выполнены; 2-не все шаги раунда выполнены
        $termination=$params['termination'];

        $AuthDataIterations = $this->getAuthServiceDataIteration();
        if (isset($AuthDataIterations)) {
            $isSave = $this->checkAndSave($AuthDataIterations, $params);
            if ($isSave) {// После сохранения данных
                if ($termination == 0) { // Если юзер не подписывает шаг авторизации - дальнейшие шаги запрещаются
                    $this->termination = 0;
                    $this->tabuComment = $params['comment'];
                    $this->completed = 1;
                    $resStatus =1;
                } else {  // Если юзер подписывает шаг, проверяем все ли шаги выполнены по раунду
                    $is_finish = $this->checkStatus($AuthDataIterations);
                    if ($is_finish) { // Шаги выполнили все и все шаги подписаны
                        $this->termination = 1;
                        $this->completed = 1;
                        $resStatus =1;
                    } else {  // Шаги выполнениы не все
                        $this->completed = 0;
                        $resStatus =2;
                    }
                }
                $this->save(false);
            } else {
                $resStatus =2;
            }
        }
        return (int)$resStatus;
    }

    /**
     * @return array
     */
    public function getAuthRuleIterationUsers()
    {
        return $this->authRuleIterationUsers;
    }

}