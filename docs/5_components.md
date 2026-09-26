# Компоненты

| компонент | что делает | кому |
|---|---|---|
| `shef.insync:import.from.file` | загрузка файла в импорт руками, пример файла, результат разбора | администратор или право «Запись» на модуль импорта |
| `shef.insync:import.stat.local` | статистика таблицы импорта, очистка загрузки, запуск и остановка агентов импорта | администратор или право «Запись» на shef.insync; кнопки агента — права на модуль агента |

Компоненты ставятся в `/bitrix/components/shef.insync/`. До 2.0.0 — в
`/local/components/shef.insync/`; установщик эту копию убирает, иначе она
перекрывала бы новую.

## `import.from.file`

Параметры — `MODULE` (id модуля импорта) и `CLASS` (наследник
`\Shef\InSync\Sync\FromFile\AFileProcess` из namespace этого модуля). Их
кладёт в страницу левого меню модуль импорта — см. [Страницы](6_page.md).

Загружаемый файл проходит проверку имени: без пути, без исполняемых
расширений, и если класс импорта объявил `getImportFileAccept()` с
расширениями (`.csv`, `.xml,.zip`) — только с ними. Подробно — в
[security.md](security.md).

## `import.stat.local`

Список агентов собирает событие `shef.insync::onComponentStatLocal`: модуль
импорта отвечает на него `items` — массивом `\Shef\InSync\Agents\Entity`.
Страница обновляется сама — модуль шлёт pull-команду `reload` после каждого
шага импорта (`\Shef\InSync\Sync\Integration\Manager::sendPullForImportStatLocal()`).

## Разметка

Только штатные расширения ядра — `ui.forms`, `ui.buttons`, `ui.alerts`,
`ui.notification`, загрузчик `main.loader`, — и несколько правил сетки в
`style.css` шаблона. Bootstrap, карточки и загрузчик shef.uiclear ушли вместе
с зависимостью.

Расширение `shef-insync.ui-anchors` открывает страницы импорта
(`/page/shinsync/csvfile…/`, `/page/shinsync/xmlfile…/`) в слайдере.

---

[← Парсинг XML](4_xml.md) | [↑ Содержание](../README.md) | [Страницы →](6_page.md)
