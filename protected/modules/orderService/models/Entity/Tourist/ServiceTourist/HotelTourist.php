<?php

use Symfony\Component\Validator\Validation;

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/31/16
 * Time: 6:13 PM
 */
class HotelTourist extends AbstractServiceTourist
{
    //public $countryService = []; // Страна размещения

    public function getTouristData(Tourist $Tourist, AbstractDocument $TouristDocument)
    {
        $citizenship = CountriesMapperHelper::getCountryIdMatched(
            CountriesMapperHelper::GPTS_SUPPLIER_ID,
            $TouristDocument->getCitizenship()
        );

        if (!$citizenship) {
            return OrdersErrors::INCORRECT_TOURIST_CITIZENSHIP;
        }

        $age = date('Y') - DateTime::createFromFormat('Y-m-d', $Tourist->getBirthdate())->format('Y');

        return [
//            'id' => $Tourist->getTouristIDbase(),         // (необязательный) Идентификатор туриста (для корпоратора)
//            'onExtrabed' => false,                        // (пока не используется) Признак размещения туриста на доп. месте
//            'onWithoutPlace' => false,                    // (пока не используется) Признак размещения туриста без предоставления доп.места
            'citizenshipId' => $citizenship,                // Идентификатор страны туриста (гражданство) (в терминах КТ)
            'lastName' => $TouristDocument->getSurname(),   // Фамилия
            'firstName' => $TouristDocument->getName(),     // Имя
            'birthdate' => $Tourist->getBirthdate(),                // Др
            'prefix' => ($Tourist->getSex() == 0) ? 'Ms' : 'Mr',
            'type' => ($age > 17) ? 'adult' : 'child',
            'personId' => '',
        ];
    }
    /**
    * // Страна размещения
    * @param
    * @returm
    */
    public function setCountryService()
    {
        $offer = $this->Offer;
        if (isset($offer)) {
            $this->countryService[] = StdLib::nvl($offer->getCountryId());
        }
    }

    /**
     * @return mixed
     */
    public function validate()
    {
        // валидация параметров
        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadAccomodationValidatorMetadata')
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