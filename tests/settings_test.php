<?php declare(strict_types=1);

/**
 * .settings.php: ссылки наружу обязаны никуда не висеть.
 *
 * Этот файл — единственное место, где модуль говорит ядру, что и откуда
 * брать: что копировать установщику, откуда грузить библиотеки разбора XML,
 * какой провайдер отдаёт страницы левого меню, какие контроллеры отвечают на
 * ajax. Все ссылаются на файлы, классы и компоненты ИМЕНЕМ, и ни одну не
 * проверяет ни php -l, ни автозагрузка.
 *
 * Цена промаха одинаково неочевидная: установщик молча ничего не скопирует,
 * автозагрузка молча не найдёт XmlNavigator, страница меню молча откроется
 * пустой. Ни одного сообщения об ошибке установки при этом не будет.
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/bitrix.php';
require_once $root.'/tests/assert.php';

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

/** Файл класса модуля по соглашению автозагрузки либо null. */
$classFile = static function(string $class) use ($root): null|string
{
	$class = ltrim($class, '\\');
	if(!str_starts_with(mb_strtolower($class), 'shef\\insync\\'))
	{
		return null;
	}

	$path = $root.'/lib/'.mb_strtolower(str_replace('\\', '/', mb_substr($class, mb_strlen('Shef\\InSync\\')))).'.php';

	return is_file($path) ? $path : null;
};

// Корень сайта — каталог над репозиторием: модуль лежит в нём, как в
// bitrix/modules, и путь модуля от корня сайта выходит «/<имя каталога>».
Application::$documentRoot = dirname($root);
$settings = require $root.'/.settings.php';

Check::group('структура файла');

Check::same('.settings.php вернул массив', is_array($settings), true);

$shape = [];
foreach($settings as $key => $section)
{
	if(!is_array($section) || !array_key_exists('value', $section) || !array_key_exists('readonly', $section))
	{
		$shape[] = $key;
	}
}

Check::same('у каждой секции есть value и readonly', $shape, []);
Check::same('обязательные модули — shef.options и shef.problems', $settings['requireModules']['value'], ['shef.options', 'shef.problems']);
Check::same('расширение xmlreader обязательно', $settings['requirePhpExt']['value'], ['xmlreader']);

Check::group('registerNamespace — откуда брать библиотеки XML');

$namespaces = $settings['registerNamespace']['value'];

Check::same('свой namespace не регистрируется — его даёт соглашение', isset($namespaces['Shef\\InSync']), false);
Check::same(
	'без shef.options — своя копия всех трёх библиотек',
	array_keys($namespaces),
	['LanguageSpecific', 'SbWereWolf\\XmlNavigator', 'SbWereWolf\\JsonSerializable']
);

$missing = [];
foreach($namespaces as $namespace => $path)
{
	if(!is_dir(Application::getDocumentRoot().$path))
	{
		$missing[] = $namespace.' -> '.$path;
	}
}
Check::same('каждый путь своей копии ведёт в каталог vendor модуля', $missing, []);
Check::same(
	'путь от корня сайта, а не зашитый /bitrix/modules',
	$namespaces['SbWereWolf\\XmlNavigator'] ?? null,
	'/'.basename($root).'/vendor/sbwerewolf/xml-navigator/src/SbWereWolf/XmlNavigator'
);

// shef.options есть, но его project-context.php падает: разбору XML без
// библиотеки хуже, чем с библиотекой из своего vendor.
$broken = tempnam(sys_get_temp_dir(), 'ctx');
file_put_contents($broken, '<?php throw new RuntimeException("сломан");');
Loader::$local['modules/shef.options/project-context.php'] = $broken;

$settings = require $root.'/.settings.php';
Check::same(
	'сломанный project-context.php — своя копия',
	$settings['registerNamespace']['value'],
	$namespaces
);

unlink($broken);
Loader::$local = [];

// Модуль вне корня сайта (симлинк, нестандартная раскладка) — путь по умолчанию.
Application::$documentRoot = '/somewhere/else';
$settings = require $root.'/.settings.php';
Check::same(
	'модуль вне корня сайта — путь по умолчанию',
	$settings['registerNamespace']['value']['SbWereWolf\\XmlNavigator'] ?? null,
	'/bitrix/modules/shef.insync/vendor/sbwerewolf/xml-navigator/src/SbWereWolf/XmlNavigator'
);

Application::$documentRoot = dirname($root);
$settings = require $root.'/.settings.php';

Check::group('installDir — что копирует установщик');

$installDir = $settings['installDir']['value'] ?? [];

Check::same('installDir не пуст', !empty($installDir), true);

$missing = [];
$wrongTarget = [];

foreach($installDir as $i => $map)
{
	$from = (string)($map['from'] ?? '');
	$to = (string)($map['to'] ?? '');

	if($from === '' || !is_dir($root.$from))
	{
		$missing[] = sprintf('запись %d: каталога %s в репозитории нет', $i, $from);
	}

	// Каталог модуля браузеру недоступен, поэтому «куда» — под /bitrix/.
	// Компоненты тоже: /local/components ядро смотрит первым, и копия там
	// перекрыла бы любую новую версию (см. getLegacyDirList() установщика).
	if(!str_starts_with($to, '/bitrix/'))
	{
		$wrongTarget[] = sprintf('запись %d: to = %s', $i, $to);
	}
}

Check::same('каждый from существует', $missing, []);
Check::same('каждый to ведёт под /bitrix/', $wrongTarget, []);
Check::same(
	'картинок больше не раскладываем — каталога нет',
	in_array('/install/images', array_column($installDir, 'from'), true),
	false
);

Check::group('installEvents — событий нет');

// shef.uiclear не будет: пункт «Импорт» в верхней панели (его событие) ушёл,
// страницы — в левом меню штатного раздела intranet.
Check::same('обработчиков событий модуль не регистрирует', $settings['installEvents']['value'], []);

Check::group('левое меню — страницы на месте');

$provider = (string)($settings['intranet.customSection']['value']['provider'] ?? '');
Check::same('провайдер страниц — существующий класс', null !== $classFile($provider), true);

$badPages = [];
foreach($settings['installLeftMenu']['value'] as $section)
{
	if(!preg_match('/^[a-z0-9]+$/', (string)$section['code']))
	{
		$badPages[] = 'раздел '.$section['code'].': код с разделителями';
	}

	foreach($section['pages'] as $page)
	{
		if(!preg_match('/^[a-z0-9]+$/', (string)$page['code']))
		{
			$badPages[] = 'страница '.$page['code'].': код с разделителями';
		}

		$component = explode('~', (string)$page['settingsRow'])[0];
		[$vendor, $name] = explode(':', $component) + [1 => ''];

		// shef.insync:redirect — не компонент, а переход; его обрабатывает
		// сам провайдер.
		if($component !== 'shef.insync:redirect' && !is_file($root.'/install/components/'.$vendor.'/'.$name.'/class.php'))
		{
			$badPages[] = 'страница '.$page['code'].': компонента '.$component.' нет';
		}

		if('' === (string)($page['title'] ?? ''))
		{
			$badPages[] = 'страница '.$page['code'].': без названия (нет перевода?)';
		}
	}
}

Check::same('коды без разделителей, компоненты существуют, названия переведены', $badPages, []);

Check::group('controllers — namespace сходится с каталогом');

$wrongControllers = [];
foreach($settings['controllers']['value']['namespaces'] as $namespace => $alias)
{
	$dir = $root.'/lib/'.mb_strtolower(str_replace('\\', '/', mb_substr(ltrim($namespace, '\\'), mb_strlen('Shef\\InSync\\'))));
	if(!is_dir($dir) || !is_file($dir.'/controller.php'))
	{
		$wrongControllers[] = $namespace;
	}
}

Check::same('каждый namespace контроллеров — каталог с controller.php', $wrongControllers, []);

Check::finish();
