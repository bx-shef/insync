# [`\Shef\InSync\Main\Options`] Опции настроек модуля

Опции для страницы настроек **модуля импорта** (на `ShOptionsConfig` из
shef.options): добавьте их во вкладку своего `options_conf.php`.

| класс | что выводит |
|---|---|
| Agent\Option | агент: состояние и кнопка запуска или остановки |
| Import\FromFile\Option | пошаговый импорт демо-файла диалогом `ui.stepprocessing` |

## `Agent\Option`

```php
(new \Shef\InSync\Main\Options\Agent\Option('agentPrice'))
	->setAgentEntity(\Shef\Demo\Agents\Price::buildAgentsEntity())
```

Кнопка ведёт в `\Shef\InSync\Main\Options\Agent\Controller`. Видна она тому,
кто вправе управлять агентом, — администратору или с правом «Запись» на модуль
агента; контроллер проверяет права ещё раз, и на модуль агента из `b_agent`, а
не из запроса.

## `Import\FromFile\Option`

Контроллер импорта — наследник `\Shef\InSync\Main\Options\Import\FromFile\AController`
в namespace модуля импорта: `checkFile` загружает демо-файл в таблицу
импорта, `import` гоняет агент разбора, пока таблица не опустеет. Права
проверяются перед каждым действием — на модуль, чей это контроллер.

## Опции самого модуля

Вкладка «Общие»: сколько дней хранится файл в архиве импорта (1, 2, 3, 5, 15,
30; по умолчанию 3). Разбор строгий — `\Shef\InSync\Main\Constants::parseDays()`:
всё, что не целое > 0, даёт умолчание. До 2.0.0 «0» или мусор в настройке
означали «хранить 0 дней», и архив стирался при каждом запуске импорта.

---

[← Страницы](6_page.md) | [↑ Содержание](../README.md) | [Безопасность →](security.md)
