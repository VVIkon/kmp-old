<?php
use Symfony\Component\Validator\Validation;
require_once('ApiHelper.php');

/**
 * APIKT работа с документами
 * @param
 * @returm
 */
class ApiDocumentController extends ServiceAuthController
{

    /**
    * Загружает документ и добавляет его к списку документов заказа
    * @param
       usertoken = ''
       presentationFileName = "Картинка.png"/ // Раздел с файлом
       name="comment" // Раздел с комментарием к файлу
       name="orderNumber" // Раздел с номером заявки

    * @returm
     *  {
            "documentId" : 681 // Идентификатор документа в системе
        }
    */
    public function actionClientAddDocumentToOrder()
    {
        //$params = $this->_getRequestParams();
        //var_dump($_POST);

        $params = filter_var_array($_POST);

        // валидация параметров
        if (!isset($params['orderNumber']) || !is_numeric($params['orderNumber']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_ORDERID);
        if (!isset($params['filename']) )
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_FILENAME);
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);
        if (!isset($_FILES['doc']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_NOT_TEMPFILE);

        $filedata = $_FILES['doc'];
        if ($filedata['error'] == 0){
            $params['filedata'] = $filedata;

            // Формирование имени
            $dot = strrpos($filedata['name'], '.');
            if ($dot === false) {
                $ext = '';
            } else {
                $ext = mb_substr($filedata['name'], $dot);
            }
            $docname = $params['orderNumber'] . '_' . hash_file('md5', $filedata['tmp_name']) . $ext;

            //API структура
            $ap = new ApiPackage([]);
            $tmpStorageConf = $ap->getTempStorageConfig();
            // Пути
            $ftpPath= nvl($tmpStorageConf['ftpPath']) .'/'. $docname;
            $params['url'] = $ftpPath;
            $tmpPath = nvl($tmpStorageConf['tmpPath']) .'/'. $docname;

            $res = move_uploaded_file($filedata['tmp_name'], $tmpPath);
            if ($res === false) {
                LogHelper::logExt(get_class($this), __METHOD__, 'Сохранение файла в storage вызвало ошибку', $ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
                $this->_sendResponse(false, array(), '1005','Сохранение файла в storage вызвало ошибку' );
            }else{
                $ap->addCmd($params, ['serviceName' => 'systemService', 'action' => 'UploadFile', 'cmdIndex' => '0'], 'addDocumentToOrderTemplate');
                $ap->runCmd();

                if ($ap->status == 1) {
                    LogHelper::logExt(get_class($this), __METHOD__, 'Операция "Загрузка дорумента к заявке"', '', $params, 'info', 'system.apiservice.info');
                    $this->_sendResponseData($ap->fullResult);
                } else {
                    LogHelper::logExt(get_class($this), __METHOD__, 'Операция "Загрузка дорумента к заявке"', $ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
//                    $this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
                    $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
                }
//            } else {
//                $this->_sendResponseWithErrorCode(ApiErrors::ERROR_LOAD_TEMPFILE_TO_KT);
            }
        }else{
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_LOAD_TEMPFILE);
        }
    }
    /**
    * Функция возвращает список документов заявки
    * @param
     *  {
     *      "usertoken" : 'fc466fbe53d829f6'
            "orderNumber" : 12345,  // Номер заявки
        }
    * @returm
     * {
        "status": 0,
        "errors": "",
        "body": [
                    {
                    "documentId": 1379,
                    "mimeType": "image/jpeg",
                    "fileName": "photo_2016-09-20_16-04-00.jpg",
                    "fileSize": 35865,
                    "fileUrl": "https://dev.kmp.travel/storage/17091_order_17091_photo_2016-09-20_16-04-00jpg.jpg",
                    "fileComment": ""
                    },
                    {
                    "documentId": 1380,
                    "mimeType": "image/png",
                    "fileName": "1.png",
                    "fileSize": 139208,
                    "fileUrl": "https://dev.kmp.travel/storage/17091_order_17091_1png.png",
                    "fileComment": ""
                    },
                    {
                    "documentId": 1381,
                    "mimeType": "application/zip",
                    "fileName": "citieslist-v0.4.zip",
                    "fileSize": 12347339,
                    "fileUrl": "https://dev.kmp.travel/storage/17091_order_17091_citieslist-v04zip.zip",
                    "fileComment": ""
                    }
                ]
        }
    */
    public function actionClientGetOrderDocuments()
    {
        $params = $this->_getRequestParams();

        // валидация параметров
        if (!isset($params['orderNumber']) || !is_numeric($params['orderNumber']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_ORDERID);
        if (!isset($params['usertoken']))
            $this->_sendResponseWithErrorCode(ApiErrors::ERROR_PARAM_USERTOKEN);

        $ap = new ApiPackage([]);
        $ap->addCmd($params, ['serviceName'=>'orderService', 'action'=>'GetOrderDocuments','cmdIndex'=> '0'], 'getOrderDocumentsTemplate');
        $ap->runCmd();

        if ($ap->status == 1) {
            LogHelper::logExt(get_class($this), __METHOD__, 'Получение списока документов заявки', '', $params, 'info', 'system.apiservice.info');
            $this->_sendResponseData($ap->fullResult);
        } else {
            LogHelper::logExt(get_class($this), __METHOD__, 'Получение списока документов заявки', $ap->fullResult['errors'], $params, 'error', 'system.apiservice.error');
//            $this->_sendResponse(false, array(), $ap->fullResult['errors'], $ap->fullResult['errorCode']);
            $this->_sendResponse(false, array(), 'Error', $ap->fullResult['errorCode']);
        }
    }
}