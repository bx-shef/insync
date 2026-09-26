# Агенты
## [`Agent\AAgent`]
Родительский класс для агентов

* `AAgent::buildAgent` - строит объект агента, можно использовать во внешнем коде для обращения к функциям агента
* `AAgent::process` - внешний обработчик агента
* `AAgent::action` - исполнение агента

> Использует [\[\Shef\Options\Main\Context\] Контекст](/bitrix/admin/settings.php?mid=shef.options&tabControl_active_tab=edit_DOCS) для указания пользователя

## [`Agent\Entity`]
Сущность агента. Используется для хранения данных по агенту.

## [`Agent\Manager`]

Управляет агентами на основании сущности Агента `Agent\Entity`

* **Agent\Manager::start** - Активирует агент
* **Agent\Manager::stop** - Деактивирует агент
* **Agent\Manager::delete** - Удаляет агент
* **Agent\Manager::findAll** - Ищет агенты с одинаковым названием. Тут предпринята попытка восстановить параметры агента из строки. **Не стоит полагаться на них.**
* **Agent\Manager::install** - Устанавливает агент

[↑ Содержание](README.md) | [Импорт →](docs/2_import.md)