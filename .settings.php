<?php declare(strict_types=1);

/**
 * Настраиваемые параметры модуля
 *
 * * requireModules -> обязательные модулей
 * * requirePhpExt -> обязательные расширения PHP
 * * registerAutoLoadClasses -> авто подгрузка классов
 * * registerNamespace -> авто подгрузка Namespace
 * * options -> опции устанавливаемые через окружение
 * * installEvents -> события для установки
 * * installDir -> пути установки файлов
 * * controllers -> контроллеры для ajax
 * * ui.entity-selector -> провайдер для диалога выбора сущностей
 * * intranet.customSection -> указывает провайдер страниц левого меню Если нужно использовать из другого модуля - то в installLeftMenu[] указываем moduleId
 * * installLeftMenu -> разделы и страницы в левом меню
 *
 * @memo installLeftMenu[].pages[].settingsRow не серилизовать.
 * @memo installLeftMenu[].code и installLeftMenu[].pages[].code писать без разделителей
 * @memo installLeftMenu[].pages[].settingsRow первый параметр компонет. Остальное смотреть в контроллере intranet.customSection
 *
 */

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(Application::getDocumentRoot().'/bitrix/modules/shef.insync/install/index.php');

return [
	'requireModules' => [
		'value' => [
			'shef.options',
			'shef.uiclear',
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
		'value' => [
			'LanguageSpecific' => '/bitrix/modules/shef.insync/vendor/sbwerewolf/language-specific/src/LanguageSpecific',
			'SbWereWolf\\XmlNavigator' => '/bitrix/modules/shef.insync/vendor/sbwerewolf/xml-navigator/src/SbWereWolf/XmlNavigator',
			'SbWereWolf\\JsonSerializable' => '/bitrix/modules/shef.insync/vendor/sbwerewolf/json-serialize-trait/src/SbWereWolf/JsonSerializable',
		],
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
				'to' => '/local/components',
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
			[
				'type' => 'images',
				'from' => '/install/images',
				'to' => '/bitrix/images',
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