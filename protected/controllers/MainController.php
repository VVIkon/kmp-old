<?php

/**
 * Controller for Guest cabinet
 */
class MainController extends CController
{
    //use TourTrait;

    /**
     * @return array action filters
     */
    /*
   public function filters()
   {
       return [
           'accessControl',
//            'ajaxOnly + reservation, sendTourist'
       ];
   }
   */

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    /*
   public function accessRules()
   {
       return [
           ['allow',
               'actions' => ['error'],
               'users' => ['*'],
           ],
           ['allow',
               'actions' => ['air', 'detail', 'excursion', 'hotel', 'index', 'service', 'tour'],
               'roles' => ['guest'],
           ],

           ['deny', // deny all users
               'users' => ['*'],
           ],
       ];
   }
   */

    public function actionIndex()
    {
        if (Yii::app()->session->contains('userData')) {
            $userData = Yii::app()->session->get('userData');
            $role = $userData['role'];
        } else {
            $role = 'guest';
        }

        if ($role == 'op' || $role == 'agent') {
            Yii::app()->controller->redirect('/cabinetUI/orders/index');
        } else {
            $this->layout = "main";
            $this->render('stub');
        }
    }

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError()
    {
        if ($error = Yii::app()->errorHandler->error) {
            if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json') {
//                $trace = '';
//                if (defined('YII_DEBUG')) {
//                    $trace = print_r(Yii::app()->errorHandler->error, 1);
//                }

                $status_header = 'HTTP/1.1 ' . 200 . ' ';
                header($status_header);
                header('Content-type: application/json');

                $response = array(
                    'status' => 1,
                    'errors' => $error['message'],
                    'body' => "File {$error['file']}, Line: {$error['line']}",
                    'errorCode' => $error['code'],
                    'trace' => Yii::app()->errorHandler->error
                );

                if (defined('YII_DEBUG')) {
                    $response['trace'] = Yii::app()->errorHandler->error;
                }

                print JsonHelper::encodeJson($response, JSON_BIGINT_AS_STRING | JSON_NUMERIC_CHECK);

                Yii::app()->end();
            } else {
                $this->renderPartial('error' . $error['code'], $error);
            }
        }
    }
}
