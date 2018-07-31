<?php 

class AdminController extends CController {
    public $layout = 'main';

    private $apiClient;

    protected function beforeAction($action)
    {
        $module = Yii::app()->getModule('adminUI');
        $authData = $module->getConfig('serviceAuth');
        $appConfig = Yii::app()->getParams();
        if (!isset($appConfig['serviceRoutes'])) {
            throw new Exception("Не настроены пути к сервисам в параметре serviceRoutes");
        }
        $servicePaths = $appConfig['serviceRoutes'];
        $this->apiClient = new UIApiClient($authData,$servicePaths);
        return true;
    }

    private function apiRequest($service, $request) {
        $params = json_decode(Yii::app()->request->getRawBody(), true);
        $response = $this->apiClient->makeRestRequest($service, $request, $params);
        echo $response;
    }

    public function actionIndex() {
        $this->render('app', []);
    }

    /** Проверка прав пользователя */
    public function actionCheckUserAccess() {
        $this->apiRequest('systemService', 'UserAccess');
    }

    /** Авторизация пользователя */
    public function actionUserAuth() {
        $this->apiRequest('systemService', 'UserAuth');
    }

    /** Получение информации из справочника KT */
    public function actionGetDictionary() {
        $this->apiRequest('systemService', 'GetDictionary');
    }

    /** Получение справочника ролей и прав доступа */
    public function actionGetRolesAndPermissions() {
        $params = json_decode(Yii::app()->request->getRawBody(), true);
        $response = $this->apiClient->makeRestRequest('systemService', 'GetDictionary', $params);

        try {
            $result = json_decode($response, true);
            if ($result['status'] === 0) {
                foreach ($result['body']['roles'] as &$role) {
                    $role['permissionsCodeHex'] = dechex($role['permissionsCode']);
                }
                echo json_encode($result);
            } else {
                echo $response;
            }
        } catch (Exception $e) {
            echo $response;
        }
    }

    /** Саджест пользователей компании */
    public function actionGetUserSuggest() {
        $this->apiRequest('systemService', 'GetUserSuggest');
    }

    /** Получение данных пользователя */
    public function actionGetUser() {
        $this->apiRequest('systemService', 'GetUser');
    }

    /** Сохранение роли пользователя */
    public function actionSetUserRole() {
        $this->apiRequest('systemService', 'SetUserRole');
    }

    /** Сохранение данных пользователя */
    public function actionSetUser() {
        $this->apiRequest('systemService', 'SetUser');
    }
}