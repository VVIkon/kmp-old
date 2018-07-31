<?php

use Symfony\Component\Validator\Validation;

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/15/16
 * Time: 11:46 AM
 */
class AviaTourist extends AbstractServiceTourist
{

    /**
     * @param Tourist $Tourist
     * @param AbstractDocument $TouristDocument
     * @param OrdersServicesTourists $OrdersServicesTourist
     * @return mixed
     */
    public function getTouristData(Tourist $Tourist, AbstractDocument $TouristDocument, $OrdersServicesTourist = null)
    {
        $citizenship = CountriesMapperHelper::getCountryIdMatched(
            CountriesMapperHelper::GPTS_SUPPLIER_ID,
            $TouristDocument->getCitizenship()
        );

        if (empty($TouristDocument->getCitizenship()) || empty($citizenship)) {
            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::INCORRECT_TOURIST_CITIZENSHIP,
                ['touristInfo' => '']
            );
        }

        $bonusCardDescription = [
            'id' => null,
            'cardNumber' => null,
            'airLine' => ''
        ];

        if ($loyaltyProgram = $OrdersServicesTourist->getLoyaltyProgram()) {
            $bonusCardDescription['id'] = $loyaltyProgram->getLoyaltyProgramId();
            $bonusCardDescription['cardNumber'] = $OrdersServicesTourist->getMileCard();
            $bonusCardDescription['airLine'] = $loyaltyProgram->getCarrierAlliance()->getCarrierIATA();
        }

        return [
            'id' => $OrdersServicesTourist->getTouristID(),
            'citizenshipId' => CountriesMapperHelper::getCountryIdMatched(
                CountriesMapperHelper::GPTS_SUPPLIER_ID,
                $TouristDocument->getCitizenship()
            ),
            'email' => $Tourist->getEmail(),
            'phone' => $Tourist->getPhone(),
            'passport' => [
                'id' => $TouristDocument->getTouristIDdoc(),
                'number' => $TouristDocument->getDocSerial() . $TouristDocument->getDocNumber(),
                'issueDate' => $TouristDocument->getDocValidFrom(),
                'expiryDate' => $TouristDocument->getDocValidUntil()->format('Y-m-d')
            ],
            'sex' => $Tourist->getSex(),
            'lastName' => $TouristDocument->getSurname(),
            'firstName' => $TouristDocument->getName(),
            'birthdate' => $Tourist->getBirthdate(),
            'bonusCard' => $bonusCardDescription
        ];
    }

    /**
     * Страны сегментов
     * @param $this ->Offer
     * @returm array
     */
    public function setCountryService() // Страна(ы) в которой осуществляется услуга
    {
        $offer = $this->Offer;
        if (isset($offer)) {
            $segments = StdLib::nvl($offer->getAviaOfferSegments(), []);
            foreach ($segments as $segment) {
                $countryID = $segment->getArrivalAirport()->getCountryId();
                $this->countryService[] = $countryID;
            }
        }
    }


    public function validate()
    {
        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadAviaValidatorMetadata')
            ->getValidator();

        $violations = $validator->validate($this->Tourist);
        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                throw new ServiceTouristException($violation->getMessage());
            }
        }

        $violations = $validator->validate($this->TouristDocument);
        if (count($violations) > 0) {
            foreach ($violations as $violation) {

                throw new ServiceTouristException($violation->getMessage());
            }
        }
    }
}