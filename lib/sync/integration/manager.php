<?php declare(strict_types=1);
namespace Shef\InSync\Sync\Integration;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Shef\InSync\Main\Utils;

class Manager
{
	/**
	 * Шлем компоненту статистики импорта широковещательное уведомление на обновление контента
	 *
	 * @return Result
	 */
	public static function sendPullForImportStatLocal(): Result
	{
		return Utils::sendPullShared(
			'reload',
			[
				'component' => 'shef.insync:import.stat.local'
			]
		);
	}
	
	/**
	 * Возвращает ссылку на таблицу shef_insync_model в админке
	 * @return string
	 */
	public static function getUrlShefInsyncModel(): string
	{
		return sprintf(
			'/bitrix/admin/perfmon_table.php?lang=%s&table_name=%s',
			\Bitrix\Main\Application::getInstance()->getContext()->getLanguage(),
			'shef_insync_model'
		);
	}
}