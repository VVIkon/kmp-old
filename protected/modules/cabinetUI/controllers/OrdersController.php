<?php

/**
 * Class OrdersController
 * Команды для работы FE с сервисом заявок
 */
class OrdersController extends CController
{
    public $layout = 'main';

    /** @var UIApiClient */
    private $apiClient;

    private function apiRequest($service, $request) {
        $params = json_decode(Yii::app()->request->getRawBody(), true);
        $response = $this->apiClient->makeRestRequest($service, $request, $params);
        echo $response;
    }

    protected function beforeAction($action)
    {
        $module = Yii::app()->getModule('cabinetUI');
        $authData = $module->getConfig('authdata');
        $appConfig = Yii::app()->getParams();
        if (!isset($appConfig['serviceRoutes'])) {
            throw new Exception("Не настроены пути к сервисам в параметре serviceRoutes");
        }
        $servicePaths = $appConfig['serviceRoutes'];
        $this->apiClient = new UIApiClient($authData, $servicePaths);
        return true;
    }

    public function actionIndex()
    {
        session_write_close();
        /**
         * Сохранение в сессии настроек фильтра заявок
         * @todo перенести в webstorage?
         */
        if (!Yii::app()->session->contains('olSearchFilter')) {
            Yii::app()->session->add('olSearchFilter', []);
        }
        $storedFilter = Yii::app()->session->get('olSearchFilter');

        $this->render('orderList', $storedFilter);
    }

    public function actionOrder()
    {
        session_write_close();
        $this->render('orderEdit');
    }

    /**
     * Получение списка заявок в JSON формате
     */
    public function actionGetOrderList()
    {
        $this->apiRequest('orderService', 'GetOrderList');
    }

    /**
     * Получение информацию по указанной заявке
     */
    public function actionGetOrder()
    {
        $this->apiRequest('orderService', 'GetOrder');
    }

    /**
     * Получение счета по указанной заявке
     */
    public function actionGetOrderInvoices()
    {
        $this->apiRequest('orderService', 'GetOrderInvoices');
    }

    public function actionGetOrderDocuments()
    {
        $this->apiRequest('orderService', 'GetOrderDocuments');
    }

    public function actionGetOrderHistory()
    {
        $this->apiRequest('orderService', 'GetOrderHistory');
    }

    public function actionSetInvoice()
    {
        $this->apiRequest('orderService', 'SetInvoice');
    }

    public function actionSetInvoiceCancel()
    {
        $this->apiRequest('orderService', 'SetInvoiceCancel');
    }

    public function actionSetDiscount()
    {
        $this->apiRequest('orderService', 'SetDiscount');
    }

    public function actionGetOrderTourists()
    {
        $this->apiRequest('orderService', 'GetOrderTourists');
    }

    public function actionRemoveTouristFromOrder()
    {
        $this->apiRequest('orderService', 'RemoveTouristFromOrder');
    }

    public function actionSetTouristToOrder()
    {
        $this->apiRequest('orderService', 'SetTouristToOrder');
    }

    public function actionGetOrderOffers()
    {
        $this->apiRequest('orderService', 'GetOrderOffers');
    }

    public function actionOrderWorkflowManager()
    {
        $this->apiRequest('orderService', 'OrderWorkflowManager');
    }

    public function actionCheckWorkflow()
    {
        $this->apiRequest('orderService', 'CheckWorkflow');
    }

    /**
     * Загрузка документов к заявке
     * @todo для IE8 посмотреть в сторону обертки в textarea: http://malsup.com/jquery/form/#file-upload
     */
    public function actionUploadDocumentToOrder()
    {
        $params = filter_var_array($_POST);

        if (!isset($_FILES['doc'])) {
            $response = json_encode([
                'status' => 2,
                'body' => null,
                'errors' => 'Временный файл не сохранен',
                'errorCode' => 2
            ]);
        } else if (!isset($params['orderId'])) {
            $response = json_encode([
                'status' => 2,
                'body' => null,
                'errors' => 'Не указан orderId',
                'errorCode' => 1
            ]);
        } else {
            $filedata = $_FILES['doc'];

            if ($filedata['error'] == 0) {
                $module = YII::app()->getModule('cabinetUI');
                $config = $module->getConfig('tempStorage');

                $dot = strrpos($filedata['name'], '.');
                if ($dot === false) {
                    $ext = '';
                } else {
                    $ext = mb_substr($filedata['name'], $dot);
                }
                $docname = $params['orderId'] . '_' . hash_file('md5', $filedata['tmp_name']) . $ext;

                if (move_uploaded_file($filedata['tmp_name'], $config['path'] . '/' . $docname)) {
                    $docinfo = [
                        'presentationFileName' => $filedata['name'],
                        'comment' => isset($params['comment']) ? $params['comment'] : '',
                        'orderId' => $params['orderId'],
                        'objectType' => 1,
                        'objectId' => $params['orderId'],
                        'url' => $config['urlbase'] . '/' . $docname,
                        'usertoken' => $params['usertoken']
                    ];
                    $response = $this->apiClient->makeRestRequest('systemService', 'UploadFile', $docinfo);
                } else {
                    $response = json_encode([
                        'status' => 2,
                        'body' => null,
                        'errors' => 'Ошибка записи файла',
                        'errorCode' => 3
                    ]);
                }
            } else {
                $response = json_encode([
                    'status' => 2,
                    'body' => null,
                    'errors' => 'Ошибка загрузки файла, код ошибки: ' . $filedata['error'],
                    'errorCode' => 4
                ]);
            }
        }

        echo $response;
    }

    /**
     * Возвращает шаблоны mustache :{)
     */
    public function actionGetTemplates()
    {
        session_write_close();
        $params = json_decode(Yii::app()->request->getRawBody(), true);
        $templates = array();
        foreach ($params as $key => $templ) {
            //$templates[$key]=$this->renderPartial('templates/'.$templ,null,true);
            $vf = $this->getViewFile('templates/' . $templ);
            if ($vf === false) {
                $templates[$key] = 'no template';
            } else {
                $templates[$key] = file_get_contents($vf);
            }
        }
        echo json_encode($templates);
    }

    /**
     * Возвращает требуемый документ
     * @todo добавить error codes?
     */
    public function actionGetStaticDocument()
    {
        session_write_close();
        $params = json_decode(Yii::app()->request->getRawBody(), true);
        $response = [];

        if (!isset($params['document'])) {
            $response['status'] = 1;
            $response['document'] = '';
            $response['errors'] = 'document not found';
        } else {
            $vf = $this->getViewFile('templates/staticDocuments/' . $params['document']);

            if ($vf === false) {
                $response['status'] = 1;
                $response['document'] = '';
                $response['errors'] = 'document not found';
            } else {
                $response['status'] = 0;
                $response['document'] = file_get_contents($vf);
                $response['errors'] = '';
            }
        }
        echo json_encode($response);
    }
}
