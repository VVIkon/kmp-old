<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/4/16
 * Time: 3:47 PM
 */
abstract class AbstractServiceBookData
{
    protected $bookData;

    public function fromArray($bookData)
    {
        $this->bookData = $bookData;
    }

    abstract public function getGPTSServiceRef();
    abstract public function getGPTSOrderRef();
    abstract public function getGPTSProcessID();
}