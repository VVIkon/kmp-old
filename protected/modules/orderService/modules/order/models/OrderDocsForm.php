<?php

/**
 * Class OrderDocsForm
 * Реализует функциональность для работы с приложенными документами к заявке
 */
class OrderDocsForm extends KFormModel
{

	/**
	 * Конструктор объекта
	 * @param array $values
	 */
	public function __construct($params, $namespace) {
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
			['', 'safe']
		];

	}

	public static function getOrderDocs($orderId) {

		if (empty($orderId)) {
			return false;
		}

		$command = Yii::app()->db->createCommand()
			->select('documentID documentId, orderID orderId, documentSource,
						mimtype mimetype, fileName, fileSize, fileURL fileUrl, fileComment, objectType, objectId')
			->from('kt_orders_doc')
			->where('kt_orders_doc.OrderID = :orderId', array(':orderId' => $orderId));

		try {
			return $command->queryAll();
		} catch (Exception $e) {
			throw new KmpDbException(
				get_class(),__FUNCTION__,
				OrdersErrors::CANNOT_GET_ORDER_ATTACHED_DOCUMENTS,
				$command->getText(),
				$e
			);

			return false;
		}

	}
}