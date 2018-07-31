<?php

/**
 * Модель туриста для модификаци брони
 */
class ModifyTouristRQ
{
    protected $prefix;
    protected $firstName;
    protected $lastName;

    public function init(array $params)
    {
        if (isset($params['firstName']) && isset($params['lastName']) && isset($params['maleFemale'])) {
            $this->firstName = $params['firstName'];
            $this->lastName = $params['lastName'];
            $this->prefix = ($params['maleFemale'] == 0) ? 'Ms' : 'Mr';

            return true;
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'prefix' => $this->prefix,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName
        ];
    }
}