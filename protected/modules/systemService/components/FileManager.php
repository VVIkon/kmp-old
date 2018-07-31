<?php

/**
 * Class FileManager
 * Реализует функциональность для работы с файлами документов
 */
class FileManager
{
    /**
     * Код последней ошибки
     * @var int
     */
    private $errorCode;

    /**
     * Идентифкатор сессии
     * @var string
     */
    private $token;

    /**
     * Объект модуля
     * @var string
     */
    private $module;

    private $namespace;

    public function __construct($module) {

        $this->module = $module;
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Запуск действий менеджера
     * @param $action
     * @param $params
     * @return bool
     */
    public function runAction($action, $params)
    {
        try {
            switch($action) {
                case 'uploadFile':
                    $validator = new FileRequestValidator($this->module);
                    $validator->checkFileUploadParams($params);

                    $filePath = $this->uploadFile($params);

                    return $this->addDocumentToOrder($filePath, $params);
            }

        } catch (KmpException $ke) {
            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $ke->getCode();
            return false;
        }
    }

    /**
     * Загрузка файла в хранилище по указаному url
     * @param $params array массив
     * @return bool|string
     */
    private function uploadFile($params) {

        $loader = new FileLoader($this->module->getModule('file'));

        $fileName = $this->setFileName(
            $params['orderId'],
            $params['objectType'],
            $params['objectId'],
            $params['presentationFileName']
        );

        $result = $loader->downloadFile($fileName, $params['url']);

        if ($result == false) {
            throw new KmpException(get_class($this), __FUNCTION__,
                SysSvcErrors::CANNOT_DOWNLOAD_FILE,
                [
                    'url' => $params['url'],
                    'fileName' => $fileName,
                    'error'     => $loader->getLastError()
                ]
            );
        }

        return ($result == true ? $loader->getLastUploadedFile() : false);
    }

    /**
     * Установка имени файла
     * @param $orderId
     * @param $objectType
     * @param $objectId
     * @param $displayName
     * @return string
     */
    private function setFileName($orderId, $objectType, $objectId, $displayName) {

        $typeName = BusinessEntityTypes::getTypeNameById($objectType);

        if ($typeName === false) {
            $typeName = '';
        }

        $filename = $orderId . '_' . $typeName . '_' . $objectId . '_' . $displayName;

        return $filename;
    }

    /**
     * Привязать загруженный файл к заявке
     * @param $filepath
     * @param $params
     */
    private function addDocumentToOrder($filepath, $params)
    {
        $orderServiceClient = $this->module->ApiClient($this->module);

        $fileInfo = (new FileInformer($this->module->getModule('file')))->getFileInfo($filepath);

        $actionParams = [
            "presentationFileName" => $params['presentationFileName'],
  	        "url" => $fileInfo['filePath'],
  	        "comment" => $params['comment'],
            "orderId" => $params['orderId'],
  	        "objectType" => $params['objectType'],
	        "objectId" => $params['objectId'],
  	        "fileSize" => $fileInfo['size'],
  	        "mimeType" => $fileInfo['mimeType'],
            "usertoken" => $params['usertoken']
        ];

        $linkResult = $orderServiceClient->makeRestRequest('orderService', 'AddDocumentToOrder', $actionParams);
        $linkResult = json_decode($linkResult, true);

        if ($linkResult === false ||
            !isset($linkResult['status']) ||
            $linkResult['status'] != 0 ||
            !isset($linkResult['body']))
        {
            throw new KmpException(get_class($this), __FUNCTION__,
                SysSvcErrors::CANNOT_LINK_FILE_TO_ORDER,
                [
                    'actionParams' => [
                        "presentationFileName" => $params['presentationFileName'],
                        "url" => $fileInfo['filePath'],
                        "comment" => $params['comment'],
                        "orderId" => $params['orderId'],
                        "objectType" => $params['objectType'],
                        "objectId" => $params['objectId'],
                        "fileSize" => $fileInfo['size'],
                        "mimeType" => $fileInfo['mimeType'],
                        "usertoken" => $params['usertoken']
                    ],
                    'response' => print_r($linkResult, 1)
                ]
            );
        } else {
            return $linkResult['body'];
        }
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError() {
        return $this->errorCode;
    }
}