<?php declare(strict_types=1);

/**
 * Своя копия библиотек разбора XML: та, что обещана, целая и рабочая.
 *
 * Библиотеки приезжают двумя путями — Composer проекта и своей копией в
 * vendor/ для установки архивом. Две копии одного и того же расходятся
 * молча, поэтому здесь:
 *
 * 1. версии своей копии (vendor/versions.json — в копии пакетов версии нет)
 *    подходят под ограничение из composer.json;
 * 2. language-specific — той ветки, чьим namespace пользуется xml-navigator
 *    7.2: в 8.4 пакет переехал в SbWereWolf\LanguageSpecific, и связка
 *    7.2 + 8.4 падает на первом же XmlConverter;
 * 3. копия не даёт deprecation при разборе файлов — CI гоняет это на каждой
 *    версии PHP из матрицы: новая версия PHP с новыми deprecation покраснеет
 *    здесь, а не в логе портала;
 * 4. разбор работает: трейты модуля ToArray и ToYield отдают одно и то же,
 *    и внешняя сущность XML не раскрывается (XXE).
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/autoload.php';
require_once $root.'/tests/assert.php';

$vendor = $root.'/vendor/sbwerewolf';

Check::group('версии своей копии');

$versions = json_decode((string)@file_get_contents($root.'/vendor/versions.json'), true);
$composer = json_decode((string)file_get_contents($root.'/composer.json'), true);

Check::same('versions.json разобран', is_array($versions), true);
Check::same('в нём три пакета — ровно те, что лежат в vendor/', array_keys($versions ?? []), array_map(
	static fn(string $dir): string => 'sbwerewolf/'.basename($dir),
	array_values(array_filter(
		[$vendor.'/xml-navigator', $vendor.'/language-specific', $vendor.'/json-serialize-trait'],
		'is_dir'
	))
));

$constraint = (string)($composer['require']['sbwerewolf/xml-navigator'] ?? '');
Check::same('ограничение вида ~X.Y.Z', 1 === preg_match('/^~(\d+)\.(\d+)\.(\d+)$/', $constraint, $want), true);

[$major, $minor, $patch] = array_map('intval', explode('.', (string)($versions['sbwerewolf/xml-navigator'] ?? '0.0.0')) + [0, 0, 0]);
Check::same(
	sprintf('xml-navigator %d.%d.%d подходит под %s', $major, $minor, $patch, $constraint),
	$major === (int)($want[1] ?? -1) && $minor === (int)($want[2] ?? -1) && $patch >= (int)($want[3] ?? PHP_INT_MAX),
	true
);

$navigator = json_decode((string)file_get_contents($vendor.'/xml-navigator/composer.json'), true);
preg_match('/^\^(\d+)\.(\d+)$/', (string)($navigator['require']['sbwerewolf/language-specific'] ?? ''), $needLs);
[$lsMajor] = array_map('intval', explode('.', (string)($versions['sbwerewolf/language-specific'] ?? '0')));
Check::same('language-specific подходит под требование xml-navigator', $lsMajor, (int)($needLs[1] ?? -1));
Check::same('…и лежит в том namespace, что зовёт xml-navigator', is_file($vendor.'/language-specific/src/LanguageSpecific/ArrayHandler.php'), true);
Check::same(
	'xml-navigator зовёт именно LanguageSpecific\\',
	str_contains((string)file_get_contents($vendor.'/xml-navigator/src/SbWereWolf/XmlNavigator/Navigation/XmlElement.php'), 'use LanguageSpecific\\'),
	true
);

Check::group('без deprecation — в том, что модуль загружает');

// Модуль зовёт из библиотеки один класс — HierarchyComposer (AXmlProcess,
// трейты ToArray и ToYield), а тот тянет ещё три. Разбор идёт в отдельном процессе с
// error_reporting=-1: deprecation, как и в логе портала, появляется при
// загрузке файла, и увидеть её можно только так.
//
// В остальной библиотеке deprecation на PHP 8.4 есть: XmlConverter,
// FastXmlToArray, XmlElement («Implicitly marking parameter as nullable»).
// Модуль их не загружает; ветки xml-navigator без них требуют PHP 8.4, а
// модуль поддерживает 8.2. См. CLAUDE.md, «Известные шероховатости».
$probe = <<<'PHP'
require $argv[1].'/tests/stub/autoload.php';
final class Probe { use \Shef\InSync\TraitList\Xml\ToArray; }
Probe::toArrayConvert('<r><item a="1"><b>x</b></item></r>', 'item');
foreach(get_included_files() as $file)
{
	if(str_contains($file, '/vendor/'))
	{
		echo 'LOADED ', basename($file), PHP_EOL;
	}
}
PHP;

$output = [];
exec(
	sprintf(
		'%s -d error_reporting=-1 -d display_errors=1 -r %s %s 2>&1',
		escapeshellarg(PHP_BINARY),
		escapeshellarg($probe),
		escapeshellarg($root)
	),
	$output,
	$code
);

Check::same('разбор отработал', $code, 0);
Check::same(
	'загружено то, что тянет HierarchyComposer',
	array_values(array_filter($output, static fn(string $line): bool => str_starts_with($line, 'LOADED '))),
	['LOADED HierarchyComposer.php', 'LOADED ElementComposer.php', 'LOADED Notation.php', 'LOADED ElementExtractor.php']
);
Check::same(
	'ни одного deprecation на PHP '.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION,
	array_values(array_filter($output, static fn(string $line): bool => 1 === preg_match('/\b(Deprecated|Warning|Notice)\b/', $line))),
	[]
);

$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($vendor));
foreach($iterator as $file)
{
	if($file->isFile() && 'php' === $file->getExtension())
	{
		$files[] = $file->getPathname();
	}
}

$broken = [];
foreach($files as $file)
{
	exec(sprintf('%s -l %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($file)), $lint, $lintCode);
	if(0 !== $lintCode)
	{
		$broken[] = mb_substr($file, mb_strlen($root) + 1);
	}
}

Check::same('файлов в копии больше двадцати', count($files) > 20, true);
Check::same('вся копия разбирается', $broken, []);

Check::group('лицензии на месте');

Check::same('language-specific — MIT', str_contains((string)@file_get_contents($vendor.'/language-specific/LICENSE'), 'MIT'), true);
Check::same('json-serialize-trait — MIT', str_contains((string)@file_get_contents($vendor.'/json-serialize-trait/LICENSE.md'), 'MIT'), true);
Check::same('xml-navigator — Apache-2.0 по composer.json', $navigator['license'] ?? null, 'Apache-2.0');

Check::group('разбор XML трейтами модуля');

final class XmlProbe
{
	use \Shef\InSync\TraitList\Xml\ToArray;
	use \Shef\InSync\TraitList\Xml\ToYield;
}

$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<catalog>
	<item id="1"><name>Стол</name><price>10.5</price></item>
	<item id="2"><name>Стул</name><price>3</price></item>
</catalog>
XML;

$rows = XmlProbe::toArrayConvert($xml, 'item');
Check::same('два элемента', count($rows), 2);
Check::same('ToYield отдаёт то же, что ToArray', iterator_to_array(XmlProbe::toYieldConvert($xml, 'item'), false), $rows);
Check::same('атрибут на месте', str_contains(json_encode($rows[1], JSON_UNESCAPED_UNICODE), '"2"'), true);
Check::same('кириллица на месте', str_contains(json_encode($rows[0], JSON_UNESCAPED_UNICODE), 'Стол'), true);

$secret = tempnam(sys_get_temp_dir(), 'xxe');
file_put_contents($secret, 'СЕКРЕТ');
$xxe = '<?xml version="1.0"?><!DOCTYPE r [<!ENTITY s SYSTEM "file://'.$secret.'">]><r><item><name>&s;</name></item></r>';
$rows = XmlProbe::toArrayConvert($xxe, 'item');
unlink($secret);
Check::same('внешняя сущность не раскрыта', str_contains(json_encode($rows, JSON_UNESCAPED_UNICODE), 'СЕКРЕТ'), false);

Check::finish();
