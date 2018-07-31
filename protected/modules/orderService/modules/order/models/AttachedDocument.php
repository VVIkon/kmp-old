<?php

/**
 * Class AttachedDocument
 * Реализует функциональность для работы с приложенными документами
 */
class AttachedDocument extends KFormModel
{
	const SOURCE_TYPE_KT = 1;
	const SOURCE_TYPE_UTK = 2;
	const SOURCE_TYPE_GPTS = 3;

	/**
	 * Ид приложенного документа
	 * @var int
	 */
	public $documentId;

	/**
	 * Ид заявки в КТ
	 * @var int
	 */
	public $orderId;

	/**
	 * Тип хранилища документа
	 * @var int
	 */
	public $documentSource;

	/**
	 * mime тип содержимого документа
	 * @var string
	 */
	public $mimeType;

	/**
	 * Наименование документа
	 * @var string
	 */
	public $fileName;

	/**
	 * Размер файла в байтах
	 * @var string
	 */
	public $fileSize;

	/**
	 * Путь к содержимому файла
	 * @var string
	 */
	public $fileURL;

	/**
	 *	Комментарий
	 * @var string
	 */
	public $fileComment;

	/**
	 *	Тип бизнес объекта к которому привязан документ
	 * @var int
	 */
	public $objectType;

	/**
	 *	Тип бизнес объекта к которому привязан документ
	 * @var int
	 */
	public $objectId;

	/**
	 * namespace для логирования
	 * @var
	 */
	public $_namespace;

	/**
	 * Конструктор объекта
	 * @param array $values
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return [
			['documentId, orderId, documentSource, mimeType, fileName, fileSize,
			fileURL, fileComment, objectType, objectId', 'safe']
		];
	}

	public function save() {

		$adoc = new self();
		if ($adoc->load($this->documentId)) {
			$this->update();
		} else {
			$this->create();
		}
	}

	/**
	 * Создание информации об объекте в БД
	 * @return mixed
	 */
	public function create() {

		$command = Yii::app()->db->createCommand();

		try {
			$res = $command->insert('kt_orders_doc', [
				'orderID'     		=> $this->orderId,
				'documentSource'  	=> $this->documentSource,
				'mimtype'      		=> $this->mimeType,
				'fileName'        	=> $this->fileName,
				'fileSize'   		=> $this->fileSize,
				'fileURL'     		=> $this->fileURL,
				'fileComment'       => $this->fileComment,
				'objectId'       	=> $this->objectId,
				'objectType'        => $this->objectType
			]);

		} catch (Exception $e) {
			throw new KmpDbException(
				get_class(),__FUNCTION__,
				OrdersErrors::CANNOT_CREATE_ATTACHED_DOCUMENT,
				$command->getText(),
				$e
			);
		}

		$this->documentId = Yii::app()->db->lastInsertID;

		return true;
	}

	/**
	 * Обновление данных об объекте в БД
	 */
	public function update() {

		$command = Yii::app()->db->createCommand();

		try {
			$res = $command->update('kt_orders_doc', [
				'orderID'     		=> $this->orderId,
				'documentSource'  	=> $this->documentSource,
				'mimtype'      		=> $this->mimeType,
				'fileName'        	=> $this->fileName,
				'fileSize'   		=> $this->fileSize,
				'fileURL'     		=> $this->fileURL,
				'fileComment'       => $this->fileComment,
				'objectId'       	=> $this->objectId,
				'objectType'        => $this->objectType

				], 'documentID = :documentId', [':documentId' => $this->documentId]
			);
		} catch (Exception $e) {

			throw new KmpDbException(
				get_class(),
				__FUNCTION__,
				OrdersErrors::CANNOT_UPDATE_ATTACHED_DOCUMENT,
				$command->getText(),
				$e
			);

		}
		return true;
	}

	/**
	 * Инициализация объекта по данным из БД
	 * @param $documentId string идентифкатор присоединённого документа
	 * @return CDbDataReader|mixed
	 */
	public function load($documentId) {

		$command = Yii::app()->db->createCommand();

		$command->select('documentID documentId, orderID orderId, documentSource documentSource,
							mimtype mimeType, fileName fileName, fileSize fileSize,
							 fileURL fileURL, fileComment fileComment , objectId objectId, objectType objectType');
		$command->from('kt_orders_doc');
		$command->where('documentID = :documentId',[':documentId' => $documentId]);

		try {
			$docInfo = $command->queryRow();
		} catch (Exception $e) {
			throw new KmpDbException(
				get_class(),
				__FUNCTION__,
				OrdersErrors::CANNOT_GET_ATTACHED_DOCUMENT,
				$command->getText(),
				$e
			);
		}

		$this->setAttributes($docInfo);
		return $docInfo;
	}

	/**
	 * Получение свойств объекта в виде массива
	 * @return array
	 */
	public function getData()
	{
		return [
			'documentId' => $this->documentId,
			'orderId' => $this->orderId,
			'documentSource' => $this->documentSource,
			'mimeType' => $this->mimeType,
			'fileName' => $this->fileName,
			'fileSize' => $this->fileSize,
			'fileURL' => $this->fileURL,
			'fileComment' => $this->fileComment,
			'objectType' => $this->objectType,
			'objectId' => $this->objectId,
		];
	}

}