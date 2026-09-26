<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

use LogicException;
use Bitrix\Main\ArgumentException;

/**
 * Интерфейс контроля уникальности символьного кода
 * @see \Shef\InSync\Sync\Model\IBlock\CheckCodeTrait
 */
interface ICheckCode
{
	/**
	 * Делает символьный код уникальным
	 *
	 * @param string $code
	 * @return string
	 */
	public static function checkCode(string $code): string;
}