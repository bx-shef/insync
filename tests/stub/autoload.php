<?php

/**
 * Автозагрузка для тестов и примеров без портала.
 *
 * Правила — те же, по которым классы ищет портал:
 *
 * * Shef\InSync\Foo\Bar -> lib/foo/bar.php, путь СТРОЧНЫМИ. Так
 *   \Bitrix\Main\Loader отображает классы модуля; держит это соглашение
 *   tests/autoload_test.php;
 * * библиотеки разбора XML -> своя копия в vendor/sbwerewolf — та, что
 *   регистрирует .settings.php, когда их нет в Composer проекта.
 *
 * Классы модуля подключаются настоящие: тест, проверяющий свою копию логики,
 * ничего не проверяет.
 */

require_once __DIR__.'/bitrix.php';
require_once __DIR__.'/insync.php';
require_once __DIR__.'/options-lib.php';

spl_autoload_register(static function(string $class): void
{
	$root = dirname(__DIR__, 2);
	$vendor = $root.'/vendor/sbwerewolf';

	$map = [
		'Shef\\InSync\\' => static fn(string $rest): string => $root.'/lib/'.mb_strtolower(str_replace('\\', '/', $rest)).'.php',
		'SbWereWolf\\XmlNavigator\\' => static fn(string $rest): string => $vendor.'/xml-navigator/src/SbWereWolf/XmlNavigator/'.str_replace('\\', '/', $rest).'.php',
		'SbWereWolf\\JsonSerializable\\' => static fn(string $rest): string => $vendor.'/json-serialize-trait/src/SbWereWolf/JsonSerializable/'.str_replace('\\', '/', $rest).'.php',
		'LanguageSpecific\\' => static fn(string $rest): string => $vendor.'/language-specific/src/LanguageSpecific/'.str_replace('\\', '/', $rest).'.php',
	];

	foreach($map as $prefix => $toPath)
	{
		if(!str_starts_with($class, $prefix))
		{
			continue;
		}

		$path = $toPath(substr($class, strlen($prefix)));
		if(is_file($path))
		{
			require_once $path;
		}

		return;
	}
});
