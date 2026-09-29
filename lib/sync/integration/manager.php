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
}