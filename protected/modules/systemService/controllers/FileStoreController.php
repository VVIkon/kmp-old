<?php

/**
 * Class FileStoreController
 * Реализует команды получения информации от поставщиков
 */
class FileStoreController extends RestController
{

    const UPLOAD_FILE_FILE_MGR_ACTION = 'uploadFile';

    /**
     * Загрузка файла в хранилище КТ
     */
    public function actionUploadFile()
    {
        $response = $this->runFileManagerAction(self::UPLOAD_FILE_FILE_MGR_ACTION);
    }

    /**
     * Метод запуска команд файлового менеджера
     * @param string $action запускаемая команда
     */
    private function runFileManagerAction($action)
    {
        $module = YII::app()->getModule('systemService');

        $params = $this->_getRequestParams();

        $fileMgr = new FileManager($module);

        $response = $fileMgr->runAction($action, $params);

        if ($response === false) {
            $this->_sendResponse(false, array(),
                $module->getError($fileMgr->getLastError()),
                $fileMgr->getLastError()
            );
        } else {
            $this->_sendResponse(true, $response, '');
        }
    }

}