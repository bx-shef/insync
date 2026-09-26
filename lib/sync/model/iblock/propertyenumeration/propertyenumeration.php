<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\PropertyEnumeration;

use Bitrix\Iblock\EO_PropertyEnumeration;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\ORM;
use Shef\InSync\Main\Utils;

class PropertyEnumeration
	extends EO_PropertyEnumeration
	implements Arrayable
{
	/**
	 * @var string
	 * @memo обязательно нужно указать свой кастомный таблет
	 */
	static public $dataClass = PropertyEnumerationTable::class;
	
	/**
	 * @throws ArgumentException
	 */
	public function toArray(): array
	{
		return Utils::prepareCollectValues($this);
	}
}