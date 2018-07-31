<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/26/16
 * Time: 4:03 PM
 */
class DictionaryController extends RestController
{
    /**
     * Команда возвращает информацию по указанному отелю
     */
    public function actionGetHotelInfo()
    {
        $params = $this->_getRequestParams();

        if (!isset($params['hotelId'])) {
            $this->_sendResponseWithErrorCode(SupplierErrors::HOTEL_ID_NOT_SET);
        }

        if (!isset($params['lang'])) {
            $this->_sendResponseWithErrorCode(SupplierErrors::LANG_NOT_SET);
        }

        $HotelInfo = HotelInfoRepository::getHotelById($params['hotelId']);

        if (is_null($HotelInfo)) {
            $this->_sendResponseWithErrorCode(SupplierErrors::HOTEL_NOT_FOUND);
        }

        $HotelInfo->setLang($params['lang']);
        $this->_sendResponseData($HotelInfo->toSSHotelInfo());
    }

    /**
    * Создание отеля по параметрам, переданным в поиске
    */
    public function actionCreateHotelFromSearch() {
        $params = $this->_getRequestParams();

        if (!isset($params['hotelInfo']) || !isset($params['gptsMainCityId'])) {
            return false;
        }
        

        $dictionaryModule = Yii::app()->getModule('supplierService')->getModule('Dictionaries');
        $HotelsDictionary = $dictionaryModule->useDictionary($dictionaryModule::HOTELS_DICTIONARY);
        $response = $HotelsDictionary->createHotelFromSearch($params['hotelInfo'], $params['gptsMainCityId']);

        if (is_null($response)) {
            $this->_sendResponseWithErrorCode(SupplierErrors::CANNOT_CREATE_HOTEL);
        } else {
            $this->_sendResponseData([
                'hotelId' => $response
            ]);
        }


    }
}