<?php

/**
 * Class WorkflowDelegatesFactory
 * Реализация фабрики классов обработчиков команд workflow заявки
 */
class WorkflowDelegatesFactory
{
    const DEFAULT_OFFER_CLASS_NAME = 'WorkflowDelegate';

    /** Установка статуса услуги */
    const SET_SERVICE_STATUS_DELEGATE = 1;

    /** Запуск команды OWM */
    const RUN_OWM_COMMAND_DELEGATE = 2;

    /** Отправка информации в УТК по обновлённой заявке */
    const UPDATE_ORDER_IN_UTK = 3;

    /** Получение маршрутной квитанции */
    const GET_ETICKETS = 4;

    /** Создание счёта в КТ */
    const CREATE_INVOICE_DELEGATE = 5;

    /** Отправка запроса на создание счёта в УТК */
    const SEND_REQUEST_INVOICE_TO_UTK_DELEGATE = 6;

    private static $delegatesClasses = [
        self::SET_SERVICE_STATUS_DELEGATE => 'SetServiceStatusDelegate',
        self::RUN_OWM_COMMAND_DELEGATE => 'RunOwmCommandDelegate',
        self::UPDATE_ORDER_IN_UTK => 'UpdateOrderInUtkOLDDelegate',
        self::GET_ETICKETS => 'GetEticketsDelegate',
        self::CREATE_INVOICE_DELEGATE => 'CreateInvoiceDelegate',
        self::SEND_REQUEST_INVOICE_TO_UTK_DELEGATE => 'SendRequestInvoiceToUtkDelegate'
    ];

    /**
     * Создание объекта обработчика в зависимости от указанного типа команды
     * @param $type string тип услуги
     * @return mixed объект предложения
     */
    public static function createDelegate($type) {

        if (!array_key_exists($type, self::$delegatesClasses)) {
            return false;
        }

        return new self::$delegatesClasses[$type];
    }
}