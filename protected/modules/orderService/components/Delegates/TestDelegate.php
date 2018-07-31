<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/15/16
 * Time: 1:14 PM
 */
class TestDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $AsyncTask = new AsyncTask();
        $AsyncTask->setModule(Yii::app()->getModule('orderService'));
        $AsyncTask->setTaskParams('orderService', 'OrderWorkflowManager',
            [
                'action' => 'New',
                "actionParams" => [],
                "usertoken" => "3853a2bcff70fc6a"
            ]
        );

        $this->setObjectToContext($AsyncTask);
    }
}