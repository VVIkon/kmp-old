<?php

/**
 * Class ServiceTypeForm
 * Реализует функциональность для работы типами услуг
 */
class ServiceTypeForm extends KFormModel
{

	/**
	 * Конструктор объекта
	 * @param array $values
	 */
	public function __construct() {

		parent::__construct();
		$this->validate();
	}

	/**
	 * Declares the validation rules.
	 * The rules state that username and password are required,
	 * and password needs to be authenticated.
	 */
	public function rules()
	{
		return [];

	}

	/**
	 * Получение услуги по ид КТ
	 * @return null
	 */
	public static function getServiceTypeNameById($typeId) {

		if (empty($typeId)) {
			return null;
		}

		$command = Yii::app()->db->createCommand()
			->select('*')
			->from('kt_ref_services')
			->where('ServiceID = :serviceId', array(':serviceId' => $typeId));

		$name = $command->queryRow();

		return (empty($name['Name'])) ? false : $name['Name'];
	}

}