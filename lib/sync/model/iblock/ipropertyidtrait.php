<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

use LogicException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree as Filter;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;

trait IPropertyIdTrait
{
	/** @var int[]  */
	protected static array $propertyId;
	
	public static function setPropertyId(int $propertyId): void
	{
		$subclass = static::class;
		static::$propertyId[$subclass] = $propertyId;
	}
	
	/**
	 * @return int
	 * @throws LogicException
	 */
	public static function getPropertyId(): int
	{
		$subclass = static::class;
		if(empty((string)static::$propertyId[$subclass]))
		{
			throw new LogicException(sprintf(
				'Not set proprtyId at %s. Use setPropertyId()',
				$subclass
			));
		}
		
		return (int)static::$propertyId[$subclass];
	}
}