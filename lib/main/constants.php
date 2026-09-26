<?php declare(strict_types=1);

namespace Shef\InSync\Main;

use Bitrix\Main\Application;
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

	/**
	 * Свой каталог импорта задаётся в /bitrix/.settings_extra.php:
	 *   'shef.insync' => ['value' => ['importDir' => '/var/data/import'], 'readonly' => true],
	 */
	public const SETTINGS_KEY = 'shef.insync';
	public const SETTINGS_IMPORT_DIR = 'importDir';
	
	/** Имя каталога импорта по умолчанию — рядом с корнем сайта. */
	public const IMPORT_DIR_NAME = 'sh_import';
	
	/**
	 * Каталог импорта — абсолютный путь, ВНЕ корня сайта.
	 *
	 * По умолчанию — на уровень выше корня: при корне /home/bitrix/www это
	 * /home/bitrix/sh_import. Внутри: <код>/ — файлы на импорт, copy/<код>/ —
	 * архив, problem/<код>/ — файлы с проблемой.
	 *
	 * До 2.0.0 каталог был /upload/import, под корнем сайта: выгрузки (цены,
	 * клиенты, заказы) веб-сервер отдавал любому, кто угадал имя, а имена
	 * архива были предсказуемы. Вне корня сайта настраивать ничего не нужно —
	 * так же, как логи shef.problems.
	 *
	 * Проект может задать свой каталог — ключ SETTINGS_KEY в
	 * /bitrix/.settings_extra.php. Принимается только абсолютный путь; что-то
	 * другое — каталог по умолчанию: относительный путь зависел бы от текущего
	 * каталога процесса, и агент из cron искал бы файлы не там, куда их
	 * положила страница загрузки.
	 *
	 * Корня сайта нет (CLI без DOCUMENT_ROOT) — временный каталог системы, а
	 * не «/sh_import» в корне файловой системы.
	 *
	 * @see docs/security.md
	 * @return string без «/» на конце
	 */
	public static function getImportDir(): string
	{
		$settings = Config\Configuration::getValue(static::SETTINGS_KEY);
		$custom = is_array($settings) ? ($settings[static::SETTINGS_IMPORT_DIR] ?? null) : null;
		
		if(is_string($custom) && str_starts_with($custom, '/') && rtrim($custom, '/') !== '')
		{
			return rtrim($custom, '/');
		}
		
		$documentRoot = rtrim(str_replace('\\', '/', (string)Application::getDocumentRoot()), '/');
		if($documentRoot === '')
		{
			return rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/').'/'.static::IMPORT_DIR_NAME;
		}
		
		$parent = dirname($documentRoot);
		
		return ($parent === '/' ? '' : $parent).'/'.static::IMPORT_DIR_NAME;
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
