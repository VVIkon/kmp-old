<?php

/**
 * Class FileLoader
 * Реализует функциональность для загрузки файлов на сервер
 */
class FileLoader
{
    /**
     * Код последней ошибки
     * @var int
     */
    private $errorCode;

    /**
     * Объект модуля
     * @var string
     */
    private $module;

    /**
     * Путь для сохранения файлов
     * @var
     */
    private $storagePath;

    private $lastUploadedFile = '';

    public function __construct($module)
    {

        $this->module = $module;
        $this->initConfig();
    }

    /**
     * Инициализация свойств объекта
     */
    public function initConfig()
    {
        $storagePath = $this->module->getConfig('storagepath');
        $storagePath = Yii::getPathOfAlias('webroot') . $storagePath;

        if (!file_exists($storagePath) || !is_dir($storagePath)) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__,
                SysSvcErrors::INCORRECT_STORAGE_PATH,
                [
                    'storagePath' => $storagePath
                ]
            );
        }
        $this->storagePath = $storagePath;
    }

    /**
     * Загрузить файл в хранилище из указанного url
     * @param $fileName
     * @param $fileUrl
     * @return mixed
     */
    public function downloadFile($fileName, $fileUrl)
    {
        set_time_limit(0);
        $fileName = preg_replace('/[^A-z,0-9,_,\-]/', '', $fileName);
        if (empty($this->storagePath) || empty($fileName)) {

            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__,
                SysSvcErrors::INCORRECT_FILE_SAVE_PARAMETERS,
                [
                    'storagePath' => $this->storagePath,
                    'fileName' => $fileName
                ]
            );
        }

        if (!$this->checkUrlExists($fileUrl)) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__,
                SysSvcErrors::CANNOT_GET_ACCESS_FILE_BY_URL,
                [
                    'storagePath' => $this->storagePath,
                    'fileName' => $fileName,
                    'fileUrl' => $fileUrl
                ]
            );
        }

        $pathData = pathinfo($fileUrl);
        $fileExt = isset($pathData['extension']) ? $pathData['extension'] : '';

        $fileName = $fileName . (!empty($fileExt) ? '.' . $fileExt : '');

        if (PHP_OS == 'WINNT') {
            $writeFileName = mb_convert_encoding($fileName, "windows-1251", "utf-8");
        } else {
            $writeFileName = $fileName;
        }

        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );

        set_error_handler(function ($errno, $errstr) {
            throw new Exception("$errno - $errstr");
        }, E_WARNING);

        try {
            $downloadedFile = file_get_contents($fileUrl, false, stream_context_create($arrContextOptions));

            if ($downloadedFile === false) {
                LogHelper::logExt(
                    get_class(), __METHOD__,
                    'Скачивание файла из хранилища вызвало ошибку', '',
                    [
                        'error' => error_get_last(),
                        'downloadPath' => $fileUrl
                    ],
                    LogHelper::MESSAGE_TYPE_ERROR,
                    'system.systemservice.errors'
                );

                $this->errorCode = SysSvcErrors::CANNOT_DOWNLOAD_FILE;
                return false;
            }

            $result = file_put_contents($this->storagePath . '/' . $writeFileName, $downloadedFile);

            if ($result === false) {
                LogHelper::logExt(
                    get_class(), __METHOD__,
                    'Сохранение файла в storage вызвало ошибку', '',
                    [
                        'error' => error_get_last(),
                        'writePath' => $this->storagePath . '/' . $writeFileName
                    ],
                    LogHelper::MESSAGE_TYPE_ERROR,
                    'system.systemservice.errors'
                );

                $this->errorCode = SysSvcErrors::CANNOT_CREATE_FILE;
                return false;
            }
        } catch (Exception $e) {
            LogHelper::logExt(
                get_class(), __METHOD__,
                'Ошибка во время file_get_contents или file_put_contents', '',
                [
                    'error' => $e->getMessage(),
                ],
                LogHelper::MESSAGE_TYPE_ERROR,
                'system.systemservice.errors'
            );

            $this->errorCode = SysSvcErrors::CANNOT_CREATE_FILE;
            return false;
        } finally {
            restore_error_handler();
        }

        if ($result) {
            if (empty($fileExt)) {
                $fileExt = $this->getFileExtByMimeType(mime_content_type($this->storagePath . '/' . $writeFileName));

                if (!empty($fileExt)) {

                    rename(
                        $this->storagePath . '/' . $writeFileName,
                        $this->storagePath . '/' . $writeFileName . '.' . $fileExt
                    );
                    $fileName = $fileName . '.' . $fileExt;
                }
            }
            $this->lastUploadedFile = 'storage://' . $fileName;
        } else {
            $this->errorCode = SysSvcErrors::CANNOT_CREATE_FILE;
        }

        return $result;
    }

    /**
     * Проверка существования указанного URL
     * @param $url
     * @return bool
     */
    private function checkUrlExists($url)
    {
        if (empty($url)) {
            return false;
        }

        set_error_handler(function ($errno, $errstr) {
            throw new Exception("$errno - $errstr");
        }, E_WARNING);

        try {
            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            );

            $f = fopen($url, 'r', false, stream_context_create($arrContextOptions));

            restore_error_handler();
            return $f == true;
        } catch (Exception $e) {
            restore_error_handler();
            return false;
        }
    }

    /**
     * Получить расширение файла по его mime type
     * @param $mimeType
     * @return bool|string
     */
    private function getFileExtByMimeType($mimeType)
    {
        if (empty($mimeType)) {
            return false;
        }

        switch ($mimeType) {
            case 'application/pdf' :
                return 'pdf';
            default :
                return false;
        }
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->errorCode;
    }

    /**
     * Получение полного пути к последнему загруженному файлу
     * @return bool|string
     */
    public function getLastUploadedFile()
    {
        return !empty($this->lastUploadedFile) ? $this->lastUploadedFile : false;
    }
}
