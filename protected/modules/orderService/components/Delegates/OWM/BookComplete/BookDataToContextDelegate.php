<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/14/16
 * Time: 5:19 PM
 */
class BookDataToContextDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
//        if (isset($params['BookData'])) {
            $BookData = new BookData();
            $BookData->fromArray($params);

            $this->setObjectToContext($BookData);
//        } else {
//            $this->setError(OrdersErrors::BOOK_DATA_NOT_FOUND);
//            return null;
//        }
    }

}