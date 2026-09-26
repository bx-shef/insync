<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

use LogicException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree as Filter;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;

/**
 * Добавляем учет IBlockId
 *
 * @see \Shef\InSync\Sync\Model\IBlock\IIBlockId
 * @see \Shef\InSync\Sync\Model\IBlock\IFixGetList
 */
trait IBlockIdTrait
{
	/** @var int[]  */
	protected static array $iblockId;
	
	/**
	 * @inheritDoc
	 */
	public static function setIblockId(int $iblockId): void
	{
		$subclass = static::class;
		static::$iblockId[$subclass] = $iblockId;
	}
	
	/**
	 * @inheritDoc
	 */
	public static function getIblockId(): int
	{
		$subclass = static::class;
		// Без ?? PHP 8 давал warning до исключения, которое и так объясняет.
		if((int)(static::$iblockId[$subclass] ?? 0) < 1)
		{
			throw new LogicException(sprintf(
				'Not set iblockId at %s. Use setIblockId()',
				$subclass
			));
		}
		
		return (int)static::$iblockId[$subclass];
	}
}