<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 20.06.17
 * Time: 9:10
 */
class ScheduleCacheRepository
{

    /** Запрос кеша по параметрам
     * @param $params
     *  $params['supplierCode'] - необязательный
     *  $params['airlineCode']  - необязательный ('SU&airlineCodes=S7')
     *  $params['route']  = "LED-MOW,2017-09-01,P30D"
     * @return mixed
     */
    public static function getScheduleDataByParam($params)
    {
        $schedule = ScheduleCache::model()->findByAttributes($params);
        if (is_null($schedule)){
            return false;
        }
        return json_decode(StdLib::nvl($schedule->getScheduleData(),'{}'), true);
    }

    /**
     * Сохранение параметов запроса в кеш
     * Запуск: php protected/yiic CacheClear Clear
     * @param $requestParams
     * @param $actualData
     * @param $arrSchedule
     * @return bool
     */
    public static function setCacheScheduleByParam($requestParams, $actualData, $arrSchedule)
    {
        $scheduleCache = new ScheduleCache();
        $scheduleCache->supplierCode = StdLib::nvl($requestParams['supplierId']);
        $scheduleCache->route = StdLib::nvl($requestParams['routes']);
        $scheduleCache->airlineCode = StdLib::nvl($requestParams['airlineCodes']);
        $scheduleCache->actualDate = StdLib::nvl($actualData);
        $scheduleCache->scheduleData = json_encode( StdLib::nvl($arrSchedule,[]) );
        return $scheduleCache->save(false);
    }

    /**
     * Очистка кеша расписаний
     * @param $expireSec
     * @return int
     */
    public static function delExpireScheduleCache($expireSec)
    {
        $DateTime = new DateTime();
        $DateTime->setTimestamp(time() - $expireSec);
        $expireDate = $DateTime->format('Y-m-d H:i:s');
        $command = Yii::app()->db->createCommand();
        return $command->delete('kt_schedule', 'lastUpdate<:expireDate', [':expireDate'=>$expireDate]);
    }


}