<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/13/16
 * Time: 4:57 PM
 */
class ValidateSWMBookCompleteDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    public function run(array $params)
    {
        if(isset($params['bookData'])){
            $BookData = new BookData();
            $BookData->fromArray($params);
            $this->setObjectToContext($BookData);
        } else {
            $this->setError(OrdersErrors::BOOK_DATA_NOT_SET);
            return null;
        }
    }

}