<?php

class AuthorizationPostActionDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_POST_ACTION;

    public function run(array $params)
    {
        $service = new OrdersServices();
        $service->unserialize($params['object']);

        // проверим создалась ли услуга
        if (!$service->getOrderModel()) {
            return;
        }

        // проверим есть у услуги незавершенная авторизация
        if (AuthServiceRepository::countNotCompletedServiceAuth()) {
            $this->addLog('Есть незавершенная авторизация, новую не создаем');
            return;
        }

        $authRules = AuthRuleRepository::getForService($service);

        // правил не нашлось, выходим
        if (!count($authRules)) {
            $this->addLog('Не нашлось правил авторизации, авторизация не нужна');
            return;
        }

        // инициализация авторизации услуги
        $serviceAuthMgr = new ServiceAuthMgr();

        // если успешно создали правило, остается разослать письма и создать токены
        if ($authTokens = $serviceAuthMgr->init($service, $authRules[0], $this->Event)) {
            $this->addLog('Успешная авторизация, рассылаем токены');

            foreach ($authTokens as $authToken) {
                $this->addNotificationTemplate('authorization', [
                    'token' => $authToken->getToken()
                ]);
            }
        }
    }
}