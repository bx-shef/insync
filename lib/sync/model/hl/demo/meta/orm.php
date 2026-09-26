<?php

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
	class CardTable extends \Bitrix\Main\ORM\Data\DataManager {}
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