<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\PropertyEnumeration;

use Bitrix\Iblock\EO_PropertyEnumeration_Collection;

class PropertyEnumerationCollection
	extends EO_PropertyEnumeration_Collection
{
	/**
	 * @var string
	 * @memo обязательно нужно указать свой кастомный таблет
	 */
	static public $dataClass = PropertyEnumerationTable::class;
}