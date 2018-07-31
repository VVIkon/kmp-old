<?php

/**
 * Class ProvidersFactory
 * Реализация фабрики классов поставщиков предложений
 */
class ProvidersFactory
{
    const DEFAULT_OFFER_CLASS_NAME = 'NullProvider';

    const GPTS_PROVIDER = 'engineGPTS';
    const GPTS_PROVIDER_ID = '5';

    private static $offerProviders = [
        self::GPTS_PROVIDER => [
            'class' => 'GptsSearch',
            'id'    => self::GPTS_PROVIDER_ID
        ]

    ];

    private static $offerProvidersById = [
        self::GPTS_PROVIDER_ID => [
            'class' => 'GptsSearch',
            'id'    => self::GPTS_PROVIDER_ID
        ]

    ];

    /**
     * Создание объекта провайдера по его типу
     * @param $gateId string тип услуги
     * @return GptsSearch|bool
     */
    public static function createOfferProviderByGateId($gateId)
    {
        if (!array_key_exists($gateId, self::$offerProvidersById)) {
            return false;
        }

        return new self::$offerProvidersById[$gateId]['class']();
    }

    /**
     * Создание объекта провайдера по его типу
     * @param $type string тип услуги
     * @return GptsSearch|bool
     */
    public static function createOfferProvider($type)
    {
        if (!array_key_exists($type, self::$offerProviders)) {

            return false;
        }

        return new self::$offerProviders[$type]['class']();
    }

    /**
     * Получить тип провайдера предложений по названию класса
     * @param $className
     * @return mixed
     */
    public static function getOfferProviderTypeByClassName($className)
    {
        $info = self::getOfferProviderInfoByClassName($className);

        return isset($info['type']) ? $info['type'] : false;
    }

    /**
     * Получить идентифкатор провайдера предложений по названию класса
     * @param $className
     * @return mixed
     */
    public static function getOfferProviderIdByClassName($className)
    {
        $info = self::getOfferProviderInfoByClassName($className);

        return isset($info['id']) ? $info['id'] : false;
    }

    /**
     * Получить данные провайдера предложений по названию класса
     * @param $className
     * @return mixed
     */
    private static function getOfferProviderInfoByClassName($className)
    {
        foreach (self::$offerProviders as $providerType => $provider) {
            if ($provider['class'] == $className) {
                return [
                    'id' => $provider['id'],
                    'type' => $providerType,
                    'class' => $provider['class']
                ];
            }
        }

        return false;
    }


    /**
     * Проверить наличие указанного провайдера
     * @param $providerId
     * @return bool
     */
    public static function isProviderExists($providerType)
    {
        if (empty($providerId)) {
            return false;
        }

        return array_key_exists($providerType, self::$offerProviders);
    }
}