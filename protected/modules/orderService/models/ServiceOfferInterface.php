<?php

/**
 * Интерфейс для работы с оффером услуги
 */
interface ServiceOfferInterface
{
    /**
     * Формирование названия услуги
     * @return mixed
     */
    public function generateServiceName();

    /**
     * Сохранение данных брони
     * @param array $bookData
     */
    public function setBookData(array $bookData);

    /**
     * Получение данных для бронирования
     * @return mixed
     */
    public function getOfferDataForBooking();

    /**
     * Получение ID оффера
     * @return mixed
     */
    public function getOfferId();

    /**
     * Получение ключа оффера
     * @return mixed
     */
    public function getOfferKey();

    /**
     * Добавление информации по штрафу в оффер
     * @param array $cancelPenalty
     * @return mixed
     */
    public function addCancelPenalty(array $cancelPenalty);

    /**
     * Получение плановых штрафов
     * @return AbstractCancelPenalty[]
     */
    public function getCancelPenalties();

    /**
     * проверка возможности отмены бронирования
     * @return mixed
     */
    public function hasCancelAbility();

    /**
     * проверка возможности отмены бронирования
     * @return mixed
     */
    public function hasModifyAbility();

    /**
     * Получение данных бронирования
     * @return array
     */
    public function getBookData();

    /**
     * Возвращает ценовые показатели оффера
     * @return SalesTermsInfo
     */
    public function getSalesTerms();

    /**
     * Получение данных шлюза из оффера
     * @return mixed
     */
    public function getEngineData();

    /**
     * Возвращает объект поставщика
     * @return RefSuppliers|bool
     */
    public function getSupplier();

    public function setDateFrom($dateFrom);
    public function setDateTo($dateTo);

    public function getDateFrom();
    public function getDateTo();

    /**
     * @return integer
     */
    public function getServiceType();

    public function getCityId();

    public function getCountryId();

    /**
     * @return AbstractPriceOffer []
     */
    public function getPriceOffers();

    /**
     * @param Tourist [] $Tourists
     * @param bool $strict
     * @return (bool)
     */
    public function checkTouristAges($Tourists, $strict = false);

    /**
     * Создание ваучера или билета из документа заявки
     * @param OrderDocument $OrderDocument
     * @return mixed
     */
    public function addVoucher(OrderDocument $OrderDocument);

    /**
     * Сохранение данных резервации
     * при ручном режиме
     * @param $reservationData
     * @return mixed
     */
    public function setReservationData($reservationData);

    /**
     * Возвращает номер брони
     *
     * Отель - номер брони
     * Авиа - PNR
     *
     * @return string
     */
    public function getReservationNumber();

    /**
     * Получение экстра данных из оффера для отправки в УТК
     * @return array
     */
    public function getUtkServiceDetails();

    /**
     * Создание из массива
     * @param $offerData array
     * @return mixed
     */
    public function fromArray(array $offerData);

    /**
     * @return AbstractTravelPolicyValue
     */
    public function getOfferValue();

    /**
     * @param $field
     * @param $value
     * @return AbstractTravelPolicyValue
     */
    public function addTravelPolicyValue($field, $value);

    /**
     * Очистка всех плановых штрафов в оффере
     * @return mixed
     */
    public function clearCancelPenalties();


    /**
     * Оффер может содержать доп услуги
     * @return bool
     */
    public function supportsAdditionalServices();


    /**
     * В оффере имеются доп услуги
     * @return bool
     */
    public function hasAddOffers();

    /**
     * Получение возможных доп услуг из оффера
     * @return HotelAddOffer[]
     */
    public function getAddOffers();


    /**
     * Получение активного штрафа
     * @return AbstractCancelPenalty|null
     */
    public function getActiveCancelPenalty();

    /**
     * Обновление цены в оффере
     * @param $type string client|supplier
     * @param $salesTerm
     * @return mixed
     */
    public function updatePrices($type, $salesTerm);

     /**
     * Возможность повторного бронирования услуги
     * Например при наличии PNR или номера брони услугу повторно забронировать нельзя
     * @return bool
     */
    public function canBeBooked();


    public function save($validate = false);


    /**
     * Таймлимит на бронирование услуги
     * @return mixed
     */
    public function getTimeLimitBookingDate();

}