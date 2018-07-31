<?php

/**
 * Class ReportsController
 * Панель управления отчетами
 */
class ReportsController extends CController
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
        $this->render('reports');
    }

    /** Создание отчета */
    public function actionCreateReport() {
        $this->apiRequest('orderService', 'CreateReport');
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

}
