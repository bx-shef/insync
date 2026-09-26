<?php

/* ORMENTITYANNOTATION:Shef\InSync\Sync\Model\Catalog\ProductTable */
namespace Shef\InSync\Sync\Model\Catalog {
	/**
	 * Product
	 * @see \Shef\InSync\Sync\Model\Catalog\ProductTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \Bitrix\Catalog\EO_Vat getShVat()
	 * @method \Bitrix\Catalog\EO_Vat remindActualShVat()
	 * @method \Bitrix\Catalog\EO_Vat requireShVat()
	 * @method \Shef\InSync\Sync\Model\Catalog\Product setShVat(\Bitrix\Catalog\EO_Vat $object)
	 * @method \Shef\InSync\Sync\Model\Catalog\Product resetShVat()
	 * @method \Shef\InSync\Sync\Model\Catalog\Product unsetShVat()
	 * @method bool hasShVat()
	 * @method bool isShVatFilled()
	 * @method bool isShVatChanged()
	 * @method \Bitrix\Catalog\EO_Vat fillShVat()
	 *
	 * Common methods:
	 * ---------------
	 *
	 */
	class EO_Product
		extends \Bitrix\Catalog\EO_Product
	{
		/* @var \Shef\InSync\Sync\Model\Catalog\ProductTable */
		static public $dataClass = '\Shef\InSync\Sync\Model\Catalog\ProductTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {
			parent::__construct($setDefaultValues);
		}
	}
}
namespace Shef\InSync\Sync\Model\Catalog {
	/**
	 * ProductCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \Bitrix\Catalog\EO_Vat[] getShVatList()
	 * @method \Shef\InSync\Sync\Model\Catalog\ProductCollection getShVatCollection()
	 * @method \Bitrix\Catalog\EO_Vat_Collection fillShVat()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @method void offsetSet(mixed $offset, mixed $value) ArrayAccess
	 * @method void offsetExists(mixed $offset) ArrayAccess
	 * @method void offsetUnset(mixed $offset) ArrayAccess
	 * @method void offsetGet(mixed $offset) ArrayAccess
	 *
	 */
	class EO_Product_Collection
		extends \Bitrix\Catalog\EO_Product_Collection
		implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Shef\InSync\Sync\Model\Catalog\ProductTable */
		static public $dataClass = '\Shef\InSync\Sync\Model\Catalog\ProductTable';
	}
}
namespace Shef\InSync\Sync\Model\Catalog {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method \Shef\InSync\Sync\Model\Catalog\EO_Product_Result exec()
	 * @method \Shef\InSync\Sync\Model\Catalog\Product fetchObject()
	 * @method \Shef\InSync\Sync\Model\Catalog\ProductCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Product_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Shef\InSync\Sync\Model\Catalog\Product fetchObject()
	 * @method \Shef\InSync\Sync\Model\Catalog\ProductCollection fetchCollection()
	 */
	class EO_Product_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Shef\InSync\Sync\Model\Catalog\Product createObject($setDefaultValues = true)
	 * @method \Shef\InSync\Sync\Model\Catalog\ProductCollection createCollection()
	 * @method \Shef\InSync\Sync\Model\Catalog\Product wakeUpObject($row)
	 * @method \Shef\InSync\Sync\Model\Catalog\ProductCollection wakeUpCollection($rows)
	 */
	class EO_Product_Entity extends \Bitrix\Main\ORM\Entity {}
}