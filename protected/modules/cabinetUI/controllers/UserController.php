<?php

/**
 * Контроллер пользовательской авторизации
 */
class UserController extends CController
{
    public $layout = 'login';

    /** @var UIApiClient */
    private $apiClient;

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

    public function actionLogin()
    {
        //$loginData = Yii::app()->request->getPost('loginForm', null);
        //$isAjax = (bool)Yii::app()->request->getPost('isAjax', false);
        $params = json_decode(Yii::app()->request->getRawBody(), true);
        $response = $this->apiClient->makeRestRequest('systemService', 'UserAuth', $params);
        echo $response;
    }

    public function actionLogout()
    {
        $isAjax = (bool)Yii::app()->request->getPost('isAjax', false);
        Yii::app()->session->destroy();
        Yii::app()->request->cookies->clear();
        if (!$isAjax) {
            Yii::app()->controller->redirect('/');
        }
    }
}

?>
