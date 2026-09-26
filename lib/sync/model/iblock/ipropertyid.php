<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

use LogicException;

/**
 * Интерфейс указывает что используется id свойства
 */
interface IPropertyId
{
	/**
	 * Устанавливает id свойства
	 *
	 * @param int $propertyId
	 *
	 * @return void
	 */
	public static function setPropertyId(int $propertyId): void;
	
	/**
	 * Возвращает id свойства
	 *
	 * Если не установили, генерирует исключение LogicException
	 *
	 * @return int
	 * @throws LogicException
	 */
	public static function getPropertyId(): int;
}