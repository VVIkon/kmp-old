<?php

/**
 * Делегат для записи в БД данных об аудите заявки
 * User: rock
 * Date: 8/1/16
 * Time: 4:21 PM
 */
class AuditDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_POST_ACTION;

    public function run(array $params)
    {
        $auditData = $this->getOrderAuditArr();

        // проверим нужно ли что-то делать
        if ($auditData) {
            // если нашли структуру - запишем ее в БД
            foreach ($auditData as $ORDERAUDIT) {
                $OrderHistory = new OrderHistory();
                $OrderHistory->fromArray($ORDERAUDIT);
                $OrderHistory->save(false);
            }
        }
    }
}