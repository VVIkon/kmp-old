<?php
require_once('ApiHelper.php');

class ApiPackage
{
    public  $status = 0;         // Статус 0-Errror; 1-Ok
    public  $paramPkg = [];      // Входные параметры
    private $apiCommands = [];  // Массив объектов команд
    private $pkgStructure = []; // Шаблон выходной структуры
    private $flagStructure = false; //Флаг показывающий наличие структуры
    private $rowResult = [];    // Массив полученных ответов команд
    public  $fullResult = [];   // Выходной результат
    public  $queriedArray = []; // Массив полученных данных. Набор данных кот. команды УЖЕ запрашивали
    private $module;             // API modul
    private $tempStorageConfig = [];  // envconfig=>tempStorage

    /**
    *
    * @param
    * @returm array $pkgStruct (структура ответа)
    */
    public function __construct(array $pkgStruct)
    {
        $this->pkgStructure = nvl($pkgStruct);
        $this->flagStructure = count(nvl($this->pkgStructure,0))> 0;
        $this->module = Yii::app()->getModule('apiService');
        $this->tempStorageConfig = $this->module->getConfig('tempStorage');

    }
    /**
    * Получить масив команд пакета
    * @param
    * @returm
    */
    public function getApiCommandsArr(){
        return (array) $this->apiCommands;
    }

    /**
    * Получить масив данных предыдущих запросов
    * @param
    * @returm
    */
    public function getQueriedArray($action, $oper){
        $ret = null;
//        if(array_key_exists($action, $this->queriedArray))
//           $ret = $this->queriedArray[$action];

        if (!is_null($oper) && isset($this->queriedArray[$action][$oper]) ) {
            $ret = $this->queriedArray[$action][$oper];
        }elseif (!is_null($action) && isset($this->queriedArray[$action][0]) ) {
            $ret = $this->queriedArray[$action][0];
        }
        return $ret;
    }

    /**
    * Добавление в массив запросов полученных данных
    * @param
    * @returm
    */
    public function setQueriedArray($action, $operation, array $callBackArr){
//        if (!array_key_exists($action, $this->queriedArray)){
//            $this->queriedArray[$action] = $callBackArr;
//        }

        if (!is_null($operation) && empty($this->queriedArray[$action][$operation]) ) {
            $this->queriedArray[$action][$operation] = $callBackArr;
        }elseif (!is_null($action) && empty($this->queriedArray[$action][0]) ){
            $this->queriedArray[$action][0] = $callBackArr;
        }
    }
    /**
    * GETTER tempStorageConfig
    * @param
    * @returm
    */
    public function getTempStorageConfig(){
        return $this->tempStorageConfig;
    }
    /**
    * Получение используемого модуля
    * @param
    * @returm
    */
    public function getModule(){
        return $this->module;
    }

    public function makeFileName($params)
    {
        $fn = $params['filedata'];

        $parts = pathinfo($fn);
//        $dot = strrpos($fn['name'], '.');
//        if ($dot === false) {
//            $ext = '';
//        } else {
//            $ext = mb_substr($fn['name'], $dot);
//        }
        return $params['orderId'] . '_' . hash_file('md5', $fn['tmp_name']) . nvl($parts["extension"],'pdf');
    }


    /** Добавление компнды запроса
     * @param array $param (аутентификационные параметры запроса)
     * @param array $serviceAction (параметры запрашиваемого сервиса)
     */
    public function addCmd(array $param, array $serviceAction, $cmdTemplate)
    {
        $this->paramPkg = $param;
        if(isset($param['currency']))
            $this->paramPkg["getInCurrency"] = $param['currency'];
        $cmd = new ApiCmd();
        $cmd->loadParams($this->paramPkg, $serviceAction, $cmdTemplate, $this->module);
        $this->apiCommands[]= $cmd;
    }
    /**
    * Линковщик выходной структуры
     * если выходная структура (pkgStructure) - пуста, то возвращается массив с ключём "0"
     * если выходная структура не пуста то поисходит replace массивов по ключам
    * @param
    * @returm array apiResult
    */
    private function makeOutArr()
    {
        if ($this->flagStructure){ // с выходной структурой
            foreach ($this->pkgStructure as $key => $value){
                $this->fullResult[$key] = $this->rowResult[$key];
            }
        }
        else {  // без структуры
            $this->fullResult = nvl($this->rowResult[0]);
        }
    }

    /**
    * Исполнитель команд
    * @param
    * @returm
    */
    public function runCmd()
    {
        $this->status = 1;
        foreach( $this->apiCommands as $key=>$cmd )
        {
            $cmd->setCmdArray($this->getQueriedArray($cmd->action, $cmd->owmOper));
            $cmd->onCommand($this->paramPkg, $this->module); // Выполняем команду с добавленным массивом параметров.
            if ($cmd->errorCode > 0){ // если команда вернула ошибку...
                $this->status = 0;
                $this->fullResult['errorCode'] = $cmd->errorCode; //nvl($this->rowResult[$cmd->cmdIndex]['errorCode']);
                $this->fullResult['errors'] = $cmd->errorName;    //nvl($this->rowResult[$cmd->cmdIndex]['errorName']);
                break;
            }
            // Передача параметров из услуги
            $cmdPar = $cmd->getParams();
            if (isset($cmdPar['servicesIds'])){
                $this->paramPkg['servicesIds'] = $cmdPar['servicesIds'];
            }
            if (isset($cmdPar['gatherTouristID'])){
                $this->paramPkg['gatherTouristID'] = $cmdPar['gatherTouristID'];
            }

            if( isset($cmd->fullResult['body'][$cmd->cmdIndex]) ) { // Если в body присутствует такой же cmdIndex, то берём сщдержимое его body
                $this->rowResult[$cmd->cmdIndex] = $cmd->fullResult['body'][$cmd->cmdIndex];
            }
            else {
                if (isset($this->rowResult[$cmd->cmdIndex])) {  //если у результирующего массива уже присутствует cmdIndex и есть элементы, то чтобы добавить еще элемент, выбираю ключ из возвращаемого набора
                    $this->rowResult[$cmd->cmdIndex][key(nvl($cmd->fullResult,0) )] = nvl($cmd->fullResult[key(nvl($cmd->fullResult,0))],[]);
                } elseif(isset($cmd->fullResult[$cmd->cmdIndex])){ // Если в возвращаемом наборе fullResult присутствует ключ =  cmdIndex, то поглощаем лишнюю ступень набора
                    $this->rowResult[$cmd->cmdIndex] = $cmd->fullResult[$cmd->cmdIndex];
                } else { // Возвращаемый набор fullResult присваивается ключу cmdIndex
                    $this->rowResult[$cmd->cmdIndex] = nvl($cmd->fullResult, []);
                }
            }
            // Добавление в массив запросов полученных данных
            $this->setQueriedArray($cmd->action, $cmd->owmOper, $cmd->callBackArray);
        }
        // если команда вернула ошибку или нет данных
        if ($this->status > 0 && count($this->rowResult) > 0 ){
            $this->makeOutArr();
        }
    }

}