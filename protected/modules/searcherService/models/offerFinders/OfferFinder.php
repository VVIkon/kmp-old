<?php

/**
 * Class OfferFinder
 * Базовый класс для поиска предложения
 */
class OfferFinder extends KFormModel
{
    /**
     * namespace для записи логов
     * @var string
     */
    protected $namespace;

    /**
     * Используется для хранения ссылки на текущий модуль
     * @var object
     */
    protected $module;

    /**
     * Токен для идентификации поискового запроса
     * @var string
     */
    protected $requestToken;

    /**
     * Признак новой задачи поиска
     * @var bool
     */
    protected $isNewSearchTask;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        parent::__construct();
        $this->module = $module;
        $this->namespace = $this->module->getConfig('log_namespace');

        $this->isNewSearchTask = false;
    }

    /**
     * Сохранить описательную часть задания поиска
     * @param $params
     * @return bool
     */
    public function makeSearchRequestTask($params)
    {
        $this->isNewSearchTask = true;

        $command = Yii::app()->db->createCommand();

        try {

            $res = $command->insert('token_cache', [
                'token' => $params['token'],
                'ServiceID' => $params['offerType'],
                'StartDateTime' => (new DateTime())->format('Y-m-d H:i:s'),
                'success' => 0
            ]);

        } catch (Exception $e) {
            throw new KmpDbException(
                get_class($this),
                __FUNCTION__,
                SearcherErrors::CANNOT_WRITE_TO_CACHE_SEARCH_TASK,
                $command->getText(),
                $e
            );
        }
        return true;
    }

    /**
     * Сохранить описательную часть задания поиска
     * @param $token токен поиска
     * @param $percent часть завершенного поиска
     * @return bool
     */
    public static function setSearchRequestTaskPercentComplete($token, $percent)
    {
        $command = Yii::app()->db->createCommand();

        try {
          $sql = 'update token_cache set success = if(success + :pr > 100,100,success + :percent) where token = :token';
          $res = Yii::app()->db->createCommand($sql)->execute([
            ':pr' => $percent,
            ':percent' => $percent,
            ':token' => $token
          ]);
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_WRITE_TO_CACHE_SEARCH_TASK,
                $command->getText(),
                $e
            );
        }
        return true;
    }

    /**
     * Получение текущего процента поиска
     * @param string $token Токен поиска
     * @return int Процент поиска
     */
    public static function getSearchTaskPercentComplete($token)
    {
        $command = Yii::app()->db->createCommand();

        try {
            $command->select('success')
                ->from('token_cache')
                ->where('token = :token', [':token' => $token]);

            return $command->queryScalar();

        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_WRITE_TO_CACHE_SEARCH_TASK,
                $command->getText(),
                $e
            );

            return 0;
        }
    }

    /**
     * Сохранить описательную часть задания поиска
     * @param $token
     * @param $percent
     * @return bool
     */
    public static function getSearchRequestTaskPercentComplete($token)
    {
        $command = Yii::app()->db->createCommand();

        $command->select('success');
        $command->from('token_cache');
        $command->where('token = :token', [':token' => $token]);

        try {
            $percent = $command->queryRow();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_SUCCESS_VALUE_FROM_CACHE_SEARCH_TASK,
                $command->getText(),
                $e
            );
        }

        return $percent['success'];
    }

    /**
     * Формирование нового поискового токена
     */
    protected function generateSearchToken()
    {
        $this->requestToken = TokenHelper::generateToken()['token'];
    }

    /**
     * Получение токена существующего запроса
     * @param $request
     * @return bool
     */
    protected function getExistedRequestToken($request)
    {
        return false;
    }

    /**
     * Получить тип предложения по указанному токену
     * @param $token
     * @return CDbDataReader|mixed
     */
    public static function getOfferTypeByToken($token)
    {
        $command = Yii::app()->db->createCommand();
        $command->select('ServiceID as type');
        $command->from('token_cache');
        $command->where('token = :token', [':token' => $token]);

        $typeInfo = $command->queryRow();

        return $typeInfo['type'];
    }

    /**
     * Проверка необходимости запуска задачи
     * @param $token
     */
    public function isLastRequestNew()
    {
        return $this->isNewSearchTask;
    }

}
