<?php

/**
 * Class RunOfferSearch
 * Класс для выполнения команды поиска предложений
 */
class RunOfferSearch extends CConsoleCommand
{
    public function init()
    {
        Yii::getLogger()->autoFlush = 1;
        Yii::getLogger()->autoDump = true;
    }

    public function actionStartSearch($token,$agentId)
    {

        $searchMgr = new SearchManager();
        $searchMgr->startSearchTask($token,$agentId);
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError() {
        return $this->errorCode;
    }
}
