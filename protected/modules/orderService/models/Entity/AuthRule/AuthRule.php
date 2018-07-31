<?php

/**
 * Class AuthRule
 *
 * @property $id    int(11) Auto Increment    Id условия авторизации
 * @property $companyId    bigint(20) NULL    /null - для всех, или ID компании - конкретно для какой компании выполнять
 * @property $forAllCompanyInHolding    tinyint(4) NULL    правило действует для всех компаний холдинга
 * @property $serviceType    tinyint(4) NULL    для какого сервиса
 * @property $description    varchar(100) NULL    Название правила
 * @property $active    tinyint(4) NULL    активность правила для обработки
 * @property $conditions
 *
 * @property Company $company
 * @property AuthRuleIteration[] $iterations
 */
class AuthRule extends CActiveRecord
{
    /**
     * @var AbstractAuthServiceCondition[]
     */
    private $AuthServiceConditions;

    /**
     * @var AuthRuleIteration[]
     */
    private $AuthIterations;

    public function tableName()
    {
        return 'kt_authRule';
    }

    public function relations()
    {
        return array(
            'company' => array(self::BELONGS_TO, 'Company', 'companyId'),
            'iterations' => array(self::HAS_MANY, 'AuthRuleIteration', 'authRuleId')
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isForAllCompanyInHolding()
    {
        return $this->forAllCompanyInHolding == 1;
    }

    public function makeForAllCompanyInHolding()
    {
        $this->forAllCompanyInHolding = 1;
    }

    public function makeNotForAllCompanyInHolding()
    {
        $this->forAllCompanyInHolding = 0;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getServiceType()
    {
        return $this->serviceType;
    }

    public function bindServiceType(RefServices $refService)
    {
        $this->serviceType = $refService->getId();
    }

    public function getConditions()
    {
        $conditions = json_decode($this->conditions, true);
        if (is_null($conditions)) {
            return [];
        }
        return $conditions;
    }

    public function clearConditions()
    {
        $this->conditions = json_encode([]);
    }

    public function addCondition(AbstractAuthServiceCondition $authServiceCondition)
    {
        $conditions = $this->getConditions();
        $conditions[] = $authServiceCondition->toArray();
        $this->conditions = json_encode($conditions, JSON_UNESCAPED_UNICODE);

        $this->AuthServiceConditions[] = $authServiceCondition;
    }

    public function addIteration(AuthRuleIteration $authRuleIteration)
    {
        $this->AuthIterations[] = $authRuleIteration;
    }

    /**
     * @return AuthRuleIteration[]
     */
    public function getIterations()
    {
        return $this->iterations;
    }

    /**
     * Активация поля
     */
    public function activate()
    {
        $this->active = 1;
    }

    public function deactivate()
    {
        $this->active = 0;
    }

    public function isActive()
    {
        return $this->active == 1;
    }

    public function bindCompany(Company $company)
    {
        $this->companyId = $company->getId();
    }

    public function getCompanyId()
    {
        return $this->companyId;
    }

    public function saveAll()
    {
//        $transaction = Yii::app()->db->beginTransaction();

        try {
            $this->save(false);

            AuthRuleIteration::model()->deleteAll("authRuleId = {$this->getId()}");

            foreach ($this->AuthIterations as $authIteration) {
                $authIteration->bindAuthRule($this);
                $authIteration->saveAll();
            }
        } catch (CDbException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Сохранение правила авторизации', $e->getMessage(),
                [],
                LogHelper::MESSAGE_TYPE_ERROR,
                'system.orderservice.error'
            );
//            $transaction->rollback();
        }

//        $transaction->commit();
    }

    /**
     * Проверка услуги условиями для начала процесса авторизации
     * @param OrdersServices $service
     * @return bool
     */
    public function testService(OrdersServices $service)
    {
        $conditions = $this->getConditions();
        foreach ($conditions as $condition) {
            $conditionClass = AuthConditionFactory::createFromArray($condition);

            if (!$conditionClass->apply($service)) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     * @return array
     */
    public function toSOAuthRule()
    {
        $iterations = $this->getIterations();
        $authRegulation = [];

        foreach ($iterations as $iteration) {
            $authRegulation[] = $iteration->toSOAuthRegulation();
        }

        return [
            'id' => $this->getId(),
            'companyId' => $this->companyId,        //null - для всех, или ID компании - конкретно для какой компании выполнять
            'forAllCompanyInHolding' => $this->isForAllCompanyInHolding(),    // для всего холдинга
            'description' => $this->getDescription(),
            'serviceType' => $this->getServiceType(),         // для какого сервиса
            'authServiceConditions' => $this->getConditions(), // Группа условий объединенных условием 'И'
            'authRegulation' => $authRegulation   // so_authRegulation
        ];
    }
}