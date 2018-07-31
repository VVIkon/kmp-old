<?php

use Workerman\Connection\TcpConnection;

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 18.05.17
 * Time: 16:29
 */
class ConnectionWorkermanDecorator implements ConnectionDecoratorInterface
{
    const ACTION_TYPE_MSG = 'msg';
    const ACTION_TYPE_HISTORY = 'history';
    const ACTION_TYPE_UNREAD_MSGS = 'unreadMsgs';

    /**
     * @var TcpConnection
     */
    protected $conn;

    public function __construct(TcpConnection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return TcpConnection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Генерация KMP ошибки на основе кода
     * @param $code
     * @param $closeConnection
     */
    public function sendError($code, $closeConnection = false)
    {
        $this->conn->send(json_encode([
            'status' => 1,
            'errorCode' => $code,
            'errors' => ErrorHelper::getErrorDescription(Yii::app()->getModule('systemService'), $code),
            'body' => []
        ]));

        if ($closeConnection) {
            $this->conn->close();
        }
    }

    /**
     * Генерация респонса в формате KMP
     * @param array $data
     * @param $type
     */
    public function sendResponse($type, array $data)
    {
        $this->conn->send(json_encode([
            'status' => 0,
            'body' => [
                'actionType' => $type,
                'data' => $data
            ]
        ]));
    }
}