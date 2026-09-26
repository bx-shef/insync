<?php declare(strict_types=1);

/**
 * Фронт: расширения, которые просят шаблоны, существуют там, где их ищет ядро.
 *
 * Каталог модуля браузеру недоступен, поэтому install/js установщик кладёт
 * в /bitrix/js (карта в .settings.php, ключ installDir). Ядро находит
 * расширение по имени «<каталог>.<подкаталог>» в /bitrix/js. Разойдутся имя
 * в шаблоне и раскладка — скрипт молча не подключится, ошибки не будет: на
 * странице это выглядит как «кнопки не работают», а не как поломка установки.
 *
 * И отдельно — shef.uiclear: его расширения (bootstrap, карточки, загрузчик
 * BX.ShUiClear.Loader) шаблоны звали до 2.0.0, а модуля не будет. Расширение,
 * которого нет, ядро пропускает молча, а BX.ShUiClear в скрипте — это
 * TypeError на первой же кнопке.
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/bitrix.php';
require_once $root.'/tests/assert.php';

$settings = require $root.'/.settings.php';

$jsMap = array_values(array_filter(
	$settings['installDir']['value'],
	static fn(array $map): bool => $map['from'] === '/install/js'
));

Check::group('раскладка');

Check::same('install/js копируется ровно одной записью', count($jsMap), 1);
Check::same('в /bitrix/js', $jsMap[0]['to'] ?? null, '/bitrix/js');

/** Файлы шаблонов и страниц, которые зовут Extension::load(). */
$sources = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/install'));
foreach($iterator as $file)
{
	if($file->isFile() && in_array($file->getExtension(), ['php', 'js', 'css'], true))
	{
		$sources[] = $file->getPathname();
	}
}
foreach(glob($root.'/lib/{,*/,*/*/,*/*/*/,*/*/*/*/}*.php', GLOB_BRACE) ?: [] as $file)
{
	$sources[] = $file;
}
sort($sources);

$requested = [];
foreach($sources as $file)
{
	if(!str_ends_with($file, '.php'))
	{
		continue;
	}

	$text = (string)file_get_contents($file);
	if(!preg_match_all('/Extension::load\(\s*\[(.*?)\]\s*\)/s', $text, $calls))
	{
		continue;
	}

	foreach($calls[1] as $list)
	{
		preg_match_all("/'([a-z0-9._-]+)'/", $list, $names);
		foreach($names[1] as $name)
		{
			$requested[$name][] = mb_substr($file, mb_strlen($root) + 1);
		}
	}
}

Check::same('расширения нашлись', count($requested) > 5, true);

Check::group('свои расширения на месте');

$broken = [];
foreach($requested as $extension => $files)
{
	if(!str_starts_with($extension, 'shef-insync.'))
	{
		continue;
	}

	$config = $root.$jsMap[0]['from'].'/'.str_replace('.', '/', $extension).'/config.php';
	if(!is_file($config))
	{
		$broken[] = $extension.': нет '.mb_substr($config, mb_strlen($root));
		continue;
	}

	if(!defined('B_PROLOG_INCLUDED'))
	{
		define('B_PROLOG_INCLUDED', true);
	}

	$description = require $config;
	foreach(array_merge((array)($description['js'] ?? []), (array)($description['css'] ?? [])) as $asset)
	{
		if(!is_file(dirname($config).'/'.$asset))
		{
			$broken[] = $extension.': нет файла '.$asset;
		}
	}
}

Check::same('config.php и файлы каждого своего расширения существуют', $broken, []);

Check::group('shef.uiclear не зовут');

$foreign = [];
foreach($requested as $extension => $files)
{
	if(str_starts_with($extension, 'shef-') && !str_starts_with($extension, 'shef-insync.'))
	{
		$foreign[] = $extension.' ← '.implode(', ', array_unique($files));
	}
}
Check::same('расширений соседних модулей шаблоны не просят', $foreign, []);

$uiclear = [];
foreach($sources as $file)
{
	if(preg_match('/ShUiClear|shef-uiclear|Shef\\\\UiClear/', (string)file_get_contents($file)))
	{
		$uiclear[] = mb_substr($file, mb_strlen($root) + 1);
	}
}
Check::same('ни BX.ShUiClear, ни \\Shef\\UiClear в поставке', $uiclear, []);

Check::group('минифицированных копий нет');

// Устаревший .min побеждает исходник молча: ядро берёт его, если он есть.
// Правили script.js — а на портал уезжал бы старый script.min.js.
$minified = array_values(array_filter($sources, static fn(string $file): bool => 1 === preg_match('/\.min\.(js|css)$/', $file)));
Check::same('*.min.js и *.min.css в install/ нет', $minified, []);

Check::finish();
