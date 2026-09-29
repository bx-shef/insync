# [`\Shef\InSync\Api`] API

Обращение к внешнему API по HTTP — наследник `\Shef\InSync\Api\AConnector`.

> Пример смотреть в модуле **[shef.demosync](https://marketplace.1c-bitrix.ru/solutions/shef.demosync/)**

| класс | что делает |
|---|---|
| AConnector | запрос через `HttpClient` ядра, разбор ответа, логирование ошибок в shef.problems |
| Headers | маска секретных заголовков для лога |

Что пишете вы:

* `getPath()` — адрес по имени функции API;
* `getModuleId()` — модуль, от имени которого пишутся проблемы;
* при необходимости `processSuccess()`, `processError()`, `processError50x()` —
  разбор ответа. Помните, что 200 — ещё не успех бизнес-логики.

`\Shef\InSync\Api\AConnector::sendRequest()` отправляет запрос (`GET` —
параметры в адрес, остальные методы — телом) и возвращает `Result` с
отправленным и полученным. Таймауты — из опций объекта: `socketTimeout` (30),
`streamTimeout` (60), `waitResponse` (да). Значения приводятся к типам ядра: секунды — целым, `waitResponse` — флагом.

При ошибке запрос пишется в лог проблем. Заголовки с `authorization`, `token`,
`key`, `secret`, `password`, `cookie`, `session` в имени уходят туда маской
(`\Shef\InSync\Api\Headers::mask()`); параметры — как есть, секреты в них не
кладите. Ответ не 200 без ошибок соединения даёт ошибку `status: <код>`, а не
пустую строку, как до 2.0.0.

---

[← Импорт](2_import.md) | [↑ Содержание](../README.md) | [Парсинг XML →](4_xml.md)
