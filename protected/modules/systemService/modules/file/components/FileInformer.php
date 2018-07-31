<?php

/**
 * Class FileInformer
 * Реализует функциональность для получения информации о файлах в хранилище
 */
class FileInformer
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
     * Получить инфорамцию об указанном файле по его пути
     * @param $filePath
     * @return array
     */
    public function getFileInfo($filePath)
    {
        $osFilePath = $this->getRealFilePath($filePath);

        if (PHP_OS == 'WINNT') {
            $osFilePath = mb_convert_encoding($osFilePath, "windows-1251", "utf-8");
        }

        if (!file_exists($osFilePath) || is_dir($osFilePath)) {
            throw new KmpInvalidArgumentException(get_class($this), __FUNCTION__,
                SysSvcErrors::FILE_NOT_FOUND,
                ['filePath' => $filePath]
            );
        }



        $realPath = str_replace('storage://', $this->module->getConfig('storagepath') . '/', $filePath);
        $fileUrl = Yii::app()->getBaseUrl(true) . $realPath;

        $fileInfo = [
            'size' => filesize($osFilePath),
            'mimeType' => mime_content_type($osFilePath),
            'filePath' => $fileUrl
        ];

        return $fileInfo;
    }

    /**
     * Получение физического пути к файлу
     * @param $filePath
     * @return mixed
     */
    private function getRealFilePath($filePath)
    {
        return str_replace('storage://', $this->storagePath . '/', $filePath);
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->errorCode;
    }

}