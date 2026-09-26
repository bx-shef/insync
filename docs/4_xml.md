# [`\Shef\InSync\TraitList\Xml`] Парсинг XML

Разбор **XML** сделан через [SbWereWolf/xml-navigator](https://github.com/SbWereWolf/xml-navigator)
(статья на [Хабре](https://habr.com/ru/post/712106/)): `XMLReader` идёт по
документу потоком, а каждый элемент с нужным тегом `HierarchyComposer`
превращает в массив — `n` имя, `v` значение, `a` атрибуты, `s` вложенные
элементы.

| трейт | что делает |
|---|---|
| ToArray | все элементы сразу массивом |
| ToYield | элементы по одному через `yield` — для средних и больших объёмов |

Тем же путём разбирает файл `\Shef\InSync\Sync\FromFile\AXmlProcess`.
Запускаемый пример — [examples/xml.php](../examples/xml.php).

> Большие xml файлы парсить не проблема. Нужно интервал разбора отрегулировать
> под объём данных, чтобы агент успел отработать. В модуле
> **[shef.demosync](https://marketplace.1c-bitrix.ru/solutions/shef.demosync/)**
> на основе импорта **ДК в смарт процесс** можно протестировать импорт файла
> размером 10 МБ.

Внешние сущности XML не раскрываются: `XMLReader` открывается без
`LIBXML_NOENT`.

Откуда берётся библиотека — из vendor проекта (Composer) или своя копия
модуля, — решает `.settings.php`; версии и почему ветка 7.2 — в
[build-and-install.md](build-and-install.md).

---

[← API](3_api.md) | [↑ Содержание](../README.md) | [Компоненты →](5_components.md)
