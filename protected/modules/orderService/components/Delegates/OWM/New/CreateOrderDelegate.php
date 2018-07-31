<?php

/**
 * Делегат для создания заявки, если ее не существует
 */
class CreateOrderDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        try {
            // если нет Заявки, то создадим ее и запишем данные аудита
            if (!$OrderModel->getOrderId()) {
                // запишем дату создания заявки
                $OrderModel->setOrderDate(StdLib::getMysqlDateTime());

                if (empty($params['contractId'])) {
                    $Contract = ContractRepository::getActiveContractByCompanyId($params['userProfile']['companyID']);
                } else { // создание заявки на другую компанию через переданный контракт
                    // проверим может ли пользователь создавать заявки для других компаний
                    if (!UserAccess::hasPermissions(49, $params['userPermissions'])) {
                        $this->setError(OrdersErrors::NOT_ENOUGH_USER_RIGHTS);
                        return null;
                    }

                    $Contract = ContractRepository::getByContractId($params['contractId']);

                    if (!$Contract->canBeUsed()) {
                        $this->setError(OrdersErrors::AGENCY_CONTRACT_MUST_BE_ACTIVE);
                        return;
                    }
                }

                if (is_null($Contract)) {
                    $this->setError(OrdersErrors::AGENCY_CONTRACT_NOT_FOUND);
                    return;
                }

                $Company = CompanyRepository::getById($params['userProfile']['companyID']);
                if (is_null($Company)) {
                    $this->setError(OrdersErrors::CANNOT_GET_RESPONSIBLE_MANAGER);
                    return null;
                }

                $kmpManager = $Company->getKmpManager();
                $companyManager = $Company->getCompanyManager();

                if (is_null($kmpManager) || is_null($companyManager)) {
                    $this->setError(OrdersErrors::CANNOT_GET_RESPONSIBLE_MANAGER);
                    return;
                }

                // сохраним данные
                $OrderModel->fromArray([
                    'companyId' => $Contract->getAgentID(),
                    'userId' => $params['userProfile']['userId'],
                    'contractId' => $Contract->getContractId(),
                    'companyManagerId' => $companyManager->getUserId(),
                    'kmpManagerId' => $kmpManager->getUserId()
                ]);
                $OrderModel->setStatus(OrderModel::STATUS_NEW);
                $OrderModel->setArchive(0);

                if (!$OrderModel->save()) {
                    $this->setError(OrdersErrors::CANNOT_CREATE_ORDER);
                    return null;
                }

                $OrderModel->refresh();

                // создание пустых полей услуги
                $addFields = AdditionalFieldTypeRepository::getOrderFieldsForCompany($OrderModel->getCompany());

                foreach ($addFields as $addField) {
                    $orderAddField = new OrderAdditionalField();
                    $orderAddField->bindOrder($OrderModel);
                    $orderAddField->bindAdditionalFieldType($addField);
                    $orderAddField->save(false);
                }

                // сохраним результат
                $this->params['object'] = $OrderModel->serialize();

                // запишем историю
                $OrderHistory = new OrderHistory();
                $OrderHistory->setObjectData($OrderModel);
                $OrderHistory->setOrderData($OrderModel);
                $OrderHistory->setActionResult(0);
                $OrderHistory->setCommentTpl('{{122}} {{orderId}}');
                $OrderHistory->setCommentParams([
                    'orderId' => $OrderModel->getOrderId()
                ]);

                // запишем лог
                $this->addLog('Создана заявка', 'info');

                // сохраним результат аудита
                $this->addOrderAudit($OrderHistory, 1);
            }
        } catch (CDbException $e) {
            $this->addLog($e->getMessage(), 'error');
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return;
        }

        $this->addResponse('orderId', $OrderModel->getOrderId());
    }
}