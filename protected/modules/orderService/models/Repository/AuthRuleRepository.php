<?php

class AuthRuleRepository
{
    /**
     * @param $id
     * @return AuthRule
     */
    public static function getById($id)
    {
        return AuthRule::model()->with('iterations')->findByPk($id);
    }

    /**
     * @param Company $company
     * @return AuthRule[]
     */
    public static function getByCompany(Company $company)
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition("companyId = {$company->getId()}");
        $rules = AuthRule::model()->findAll($criteria);

        $mainCompany = $company->getMainCompany();
        if ($mainCompany) {
            $mainCompanyCriteria = new CDbCriteria();
            $mainCompanyCriteria->addCondition("companyId = {$mainCompany->getId()}");
            $mainCompanyCriteria->addCondition('forAllCompanyInHolding = 1');

            $mainCompanyAuthRules = AuthRule::model()->findAll($mainCompanyCriteria);

            $rules = array_merge($rules, $mainCompanyAuthRules);
        }

        return $rules;
    }

    /**
     * @return AuthRule[]
     */
    public static function getUniversal()
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition("companyId IS NULL");
        return AuthRule::model()->findAll($criteria);
    }

    /**
     * Поиск всех правил для компании
     * @param OrdersServices $service
     * @return AuthRule[]
     */
    public static function getForService(OrdersServices $service)
    {
        $criteria = new CDbCriteria();

        $criteria->addCondition("companyId = {$service->getOrderModel()->getCompany()->getId()} OR companyId IS NULL");
        $criteria->addCondition("serviceType = {$service->getServiceType()}");
        $rules = AuthRule::model()->findAll($criteria);

        $mainCompany = $service->getOrderModel()->getCompany()->getMainCompany();
        if ($mainCompany) {
            $mainCompanyCriteria = new CDbCriteria();
            $mainCompanyCriteria->addCondition("companyId = {$mainCompany->getId()}");
            $mainCompanyCriteria->addCondition('forAllCompanyInHolding = 1');
            $mainCompanyCriteria->addCondition("serviceType = {$service->getServiceType()}");

            $mainCompanyAuthRules = AuthRule::model()->findAll($mainCompanyCriteria);

            $rules = array_merge($rules, $mainCompanyAuthRules);
        }

        return $rules;
    }
}