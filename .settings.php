<?php declare(strict_types=1);

/**
 * Настраиваемые параметры модуля
 *
 * * requireModules -> обязательные модули
 * * requirePhpExt -> обязательные расширения PHP
 * * registerAutoLoadClasses -> авто подгрузка классов
 * * registerNamespace -> авто подгрузка Namespace
 * * options -> опции устанавливаемые через окружение
 * * installEvents -> события для установки
 * * installDir -> пути установки файлов
 * * installLeftMenu -> разделы и страницы в левом меню (intranet.customSection)
 * * intranet.customSection -> провайдер страниц левого меню. Если нужно
 *   использовать из другого модуля — в installLeftMenu[] указываем moduleId
 * * controllers -> контроллеры для ajax
 *
 * @memo installLeftMenu[].pages[].settingsRow не сериализовать.
 * @memo installLeftMenu[].code и installLeftMenu[].pages[].code писать без разделителей
 * @memo installLeftMenu[].pages[].settingsRow первый параметр — компонент.
 *       Остальное смотреть в \Shef\InSync\Integration\Intranet\CustomSectionProvider
 *
 * Классы самого модуля (Shef\InSync\...) в registerNamespace не нужны: ядро
 * отображает их в lib/ по соглашению. Там только чужие — библиотеки разбора
 * XML из vendor/.
 */

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__.'/install/index.php');

// region vendor: Composer проекта либо своя копия ////
/**
 * Библиотеки разбора XML (sbwerewolf/xml-navigator и её зависимости)
 * приезжают двумя путями, и оба оставлены сознательно:
 *
 * * через Composer — пакет bxshef/insync требует sbwerewolf/xml-navigator, и
 *   тот ложится в vendor проекта;
 * * своей копией в vendor/ модуля — для установки архивом, без Composer.
 *
 * Какой из двух подключать, решает ShProjectContext из shef.options — по
 * каждому namespace отдельно: есть каталог в vendor проекта — свою копию не
 * регистрируем, классы даст автозагрузчик Composer. Нет — регистрируем свою.
 *
 * Путь модуля считается от корня сайта, потому что autoload.php приклеивает
 * к нему DOCUMENT_ROOT: модуль может стоять и в /bitrix/modules, и в
 * /local/modules. Раньше здесь был зашитый /bitrix/modules/shef.insync.
 *
 * Без shef.options (не поставлен, сломан) или при ошибке разбора
 * composer.json проекта — своя копия: разбору XML без библиотеки хуже, чем с
 * библиотекой из собственного vendor.
 */
$shInSyncNamespaces = (static function(): array
{
	$list = [
		'LanguageSpecific' => '/sbwerewolf/language-specific/src/LanguageSpecific',
		'SbWereWolf\\XmlNavigator' => '/sbwerewolf/xml-navigator/src/SbWereWolf/XmlNavigator',
		'SbWereWolf\\JsonSerializable' => '/sbwerewolf/json-serialize-trait/src/SbWereWolf/JsonSerializable',
	];

	$documentRoot = rtrim(str_replace('\\', '/', (string)Loader::getDocumentRoot()), '/');
	$moduleDir = str_replace('\\', '/', __DIR__);
	$modulePath = ($documentRoot !== '' && str_starts_with($moduleDir, $documentRoot.'/'))
		? mb_substr($moduleDir, mb_strlen($documentRoot))
		: '/bitrix/modules/shef.insync';

	$own = array_map(
		static fn(string $path): string => $modulePath.'/vendor'.$path,
		$list
	);

	$contextFile = Loader::getLocal('modules/shef.options/project-context.php');
	if(!is_string($contextFile) || !is_file($contextFile))
	{
		return $own;
	}

	try
	{
		require_once $contextFile;

		$context = new \ShProjectContext($modulePath);
		foreach($list as $namespace => $path)
		{
			$context->addNamespace(new \ShProjectNamespaceComposer($namespace, $path));
		}

		return $context->getNamespaceList();
	}
	catch(\Throwable $throwable)
	{
		return $own;
	}
})();
// endregion ////

return [
	'requireModules' => [
		'value' => [
			'shef.options',
			'shef.problems',
		],
		'readonly' => true,
	],
	'requirePhpExt' => [
		'value' => [
			'xmlreader',
		],
		'readonly' => true,
	],
	'registerAutoLoadClasses' => [
		'value' => [],
		'readonly' => true,
	],
	'registerNamespace' => [
		'value' => $shInSyncNamespaces,
		'readonly' => true,
	],
	'options' => [
		'value' => [],
		'readonly' => true,
	],
	'installEvents' => [
		'value' => [],
		'readonly' => true,
	],
	'installDir' => [
		'value' => [
			[
				'type' => 'components',
				'from' => '/install/components',
				'to' => '/bitrix/components',
				'customPathUnInstall' => [],
				'isNeedUnInstall' => true,
			],
			[
				'type' => 'js',
				'from' => '/install/js',
				'to' => '/bitrix/js',
				'customPathUnInstall' => [],
				'isNeedUnInstall' => true,
			],
		],
		'readonly' => true,
	],
	'installLeftMenu' => [
		'value' => [
			[
				'code' => 'shinsync',
				'title' => Loc::getMessage('shef.insync_MENU_TITLE'),
				'pages' => [
					[
						'code' => 'statimportlocal',
						'title' => Loc::getMessage('shef.insync_PAGE_STATISTIC'),
						'sort' => 100,
						'settingsRow' => join('~', [
							'shef.insync:import.stat.local'
						])
					],
					[
						'code' => 'shefinsyncmodel',
						'title' => Loc::getMessage('shef.insync_PAGE_ROWS_IMPORT'),
						'sort' => 1000001,
						'settingsRow' => join('~', [
							'shef.insync:redirect',
							'/bitrix/admin/perfmon_table.php?lang=ru&table_name=shef_insync_model'
						])
					]
				]
			],
		],
		'readonly' => true,
	],
	'intranet.customSection' => [
		'value' => [
			'provider' => '\\Shef\\InSync\\Integration\\Intranet\\CustomSectionProvider'
		],
		'readonly' => true,
	],
	'controllers' => [
		'value' => [
			'namespaces' => [
				'\\Shef\\InSync\\Main\\Options\\Agent' => 'agentoptions',
			],
		],
		'readonly' => true,
	]
];
