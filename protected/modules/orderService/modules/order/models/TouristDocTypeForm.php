<?php

/**
 * Class TouristDocTypeForm
 * Реализует функциональность для работы с типами документов туриста
 */
class TouristDocTypeForm extends KFormModel
{
    const DOC_TYPE_RUSSIAN_LOCAL_PASSPORT = 1; //Паспорт гражданина РФ
    const DOC_TYPE_RUSSIAN_INTERNATIONAL_PASSPORT = 2; //Загран паспорт гражданина РФ
    const DOC_TYPE_USSR_LOCAL_PASSPORT = 3; //Паспорт гражданина СССР
    const DOC_TYPE_USSR_INTERNATIONAL_PASSPORT = 4; //Загран паспорт гражданина СССР
    const DOC_TYPE_OFFICER_IDENTIFICATION = 5; //Удостоверение личности офицера
    const DOC_TYPE_MILITARY_IDENTIFICATION = 6; //Военный билет солдата (матроса, сержанта, старшины).
    const DOC_TYPE_MILITARY_IDENTIFICATION_OFFICER = 7; //Военный билет офицера
    const DOC_TYPE_MINMORFLOT_PASSPORT = 8; //Паспорт Минморфлота
    const DOC_TYPE_SEAMAN_PASSPORT = 9; //Паспорт моряка
    const DOC_TYPE_BIRTH_CERTIFICATE = 10; //Свидетельство о рождении.
    const DOC_TYPE_RUSSIAN_DIPLOMATIC_PASSPORT = 11; //Дипломатический паспорт гражданина РФ
    const DOC_TYPE_TEMPORARY_IDENTITY_CARD = 12; //Временное удостоверение личности гражданина РФ
    const DOC_TYPE_RUSSIAN_REFUGEE_CERTIFICATE = 13; //Удостоверение беженца в РФ
    const DOC_TYPE_RESIDENCE = 14; //Вид на жительство
    const DOC_TYPE_CERTIFICATE_REGISTRATION_IMMIGRANT_AS_REFUGEE = 15; //Свидетельство о регистрации ходатайства
    // о признании иммигранта беженцем
    const DOC_TYPE_FOREIGN_PASSPORT = 16; //Иностранный паспорт
    const DOC_TYPE_CERTIFICATE_OF_RELEASE = 17; //Справка об освобождении

    public static $validDocTypes = [
        self::DOC_TYPE_RUSSIAN_LOCAL_PASSPORT,
        self::DOC_TYPE_RUSSIAN_INTERNATIONAL_PASSPORT,
        self::DOC_TYPE_BIRTH_CERTIFICATE,
        self::DOC_TYPE_MILITARY_IDENTIFICATION,
        self::DOC_TYPE_MILITARY_IDENTIFICATION_OFFICER,
        self::DOC_TYPE_SEAMAN_PASSPORT
    ];

    /**
     * Конструктор объекта
     * @param array $values
     */
    public function __construct()
    {
    }

    /**
     * Declares the validation rules.
     * The rules state that username and password are required,
     * and password needs to be authenticated.
     */
    public function rules()
    {
        return [
            ['', 'safe']
        ];
    }

    /**
     * Получение типа документа туриста
     * @param $docTypeId
     * @return CDbDataReader|mixed
     */
    public static function GetDocType($docTypeId)
    {

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_tourists_doc_type doctype')
            ->where('doctype.DocTypeID = :docTypeId', array(':docTypeId' => $docTypeId));

        return $command->queryRow();
    }

    /**
     * Получить количество цифр в серии документа
     * @param $docType int тип документа
     * @return int
     */
    public static function getDocSerialLength($docType)
    {
        $length = 0;
        switch ($docType) {
            case self::DOC_TYPE_RUSSIAN_LOCAL_PASSPORT :
                $length = 4;
                break;
            case self::DOC_TYPE_RUSSIAN_INTERNATIONAL_PASSPORT :
                $length = 2;
                break;
            case self::DOC_TYPE_BIRTH_CERTIFICATE :
                $length = 4;
                break;
            case self::DOC_TYPE_MILITARY_IDENTIFICATION :
                $length = 2;
                break;
            case self::DOC_TYPE_MILITARY_IDENTIFICATION_OFFICER :
                $length = 2;
                break;
            case self::DOC_TYPE_SEAMAN_PASSPORT :
                $length = 2;
                break;
            default :
                $length = 42;
                break;
        }
        return $length;
    }

    /**
     * Получить количество цифр в номере документа
     * @param $docType int тип документа
     * @return int
     */
    public static function getDocNumberLength($docType)
    {
        $length = 0;
        switch ($docType) {
            case self::DOC_TYPE_RUSSIAN_LOCAL_PASSPORT :
                $length = 6;
                break;
            case self::DOC_TYPE_RUSSIAN_INTERNATIONAL_PASSPORT :
                $length = 7;
                break;
            case self::DOC_TYPE_BIRTH_CERTIFICATE :
                $length = 6;
                break;
            case self::DOC_TYPE_MILITARY_IDENTIFICATION :
                $length = 7;
                break;
            case self::DOC_TYPE_MILITARY_IDENTIFICATION_OFFICER :
                $length = 7;
                break;
            case self::DOC_TYPE_SEAMAN_PASSPORT :
                $length = 7;
                break;
            default :
                $length = 42;
                break;
        }

        return $length;
    }

    public static function isDocTypeValid($docType)
    {
        return in_array($docType, self::$validDocTypes);
    }
}