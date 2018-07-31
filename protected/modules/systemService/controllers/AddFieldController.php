<?php

/**
 * Управление доп полями в системе
 */
class AddFieldController extends SecuredRestController
{
    /**
     * @var AddFieldMgr
     */
    protected $addFieldMgr;

    /**
     * В данном контроллере проверка прав на правку доп полей
     * @param CAction $action
     * @return bool
     */
    protected function beforeAction($action)
    {
        parent::beforeAction($action);

        $this->addFieldMgr = new AddFieldMgr();

        return true;
    }

    /**
     * Операция выполняет редактирование данных справочника типов доп. полей компании
     */
    public function actionSetAddFieldType()
    {
        if (!UserAccess::hasPermissions(0) && !(UserAccess::hasPermissions(63) && UserAccess::hasPermissions(58))) {
            $this->_sendResponseWithErrorCode($this::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
        }

        $params = $this->_getRequestParams();

        if (empty($params['addFieldType'])) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::INPUT_PARAMS_ERROR);
        }

        try {
            $addField = $this->addFieldMgr->setAddFieldType($params['addFieldType']);

            $this->_sendResponseData([
                'addFieldType' => $addField->toArray()
            ]);
        } catch (InvalidArgumentException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'SetAddFieldType', $e->getMessage(),
                [],
                LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.errors'
            );
            $this->_sendResponseWithErrorCode($e->getCode());
        }
    }


    /**
     * Команда предназначена для установки значения поля пользователя в справочнике данных пользователя
     */
    public function actionSetUserAddField()
    {
        if (!UserAccess::hasPermissions(0) && !(UserAccess::hasPermissions(63) && UserAccess::hasPermissions(59))) {
            $this->_sendResponseWithErrorCode($this::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
        }

        $params = $this->_getRequestParams();

        if (empty($params['userCorporateField'])) {
            $this->_sendResponseWithErrorCode(SysSvcErrors::INPUT_PARAMS_ERROR);
        }

        try {
            $userAddField = $this->addFieldMgr->setUserAddField($params['userCorporateField']);

            if ($userAddField) {
                $this->_sendResponseData([
                    'userCorporateField' => $userAddField->toArray()
                ]);
            } else {
                $this->_sendResponseWithErrorCode(SysSvcErrors::INVALID_ADD_FIELD_VALUE);
            }
        } catch (InvalidArgumentException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'SetAddFieldType', $e->getMessage(),
                [],
                LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.errors'
            );
            $this->_sendResponseWithErrorCode($e->getCode());
        }
    }
}