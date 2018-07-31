<?php

class AuthRuleMgr
{
    /**
     * Получение правил авторизации по id компании
     * @param $companyId
     * @throws AuthRuleMgrException
     * @return array
     */
    public function getRulesByCompanyId($companyId)
    {
        // проверка на качество компании
        if ($companyId) {
            $company = CompanyRepository::getById($companyId);

            if (is_null($company)) {
                throw new AuthRuleMgrException(OrdersErrors::COMPANY_NOT_FOUND);
            }

            $rules = AuthRuleRepository::getByCompany($company);
        } else {
            $rules = AuthRuleRepository::getUniversal();
        }

        $rulesArr = [];

        foreach ($rules as $rule) {
            $rulesArr[] = $rule->toSOAuthRule();
        }

        return $rulesArr;
    }

    public function setAuthRule($so_AuthRule)
    {
        // проверим компанию, для которой создается правило
        if (!isset($so_AuthRule['companyId'])) {
            $so_AuthRule['companyId'] = null;
        }

        // найдем компанию, если указана
        if (!is_null($so_AuthRule['companyId'])) {
            $company = CompanyRepository::getById($so_AuthRule['companyId']);

            if (is_null($company)) {
                throw new InvalidArgumentException('Компании с таким номером не существует', OrdersErrors::COMPANY_NOT_FOUND);
            }
        }

        // создадим или найдем правило
        if (empty($so_AuthRule['id'])) {
            $authRule = new AuthRule();

            if (isset($company)) {
                $authRule->bindCompany($company);
            }
        } else {
            $authRule = AuthRuleRepository::getById($so_AuthRule['id']);

            if (is_null($authRule)) {
                throw new InvalidArgumentException('Не найдено правило авторизации с таким ID', OrdersErrors::AUTH_RULE_NOT_FOUND);
            }
            if (isset($company) && $authRule->getCompanyId() != $company->getId()) {
                throw new InvalidArgumentException('Компания из параметров не совпала с компанией правила', OrdersErrors::COMPANY_NOT_FOUND);
            }
        }

        // проверим тип сервиса
        if (isset($so_AuthRule['serviceType'])) {
            $serviceType = RefServicesRepository::getById($so_AuthRule['serviceType']);

            if (is_null($serviceType)) {
                throw new InvalidArgumentException('Сервис с таким типом не найден', OrdersErrors::SERVICE_TYPE_NOT_SET);
            }

            $authRule->bindServiceType($serviceType);
        } else {
            throw new InvalidArgumentException('Неверный тип сервиса', OrdersErrors::SERVICE_TYPE_NOT_SET);
        }

        // forAllCompanyInHolding
        if (isset($so_AuthRule['forAllCompanyInHolding'])) {
            if ($so_AuthRule['forAllCompanyInHolding']) {
                $authRule->makeForAllCompanyInHolding();
            } else {
                $authRule->makeNotForAllCompanyInHolding();
            }
        }

        // description
        if (isset($so_AuthRule['description'])) {
            $authRule->setDescription($so_AuthRule['description']);
        }

        // active
        if (isset($so_AuthRule['active'])) {
            if ($so_AuthRule['active']) {
                $authRule->activate();
            } else {
                $authRule->deactivate();
            }
        }

        // conditions
        if (isset($so_AuthRule['authServiceConditions']) && count($so_AuthRule['authServiceConditions'])) {
            $authRule->clearConditions();

            foreach ($so_AuthRule['authServiceConditions'] as $authServiceCondition) {
                $condition = AuthConditionFactory::createFromArray($authServiceCondition);
                $authRule->addCondition($condition);
            }
        } else {
            throw new InvalidArgumentException('Не заданы условия', OrdersErrors::AUTH_CONDITIONS_NOT_SET);
        }

        // authRegulation
        if (isset($so_AuthRule['authRegulation']) && count($so_AuthRule['authRegulation'])) {
            foreach ($so_AuthRule['authRegulation'] as $authRegulation) {
                $iteration = new AuthRuleIteration();
                $iteration->fromArray($authRegulation);
                $authRule->addIteration($iteration);
            }
        } else {
            throw new InvalidArgumentException('Не заданы итерации', OrdersErrors::AUTH_ITERATIONS_NOT_SET);
        }

        $authRule->saveAll();

        return $authRule->toSOAuthRule();
    }
}