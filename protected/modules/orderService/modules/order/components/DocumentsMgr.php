<?php

/**
 * Class DocumentsMgr
 * Класс для работы с документами заявки
 */
class DocumentsMgr
{
    /**
     * Код ошибки
     * @var int
     */
    private $errorCode;

    private $module;
    /**
     * namespace для записи логов
     * @var
     */
    private $namespace;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module) {
        $this->module = $module;
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Получить приложенные документы к заявке
     * @param $params
     * @return bool
     */
    public function getOrderDocuments($params)
    {
        $validator = new DocsValidator($this->module);

        try {
            $validator->checkGetOrderDocumentsParams($params);
            $result = $this->getOrderDocsInfo($params['orderId']);
        } catch (KmpException $ke) {
            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace. '.errors'
            );
            $this->errorCode = $ke->getCode();
            return false;
        }

        return $result;
    }

    /**
     * Получить информацию по файлам заявки
     * @param $orderId
     * @return array|bool
     */
    private function getOrderDocsInfo($orderId)
    {
        $orderDocsInfo = OrderDocsForm::getOrderDocs($orderId);

        if ($orderDocsInfo === false) {
            return false;
        }

        $result = [];
        foreach ($orderDocsInfo as $orderDocInfo) {
            $result[] = [
                'documentId' => hashtableval($orderDocInfo['documentId'],''),
                'mimeType' => hashtableval($orderDocInfo['mimetype'],''),
                'fileName' => hashtableval($orderDocInfo['fileName'],''),
                'fileSize' => hashtableval($orderDocInfo['fileSize'],''),
                'fileUrl' => $this->getDocumentUrl(hashtableval($orderDocInfo['fileUrl'],'')),
                'fileComment' => hashtableval($orderDocInfo['fileComment'],'')
            ];
        }

        return $result;
    }

    /**
     * Добавить описание документа к заявке
     * @param $params
     */
    public function addDocument($params)
    {
        $validator = new DocsValidator($this->module);

        try {
            $validator->checkAddDocumentParams($params);
        } catch (KmpException $ke) {
            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace. '.errors'
            );
            $this->errorCode = $ke->getCode();
            return false;
        }

        $attDoc = new AttachedDocument($this->module);

        $attDoc->setAttributes([
            'orderId' => $params['orderId'],
            'documentSource' => AttachedDocument::SOURCE_TYPE_KT,
            'mimeType' => $params['mimeType'],
            'fileName' => $params['presentationFileName'],
            'fileSize' => $params['fileSize'],
            'fileURL'  => $params['url'],
            'fileComment' => $params['comment'],
            'objectType'  => $params['objectType'],
            'objectId'  => $params['objectId'],
        ]);

        try {
            $attDoc->save();
        } catch (KmpDbException $kde) {
            LogExceptionsHelper::logExceptionEr($kde, $this->module, $this->namespace . '.errors');
            $this->errorCode = $kde->getCode();
            return false;
        }

        return ['documentId' => $attDoc->documentId];
    }

    /**
     * Получение url документа по его адресу
     * @param $path
     * @return mixed
     */
    public function getDocumentUrl($path)
    {
        if (preg_match('/http.{0,1}:\/\//', $path)) {
            return $path;
        }

        $storagePath = $this->module->getConfig('storagePath');

        if (empty($storagePath))
        {
            throw new KmpInvalidSettingsException(
                get_class(),__FUNCTION__,
                OrdersErrors::CANNOT_GET_FILES_STORAGE_SETTINGS,
                [
                    'configSection' => 'storagePath',
                    'configModule' => $this->module->getName()
                ]
            );
        }

        $storagePath .= '/';

        $storagePath = str_replace('\\','/', $storagePath);

        $realPath = preg_replace('/storage:\/\//', $storagePath, $path);

        $fileUrl = Yii::app()->getBaseUrl(true) . $realPath;

        return $fileUrl;
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError() {
        return $this->errorCode;
    }

    /**
     * Сброс кода последней ошибки
     */
    public function resetLastError() {
        $this->errorCode = 0;
    }

}
