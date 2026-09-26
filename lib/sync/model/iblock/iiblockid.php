<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

use LogicException;

/**
 * Интерфейс указывает что используется IBlockId
 *
 * @see \Shef\InSync\Sync\Model\IBlock\GetListTrait
 * @see \Shef\InSync\Sync\Model\IBlock\IBlockIdTrait
 */
interface IIBlockId
{
	/**
	 * Устанавливает IBlockId
	 * @param int $iblockId
	 *
	 * @return void
	 */
	public static function setIblockId(int $iblockId): void;
	
	/**
	 * Возвращает IBlockId
	 *
	 * Если не установили, генерирует исключение LogicException
	 *
	 * @return int
	 * @throws LogicException
	 */
	public static function getIblockId(): int;
}