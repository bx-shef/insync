<?php declare(strict_types=1);

namespace Shef\Insync\Main;

use Bitrix\Main\Config;

class Constants
{
	public const MODULE_ID = 'shef.insync';
	
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
	 * @return int
	 */
	public static function getMaxDayOffDoneFile(): int
	{
		return (int)Config\Option::get(
			static::MODULE_ID,
			'DEF_maxdaydonefile',
			3
		);
	}
}