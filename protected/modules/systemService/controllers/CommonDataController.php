<?php

/**
 * Class CommonDataController
 * Реализует команды получения общих данных в системе
 */
class CommonDataController extends SecuredRestController
{
    /**
     * Получение справочных данных
     */
    public function actionGetDictionary()
    {
        $params = $this->_getRequestParams();

        try {
            $validator = new DictionaryRequestValidator(YII::app()->getModule('systemService'));
            $validator->checkGetDictionaryParams($params);

            $dictHandler = DictionariesFactory::createDictionaryHandler(
                $params['dictionaryType'],
                YII::app()->getModule('systemService')
            );

            $data = $dictHandler->getDictionaryData($params);

            $this->_sendResponseData($data);
        } catch (KmpException $ke) {
            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                YII::app()->getModule('systemService')->getConfig('log_namespace') . '.errors'
            );
            $this->_sendResponseWithErrorCode($ke->getCode());
        }
    }
}