<?php declare(strict_types=1);

namespace Shef\InSync\Sync\FromFile;

use Bitrix\Main\IO;

/**
 * Interface IFromXmlFile
 *
 * Интерфейс для пошагового импорта из csv файла
 *
 */
interface IFromXmlFile
{
	/**
	 * Получим название тега в котором хранится элемент
	 * @return string
	 */
	public static function getItemTag(): string;
}