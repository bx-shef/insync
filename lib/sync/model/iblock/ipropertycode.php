<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

use LogicException;

/**
 * Интерфейс указывает что используется код свойства
 */
interface IPropertyCode
{
	/**
	 * Устанавливает код свойства
	 *
	 * @param string $code
	 *
	 * @return void
	 */
	public static function setPropertyCode(string $code): void;
	
	/**
	 * Возвращает код свойства
	 *
	 * Если не установили, генерирует исключение LogicException
	 *
	 * @return string
	 * @throws LogicException
	 */
	public static function getPropertyCode(): string;
}