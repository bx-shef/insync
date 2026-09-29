# [`\Shef\InSync\Agents`] Агенты

Агент импорта — наследник `\Shef\InSync\Agents\AAgent`. Описание агента для
`b_agent` — `\Shef\InSync\Agents\Entity`, установку и управление берёт на себя
`\Shef\InSync\Agents\Manager`.

> Пример смотреть в модуле **[shef.demosync](https://marketplace.1c-bitrix.ru/solutions/shef.demosync/)**.
> Строку агента без портала показывает [examples/agent.php](../examples/agent.php).

## Классы

| класс | что делает |
|---|---|
| AAgent | базовый агент: служебный пользователь, логгер проблем shef.problems, отладка, модули |
| Entity | описание агента: модуль, имя, параметры, период; строка для `b_agent` |
| Manager | установка, запуск, остановка, удаление, поиск агентов |

## `AAgent`

* `\Shef\InSync\Agents\AAgent::process()` — то, что зовёт ядро: подключает
  модули, встаёт служебным пользователем (контекст `getContext()`), зовёт
  `action()` и возвращает строку следующего запуска либо пустую строку, если
  агент попросил остановиться (`setIsNeedStop(true)`);
* `\Shef\InSync\Agents\AAgent::action()` — работа агента, пишете вы;
* `\Shef\InSync\Agents\AAgent::buildAgentsEntity()` — описание агента, пишете вы;
* `\Shef\InSync\Agents\AAgent::getName()` — строка агента для следующего запуска.

Параметр `debug = Y` включает режим отладки: ошибки выводятся администратору
на экран, а агент разбора таблицы импорта берёт по одной строке. Сбой агента
пишется проблемой в журнал событий через shef.problems (тип
`SH_PROBLEMS_SYNC`) — с трассировкой: агент падает без свидетелей.

Модуль-наследник обязан объявить `getModuleId()` — его ждёт логгер проблем
shef.problems.

## `Entity`

Строка агента — `Класс::метод(['ключ'=>'значение']);`. Ядро исполняет её как
PHP-код, поэтому `\Shef\InSync\Agents\Entity::prepareNameForDb()` экранирует
параметры; параметры — только строки и числа. Для обычных значений строка та
же, что писала 1.x, и агенты, уже лежащие в `b_agent`, находятся по имени.

## `Manager`

* `\Shef\InSync\Agents\Manager::install()` — ставит агент, если его ещё нет;
* `\Shef\InSync\Agents\Manager::start()` и `\Shef\InSync\Agents\Manager::stop()`
  — включает и выключает;
* `\Shef\InSync\Agents\Manager::delete()` — удаляет;
* `\Shef\InSync\Agents\Manager::findAll()` — агенты модуля с тем же именем,
  параметры восстанавливаются разбором строки
  (`\Shef\InSync\Agents\Manager::parseName()`). Это разбор, а не исполнение:
  **для показа, а не для логики**;
* `\Shef\InSync\Agents\Manager::getModuleIdById()` — чей агент;
* `\Shef\InSync\Agents\Manager::getImportAgentModuleId()` — модуль агента,
  только если это агент импорта (наследник `AAgent`); по нему проверяются
  права. Агенты ядра и прочие из интерфейса модуля не трогаются.

Включать и выключать агенты из интерфейса может администратор либо
пользователь с правом «Запись» на модуль агента — см. [security.md](security.md).

---

[↑ Содержание](../README.md) | [Импорт →](2_import.md)
