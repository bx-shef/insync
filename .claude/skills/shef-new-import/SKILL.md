---
name: shef-new-import
description: Сделать импорт в Битрикс24 «коробку» или БУС на модуле shef.insync (любой свой вендор) — файл CSV или XML либо записи CRM складываются в таблицу импорта, а агент разбирает её пачками в товары, цены, остатки, разделы, элементы инфоблока, сделки, компании, смарт-процессы; с ручной загрузкой файла со страницы, примером файла, стратегией для ошибочных строк и правами. Брать на задачи «импорт прайса из CSV», «загрузка каталога из XML 1С/поставщика», «обмен файлами по расписанию», «агент, который разбирает выгрузку», «страница загрузки файла импорта», «перенести данные из CRM в смарт-процесс пачками». Не для HTTP-API внешней системы — shef-new-api-client; не для записи самих товаров, цен и значений списка — shef-use-insync-models; не для агента без таблицы импорта (пересчёт, рассылка) — shef-new-agent.
---

# Импорт на shef.insync

Операция: завести в своём модуле импорт по канону shef.insync — процесс кладёт
строки в таблицу импорта, агент разносит их по сущностям. Модуль
[shef.insync](https://github.com/bx-shef/insync) должен быть **установлен**
(он требует `shef.options` 3.0.0+ и `shef.problems` 2.0.0+).

## Как устроено — два шага

1. **Процесс** (`\Shef\InSync\Sync\IProcess`) читает источник и кладёт каждую
   запись строкой в таблицу `shef_insync_model`
   (`\Shef\InSync\Sync\Model\SyncTable`): код импорта, статус, данные строки
   в `ADDITIONAL`.
2. **Агент разбора** (наследник `\Shef\InSync\Sync\FromFile\AAgent`) берёт
   строки своего кода импорта пачками, зовёт ваш `processRow()`, успешные
   удаляет, ошибочные оставляет со статусом `F` и сообщением.

Связывает их **код импорта** — `OriginatorId`: одна и та же строка в
процессе и в агенте. Разошлись — агент молча не видит ни одной строки.

## Что выбрать

| источник | базовый класс процесса |
|---|---|
| CSV | `\Shef\InSync\Sync\FromFile\ACsvProcess` |
| XML (любой объём, потоком) | `\Shef\InSync\Sync\FromFile\AXmlProcess` |
| записи CRM (сделки, компании, смарт-процесс) | `\Shef\InSync\Sync\Crm\ACrmProcess` |

## 1. Модуль

В `.settings.php` своего модуля — зависимость, чтобы модуль поднимал
shef.insync сам и удаление shef.insync отказывало, пока вы стоите:

```php
'requireModules' => [
	'value' => ['shef.options', 'shef.problems', 'shef.insync'],
	'readonly' => true,
],
```

**Модули подключаются до слова `class`** — в каждом файле, где класс
наследует что-то из `Shef\InSync\…`: PHP разбирает `extends` при чтении
файла, а `includeModule()` в методе к этому моменту ещё не выполнялся.

## 2. Процесс — CSV

```php
<?php declare(strict_types=1);

namespace Acme\Exchange\Import;

\Bitrix\Main\Loader::includeModule('shef.insync');

use Bitrix\Main\IO;
use Bitrix\Main\Result;
use Shef\InSync\Sync\FromFile\ACsvProcess;
use Shef\InSync\Sync\IElement;

final class PriceCsv extends ACsvProcess
{
	// Код импорта: тот же — в агенте. Латиница, без точек.
	protected const OriginatorId = 'AcmePriceCsv';

	public static function getModuleId(): string { return 'acme.exchange'; }
	public static function getProcessTitle(): string { return 'Прайс из CSV'; }
	public static function getProcessDescription(): string { return 'Колонки: <var>артикул;цена;остаток</var>'; }

	public static function getEncodingFrom(): string { return 'windows-1251'; }
	public static function isUseHeader(): bool { return true; }
	public static function getDelim(): string { return ';'; }
	public static function getImportFileAccept(): string { return '.csv'; }

	// Имена колонок по порядку: ключи строки в $this->content.
	public function getMapImportFile(): array
	{
		return ['ARTICLE', 'PRICE', 'AMOUNT'];
	}

	// Строка файла -> строка таблицы импорта.
	public function init(IElement $element): Result
	{
		$element
			->setInterfaceTitle((string)$this->content['ARTICLE'])
			->setInterfaceAdditional($this->content);

		return new Result();
	}

	// Необязательно: кнопка «Скачать пример» на странице загрузки.
	public static function getDemoFile(): ?IO\File
	{
		return new IO\File(dirname(__DIR__, 2).'/install/demo/price.csv');
	}
}
```

**Внешний код строки** (`setInterfaceOriginId()`) не задан — он случайный,
и строки не сталкиваются никогда. Задан (артикул) — он уникален в пределах
кода импорта: две строки с одним артикулом в файле или повторная выгрузка до
того, как агент разобрал прошлую, — ошибка вставки. Задавайте, только если
повтор кода — действительно ошибка данных.

Для **XML** — наследник `AXmlProcess`, вместо `isUseHeader`/`getDelim`/
`getMapImportFile` один метод `getItemTag()` — тег элемента (`'item'`). В
`init()` `$this->content` — массив элемента: `n` имя, `v` значение, `a`
атрибуты, `s` вложенные.

Для **CRM** — наследник `ACrmProcess`: `getEntityTypeId()` (тип сущности CRM),
`getImportConfig()` (параметры `getItems()`: filter, select, limit),
`countItemsForImport()`, `init()`; текущая запись — `$this->row`
(`\Bitrix\Crm\Item`).

**Таблицу импорта процессу задаёт тот, кто его запускает**, — страница
загрузки делает это сама, а свой запуск (агент, cron) — явно:

```php
$process = (new PriceCsv())
	->setSyncClassSyncTable(\Shef\InSync\Sync\Model\SyncTable::class);

foreach(PriceCsv::getExistFiles('price-', 'csv') as $file)
{
	$result = $process->setFile($file)->run();
}
```

Без `setSyncClassSyncTable()` — `LogicException('Need init $syncClassSyncTable')`.

## 3. Где лежат файлы

**Вне корня сайта:** `\Shef\InSync\Main\Constants::getImportDir()` — по
умолчанию на уровень выше `DOCUMENT_ROOT` (`/home/bitrix/sh_import` на
BitrixVM), внутри `<код импорта>/` — на импорт, `copy/<код>/` — архив,
`problem/<код>/` — с проблемой. Путь строкой не собирать — только
`getImportFolder()` процесса: проект может перенести каталог
(`/bitrix/.settings_extra.php`, `shef.insync` → `importDir`). Внешний обмен
(1С, FTP) кладёт файлы в `getImportFolder()`; у имени файла должен быть
префикс — `getExistFiles('price-', 'csv')` берёт файлы, имя которых
начинается с префикса, с этим расширением.

## 4. Агент разбора

```php
<?php declare(strict_types=1);

namespace Acme\Exchange\Import;

\Bitrix\Main\Loader::includeModule('shef.insync');

use Bitrix\Main\Result;
use Shef\InSync\Agents\Entity;
use Shef\InSync\Sync\FromFile\AAgent;
use Shef\InSync\Sync\FromFile\Strategy;
use Shef\InSync\Sync\IElement;

final class PriceAgent extends AAgent
{
	protected const LimitRows = 50;   // строк за один запуск

	public static function getModuleId(): string { return 'acme.exchange'; }

	// Тот же код, что OriginatorId процесса.
	public static function getOriginatorId(): string { return 'AcmePriceCsv'; }

	public static function buildAgentsEntity(): Entity
	{
		return (new Entity(
			module: 'acme.exchange',
			name: '\\'.static::class.'::process',
			params: [],
			period: 300
		))
			->setOrigin('acme')
			->setTitle('Прайс')
			->setDescription('Разбор таблицы импорта прайса');
	}

	// Ошибочные строки помечать и больше не брать.
	protected function initStrategy(): void
	{
		$this->setStrategy(new Strategy\HideFail(static::LimitRows));
	}

	protected function processRow(IElement $row): Result
	{
		$fields = $row->getInterfaceAdditional();
		// … записать товар/цену/остаток — см. shef-use-insync-models
		return new Result();   // ошибка в Result -> строка остаётся со статусом F
	}
}
```

Стратегии: `Strategy\Simple` — ошибочные берутся снова (по умолчанию),
`Strategy\MarkFail` — помечаются `.error` и берутся снова,
`Strategy\HideFail` — помечаются и больше не берутся.

Ставить агент — из `InstallDB()` установщика своего модуля:

```php
\Bitrix\Main\Loader::includeModule('shef.insync');
\Shef\InSync\Agents\Manager::install(\Acme\Exchange\Import\PriceAgent::buildAgentsEntity());
```

Параметры агента — только строки и числа: строка агента исполняется ядром как
код, `Entity::prepareNameForDb()` их экранирует и не-скаляр не пропустит.
Отладка — `['debug' => 'Y']` в параметрах: ошибки на экран администратору,
строки по одной.

**Два экземпляра.** Агент shef.insync сам не защищён от запуска в два
процесса. Разбор долгий или крон частый — переопределите `action()` и
оберните `parent::action()` в `\Shef\Options\Main\TempFile\Pid` (как — в
shef-new-agent), иначе два процесса возьмут одни и те же строки.

Сбой агента уже пишется проблемой в журнал событий через shef.problems (тип
синхронизации), с трассировкой. Свои записи — `$this->logger`, см.
shef-use-logger.

## 5. Страница загрузки файла

Страницу загрузки добавляет **ваш** модуль — в раздел «[SH] Импорт» левого меню
shef.insync, в своём `.settings.php`:

```php
'installLeftMenu' => [
	'value' => [[
		'moduleId' => 'shef.insync',
		'code' => 'shinsync',
		'pages' => [[
			'code' => 'csvfileprice',   // без разделителей; csvfile…/xmlfile… — в слайдере
			'title' => 'Прайс из CSV',
			'sort' => 200,
			// компонент ~ класс процесса ~ ваш модуль
			'settingsRow' => 'shef.insync:import.from.file~\\Acme\\Exchange\\Import\\PriceCsv~acme.exchange',
		]],
	]],
	'readonly' => true,
],
```

Ставит страницы установщик вашего модуля: перенесите в него методы
`installLeftMenu()`/`unInstallLeftMenu()` из
[установщика shef.insync](https://github.com/bx-shef/insync/blob/main/install/index.php)
и зовите их из `InstallDB()`/`UnInstallDB()`. С `moduleId` они не создают
раздел, а добавляют страницы в раздел shef.insync.

Класс процесса обязан лежать в namespace вашего модуля (`acme.exchange` →
`Acme\Exchange\…`) — компонент загрузки другой не примет. Загружаемый файл
проходит проверку имени, расширения — из `getImportFileAccept()`.

Список агентов на странице «Статистика» — ответ на событие
`shef.insync::onComponentStatLocal`: `new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, ['items' => [PriceAgent::buildAgentsEntity()]])`
— компонент берёт `items` только из успешного ответа. Кнопки агента работают
только для агентов импорта — наследников `AAgent`.

## 6. Права

Импорт — администратору или пользователю с правом «Запись» на **ваш** модуль
(`\Shef\InSync\Main\Access::canManage('acme.exchange')`). Проверяют
компоненты и контроллеры shef.insync сами. Свои кнопки и свои ajax-действия
вокруг импорта проверяйте тем же вызовом — и в действии, не только на показ.

## Чего не делать

* не собирать путь `/upload/import/…` — каталог вне корня сайта, путь — у
  процесса;
* не писать в `shef_insync_model` SQL руками — `SyncTable`/`SyncCollection`
  (`clear()`, `getStatisticByOriginator()`);
* не класть в параметры агента массивы и объекты;
* не держать в `processRow()` состояние между строками в `static`
  внутри метода — одна переменная на класс и наследников.

## В конце

Запишите отзыв о навыке — навык `shef-feedback`.
