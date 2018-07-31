<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 19.04.17
 * Time: 17:26
 */
class NotificationGateEmail extends AbstractNotificationGate
{
    protected $type = 'email';

    /**
     * @inheritdoc
     */
    public function perform($account, $subject, $text, $attachments, $email = null)
    {
        // найдем настройки smtp
        $config = Yii::app()->getParams()->toArray();
        if (empty($config['smtp']) || empty($config['emailFrom'])) {
            throw new NotificationException('smtp настройки или email отправителя (emailFrom) не задан');
        }
        $transport = Swift_SmtpTransport::newInstance($config['smtp']['host'], $config['smtp']['port']);
        $transport->setUsername($config['smtp']['username']);
        $transport->setPassword($config['smtp']['password']);

        $mailer = Swift_Mailer::newInstance(Swift_SmtpTransport::newInstance());

        // получатели письма
        $emails = [];
        if ($email) {
            $emails[] = $email;
        } elseif ($account->getEmail()) {
            $emails[$account->getEmail()] = (string)$account;
        } else {
            LogHelper::logExt(
                __CLASS__,
                __METHOD__,
                'Отправка уведомления',
                'Не задан email пользователя',
                [
                    'accountId' => $account->getUserId(),
                    'FIO' => (string)$account
                ],
                'error',
                'system.systemservice.errors'
            );
            return;
        }

        $message = Swift_Message::newInstance();

        // замена схемы встраивания изображений с dara:uri на cid для совместимости с outlook
        $mailbody = preg_replace_callback(
            '/src="data:image\/([^;]+);base64,([^"]+)"/u',
            function ($matches) use (&$message) {
                $filedata = base64_decode($matches[2]);
                $filename = uniqid() . '.' . $matches[1];
                $filemime = 'image/' . $matches[1];
                $cid = $message->embed(new Swift_Image($filedata, $filename, $filemime));
                return 'src="' . $cid . '"';
            },
            $text
        );
        try {
            $message->setSubject($subject)
                ->setTo($emails)
                ->setFrom([$config['emailFrom']])
                ->setReplyTo([$config['emailFrom']])
                ->setBody($mailbody, 'text/html');
        } catch (Swift_RfcComplianceException $e) {
            LogHelper::logExt(
                __CLASS__,
                __METHOD__,
                'Отправка уведомления',
                'Обнаружен невалидный email',
                [
                    'email' => $emails
                ],
                'error',
                'system.systemservice.errors'
            );
            return;
        }

        foreach ($attachments as $attachment) {
            $message->attach(Swift_Attachment::fromPath($attachment['url']));
        }

        LogHelper::logExt(
            __CLASS__,
            __METHOD__,
            'Отправка уведомления',
            '',
            [
                'subject' => $subject,
                'to' => implode(', ', $emails)
            ],
            'info',
            'system.systemservice.info'
        );

        try {
            $mailer->send($message);
        } catch (Swift_SwiftException $e) {
            LogHelper::logExt(
                __CLASS__,
                __METHOD__,
                'Отправка уведомления',
                'Не удалось отправить Email',
                [
                    'subject' => $subject,
                    'to' => implode(', ', $emails),
                    'e'=>$e->getMessage()
                ],
                'error',
                'system.systemservice.errors'
            );
        }
    }
}