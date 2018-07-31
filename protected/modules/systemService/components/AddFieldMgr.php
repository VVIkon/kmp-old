<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 23.08.17
 * Time: 17:11
 */
class AddFieldMgr
{
    /**
     * Создание или обновление справочника доп полей
     * @param $companyAdditionalFieldsType
     * @return AdditionalFieldType
     */
    public function setAddFieldType($companyAdditionalFieldsType)
    {
        $addField = $this->defineAdditionalFieldType($companyAdditionalFieldsType);

        $createEmptyFieldsForAccount = false;

        $company = $this->defineCompany($companyAdditionalFieldsType);

        // компания
        if ($addField->isNewRecord) {
            $addField->bindCompany($company);
            $createEmptyFieldsForAccount = true;
        } else {
            if ($addField->getCompanyId() != $company->getId()) {
                throw new InvalidArgumentException('Нельзя сохранить поле от чужой компании', SysSvcErrors::INVALID_COMPANY);
            }
        }

        // категория
        if (isset($companyAdditionalFieldsType['fieldCategory'])) {
            if (!$addField->setCategory($companyAdditionalFieldsType['fieldCategory'])) {
                throw new InvalidArgumentException('Неверная категория поля', SysSvcErrors::INVALID_ADD_FIELD_CATEGORY);
            }
        }

        // тип шаблона
        if (isset($companyAdditionalFieldsType['typeTemplate'])) {
            if (!$addField->setTypeTemplate($companyAdditionalFieldsType['typeTemplate'])) {
                throw new InvalidArgumentException('Неверный тип поля', SysSvcErrors::INVALID_ADD_FIELD_TYPE_TEMPLATE);
            }
        }

        // активация поля
        if (isset($companyAdditionalFieldsType['active'])) {
            if ($companyAdditionalFieldsType['active']) {
                $addField->activate();
            } else {
                $addField->disable();
            }
        }

        // название поля
        if (isset($companyAdditionalFieldsType['fieldTypeName'])) {
            $addField->setName($companyAdditionalFieldsType['fieldTypeName']);
        }

        // причина нарушения ТП
        if (isset($companyAdditionalFieldsType['reasonFailTP'])) {
            $addField->setReasonFailTP($companyAdditionalFieldsType['reasonFailTP']);
        }

        // стуркутра допустимых значений
        if (isset($companyAdditionalFieldsType['availableValueList'])) {
            if (!is_array($companyAdditionalFieldsType['availableValueList'])) {
                throw new InvalidArgumentException('Список возможных значений должен быть массивом', SysSvcErrors::INVALID_ADD_FIELD_VALUE_LIST);
            }

            $addField->setAvailableValueList($companyAdditionalFieldsType['availableValueList']);
        } else {
            $addField->setAvailableValueList(null);
        }

        // активация поля
        if (isset($companyAdditionalFieldsType['active'])) {
            if ($companyAdditionalFieldsType['active']) {
                $addField->activate();
            } else {
                $addField->disable();
            }
        }

        // возможность модификации
        if (isset($companyAdditionalFieldsType['modifyAvailable'])) {
            if ($companyAdditionalFieldsType['modifyAvailable']) {
                $addField->makeModifyAvailable();
            } else {
                $addField->makeModifyNonAvailable();
            }
        }

        // обязательность
        if (isset($companyAdditionalFieldsType['require'])) {
            if ($companyAdditionalFieldsType['require']) {
                $addField->makeRequired();
            } else {
                $addField->makeNonRequired();
            }
        }

        // поле распространяется на все компании холдинга
        if (isset($companyAdditionalFieldsType['forAllCompanyInHolding'])) {
            if ($companyAdditionalFieldsType['forAllCompanyInHolding']) {
                $addField->makeForAllCompaniesInHolding();
            } else {
                $addField->makeNotForAllCompaniesInHolding();
            }
        }

        $addField->save(false);

        // Если параметр sk_company_additional_fields_type.required установлен в true, и параметр sk_company_additional_fields_type.fieldCategory=1,
        // то данные данного поля должны быть занесены с пустым значением для каждого пользователя компании.
        if ($addField->isRequired() && $addField->isAccountField() && $createEmptyFieldsForAccount) {
            // вытащим пользаков компании и создадим им доп поля
            $accounts = AccountRepository::getCompanyAccounts($company);

            foreach ($accounts as $account) {
                $accountField = new AccountAdditionalField();
                $accountField->bindAdditionalFieldType($addField);
                $accountField->bindAccount($account);
                $accountField->activate();
                $accountField->save(false);
            }
        }

        return $addField;
    }

    /**
     *
     * @param $userCorporateField
     * @return AccountAdditionalField|bool
     */
    public function setUserAddField($userCorporateField)
    {
        $accountAdditionalField = $this->defineAdditionalField($userCorporateField);

        if (!isset($userCorporateField['value'])) {
            $userCorporateField['value'] = null;
        }

        $accountAdditionalField->setValue($userCorporateField['value']);

        if (!$accountAdditionalField->isValid()) {
            return false;
        }

        if (isset($userCorporateField['active'])) {
            if ($userCorporateField['active']) {
                $accountAdditionalField->activate();
            } else {
                $accountAdditionalField->deactivate();
            }
        }

        $accountAdditionalField->save(false);

        return $accountAdditionalField;
    }

    /**
     *
     * @param $params
     * @return Company
     */
    protected function defineCompany($params)
    {
        if (empty($params['companyId'])) {
            throw new InvalidArgumentException('Неверный ID компании', SysSvcErrors::INVALID_COMPANY);
        }

        $company = CompanyRepository::getById($params['companyId']);

        if (is_null($company)) {
            throw new InvalidArgumentException('Неверный ID компании', SysSvcErrors::INVALID_COMPANY);
        }

        return $company;
    }

    /**
     * Находит доп поле по его ID
     * @param $params
     * @return AdditionalFieldType
     */
    protected function defineAdditionalFieldType($params)
    {
        if (empty($params['fieldTypeId'])) {
            $addField = new AdditionalFieldType();
        } else {
            $addField = AdditionalFieldTypeRepository::getById($params['fieldTypeId']);

            if (is_null($addField)) {
                throw new InvalidArgumentException('Доп поля с таким ID не существует', SysSvcErrors::INCORRECT_FIELD_TYPE_ID);
            }
        }

        return $addField;
    }

    /**
     * Находит доп поле по его ID
     * @param $params
     * @return AccountAdditionalField
     */
    protected function defineAdditionalField($params)
    {
        if (empty($params['fieldId']) && empty($params['fieldTypeId'])) {
            throw new InvalidArgumentException('Доп поля с таким ID не существует', SysSvcErrors::INCORRECT_FIELD_TYPE_ID);
        }

        if (!empty($params['fieldId'])) {
            $addField = AccountAdditionalFieldRepository::getById($params['fieldId']);

            if (is_null($addField)) {
                throw new InvalidArgumentException('Доп поля с таким ID не существует', SysSvcErrors::INCORRECT_FIELD_TYPE_ID);
            }
        } elseif (!empty($params['fieldTypeId'])) {
            if (empty($params['userId'])) {
                throw new InvalidArgumentException('Не указан UserId', SysSvcErrors::USER_ID_NOT_SET);
            } else {
                $account = AccountRepository::getAccountById($params['userId']);

                if (is_null($account)) {
                    throw new InvalidArgumentException('Пользователь с таким ID не найден', SysSvcErrors::USER_NOT_FOUND);
                }
            }

            $addFieldType = AdditionalFieldTypeRepository::getById($params['fieldTypeId']);

            if (is_null($addFieldType)) {
                throw new InvalidArgumentException('Доп поля с таким ID не существует', SysSvcErrors::INCORRECT_FIELD_TYPE_ID);
            }

            $addField = new AccountAdditionalField();
            $addField->bindAdditionalFieldType($addFieldType);
            $addField->bindAccount($account);
        }

        return $addField;
    }
}