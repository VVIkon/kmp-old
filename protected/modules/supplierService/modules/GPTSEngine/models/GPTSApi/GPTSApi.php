<?php

/** Базовый класс GPTS Api */
abstract class GPTSApi
{
    /** @var GPTSApiClient ссылка на клиент запросов к GPTS API */
    protected $apiClient;

    public function __construct(&$apiClient)
    {
        $this->apiClient = $apiClient;
    }
}