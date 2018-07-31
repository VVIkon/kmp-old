<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/13/16
 * Time: 11:21 AM
 */
abstract class AbstractServiceBookingPoll
{
    /**
     * @var GPTSApiClient
     */
    protected $apiClient;

    public function init(GPTSApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Опрос шлюза на предмет завершения брониварония
     * @param array $params
     * @return BookData|null|false
     */
    abstract public function bookingPoll(array $params);
}