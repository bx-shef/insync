# shef.insync

Модуль Битрикс24 «коробки» и БУС — заготовки для синхронизаций: агенты с
учётом проблем, таблица импорта, импорт из CSV, XML и CRM, модели ORM для
инфоблоков, каталога и складов, обращение к внешнему API. Сам ничего не
синхронизирует — на нём пишутся модули обменов.

Опирается на [shef.options](https://github.com/bx-shef/options) и
[shef.problems](https://github.com/bx-shef/problems): их нужно поставить первыми.

# Что нужно для установки

| | |
|---|---|
| PHP | 8.2 и выше |
| Главный модуль Битрикс | 22.600.300 и выше |
| Модуль `shef.options` | 3.0.0 и выше |
| Модуль `shef.problems` | 2.0.0 и выше |
| Кодировка портала | **только UTF-8** |
| Расширения PHP | `mbstring`, `xmlreader` |
| Для левого меню | модуль `intranet` (Битрикс24) |

# Установка

**Порядок шагов важен:** сначала `shef.options` и `shef.problems`, потом файлы
этого модуля, потом установка в административном разделе.

## Через Composer

```bash
composer require bxshef/insync
```

Модуль развернётся в `bitrix/modules/shef.insync/` сам, вместе с ним приедут
`bxshef/options`, `bxshef/problems` и `sbwerewolf/xml-navigator`. Composer 2.2+
требует разрешить плагин раскладки — один раз, в `composer.json` проекта:

```json
{
	"config": {
		"allow-plugins": {
			"composer/installers": true
		}
	}
}
```

## Из архива

Скачайте `shef.insync.zip` со [страницы релизов](https://github.com/bx-shef/insync/releases)
и распакуйте в `bitrix/modules/`. Должно получиться
`bitrix/modules/shef.insync/` — именно через точку. Библиотеки разбора XML
лежат внутри архива, отдельно их ставить не нужно: есть они в Composer
проекта — модуль возьмёт их оттуда, нет — свою копию.

## Дальше — в административном разделе

1. **Настройки → Marketplace → Установленные решения** → «[SH] InSync» →
   **Установить**. Появятся таблица импорта `shef_insync_model` и раздел
   «[SH] Импорт» в левом меню.
2. **Настройки → Настройки продукта → Настройки модулей → [SH] InSync**: сколько
   дней хранить загруженные файлы в архиве импорта.
3. Файлы импорта лежат **вне корня сайта** — на уровень выше него: при корне
   `/home/bitrix/www` это `/home/bitrix/sh_import`. Туда же кладут файлы
   внешние обмены. Свой каталог, права и перенос `/upload/import` из 1.x —
   [безопасность](https://github.com/bx-shef/insync/blob/main/docs/security.md).
4. **Права доступа**: импортом управляет администратор либо пользователь с
   правом «Запись» на модуль импорта — [подробно](https://github.com/bx-shef/insync/blob/main/docs/security.md).

**Обновление с 1.x** — замена файлов не запускает установщик, а компоненты
1.x в `/local/components/shef.insync` перекрыли бы новые. И таблице импорта
нужен новый ключ: **до вызова `SyncTable::init()` импорт не работает** —
агенты на время обновления выключаются. Порядок —
[в процедуре проверки](https://github.com/bx-shef/insync/blob/main/docs/portal-check.md), шаг B.

# Как пользоваться

Агент, который разбирает таблицу импорта, — наследник
`\Shef\InSync\Sync\FromFile\AAgent`:

```php
final class PriceAgent extends \Shef\InSync\Sync\FromFile\AAgent
{
	public static function getModuleId(): string { return 'acme.exchange'; }
	public static function getOriginatorId(): string { return 'AcmePriceCsv'; }

	public static function buildAgentsEntity(): \Shef\InSync\Agents\Entity
	{
		return new \Shef\InSync\Agents\Entity(
			module: 'acme.exchange',
			name: '\\'.static::class.'::process',
			params: [],
			period: 600
		);
	}

	protected function processRow(\Shef\InSync\Sync\IElement $row): \Bitrix\Main\Result
	{
		$fields = $row->getInterfaceAdditional();
		// … записать товар, цену, остаток
		return new \Bitrix\Main\Result();
	}
}
```

Строки в таблицу импорта кладёт процесс — наследник `ACsvProcess`,
`AXmlProcess` или `ACrmProcess`. Сбой строки остаётся в таблице со статусом
«ошибка» и попадает проблемой в журнал событий через shef.problems.

# Документация

Вся документация — в репозитории:

* [агенты](https://github.com/bx-shef/insync/blob/main/docs/1_agents.md)
* [импорт: процессы, таблица, стратегии, модели, драйверы](https://github.com/bx-shef/insync/blob/main/docs/2_import.md)
* [API](https://github.com/bx-shef/insync/blob/main/docs/3_api.md)
* [парсинг XML](https://github.com/bx-shef/insync/blob/main/docs/4_xml.md)
* [компоненты](https://github.com/bx-shef/insync/blob/main/docs/5_components.md)
* [страницы в левом меню](https://github.com/bx-shef/insync/blob/main/docs/6_page.md)
* [опции страницы настроек](https://github.com/bx-shef/insync/blob/main/docs/7_options.md)
* [безопасность](https://github.com/bx-shef/insync/blob/main/docs/security.md)
* [запускаемые примеры](https://github.com/bx-shef/insync/blob/main/examples/README.md)
* [проверка на портале](https://github.com/bx-shef/insync/blob/main/docs/portal-check.md)
* [change log](https://github.com/bx-shef/insync/blob/main/CHANGELOG.md)

> Пример модуля обмена на shef.insync — **[shef.demosync](https://marketplace.1c-bitrix.ru/solutions/shef.demosync/)**.

# Развитие

* импорт агентом из внешнего источника через API
	* сайт — заказы
	* прайс

# Лицензия

[MIT](https://github.com/bx-shef/insync/blob/main/LICENSE)
