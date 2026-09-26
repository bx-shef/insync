<?php declare(strict_types=1);

/**
 * Подключение модуля: include.php поднимает зависимости и библиотеки XML.
 *
 * include.php — это autoload.php: он подключает модули из requireModules
 * (shef.options, shef.problems) и регистрирует namespace из registerNamespace
 * с корнем сайта впереди. Промах тут не виден до первого класса импорта —
 * «Class SbWereWolf\XmlNavigator\... not found» посреди разбора файла.
 *
 * Тест подключает настоящий include.php и спрашивает результат у заглушки
 * ядра, а не ищет строки в исходнике.
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/autoload.php';
require_once $root.'/tests/assert.php';

use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\Loader;

Application::$documentRoot = dirname($root);
Configuration::$settings = require $root.'/.settings.php';

$included = [];
foreach(['shef.options', 'shef.problems'] as $module)
{
	Loader::$onInclude[$module] = static function() use ($module, &$included): void
	{
		$included[] = $module;
	};
}

Loader::$namespaces = [];

require $root.'/include.php';

$leaked = array_values(array_intersect(
	['moduleId', 'list', 'modeAutoloaderError', 'documentRoot', 'namespace', 'namespacePath'],
	array_keys(get_defined_vars())
));

Check::group('зависимости');

Check::same('подключены shef.options и shef.problems — в этом порядке', $included, ['shef.options', 'shef.problems']);

Check::group('библиотеки XML');

Check::same(
	'зарегистрированы все три namespace',
	array_keys(Loader::$namespaces),
	['LanguageSpecific', 'SbWereWolf\\XmlNavigator', 'SbWereWolf\\JsonSerializable']
);

$missing = [];
foreach(Loader::$namespaces as $namespace => $path)
{
	if(!is_dir($path))
	{
		$missing[] = $namespace.' -> '.$path;
	}
}
Check::same('каждый путь — существующий каталог (корень сайта впереди)', $missing, []);

Check::group('include.php ничего не оставляет в глобальной области');

Check::same('служебные переменные autoload.php убраны', $leaked, []);

Check::finish();
