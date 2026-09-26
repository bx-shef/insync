<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

use LogicException;
use Bitrix\Main\ArgumentException;

/**
 * Интерфейс указывает что используется подменяем фильтра и выборки в getList
 *
 * @see \Shef\InSync\Sync\Model\IBlock\GetListTrait
 */
interface IFixGetList
{
	/**
	 * Обработка фильтра и выборки
	 *
	 * Если чего-то не хватает, то генерируем исключение \LogicException
	 *
	 * @param array $parameters
	 *
	 * @return array
	 *
	 * @throws ArgumentException
	 * @throws LogicException
	 */
	public static function prepareParamsForGetList(array $parameters = []): array;
}