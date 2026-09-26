# Импорт
Реализован механизм `\Shef\InSync\Sync\IProcess` импорта через таблицу импорта `\Shef\InSync\Sync\Model\SyncTable`.

> Пример смотреть в модуле **[shef.demosync](https://marketplace.1c-bitrix.ru/solutions/shef.demosync/)**

Очистить программно таблицу импорта можно так:
```php
<?php
//title: Model\SyncCollection::clear()
  \Bitrix\Main\Loader::includeModule('shef.insync');
  $syncCollection = \Shef\InSync\Sync\Model\SyncTable::createCollection();
  $importCode = 'ShefDemosyncFromFileCsv';
  $syncCollection->clear($importCode);
  $response = $syncCollection->getStatistic();
  \Shef\Problems\Logger::PrHtml->getLogger()->debug($response);
?>
```

Суть на примере импорта через файл:

1. Обработчик `\Shef\InSync\Sync\IProcess` забирает данные и складывает в таблицу
2. Обработчик `\Shef\InSync\Sync\FromFile\AAgent` берет данные из таблицы, и разносит по сущностям, используя стратегию  `\Shef\InSync\Sync\FromFile\Strategy\IStrategy` выборки и обработки плохих элементов

|                                     Класс | Описание                               | Примечание                      |
|------------------------------------------:|:---------------------------------------|:--------------------------------|
|  `\Shef\InSync\Sync\FromFile\ACsvProcess` | Абстракция для импорта CSV             | Можно передать несколько файлов |
| `\Shef\InSync\Sync\FromFile\AFileProcess` | Абстракция для Импорта XML             | Можно передать несколько файлов |
|       `\Shef\InSync\Sync\Crm\ACrmProcess` | Абстракция для обработки сущностей CRM | Проходится по типу сущности CRM |

## Папки для хранения файлов
Файлы нужно сохранять в папку указанную в `\Shef\InSync\Sync\FromFile\AFileProcess::getImportFolder`.

По умолчанию это **/upload/import/{getOriginatorId}/**. 

У файла должен быть суфикс. Пример `cart-xxx.xml`.

> Картинки стоит так же выкладывать в эту папку, например в подпапку **img**.
> 
> Пример реализации `\Shef\Demosync\FromFile\Xml\IBlock\Element\XmlProcess::getImagePath`.

После обработки файл перемещается в папку **/upload/import/copy/{getOriginatorId}/**.
> В настройках модуля можно указать сколько дней хранить здесь файл.
> 
> По-умолчанию: 3 дня
> 
> Агент может переопределить это время.

Если будет при обработке проблема, то файл будет перемещается в папку **/upload/import/problem/{getOriginatorId}/**.

## Модели
В модуле приследуется цель работать со сущностями Битрикс только через ORM.

По этой причине созданы необходимые для работы модели и аннотации к ним.
Все остальные модели/аннотации в Битрикс уже присутствуют.

> Аннотацию для модели собирать через [механизм Бирикс](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=11733):
> ```shell
> php bitrix.php orm:annotate -m shef.insync /home/bitrix/www/bitrix/modules/shef.insync/meta/orm.php
> ```
>
> Или использовать `shef-cli`, если он доступен:
> ```shell
> shef-cli module:annotate shef.insync
> ```
>
> Потом по своим сущностям разнести в `model/meta/orm.php`

### [`\Shef\InSync\Sync\Model\SyncTable`] Таблица синхронизации
Работает через модель `EO_`,  поддерживает interface `\Shef\InSync\Sync\IElement`. 

Пришлось дружить interface и магические мотоды `EO_` через фасад.

### [`\Shef\InSync\Sync\Model\Store`] Склады
Работает через модель `EO_`, для работы со складами (Название, адрес и тп)

### [`\Shef\InSync\Sync\Model\IBlock\*`] Инфоблоки
Добавили в `*Table` поддержку IblockId.

Работает через модель `EO_`, для работы с сущностями инфоблоков, поддерживает интерфейсы `Model\IBlock\IBlockId`, `Model\IBlock\IFixGetList`.

* разделы `Model\IBlock\Section`
* элементы `Model\IBlock\Element`
* перечисления для свойства типа список `Model\IBlock\PropertyEnumeration`

> Для использования аннотаций на конкретный инфоблок нужно:
>
> * задать код ORM в инфоблоке
> * унаследоваться от `\Shef\InSync\Sync\Model\IBlock\*\*Table`
> * переопределить в нем свои классы для `EO_`
> * построить аннотацию для своего класса `*Table`


### [`\Shef\InSync\Sync\Model\Catalog\ProductTable`] Каталог
Работает через модель `EO_`. Наследник `\Bitrix\Catalog\ProductTable`.

Добавлена связь со ставкой НДС `SH_VAT` с `\Bitrix\Catalog\VatTable`.

### [`Shef\InSync\Sync\Model\Hl\Demo`] HL

Приведен пример как должена выглядеть модель.

Алгоритм ее постраения:

* Делаем HL в админке руками/программно.
* Идем в таблицу БД и [генерируем ORM](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=2410&LESSON_PATH=3913.3516.5748.2410).
* Далее делаем [аннотацию](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=11733&LESSON_PATH=3913.3516.5748.11733).
* И допиливаем руками переводя названий колонок.

## Драйверы
Надстройка над штатным API для чтения/записи данных.

### [`\Shef\InSync\Sync\Model\Catalog\Driver\*`] для Bitrix\Catalog

> При работе со складски учетом - нужно импортировать остатки через документы складского учета

Модуль битрикса `catalog` использует _модели_ и апи _v2_. Тк. на текущий момент для _v2_ написано в коде что оно не стабильно, используем **модели**.

Интерфейс `Driver\ICatalogModel` указывает что используется модель каталога.

|            Класс | Интерфейс              | Описание                                                                                                    |
|-----------------:|:-----------------------|:------------------------------------------------------------------------------------------------------------|
| `Driver\Product` | `Driver\ICatalogModel` | Работа с данными по товару  -> вес, габариты, единица измерения, <br/>цена закупки, НДС, общий остаток и тп |
|   `Driver\Price` | `Driver\ICatalogModel` | Работа с ценами на товары                                                                                   |
|  `Driver\Amount` |                        | Остатки по складам                                                                                          |


[← Агенты](docs/1_agents.md) | [↑ Содержание](README.md) | [API →](docs/3_api.md)