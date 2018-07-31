<?php

/**
 * Репозиторий документа в заявке
 */
class OrderDocumentRepository
{

    /**
     * Ищем документ заявки по его ID
     * @param $documentId
     * @return OrderDocument|null
     */
    public static function getOrderDocumentByDocumentId($documentId)
    {
        return OrderDocument::model()->findByPk($documentId);
    }

    /**
     * @param $objectId
     * @return OrderDocument|null
     */
    public static function getOrderDocumentByObjectId($objectId)
    {
        return OrderDocument::model()->findByAttributes(['objectId' => $objectId]);
    }
}