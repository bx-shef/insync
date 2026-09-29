---
name: shef-use-insync-models
description: Записать данные в Битрикс24 «коробку» или БУС через модели и драйверы shef.insync, любой свой вендор — элемент и раздел инфоблока по внешнему коду (XML_ID) с родителем, картинкой и свойствами, значение свойства-списка (найти или создать), торговые данные товара (вес, НДС, закупочная цена), цену по типу цены, остаток по складу, склад. Брать на задачи «создать или обновить товар по артикулу из выгрузки», «проставить цену и остаток», «найти раздел по коду 1С и положить в него элемент», «значение списка „Цвет“ по XML_ID — нет, так создать», «записать склад». Обычно — внутри processRow() агента импорта. Не для построения самого импорта (таблица, агент, загрузка файла) — shef-new-import; не для разбора входных строк вида «1 234,50» — shef-options-traits (PrepareFields); не для сделок и смарт-процессов — там штатная фабрика CRM ядра.
---

# Модели и драйверы shef.insync

Операция: записать разобранную строку импорта в сущности портала — элемент и
раздел инфоблока, значение списка, товар, цену, остаток, склад — через ORM и
драйверы [shef.insync](https://github.com/bx-shef/insync), а не старым API
вперемешку с ORM.

**Модуль подключается до слова `class`** в каждом файле, где класс наследует
модель shef.insync:

```php
\Bitrix\Main\Loader::includeModule('shef.insync');
```

## Инфоблок: своя таблица на свой инфоблок

ORM ядра собирает сущность элемента без учёта инфоблока. Поэтому на каждый
инфоблок заводится **своя** пара классов — наследники моделей shef.insync — и
ей задаётся ID инфоблока:

```php
namespace Acme\Exchange\Model;

\Bitrix\Main\Loader::includeModule('shef.insync');

use Shef\InSync\Sync\Model\IBlock\Element\ElementTable;
use Shef\InSync\Sync\Model\IBlock\Element\Element;

final class GoodsTable extends ElementTable
{
	public static function getObjectClass(): string { return Goods::class; }
}

final class Goods extends Element
{
	public static $dataClass = GoodsTable::class;
}

GoodsTable::setIblockId(12);   // один раз до работы — например, из настроек модуля
```

Без `setIblockId()` — `LogicException('Not set iblockId at …')`. ID инфоблока
держите в настройках модуля (shef-new-option), не в коде.

То же для разделов — `\Shef\InSync\Sync\Model\IBlock\Section\SectionTable` и
`\Shef\InSync\Sync\Model\IBlock\Section\Section`.

## Элемент и раздел по внешнему коду

```php
$goods = GoodsTable::getByXmlId($fields['ARTICLE'])
	?? Goods::build([
		'XML_ID' => $fields['ARTICLE'],
		'NAME' => $fields['NAME'],
		'ACTIVE' => true,
	]);

$result = $goods->save();
```

* `getByXmlId()` — один элемент по `XML_ID` (выбираются `ID` и `XML_ID`,
  остальное — вторым аргументом);
* `build()` — новый объект: пустое имя даёт неактивный элемент с именем
  `Empty`, `CODE` — транслит имени, уникальный в инфоблоке (`checkCode()`);
* раздел: `$section->setParentByXmlId($parentXmlId)` — пустой код снимает
  родителя, несуществующий — `ArgumentException` с этим кодом;
* картинка: `$goods->configurePicture('DETAIL_PICTURE', \CFile::MakeFileArray($path))`
  — через старый API, ORM картинки не пишет; только **после** `save()`: у
  элемента уже должен быть ID.

## Свойства по коду

Трейт `\Shef\InSync\Sync\Model\IBlock\Element\PropertyTrait` — в драйвер или
процесс, где задан `static::$dataClass` (ваш `GoodsTable`):

```php
use \Shef\InSync\Sync\Model\IBlock\Element\PropertyTrait;
protected static string $dataClass = GoodsTable::class;

$color = $this->getPropertyEnum('COLOR');
$enum = $this->getEnum($color, 'Красный', 'red');   // найти по XML_ID, нет — создать
$goods->oldApiUpdateProps(['COLOR' => $enum->getId()]);
```

Значения списка сравниваются без учёта регистра. Свойства нет в инфоблоке —
`ArgumentException` с кодом свойства и ID инфоблока. Описание свойства
читается один раз на объект.

## Каталог: драйверы

| драйвер | `save(array $primary, array $params)` |
|---|---|
| `\Shef\InSync\Sync\Model\Catalog\Driver\Product` | `['ID' => товар]`, поля торгового каталога: `WEIGHT`, `VAT_ID`, `PURCHASING_PRICE`, … |
| `\Shef\InSync\Sync\Model\Catalog\Driver\Price` | `['PRODUCT_ID' => …, 'CATALOG_GROUP_ID' => тип цены]`, `['PRICE' => …, 'CURRENCY' => 'BYN']`; пишутся только `PRICE`, `PRICE_SCALE` (нет — равна `PRICE`), `CURRENCY`, прочие ключи не пишутся |
| `\Shef\InSync\Sync\Model\Catalog\Driver\Amount` | `['PRODUCT_ID' => …]`, `['STORE_ID' => …, 'AMOUNT' => …]` |

```php
$price = (new \Shef\InSync\Sync\Model\Catalog\Driver\Price())->save(
	['PRODUCT_ID' => $goods->getId(), 'CATALOG_GROUP_ID' => 1],
	['PRICE' => $fields['PRICE'], 'CURRENCY' => 'BYN']
);
```

Каждый возвращает `Result`: нет обязательного ключа — ошибка, а не
исключение; запись есть — обновление, нет — добавление. `Product` и `Price`
идут через модели каталога ядра (`\Bitrix\Catalog\Model\*`), потому что API v2
ядро помечает нестабильным.

**Складской учёт включён — остатки только документами.** `Amount` пишет
остаток напрямую; при складском учёте ядро такие правки не признаёт —
проводите документы прихода.

Склад: `\Shef\InSync\Sync\Model\Store\Store::build(['XML_ID' => …, 'TITLE' => …])->save()`,
поиск — `\Shef\InSync\Sync\Model\Store\StoreTable::getList(['filter' => ['=XML_ID' => $xmlId]])`.

## Внутри агента импорта

Модели зовут из `processRow()` агента разбора (shef-new-import). Ошибку —
в `Result` (строка останется со статусом «ошибка» и сообщением), исключение
агент тоже поймает, но с трассировкой в журнал: для ожидаемых проблем
(«нет раздела») возвращайте `Result` с ошибкой.

## Чего не делать

* не мешать в одной строке ORM-объект и `CIBlockElement::Update` по тем же
  полям: объект не узнает о правке. Старый API — только через
  `oldApiUpdate()`/`oldApiUpdateProps()` объекта;
* не читать ID инфоблока строкой из кода;
* не писать цену в `\Bitrix\Catalog\PriceTable` напрямую — драйвер `Price`
  знает про `PRICE_SCALE` и существующую цену.

## В конце

Запишите отзыв о навыке — навык `shef-feedback`.
