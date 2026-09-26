<?php declare(strict_types=1);

namespace Shef\InSync\Sync\FromFile;

use Bitrix\Main\IO;

/**
 * Interface IFromCsvFile
 *
 * Интерфейс для пошагового импорта из csv файла
 *
 */
interface IFromCsvFile
{
	/**
	 * Указывает на использование в 1й строке закголовка
	 * @return bool
	 */
	public static function isUseHeader(): bool;
	
	/**
	 * Возвращает разделитель колонок
	 * @return string
	 */
	public static function getDelim(): string;
	
	/**
	 * Возвращает колонки файла импорта
	 *
	 * @return array
	 */
	public function getMapImportFile(): array;
}