<?php

/**
 * Class SuppliersForm
 * Реализует функциональность для работы с данными конечных поставщиков услуг
 */
class SuppliersForm extends KFormModel
{
	/**
	 * Идентификатор поставщика в КТ
	 * @var int
	 */
	public $supplierId;

	/**
	 * Идентификатор поставщика в GPTS
	 * @var string
	 */
	public $supplierGPTSId;

	/**
	 * Идентификатор поставщика в УТК
	 * @var string
	 */
	public $supplierUTKId;

	/**
	 * Наименование поставщика
	 * @var string
	 */
	public $supplierName;

	/**
	 * Наименование поставщика на английском
	 * @var string
	 */
	public $supplierNameEng;

	/**
	 * Модуль
	 * @var string
	 */
	private $module;

	/**
	 * Конструктор объекта
	 * @param string $module
	 */
	public function __construct($module) {
		$this->module = $module;
	}

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return [
			[
				'supplierId, supplierGPTSId, supplierUTKId, supplierName','safe'
			]
		];
	}

	/**
	 * Получить данные поставщика по ид в КТ
	 * @param $supplierId
	 * @return bool
	 */
	public static function getSupplierById($supplierId)
	{

		if (empty($supplierId)) {
			return false;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_ref_suppliers')
			->where('kt_ref_suppliers.SupplierID = :supplierId', array(':supplierId' => $supplierId));

		return $command->queryRow();

	}

	/**
	 * Получить данные поставщика по ид в GPTS
	 * @param $supplierGptsId
	 * @return bool
	 */
	public static function getAgencyByGptsId($supplierGptsId) {

		if (empty($supplierGptsId)) {
			return false;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_ref_suppliers')
			->where('kt_ref_suppliers.SupplierID_GPTS = :supplierGptsId', array(':supplierGptsId' => $supplierGptsId));

		return $command->queryRow();
	}

	/**
	 * Получить данные поставщика по ид в УТК
	 * @param $supplierUtkId
	 * @return bool
	 */
	public static function getAgencyByUtkId($supplierUtkId)
	{

		if (empty($supplierUtkId)) {
			return false;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_ref_suppliers')
			->where('kt_ref_suppliers.SupplierID_UTK = :supplierUtkId', array(':supplierUtkId' => $supplierUtkId));

		return $command->queryRow();
	}

}