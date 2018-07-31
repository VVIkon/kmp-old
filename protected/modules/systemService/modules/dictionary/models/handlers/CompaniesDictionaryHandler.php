<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10.01.17
 * Time: 11:57
 */
class CompaniesDictionaryHandler extends AbstractDictionaryHandler
{
    /**
     * @param $params

    /**
     * @param $params
     * @return array
     */
    public function getDictionaryData($params)
    {
        $emptyAnswer = [
            'itemFound' => 0,
            'items' => []
        ];

        $userProfile = Yii::app()->user->getState('userProfile');

        // поддерживаемые поля для поиска компаний
        $responseParamsList = [
            'companyId' => 'AgentID',
            'name' => 'Name',
            'INN' => 'INN',
            'companyRoleType' => 'Type',
            'companyMainOffice' => 'parentId'
        ];

        // инициализация параметров
        $filterParams = $params['dictionaryFilter'];

        if (empty($filterParams['maxRowCount'])) {
            $filterParams['maxRowCount'] = 10;
        }

        // создадим спецификацию на запрос компаний
        $criteria = new CDbCriteria();

        // лимит записей
        $criteria->limit = $filterParams['maxRowCount'];

        // список полей для вывода
        $selectFields = [];

        // определенные поля
        if (empty($filterParams['fieldsFilter'])) {
            $selectFields = array_values($responseParamsList);
        } else {
            // найдем массивы всех полей и связанных сущностей для вывода
            foreach ($filterParams['fieldsFilter'] as $fieldToSelect) {
                // если такое поле поддерживается
                if (array_key_exists($fieldToSelect, $responseParamsList) && Company::model()->hasAttribute($responseParamsList[$fieldToSelect])) {
                    $selectFields[] = $responseParamsList[$fieldToSelect];
                }
            }
        }

        // добавим в спецификацию настройки вывода
        $criteria->select = implode(',', $selectFields);
        $criteria->addCondition('t.active = 1');
        $criteria->addCondition('t.AgentID_GP IS NOT NULL');

        // определим к чему пользователь имеет доступ
        // только своя компания, холдинг или все компании
        // 0 - свои, 1 - холдинг, 2 - все
        $accessType = 0;

        if (UserAccess::hasPermissions(0) || (UserAccess::hasPermissions([49, 53]) && $userProfile['userType'] == 1)) {
            $accessType = 2;
        } elseif (in_array($userProfile['userType'], [2, 3]) && UserAccess::hasPermissions([49, 53])) {
            $accessType = 1;
        }

        $extraConditions = new CDbCriteria();

        // если не указана конкретная компания
        if (empty($filterParams['companyId'])) {
            if (!empty($filterParams['textFilter'])) {
                $extraConditions->addCondition('(t.Name LIKE :likeQuery1 OR t.Name LIKE :likeQuery2 OR t.INN = :innQuery)');
                $extraConditions->params = array_merge($extraConditions->params, [
                    ':likeQuery1' => "{$filterParams['textFilter']}%",
                    ':likeQuery2' => "% {$filterParams['textFilter']}%",
                    ':innQuery' => $filterParams['textFilter']
                ]);
            }

            // посмотрим по доступу нужно ли ограничить выдачу компаний по саджесту
            switch ($accessType) {
                case 0: // ограничим доступ только к своей компании
                    $extraConditions->addCondition("t.AgentID = {$userProfile['companyID']}");
                    break;
                case 1:  // поиск по названию по компаниям холдинга
                    $holdingCompanies = CompanyRepository::findAllHoldingCompanies($userProfile['companyID']);

                    if (count($holdingCompanies)) {
                        $holdingCompaniesIds = [];

                        foreach ($holdingCompanies as $holdingCompany) {
                            $holdingCompaniesIds[] = $holdingCompany->getId();
                        }

                        $extraConditions->addInCondition("t.AgentID", $holdingCompaniesIds);
                    } else {
                        // не найдены компании
                        return $emptyAnswer;
                    }
                    break;
                case 2: // нет ограничений - ищем по названию по всем компаниям
                    break;
            }
        } else {    // указана конкретная компания - проверим можно ли ее выдать пользаку
            switch ($accessType) {
                case 0: // доступ только к своим компаниям - выдаем пустоту или запрошенную компанию
                    if ($userProfile['companyID'] == $filterParams['companyId']) {
                        $extraConditions->addCondition("t.AgentID = {$userProfile['companyID']}");
                    } else {
                        return $emptyAnswer;
                    }
                    break;
                case 1: // найдем все компании холдинга
                    $holdingCompanies = CompanyRepository::findAllHoldingCompanies($filterParams['companyId']);

                    if (count($holdingCompanies)) {
                        $holdingCompaniesIds = [];

                        foreach ($holdingCompanies as $holdingCompany) {
                            $holdingCompaniesIds[] = $holdingCompany->getId();
                        }

                        if (false !== array_search($filterParams['companyId'], $holdingCompaniesIds)) {
                            $extraConditions->addCondition("t.AgentID = {$filterParams['companyId']}");
                        } else {
                            // запрошена компания не из холдинга, возвращаем пустоту
                            return $emptyAnswer;
                        }
                    } else {
                        // не найдены компании
                        return $emptyAnswer;
                    }
                    break;
                case 2: // доступ ко всем - вмело выдаем запрошенную компанию
                    $extraConditions->addCondition("t.AgentID = {$filterParams['companyId']}");
                    break;
            }
        }

        $criteria->mergeWith($extraConditions, 'AND');

        $with = [];

        // добавим контракты, если спрашивают
        if (empty($filterParams['fieldsFilter']) || in_array('Contracts', $filterParams['fieldsFilter'])) {
            $with['contracts'] = [
                'select' => 'ContractID, ContractID_UTK, Contract, ContractDate, ContractExpiry',
                'condition' => 'contracts.active = 1 AND contracts.online = 1',
                'joinType' => 'INNER JOIN',
                'together' => true
            ];
        }

        // добавим до поля, если спрашивают
        if (empty($filterParams['fieldsFilter']) || in_array('AddFieldTypes', $filterParams['fieldsFilter'])) {
            // PERMISSION_57 (если roleType 2 или 3) для возвращения структуры доп. полей
            if ($userProfile['userType'] == 1 || ((in_array($userProfile['userType'], [2, 3]) && UserAccess::hasPermissions(57)))) {
                $with['addFields'] = [
                    'joinType' => 'LEFT JOIN',
                    'together' => false
                ];
            }
        }

        $criteria->with = $with;

        // ищем по нашим критериям
        $Companies = CompanyRepository::findAllByCriteria($criteria);

        if (empty($Companies)) {
            return $emptyAnswer;
        }

        // сформируем результат
        $answer = [];

        foreach ($Companies as $Company) {
            $answerItem = [
                'Contracts' => []
            ];

            // выберем все аттрибуты
            $companyAttributes = $Company->getAttributes($selectFields);

            // маппинг аттрибутов по правилам
            $attributeToExpectedFieldArr = array_flip($responseParamsList);

            foreach ($companyAttributes as $companyAttributeName => $companyAttributeVal) {
                $answerItem[$attributeToExpectedFieldArr[$companyAttributeName]] = $companyAttributeVal;
            }

            $answerItem['companyMainOffice'] = null;

            // PERMISSION_49 или PERMISSION_53 - при установленной пермиссии для roletype 3 возвращаются также подходящие по саджесту компании холдинга
            if ($userProfile['userType'] == 1 || ($userProfile['userType'] == 3 && UserAccess::hasPermissions([49, 53]))) {
                // головной офис компании
                $mainCompany = $Company->getMainCompany();
                if ($mainCompany) {
                    $answerItem['companyMainOffice'] = $mainCompany->getName();
                }
            }


            // вытащим контракты
            if ($Company->hasContracts()) {
                $Contracts = $Company->getContracts();

                foreach ($Contracts as $Contract) {
                    $answerItem['Contracts'][] = [
                        'ContractID' => $Contract->getContractId(),
                        'ContractID_UTK' => $Contract->getContractIdUTK(),
                        'ContractDate' => $Contract->getContractDate(),
                        'ContractExpiry' => $Contract->getContractExpiry(),
                        'expired' => $Contract->expired()
                    ];
                }
            }

            $answerItem['addFields'] = null;

            // PERMISSION_57 (если roleType 2 или 3) для возвращения структуры доп. полей
            if ($userProfile['userType'] == 1 || (in_array($userProfile['userType'], [2, 3]) && UserAccess::hasPermissions(57))) {
                // вытащим доп поля
                $addFields = $Company->getAllAddFields();
                foreach ($addFields as $addField) {
                    $answerItem['addFields'][] = $addField->toArray();
                }
            }

            $answer[] = $answerItem;
        }

        return [
            'itemFound' => count($answer),
            'items' => $answer
        ];
    }

}