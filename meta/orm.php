<?php

/**
 * Аннотации ORM для IDE: методы EO_-объектов, которые ядро компилирует на
 * лету. Файл никто не подключает и подключать не должен — классы здесь
 * объявлены «для вида».
 *
 * Лежит в корне модуля, как meta/orm.php у модулей ядра. До 2.0.0 таких
 * файлов было три, и все внутри lib/: там автозагрузка ищет классы
 * модуля, а один из них объявлял второй CardTable рядом с настоящим.
 *
 * Собирается механизмом ядра:
 *   php bitrix.php orm:annotate -m shef.insync <путь к модулю>/meta/orm.php
 */

/* ORMENTITYANNOTATION:Shef\InSync\Sync\Model\SyncTable */
namespace Shef\InSync\Sync\Model {
	/**
	 * Sync
	 * @see \Shef\InSync\Sync\Model\SyncTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string getOriginId()
	 * @method \Shef\InSync\Sync\Model\Sync setOriginId(\string|\Bitrix\Main\DB\SqlExpression $originId)
	 * @method bool hasOriginId()
	 * @method bool isOriginIdFilled()
	 * @method bool isOriginIdChanged()
	 * @method \string getOriginatorId()
	 * @method \Shef\InSync\Sync\Model\Sync setOriginatorId(\string|\Bitrix\Main\DB\SqlExpression $originatorId)
	 * @method bool hasOriginatorId()
	 * @method bool isOriginatorIdFilled()
	 * @method bool isOriginatorIdChanged()
	 * @method \string remindActualOriginatorId()
	 * @method \string requireOriginatorId()
	 * @method \Shef\InSync\Sync\Model\Sync resetOriginatorId()
	 * @method \Shef\InSync\Sync\Model\Sync unsetOriginatorId()
	 * @method \string fillOriginatorId()
	 * @method \Bitrix\Main\Type\DateTime getDateInsert()
	 * @method \Shef\InSync\Sync\Model\Sync setDateInsert(\Bitrix\Main\Type\DateTime|\Bitrix\Main\DB\SqlExpression $dateInsert)
	 * @method bool hasDateInsert()
	 * @method bool isDateInsertFilled()
	 * @method bool isDateInsertChanged()
	 * @method \Bitrix\Main\Type\DateTime remindActualDateInsert()
	 * @method \Bitrix\Main\Type\DateTime requireDateInsert()
	 * @method \Shef\InSync\Sync\Model\Sync resetDateInsert()
	 * @method \Shef\InSync\Sync\Model\Sync unsetDateInsert()
	 * @method \Bitrix\Main\Type\DateTime fillDateInsert()
	 * @method \string getTitle()
	 * @method \Shef\InSync\Sync\Model\Sync setTitle(\string|\Bitrix\Main\DB\SqlExpression $title)
	 * @method bool hasTitle()
	 * @method bool isTitleFilled()
	 * @method bool isTitleChanged()
	 * @method \string remindActualTitle()
	 * @method \string requireTitle()
	 * @method \Shef\InSync\Sync\Model\Sync resetTitle()
	 * @method \Shef\InSync\Sync\Model\Sync unsetTitle()
	 * @method \string fillTitle()
	 * @method \string getStatus()
	 * @method \Shef\InSync\Sync\Model\Sync setStatus(\string|\Bitrix\Main\DB\SqlExpression $status)
	 * @method bool hasStatus()
	 * @method bool isStatusFilled()
	 * @method bool isStatusChanged()
	 * @method \string remindActualStatus()
	 * @method \string requireStatus()
	 * @method \Shef\InSync\Sync\Model\Sync resetStatus()
	 * @method \Shef\InSync\Sync\Model\Sync unsetStatus()
	 * @method \string fillStatus()
	 * @method \string getMessage()
	 * @method \Shef\InSync\Sync\Model\Sync setMessage(\string|\Bitrix\Main\DB\SqlExpression $message)
	 * @method bool hasMessage()
	 * @method bool isMessageFilled()
	 * @method bool isMessageChanged()
	 * @method \string remindActualMessage()
	 * @method \string requireMessage()
	 * @method \Shef\InSync\Sync\Model\Sync resetMessage()
	 * @method \Shef\InSync\Sync\Model\Sync unsetMessage()
	 * @method \string fillMessage()
	 * @method array getAdditional()
	 * @method \Shef\InSync\Sync\Model\Sync setAdditional(array|\Bitrix\Main\DB\SqlExpression $additional)
	 * @method bool hasAdditional()
	 * @method bool isAdditionalFilled()
	 * @method bool isAdditionalChanged()
	 * @method array remindActualAdditional()
	 * @method array requireAdditional()
	 * @method \Shef\InSync\Sync\Model\Sync resetAdditional()
	 * @method \Shef\InSync\Sync\Model\Sync unsetAdditional()
	 * @method array fillAdditional()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Shef\InSync\Sync\Model\Sync set($fieldName, $value)
	 * @method \Shef\InSync\Sync\Model\Sync reset($fieldName)
	 * @method \Shef\InSync\Sync\Model\Sync unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Shef\InSync\Sync\Model\Sync wakeUp($data)
	 */
	class EO_Sync {
		/* @var \Shef\InSync\Sync\Model\SyncTable */
		static public $dataClass = '\Shef\InSync\Sync\Model\SyncTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Shef\InSync\Sync\Model {
	/**
	 * SyncCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \string[] getOriginIdList()
	 * @method \string[] getOriginatorIdList()
	 * @method \string[] fillOriginatorId()
	 * @method \Bitrix\Main\Type\DateTime[] getDateInsertList()
	 * @method \Bitrix\Main\Type\DateTime[] fillDateInsert()
	 * @method \string[] getTitleList()
	 * @method \string[] fillTitle()
	 * @method \string[] getStatusList()
	 * @method \string[] fillStatus()
	 * @method \string[] getMessageList()
	 * @method \string[] fillMessage()
	 * @method array[] getAdditionalList()
	 * @method array[] fillAdditional()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Shef\InSync\Sync\Model\Sync $object)
	 * @method bool has(\Shef\InSync\Sync\Model\Sync $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Shef\InSync\Sync\Model\Sync getByPrimary($primary)
	 * @method \Shef\InSync\Sync\Model\Sync[] getAll()
	 * @method bool remove(\Shef\InSync\Sync\Model\Sync $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Shef\InSync\Sync\Model\SyncCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Shef\InSync\Sync\Model\Sync current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Sync_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Shef\InSync\Sync\Model\SyncTable */
		static public $dataClass = '\Shef\InSync\Sync\Model\SyncTable';
	}
}
namespace Shef\InSync\Sync\Model {
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Sync_Result exec()
	 * @method \Shef\InSync\Sync\Model\Sync fetchObject()
	 * @method \Shef\InSync\Sync\Model\SyncCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Sync_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Shef\InSync\Sync\Model\Sync fetchObject()
	 * @method \Shef\InSync\Sync\Model\SyncCollection fetchCollection()
	 */
	class EO_Sync_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Shef\InSync\Sync\Model\Sync createObject($setDefaultValues = true)
	 * @method \Shef\InSync\Sync\Model\SyncCollection createCollection()
	 * @method \Shef\InSync\Sync\Model\Sync wakeUpObject($row)
	 * @method \Shef\InSync\Sync\Model\SyncCollection wakeUpCollection($rows)
	 */
	class EO_Sync_Entity extends \Bitrix\Main\ORM\Entity {}
}

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

/* ORMENTITYANNOTATION:Shef\InSync\Sync\Model\Hl\Demo\CardTable */
namespace Shef\InSync\Sync\Model\Hl\Demo {
	/**
	 * Card
	 * @see \Shef\InSync\Sync\Model\Hl\Demo\CardTable
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int getId()
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card setId(\int|\Bitrix\Main\DB\SqlExpression $id)
	 * @method bool hasId()
	 * @method bool isIdFilled()
	 * @method bool isIdChanged()
	 * @method \string getUfXmlId()
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card setUfXmlId(\string|\Bitrix\Main\DB\SqlExpression $ufXmlId)
	 * @method bool hasUfXmlId()
	 * @method bool isUfXmlIdFilled()
	 * @method bool isUfXmlIdChanged()
	 * @method \string remindActualUfXmlId()
	 * @method \string requireUfXmlId()
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card resetUfXmlId()
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card unsetUfXmlId()
	 * @method \string fillUfXmlId()
	 * @method \float getUfSum()
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card setUfSum(\float|\Bitrix\Main\DB\SqlExpression $ufSum)
	 * @method bool hasUfSum()
	 * @method bool isUfSumFilled()
	 * @method bool isUfSumChanged()
	 * @method \float remindActualUfSum()
	 * @method \float requireUfSum()
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card resetUfSum()
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card unsetUfSum()
	 * @method \float fillUfSum()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @property-read array $primary
	 * @property-read int $state @see \Bitrix\Main\ORM\Objectify\State
	 * @property-read \Bitrix\Main\Type\Dictionary $customData
	 * @property \Bitrix\Main\Authentication\Context $authContext
	 * @method mixed get($fieldName)
	 * @method mixed remindActual($fieldName)
	 * @method mixed require($fieldName)
	 * @method bool has($fieldName)
	 * @method bool isFilled($fieldName)
	 * @method bool isChanged($fieldName)
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card set($fieldName, $value)
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card reset($fieldName)
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card unset($fieldName)
	 * @method void addTo($fieldName, $value)
	 * @method void removeFrom($fieldName, $value)
	 * @method void removeAll($fieldName)
	 * @method \Bitrix\Main\ORM\Data\Result delete()
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method mixed[] collectValues($valuesType = \Bitrix\Main\ORM\Objectify\Values::ALL, $fieldsMask = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL)
	 * @method \Bitrix\Main\ORM\Data\AddResult|\Bitrix\Main\ORM\Data\UpdateResult|\Bitrix\Main\ORM\Data\Result save()
	 * @method static \Shef\InSync\Sync\Model\Hl\Demo\Card wakeUp($data)
	 */
	class EO_Card {
		/* @var \Shef\InSync\Sync\Model\Hl\Demo\CardTable */
		static public $dataClass = '\Shef\InSync\Sync\Model\Hl\Demo\CardTable';
		/**
		 * @param bool|array $setDefaultValues
		 */
		public function __construct($setDefaultValues = true) {}
	}
}
namespace Shef\InSync\Sync\Model\Hl\Demo {
	/**
	 * CardsCollection
	 *
	 * Custom methods:
	 * ---------------
	 *
	 * @method \int[] getIdList()
	 * @method \string[] getUfXmlIdList()
	 * @method \string[] fillUfXmlId()
	 * @method \float[] getUfSumList()
	 * @method \float[] fillUfSum()
	 *
	 * Common methods:
	 * ---------------
	 *
	 * @property-read \Bitrix\Main\ORM\Entity $entity
	 * @method void add(\Shef\InSync\Sync\Model\Hl\Demo\Card $object)
	 * @method bool has(\Shef\InSync\Sync\Model\Hl\Demo\Card $object)
	 * @method bool hasByPrimary($primary)
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card getByPrimary($primary)
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card[] getAll()
	 * @method bool remove(\Shef\InSync\Sync\Model\Hl\Demo\Card $object)
	 * @method void removeByPrimary($primary)
	 * @method void fill($fields = \Bitrix\Main\ORM\Fields\FieldTypeMask::ALL) flag or array of field names
	 * @method static \Shef\InSync\Sync\Model\Hl\Demo\CardsCollection wakeUp($data)
	 * @method \Bitrix\Main\ORM\Data\Result save($ignoreEvents = false)
	 * @method void offsetSet() ArrayAccess
	 * @method void offsetExists() ArrayAccess
	 * @method void offsetUnset() ArrayAccess
	 * @method void offsetGet() ArrayAccess
	 * @method void rewind() Iterator
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card current() Iterator
	 * @method mixed key() Iterator
	 * @method void next() Iterator
	 * @method bool valid() Iterator
	 * @method int count() Countable
	 */
	class EO_Card_Collection implements \ArrayAccess, \Iterator, \Countable {
		/* @var \Shef\InSync\Sync\Model\Hl\Demo\CardTable */
		static public $dataClass = '\Shef\InSync\Sync\Model\Hl\Demo\CardTable';
	}
}
namespace Shef\InSync\Sync\Model\Hl\Demo {
	/**
	 * @method static EO_Card_Query query()
	 * @method static EO_Card_Result getByPrimary($primary, array $parameters = [])
	 * @method static EO_Card_Result getById($id)
	 * @method static EO_Card_Result getList(array $parameters = [])
	 * @method static EO_Card_Entity getEntity()
	 * @method static \Shef\InSync\Sync\Model\Hl\Demo\Card createObject($setDefaultValues = true)
	 * @method static \Shef\InSync\Sync\Model\Hl\Demo\CardsCollection createCollection()
	 * @method static \Shef\InSync\Sync\Model\Hl\Demo\Card wakeUpObject($row)
	 * @method static \Shef\InSync\Sync\Model\Hl\Demo\CardsCollection wakeUpCollection($rows)
	 */
	// class CardTable — настоящий, в lib/sync/model/hl/demo/cardtable.php
	/**
	 * Common methods:
	 * ---------------
	 *
	 * @method EO_Card_Result exec()
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card fetchObject()
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\CardsCollection fetchCollection()
	 *
	 * Custom methods:
	 * ---------------
	 *
	 */
	class EO_Card_Query extends \Bitrix\Main\ORM\Query\Query {}
	/**
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card fetchObject()
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\CardsCollection fetchCollection()
	 */
	class EO_Card_Result extends \Bitrix\Main\ORM\Query\Result {}
	/**
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card createObject($setDefaultValues = true)
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\CardsCollection createCollection()
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\Card wakeUpObject($row)
	 * @method \Shef\InSync\Sync\Model\Hl\Demo\CardsCollection wakeUpCollection($rows)
	 */
	class EO_Card_Entity extends \Bitrix\Main\ORM\Entity {}
}
