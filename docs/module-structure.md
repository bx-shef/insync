# Раскладка репозитория

Файл про устройство репозитория. Опорные точки модуля — в [CLAUDE.md](../CLAUDE.md),
процесс — в [CONTRIBUTING.md](../CONTRIBUTING.md), сборка — в
[build-and-install.md](build-and-install.md).

## Модуль лежит в корне, и это вынужденно

Composer разворачивает в целевой каталог **корень пакета целиком** и подкаталоги
выбирать не умеет. Поэтому `lib/`, `install/`, `lang/` лежат прямо в корне
репозитория, рядом с `build.sh` и `.github/`.

Плата за это — два списка в шапке `build.sh`:

* **SHIP** — уезжает на портал и в Composer-пакет;
* **KEEP** — остаётся в репозитории.

**Файл, не попавший ни в один список, роняет сборку.** Тот же список продублирован
в `.gitattributes` через `export-ignore`; списки обязаны совпадать, сверяется
автоматически, см. `check_gitattributes`.

## Что где лежит

| путь | | что это |
|---|---|---|
| `install/index.php` | SHIP | установщик, класс `shef_insync extends CModule`: таблица импорта, левое меню, раскладка компонентов и js |
| `install/version.php` | SHIP | `VERSION` и `VERSION_DATE` — источник истины о версии |
| `install/components/shef.insync/` | SHIP | компоненты `import.from.file` и `import.stat.local`; установщик раскладывает их в `/bitrix/components` |
| `install/js/shef-insync/` | SHIP | расширение `shef-insync.ui-anchors` (страницы импорта в слайдере); установщик раскладывает в `/bitrix/js` |
| `.settings.php` | SHIP | зависимости, раскладка, левое меню, контроллеры, откуда брать библиотеки XML |
| `include.php` | SHIP | точка входа: `autoload.php` |
| `autoload.php` | SHIP | подключает `shef.options`, `shef.problems` и регистрирует библиотеки XML |
| `default_option.php` | SHIP | умолчания настроек |
| `options.php`, `options_conf.php` | SHIP | страница настроек на `ShOptionsConfig` из `shef.options` |
| `lib/` | SHIP | классы модуля, **имена файлов строго строчными** |
| `lang/ru/` | SHIP | языковые файлы, зеркалят структуру `lib/` |
| `meta/orm.php` | SHIP | аннотации ORM для IDE; никем не подключается |
| `vendor/sbwerewolf/` | SHIP | своя копия библиотек разбора XML для установки архивом; версии — в `vendor/versions.json` |
| `README.md`, `CHANGELOG.md`, `LICENSE` | SHIP | |
| `composer.json` | SHIP | манифест пакета `bxshef/insync` |
| `docs/` | KEEP | вся документация |
| `build.sh` | KEEP | сборка и проверки |
| `tests/` | KEEP | тесты и заглушки ядра |
| `examples/` | KEEP | запускаемые примеры |
| `.claude/skills/` | KEEP | навыки агента: навыки линейки — **копия** из `bx-shef/options` (`sync.sh --to`), навыки про shef.insync — свои, перечислены в `LOCAL.MANIFEST` (`sync.sh --local`) |
| `.github/` | KEEP | CI и релиз |
| `CONTRIBUTING.md`, `CLAUDE.md` | KEEP | процесс и памятка агенту |
| `.gitattributes`, `.gitignore` | KEEP | |

## Что где в `lib/`

| каталог | что там |
|---|---|
| `agents/` | `AAgent` — базовый агент, `Entity` — описание агента, `Manager` — установка, запуск, остановка, поиск |
| `api/` | `AConnector` — обращение к внешнему API по HTTP, `Headers` — маска секретных заголовков для лога |
| `sync/` | интерфейсы импорта (`IProcess`, `IElement`, …), `EStatus`, `AProcess` |
| `sync/fromfile/` | импорт файлов: `AFileProcess`, `ACsvProcess`, `AXmlProcess`, агент разбора `AAgent`, стратегии `Strategy\*` |
| `sync/crm/` | `ACrmProcess` — импорт из сущностей CRM |
| `sync/model/` | таблица импорта `SyncTable`, модели инфоблоков, каталога, складов, пример HL |
| `sync/integration/` | `Manager` — push-обновление страницы статистики |
| `main/` | `Constants`, `Utils`, `Access` — кто управляет импортом |
| `main/options/` | опции страницы настроек для модулей импорта: агент и пошаговый импорт |
| `integration/intranet/` | провайдер страниц левого меню |
| `traitlist/` | трейты: класс таблицы импорта, разбор XML |

## Нижний регистр в `lib/` обязателен

`Bitrix\Main\Loader` отображает класс в путь **строчными**, разбирая первые два
сегмента namespace как id модуля: `Shef\InSync\Main\Utils` ищется как
`bitrix/modules/shef.insync/lib/main/utils.php`. Поэтому свой namespace в
`registerNamespace` не нужен — там только библиотеки XML.

На macOS заглавная буква сходит с рук, на боевом Linux класс просто не найдётся.
Проверяется в `build.sh`, `check_lowercase`, и в `tests/autoload_test.php`.

У `vendor/` соглашение своё — PSR-4 с заглавными, путь задаёт `.settings.php`.

## Компоненты и фронт

```
install/components/shef.insync/   -> /bitrix/components/shef.insync/
install/js/shef-insync/ui-anchors -> /bitrix/js/shef-insync/ui-anchors/
```

Каталог модуля браузеру недоступен, поэтому компоненты и js копирует
установщик — карта в `.settings.php`, ключ `installDir`. Расширение находится
ядром по имени `shef-insync.ui-anchors`: каталог через дефис — требование имён
расширений. Сходимость с раскладкой проверяет `tests/assets_test.php`.

До 2.0.0 компоненты ложились в `/local/components/shef.insync`. Ядро смотрит
туда первым, и оставшаяся копия перекрыла бы новую, поэтому установщик убирает
её и при установке, и при удалении.

**Минифицированных копий (`*.min.js`, `*.min.css`) нет.** Ядро берёт `.min`,
если он есть, — и устаревший `.min` молча побеждал бы исправленный исходник.
Отсутствие проверяет `tests/assets_test.php`. `*.min.min.*` — мусор сборщиков,
его отсекает `.gitignore`.

Раскладка страниц — штатные `ui.*` и свои несколько правил в `style.css`
шаблона. Сетка и карточки shef.uiclear ушли вместе с зависимостью.

## Документация не едет на портал

Документация живёт в репозитории. В поставке остаётся только `README.md` — как
readme пакета, — и все ссылки из него ведут на GitHub.
