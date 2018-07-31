<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/31/16
 * Time: 6:13 PM
 */
abstract class AbstractServiceTourist
{
    protected $Tourist;
    protected $TouristDocument;
    protected $Offer;
    public $yearsOld;
    public $countryService;

    abstract public function getTouristData(Tourist $Tourist, AbstractDocument $TouristDocument);
    abstract public function validate();
    abstract public function setCountryService(); // Страна в которой осуществляется услуга

    public function setTourist(Tourist $Tourist)
    {
        $this->Tourist = $Tourist;

        $today = new DateTime("now");
        $birthday = new DateTime($this->Tourist->getBirthdate());
        $interval = $today->diff($birthday);
        $this->yearsOld = $interval->y;
    }

    public function setTouristDocument(AbstractDocument $TouristDocument)
    {
        $this->TouristDocument = $TouristDocument;
        $this->setCountryService();
        $this->TouristDocument->touristYearsOld = $this->yearsOld;
        $this->TouristDocument->countryService = $this->countryService;
    }

    public function setOffer(ServiceOfferInterface $offer)
    {
        $this->Offer = $offer;
    }
}