<?php declare(strict_types=1);

namespace Shef\InSync\Main;

use Bitrix\Main\Config;

class Constants
{
	public const MODULE_ID = 'shef.insync';

	/** Сколько дней хранится файл в результате импорта, пока настройку не задали */
	public const DEFAULT_MAX_DAY_DONE_FILE = 3;

	public static function getModuleId(): string
	{
		return static::MODULE_ID;
	}

	public static function getSettingsOptions(): array
	{
		$list = Config\Configuration::getInstance(static::getModuleId())
			->get('options');

		if(!is_array($list))
		{
			$list = [];
		}

		return $list;
	}

	// region Users ////
	public static function getSystemUserId(): int
	{
		return \Shef\Options\Main\Constants::getSystemUserId();
	}
	// endregion ////

	/**
	 * Сколько дней хранится файл в результате импорта
	 *
	 * Разбор строгий: целое > 0 либо строка из одних цифр. Было (int) — и
	 * «0», пустая строка или мусор в настройке давали 0 дней, то есть
	 * clearDoneFolder() стирал архив импорта целиком на каждом запуске.
	 *
	 * @return int
	 */
	public static function getMaxDayOffDoneFile(): int
	{
		return static::parseDays(
			Config\Option::get(
				static::MODULE_ID,
				'DEF_maxdaydonefile',
				(string)static::DEFAULT_MAX_DAY_DONE_FILE
			)
		);
	}

	/**
	 * Число дней из настройки; всё, что не целое > 0, — умолчание.
	 */
	public static function parseDays(mixed $value): int
	{
		if(is_int($value) && $value > 0)
		{
			return $value;
		}

		if(is_string($value) && preg_match('/^\d{1,4}$/', $value) && (int)$value > 0)
		{
			return (int)$value;
		}

		return static::DEFAULT_MAX_DAY_DONE_FILE;
	}
}
