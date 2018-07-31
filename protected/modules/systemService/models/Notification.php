<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 19.04.17
 * Time: 17:11
 */
class Notification
{
    const GATE_EMAIL = 1;
    const GATE_SMS = 2;
    const GATE_CHAT = 3;
    const GATE_FILE = 4;

    protected $templatesBasePath = '';

    public function __construct()
    {
        $config = Yii::app()->getModule('systemService')->getConfig();

        if (empty($config['templatesBasePath'])) {
            throw new NotificationException('Не указана базовая директория шаблонов (templatesBasePath)');
        }

        $this->templatesBasePath = $config['templatesBasePath'];
    }

    public function run($params)
    {
        $gates = [];
        // фабрика шлюза
        foreach ($params['types'] as $type) {
            $gate = $this->gateFactory($type);
            $gates[] = $gate;
        }

        // получатели
        $accounts = AccountRepository::getAccountByIds($params['users']);

        // шаблонизатор
        $tplEngine = new Mustache_Engine();

        // шаблон
        $notificationTemplate = NotificationTemplateRepository::getByTemplateId($params['templateId']);
        $notificationTemplate->setBasePath($this->templatesBasePath);

        // если есть прикрепления к письму, то передадим в шлюз
        $attachments = (isset($params['data']['attachments'])) ? $params['data']['attachments'] : [];

        // отправим уведомление в каждый шлюз
        foreach ($gates as $gate) {
            // найдем шаблон
            $notificationTemplate->setChannel($gate->getType());

            // переберем пользователей, кому надо отправить уведомление
            foreach ($accounts as $account) {
                $notificationTemplate->setLang($account->getLanguage());

                $bodyTpl = $notificationTemplate->getBodyTemplate();
                $subjectTpl = $notificationTemplate->getSubjectTemplate();

                // шаблонизатор
                $text = $tplEngine->render($bodyTpl, $params['data']);
                $subject = $tplEngine->render($subjectTpl, $params['data']);

                // отправим в шлюз данные для отправки
                $gate->perform($account, $subject, $text, $attachments);
            }

            // если указан доп адрес для отправки письма и Gate - email
            if (!empty($params['address']) && $gate instanceof NotificationGateEmail) {
                $notificationTemplate->setLang('ru');

                $bodyTpl = $notificationTemplate->getBodyTemplate();
                $subjectTpl = $notificationTemplate->getSubjectTemplate();

                // шаблонизатор
                $text = $tplEngine->render($bodyTpl, $params['data']);
                $subject = $tplEngine->render($subjectTpl, $params['data']);

                $gate->perform(null, $subject, $text, $attachments, $params['address']);
            }
        }
    }

    /**
     * @param $templateId
     * @return ImplodeTplEngine|Mustache_Engine
     */
//    protected function tplEngineFactory($templateId)
//    {
//        if (is_null($templateId)) {
//            return new ImplodeTplEngine();
//        } else {
//            return new Mustache_Engine();
//        }
//    }

    /**
     * @param $gate
     * @return AbstractNotificationGate
     * @throws NotificationException
     */
    protected function gateFactory($gate)
    {
        switch ($gate) {
            case self::GATE_EMAIL:
                return new NotificationGateEmail();
            case self::GATE_SMS:
                return new NotificationGateSMS();
            case self::GATE_CHAT:
                return new NotificationGateChat();
            case self::GATE_FILE:
                return new NotificationGateFile();
            default:
                throw new NotificationException("Неизвестный шлюз: $gate");
        }
    }
}