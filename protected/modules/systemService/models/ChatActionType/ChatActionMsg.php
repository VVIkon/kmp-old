<?php

/**
 * ws://localhost:8100?usertoken=70622e30e3d24c69
 *
 *
 * Msg to User
 {
  "actionType": "msg",
  "data":
  {
  "orderId": null,
  "userId": 4659,
  "msgText": "Test"
  }
  }
 *
 * Msg to Order
  {
  "actionType": "msg",
  "data":
  {
  "orderId": null,
  "userId": 4659,
  "msgText": "Test"
  }
  }
 */
class ChatActionMsg extends AbstractChatAction
{
    /**
     * @var ChatMessage
     */
    protected $chatMessage;
    protected $apiClient;

    /**
     * @var AccountsMgr
     */
    protected $accountManager;

    /**
     *
     * ChatActionMsg constructor.
     */
    public function __construct()
    {
        $this->chatMessage = new ChatMessage();
        $this->apiClient = new ApiClient(Yii::app()->getModule('systemService'));
        $this->accountManager = new AccountsMgr(Yii::app()->getModule('systemService'));
    }

    /**
     * @param $from
     * @param array $data
     * @param SplObjectStorage $clients
     * @throws DomainException
     * @throws InvalidArgumentException
     * @throws ChatActionRightsException
     * @return mixed
     */
    public function process($from, array $data, SplObjectStorage $clients)
    {
        // запросим orderService для менеджеров заявки, сохраним получателей сообщения
        if (empty($data['orderId']) && empty($data['userId'])) {
            throw new InvalidArgumentException('Не заданы получатели сообщения');
        }

        $connectionDecorator = new ConnectionWorkermanDecorator($from);

        // сохраним сообщение в базу
        $this->chatMessage->fromArray($data);
        $this->chatMessage->setUserId($clients->offsetGet($from));
        $this->chatMessage->save(false);
        $this->chatMessage->refresh();

        // очередь на отправку сообщ
        $msgSender = new MsgSender($clients);

        // проверим куда сообщение
        if (!empty($data['orderId'])) { // если в заявку
            if (!UserAccess::hasPermissions(54, $this->accountManager->getUserRights($clients->offsetGet($from)))) {
                throw new ChatActionRightsException ('Нет прав на отправку сообщения в заявку');
            }

            // отправим сообщение в ответ сразу
            $connectionDecorator->sendResponse(ConnectionDecorator::ACTION_TYPE_MSG, $this->chatMessage->toArray());

            // ищем кешированный список участников чата заявки
            $ChatOrderUser = ChatOrderUserRepository::getByOrderId($data['orderId']);
            if (is_null($ChatOrderUser)) {
                $ChatOrderUser = new ChatOrderUser();
                $ChatOrderUser->setOrderId($data['orderId']);

                // делаем запрос в сервис заявок
                $response = json_decode($this->apiClient->makeRestRequest('orderService', 'GetOrderManagers', [
                    'orderId' => $data['orderId'],
                    'usertoken' => $this->accountManager->getUserToken($clients->offsetGet($from))
                ]), true);

                if (!empty($response['body'])) {
                    $ChatOrderUser->addUser($response['body']['clientManager']['id']);
                    $ChatOrderUser->addUser($response['body']['managerKMP']['id']);
                    $ChatOrderUser->addUser($response['body']['creator']['id']);
                } else {
                    LogHelper::logExt(
                        __CLASS__, __METHOD__,
                        'Запрос GetOrderManagers', 'Некорректный ответ',
                        [
                            'response' => $response
                        ],
                        LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.errors'
                    );
                    throw new DomainException('GetOrderManagers некорректный ответ');
                }
            }

            $ChatOrderUser->addUser($clients->offsetGet($from));
            $ChatOrderUser->save();

            $orderUserIds = $ChatOrderUser->getUserIds();

            foreach ($orderUserIds as $orderUserId) {
                if ($orderUserId == $clients->offsetGet($from)) {
                    continue;
                }

                $account = AccountRepository::getAccountById($orderUserId);

                if ($account && $account->hasSubscribeToChat()) {
                    $chatMessageAbonent = new ChatMessageAbonent();
                    $chatMessageAbonent->createByUser($account);
                    $chatMessageAbonent->bindChatMessage($this->chatMessage);
                    $chatMessageAbonent->save();

                    $msgSender->addMessageToQueue($chatMessageAbonent);
                }
            }
        } elseif (!empty($data['userId']) && is_numeric($data['userId'])) { // если пользователю личным
            // проверим валиден ли пользователь
            $account = AccountRepository::getAccountById($data['userId']);

            if (is_null($account)) {
                throw new InvalidArgumentException('Не заданы получатели сообщения');
            }

            $chatMessageAbonent = new ChatMessageAbonent();
            $chatMessageAbonent->createByUser($account);
            $chatMessageAbonent->bindChatMessage($this->chatMessage);
            $chatMessageAbonent->save();

            // отправим сообщение в ответ сразу
            $connectionDecorator->sendResponse(ConnectionDecorator::ACTION_TYPE_MSG, $chatMessageAbonent->toArray());

            $msgSender->addMessageToQueue($chatMessageAbonent);
        } elseif (!empty($data['userId']) && is_string($data['userId'])) { // если в группу пользователей
            $config = Yii::app()->getModule('systemService')->getConfig();

            if (!array_key_exists($data['userId'], $config['chat']['userGroups'])) {
                throw new InvalidArgumentException('Группа рассылки не описана');
            }

            // отправим сообщение в ответ сразу
            $connectionDecorator->sendResponse(ConnectionDecorator::ACTION_TYPE_MSG, $this->chatMessage->toArray());

            $groupUserIds = $config['chat']['userGroups'][$data['userId']];

            foreach ($groupUserIds as $groupUserId) {
                if ($groupUserId == $clients->offsetGet($from)) {
                    continue;
                }

                $account = AccountRepository::getAccountById($groupUserId);

                if ($account && $account->hasSubscribeToChat()) {
                    $chatMessageAbonent = new ChatMessageAbonent();
                    $chatMessageAbonent->createByUser($account);
                    $chatMessageAbonent->bindChatMessage($this->chatMessage);
                    $chatMessageAbonent->save();

                    $msgSender->addMessageToQueue($chatMessageAbonent);
                }
            }
        } else {
            throw new InvalidArgumentException('Не заданы получатели сообщения');
        }

        $msgSender->sendMsgs();

        // создадим гирман клиент
        $serviceConfig = Yii::app()->getModule('systemService')->getConfig();
        if (empty($serviceConfig['gearman'])) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Отправка уведомления из чата', 'Не настроен gearman',
                [],
                LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.errors'
            );
            throw new DomainException('Не настроен gearman');
        }

        $GearmanClient = new GearmanClient();
        $GearmanClient->addServer($serviceConfig['gearman']['host'], $serviceConfig['gearman']['port']);

        $usersToSendNotifications = $msgSender->getOfflineUsers();

        // отправим уведомления пользователям оффлайн
        if (count($usersToSendNotifications)) {
            $chatEvent = EventRepository::getChatEvent();
            $chatEvent->setLang('ru');

            // добавим данные для шаблонизации
            $notificationData = [
                'event' => $chatEvent->toArray(),
                'chatMessage' => $this->chatMessage->toArray()
            ];

            foreach ($usersToSendNotifications as $userToSendNotifications) {
                $account = AccountRepository::getAccountById($userToSendNotifications);
                $EventNotificationGroups = NotificationGroupRepository::getNotificationGroups($chatEvent, 'unreadmsg', $account->getCompany());

                if (empty($EventNotificationGroups)) {
                    continue;
                }

                // отправим по группам уведомлений
                foreach ($EventNotificationGroups as $eventNotificationGroup) {
                    // сформируем ss_userNotification
                    $ss_userNotification = [
                        'templateId' => $eventNotificationGroup->getTemplateId(),
                        'types' => [1],
                        'users' => [$account->getUserId()],
                        'createdAt' => StdLib::getMysqlDateTime(),
                        'data' => $notificationData
                    ];

                    // заполнение данными и отправка
                    $GearmanClient->doBackground("{$serviceConfig['gearman']['workerPrefix']}_notification", json_encode($ss_userNotification));
                }
            }
        }

    }
}