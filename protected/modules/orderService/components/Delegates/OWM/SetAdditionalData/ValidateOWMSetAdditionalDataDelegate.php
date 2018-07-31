<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 28.03.17
 * Time: 18:32
 */
class ValidateOWMSetAdditionalDataDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (empty($params['additionalFields']) || !count($params['additionalFields'])) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return;
        }

        foreach ($params['additionalFields'] as $additionalField) {
            if(!isset($additionalField['fieldTypeId'])){
                $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
                return;
            }
//            if(!(isset($additionalField['orderId']) || isset($additionalField['serviceId']))){
//LogHelper::logExt(get_class($this), __METHOD__, '----------Circle.1', '', ['Stage'=>5, '$additionalField'=>$additionalField ], 'info', 'system.searcherservice.info');
//                $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
//                return;
//            }

            $fieldType = AdditionalFieldTypeRepository::getById($additionalField['fieldTypeId']);
            if(is_null($fieldType)){
                $this->setError(OrdersErrors::ADD_FIELD_NOT_FOUND);
                return;
            }
        }
    }
}