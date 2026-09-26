<?php declare(strict_types=1);

namespace Shef\InSync\Main;

use Bitrix\Main\Engine\CurrentUser;

/**
 * Кто вправе управлять импортом: запускать и останавливать агенты, загружать
 * файлы, чистить таблицу импорта.
 *
 * Администратор портала — всегда. Остальные — с правом «W» (запись) на
 * модуль в «Настройки → Права доступа»: на модуль, которому принадлежит
 * агент или импорт, а не на shef.insync. Чей это импорт, знает вызывающий.
 *
 * Проверка нужна и на показ кнопки, и в самом ajax-действии: адрес действия
 * виден в коде страницы и вызывается напрямую. До 2.0.0 действия
 * проверяли только вход на портал — любой сотрудник мог включить или
 * выключить любой агент портала по его ID и загрузить файл в импорт.
 */
final class Access
{
	public const RIGHT_MANAGE = 'W';

	public static function canManage(null|string $moduleId = null): bool
	{
		$user = CurrentUser::get();

		if(null === $user->getId() || (int)$user->getId() < 1)
		{
			return false;
		}

		if($user->isAdmin())
		{
			return true;
		}

		$moduleId = trim((string)($moduleId ?? Constants::MODULE_ID));
		if($moduleId === '')
		{
			return false;
		}

		$application = $GLOBALS['APPLICATION'] ?? null;
		if(!is_object($application) || !method_exists($application, 'GetGroupRight'))
		{
			return false;
		}

		return (string)$application->GetGroupRight($moduleId) >= static::RIGHT_MANAGE;
	}
}
