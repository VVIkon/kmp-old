<?php

/**
 * Менеджер для работы с КП
 */
class TPMgr
{
    /**
     * Создание правила ТП
     * @param $travelPolicyEditRule
     * @throws InvalidArgumentException
     * @return TravelPolicyRuleGroup
     */
    public function setTPforCompany($travelPolicyEditRule)
    {
        // проверим компанию, для которой создается правило
        if (!isset($travelPolicyEditRule['companyId'])) {
            $travelPolicyEditRule['companyId'] = null;
        }

        if (!is_null($travelPolicyEditRule['companyId'])) {
            $company = CompanyRepository::getById($travelPolicyEditRule['companyId']);

            if (is_null($company)) {
                throw new InvalidArgumentException('Компании с таким номером не существует', SysSvcErrors::INVALID_COMPANY);
            }
        }

        // проверим тип сервиса
        if (isset($travelPolicyEditRule['serviceType'])) {
            $serviceType = RefSubServicesRepository::getById($travelPolicyEditRule['serviceType']);

            if (is_null($serviceType)) {
                throw new InvalidArgumentException('Неверный тип сервиса', SysSvcErrors::SERVICE_TYPE_NOT_SET);
            }
        } else {
            throw new InvalidArgumentException('Неверный тип сервиса', SysSvcErrors::SERVICE_TYPE_NOT_SET);
        }

        // проверим поставщиков
        if (!isset($travelPolicyEditRule['supplierId'])) {
            $travelPolicyEditRule['supplierId'] = null;
        } else {
            if (is_array($travelPolicyEditRule['supplierId']) && count($travelPolicyEditRule['supplierId'])) {
                foreach ($travelPolicyEditRule['supplierId'] as $supplierId) {
                    $supplier = SupplierRepository::getById($supplierId);

                    if (is_null($supplier)) {
                        throw new InvalidArgumentException("Неверный поставщик с ID {$supplierId}", SysSvcErrors::INCORRECT_SUPPLIER);
                    }
                }
            } else {
                throw new InvalidArgumentException('Неверный поставщик', SysSvcErrors::INCORRECT_SUPPLIER);
            }
        }

        // проверим остальные параметры
        if (!isset($travelPolicyEditRule['comment'])) {
            $travelPolicyEditRule['comment'] = null;
        }

        if (!isset($travelPolicyEditRule['ruleType']) || !isset($travelPolicyEditRule['conditions']) || !isset($travelPolicyEditRule['actions']) || !is_array($travelPolicyEditRule['conditions']) || !is_array($travelPolicyEditRule['actions'])) {
            throw new InvalidArgumentException('Не указаны обязательные параметры', SysSvcErrors::INPUT_PARAMS_ERROR);
        }

        // попробуем создать само правило
        if (!empty($travelPolicyEditRule['id'])) {
            $tpRule = TravelPolicyRuleGroupRepository::getById($travelPolicyEditRule['id']);

            if (is_null($tpRule)) {
                throw new InvalidArgumentException('Правило не найдено', SysSvcErrors::TP_RULE_NOT_FOUND);
            }

            if (isset($company) && $company->getId() != $tpRule->getCompanyId()) {
                throw new InvalidArgumentException('Нельзя записывать правила из чужой компании', SysSvcErrors::INVALID_COMPANY);
            }
        } else {
            $tpRule = new TravelPolicyRuleGroup();
        }

        if (isset($company)) {
            $tpRule->bindCompany($company);
        }
        if (!$tpRule->setRuleType($travelPolicyEditRule['ruleType'])) {
            throw new InvalidArgumentException('Некорректный тип области применения правила', SysSvcErrors::INCORRECT_TP_RULE_TYPE);
        }
        if (!$tpRule->setConditions($travelPolicyEditRule['conditions'])) {
            throw new InvalidArgumentException('Некорректные условия', SysSvcErrors::INCORRECT_TP_CONDITIONS);
        }
        if (!$tpRule->setActions($travelPolicyEditRule['actions'])) {
            throw new InvalidArgumentException('Некорректные действия', SysSvcErrors::INCORRECT_TP_ACTIONS);
        }
        $tpRule->setComment($travelPolicyEditRule['comment']);
        $tpRule->setSupplierIds($travelPolicyEditRule['supplierId']);
        $tpRule->setServiceType($travelPolicyEditRule['serviceType']);
        if (isset($travelPolicyEditRule['forAllCompanyInHolding'])) {
            if ($travelPolicyEditRule['forAllCompanyInHolding']) {
                $tpRule->makeForAllCompanyInHolding();
            } else {
                $tpRule->makeNotForAllCompanyInHolding();
            }
        }

        if (isset($travelPolicyEditRule['active'])) {
            if ($travelPolicyEditRule['active']) {
                $tpRule->activate();
            } else {
                $tpRule->deactivate();
            }
        }

        $tpRule->save(false);

        return $tpRule;
    }
}