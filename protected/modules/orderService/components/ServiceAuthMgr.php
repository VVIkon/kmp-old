<?php

/**
 * Class ServiceAuthMgr
 *
 * Обработчик авторизации услуг
 */
class ServiceAuthMgr
{
    /**
     * @param OrdersServices $service
     * @param AuthRule $rule
     * @param Events $event
     * @return AuthTokenTemp[]|bool
     */
    public function init(OrdersServices $service, AuthRule $rule, Events $event)
    {
        // проверим удовлетворяет ли услуга нашим правилам
        if (!$rule->testService($service)) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Применение правил авторизации', 'Правила не сработало, авторизация не требуется',
                [],
                LogHelper::MESSAGE_TYPE_INFO, 'system.orderservice.info'
            );
            return false;
        }

        $authServiceData = new AuthServiceData();
        $authServiceData->fromAuthRule($rule);
        $authServiceData->bindService($service);
        // reason заполняется названием события в котором выполняется делегат
        $authServiceData->setReason($event->getName());
        // price - текущим значением ценообразователей
        $authServiceData->setPrice($service->getClientPrice()->getBrutto());

        // созраним итерации и правило
        $authServiceData->saveAll();
        $authServiceData->refresh();

        // найдем всех пользователей, которым надо создать токены
        $currentIteration = $authServiceData->getCurrentIteration();
        $usersToCreateToken = $currentIteration->getAllIterationUsers();

        $tokens = [];

        // создадим токен на каждого пользователя
        foreach ($usersToCreateToken as $userToCreateToken) {
            $authTokenTemp = new AuthTokenTemp();
            $authTokenTemp->bindService($service);
            $authTokenTemp->bindAccount($userToCreateToken);
            $authTokenTemp->setExpire($currentIteration->getFinishDateTime());
            $authTokenTemp->generateToken();
            $authTokenTemp->save(false);
            $tokens[] = $authTokenTemp;
        }

        return $tokens;
    }
}