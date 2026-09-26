<?php declare(strict_types=1);

/**
 * Установка и удаление модуля: уносит своё, не трогает чужое, прибирает за 1.x.
 *
 * * Настройки уходят вместе с модулем (решение владельца в shef.options):
 *   иначе повторная установка молча поднимает прежние значения. Проверяются
 *   обе стороны — Option::delete() работает по модулю, и ошибка в
 *   идентификаторе унесла бы настройки соседа.
 * * Таблица импорта — данные: уходит вместе с модулем, а с savedata = Y
 *   остаётся вместе с настройками (уговор ядра).
 * * Файлы ставятся из того каталога, где модуль стоит на самом деле, а не из
 *   зашитого /bitrix/modules: модуль из /local/modules иначе не ставил ничего.
 * * Компоненты 1.x лежали в /local/components/shef.insync. Ядро смотрит туда
 *   первым, и старая копия перекрыла бы новую — поэтому она уходит и при
 *   установке, и при удалении. Чужие компоненты в /local/components целы.
 *
 * Ядро подменяется заглушками, установщик подключается настоящий.
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/autoload.php';
require_once $root.'/tests/assert.php';

use Bitrix\Main\Config\Option;
use Bitrix\Main\DB\Connection;

// region Заглушка ядра ////
class CoreCalls
{
	/** @var list<string> */
	public static array $unregistered = [];

	public static int $cacheCleaned = 0;

	/** @var list<array{0: string, 1: string}> что установщик копировал */
	public static array $copied = [];

	public static function reset(): void
	{
		static::$unregistered = [];
		static::$cacheCleaned = 0;
		static::$copied = [];
	}
}

if(!class_exists('CModule'))
{
	class CModule
	{
	}
}

function IsModuleInstalled(string $moduleId): bool
{
	return false;
}

function UnRegisterModule(string $moduleId): void
{
	CoreCalls::$unregistered[] = $moduleId;
}

function RegisterModule(string $moduleId): void
{
}

/** Копирование каталогов ядра: запоминаем откуда и куда. */
function CopyDirFiles(string $from, string $to, bool $rewrite = true, bool $recursive = false): bool
{
	CoreCalls::$copied[] = [$from, $to];
	return true;
}

$GLOBALS['APPLICATION'] = new class extends CMain
{
	public function ThrowException(string $message): void {}
};

$GLOBALS['CACHE_MANAGER'] = new class
{
	public function CleanAll(): void
	{
		CoreCalls::$cacheCleaned++;
	}
};

// Установщик читает installDir из настоящего .settings.php.
\Bitrix\Main\Config\Configuration::$settings = require $root.'/.settings.php';
// endregion ////

require_once $root.'/install/index.php';

const NEIGHBOUR = 'shef.options';
const TABLE = 'shef_insync_model';

$given = static function(): shef_insync
{
	CoreCalls::reset();
	Option::$values = [];
	Connection::$tables = [TABLE, 'b_user'];
	Connection::$queries = [];

	Option::set('shef.insync', 'DEF_maxdaydonefile', '15');
	Option::set(NEIGHBOUR, 'DEF_systemuserid', '9');

	return new shef_insync();
};

Check::group('установка: таблица импорта создаётся');

$module = $given();
Connection::$tables = [];
$module->InstallDB();

Check::same('таблица создана', in_array(TABLE, Connection::$tables, true), true);
Check::same('поле ADDITIONAL расширено и индексы построены', count(array_filter(
	Connection::$queries,
	static fn(string $sql): bool => str_starts_with($sql, 'ALTER TABLE '.TABLE) || str_starts_with($sql, 'CREATE INDEX')
)), 4);

$queries = count(Connection::$queries);
$module->InstallDB();
Check::same('повторная установка таблицу не трогает', count(Connection::$queries), $queries);

Check::group('удаление уносит настройки и таблицу импорта');

$module = $given();
$module->UnInstallDB();

Check::same('идентификатор модуля тот самый', $module->MODULE_ID, 'shef.insync');
Check::same('настройка стёрта', Option::get('shef.insync', 'DEF_maxdaydonefile', 'нет'), 'нет');
Check::same('таблица импорта удалена', in_array(TABLE, Connection::$tables, true), false);
Check::same('модуль снят с регистрации', CoreCalls::$unregistered, ['shef.insync']);
Check::same('кеш сброшен', CoreCalls::$cacheCleaned, 1);

Check::group('чужое не трогаем');

Check::same('настройка shef.options на месте', Option::get(NEIGHBOUR, 'DEF_systemuserid', 'нет'), '9');
Check::same('чужие таблицы на месте', in_array('b_user', Connection::$tables, true), true);

Check::group('savedata');

$module = $given();
$module->UnInstallDB(['savedata' => 'Y']);
Check::same('savedata = Y оставляет настройки', Option::get('shef.insync', 'DEF_maxdaydonefile', 'нет'), '15');
Check::same('…и таблицу импорта', in_array(TABLE, Connection::$tables, true), true);

$module = $given();
$module->UnInstallDB(['savedata' => 'N']);
Check::same('savedata = N стирает', Option::get('shef.insync', 'DEF_maxdaydonefile', 'нет'), 'нет');

Check::group('установка файлов: из того каталога, где стоит модуль');

$portal = sys_get_temp_dir().'/shef-insync-uninstall-'.getmypid();
\Bitrix\Main\Application::$documentRoot = $portal.'/www';

$touch = static function(string $path, string $content = 'x'): void
{
	if(!is_dir(dirname($path)))
	{
		mkdir(dirname($path), 0777, true);
	}
	file_put_contents($path, $content);
};

$touch($portal.'/www/local/components/shef.insync/import.stat.local/class.php');
$touch($portal.'/www/local/components/acme/list/class.php');
$touch($portal.'/www/bitrix/js/main/core/core.js');

$module = $given();
Check::same('InstallFiles отработал', $module->InstallFiles(), true);

// Каталог модуля берётся у самого установщика, а не зашитый /bitrix/modules:
// модуль здесь лежит вне корня сайта-песочницы, и файлы всё равно нашлись.
Check::same('компоненты и js разложены из каталога модуля', CoreCalls::$copied, [
	[$root.'/install/components', $portal.'/www/bitrix/components'],
	[$root.'/install/js', $portal.'/www/bitrix/js'],
]);
Check::same('копия компонентов 1.x из /local/components убрана', is_dir($portal.'/www/local/components/shef.insync'), false);
Check::same('чужие компоненты в /local/components на месте', is_file($portal.'/www/local/components/acme/list/class.php'), true);

Check::group('удаление файлов: своё уносим, чужое — нет');

$touch($portal.'/www/bitrix/components/shef.insync/import.stat.local/class.php');
$touch($portal.'/www/bitrix/js/shef-insync/ui-anchors/script.js');
$touch($portal.'/www/local/components/shef.insync/import.from.file/class.php');

$module = $given();
Check::same('UnInstallFiles отработал', $module->UnInstallFiles(), true);

Check::same('компоненты модуля убраны', is_dir($portal.'/www/bitrix/components/shef.insync'), false);
Check::same('js модуля убран', is_dir($portal.'/www/bitrix/js/shef-insync'), false);
Check::same('копия 1.x в /local убрана', is_dir($portal.'/www/local/components/shef.insync'), false);
Check::same('чужие расширения на месте', is_file($portal.'/www/bitrix/js/main/core/core.js'), true);
Check::same('чужие компоненты на месте', is_file($portal.'/www/local/components/acme/list/class.php'), true);

\Bitrix\Main\IO\Directory::deleteDirectory($portal);

Check::finish();
