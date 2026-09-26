<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

use LogicException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree as Filter;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;

trait IPropertyCodeTrait
{
	/** @var string[]  */
	protected static array $code;
	
	public static function setPropertyCode(string $code): void
	{
		$subclass = static::class;
		static::$code[$subclass] = $code;
	}
	
	/**
	 * @return string
	 * @throws LogicException
	 */
	public static function getPropertyCode(): string
	{
		$subclass = static::class;
		if(empty((string)static::$code[$subclass]))
		{
			throw new LogicException(sprintf(
				'Not set code at %s. Use setPropertyCode()',
				$subclass
			));
		}
		
		return (string)static::$code[$subclass];
	}
}