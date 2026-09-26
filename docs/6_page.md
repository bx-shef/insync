# Страницы

Страницы модуля — в левом меню, штатным разделом `intranet`
(`Bitrix\Intranet\CustomSection`): раздел «[SH] Импорт», код `shinsync`.
Верхней панели и shef.uiclear больше нет.

| адрес | что там |
|---|---|
| `/page/shinsync/statimportlocal/` | статистика импорта — компонент `shef.insync:import.stat.local` |
| `/page/shinsync/shefinsyncmodel/` | переход к таблице `shef_insync_model` в «Производительности» (модуль `perfmon`) |

Раздел и страницы ставит установщик из `installLeftMenu` в `.settings.php`,
отдаёт — `\Shef\InSync\Integration\Intranet\CustomSectionProvider`. Страницы
модуля видны тому, кто вправе управлять импортом (см. [security.md](security.md));
переход ведёт только внутрь портала.

## Страницы модулей импорта

Страницы загрузки файла модуль импорта добавляет в тот же раздел сам — в
своём `.settings.php`, с `moduleId` = `shef.insync`:

```php
'installLeftMenu' => [
	'value' => [
		[
			'moduleId' => 'shef.insync',
			'code' => 'shinsync',
			'pages' => [
				[
					'code' => 'csvfileprice',
					'title' => 'Прайс из CSV',
					'sort' => 200,
					// компонент ~ класс импорта ~ модуль импорта
					'settingsRow' => 'shef.insync:import.from.file~\\Shef\\Demo\\FromFile\\PriceCsv~shef.demo',
				],
			],
		],
	],
	'readonly' => true,
],
```

Код страницы — без разделителей; `csvfile…` и `xmlfile…` открываются в
слайдере. Пример — модуль **[shef.demosync](https://marketplace.1c-bitrix.ru/solutions/shef.demosync/)**.

На БУС, без `intranet`, левого меню нет: страницы не ставятся, компоненты
подключаются на свою страницу обычным образом.

---

[← Компоненты](5_components.md) | [↑ Содержание](../README.md) | [Опции настроек модуля →](7_options.md)
