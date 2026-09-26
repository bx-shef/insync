# shef.insync change log

## 1.2.10 - XXXX
* -

## 1.2.9 - 2023-09-20
* фиксация кода
* учет ошибок $this->httpClient->getError
* добавлена опция option.import dialog option.text

## 1.2.7 - 2023-04-28
* подготовлено к публикации в МП
* изменен тип поля shef_insync_model.ADDITIONAL в таблице синхронизации на array

## 1.2.6 - 2023-04-18
* добавлен контекст для агентов
* добавлен инсталятор для опций
* поддержка смежных модулей

## 1.2.5 - 2023-03-28
* импорт в crm
	* смарт процесс
* передалан механизм страниц

## 1.2.1 — 2023-03-13
* увеличен размер поля shef_insync_model.ADDITIONAL на MEDIUMTEXT
* В пошаговую обработку таблицы импорта `\Shef\InSync\Sync\FromFile\AAgent` добавлена поддрежка стратегий `\Shef\InSync\Sync\FromFile\Strategy\IStrategy`
* Изменен логгер для агента разбора таблицы импорта
* Включена трассировка при ошибках в агента разбора таблицы импорта
* Добавлены модели для импорта `\Shef\InSync\Sync\Model`
	* склады `Model\Store`
	* инфоблок
		* разделы `Model\IBlock\Section`
		* элементы `Model\IBlock\Element`
		* перечисления для свойства типа список `Model\IBlock\PropertyEnumeration`
		* информация о товаре `Model\Catalog\ProductTable`
	* HL
		* приведен пример `Shef\InSync\Sync\Model\Hl\Demo` как должена выглядеть модель -> создается из админки + анатация генерируется
* Добавлены драйверы для импорта в каталог `\Shef\InSync\Sync\Model\Catalog\Driver`
	* товары `Driver\Product`
	* цены `Driver\Price`
	* остатки на складах `Driver\Amount`
* Сущности агента `\Shef\InSync\Agents\Entity` добавлена поддержка описания
* Переработан компонент `\Local\Component\Shef\InSync\ShefInSyncImportStatLocalComponent`
	* используем SmartStd для списка агентов
	* добавлена поддержака вывода описания об агенет
* Сделана инициализация логгера проблем и логгера отладки в агенте `\Shef\InSync\Agents\AAgent`
* Сделана инициализация логгера проблем и логгера отладки в api `\Shef\InSync\Api\AConnector`

## 1.1.2 — 2023-02-20
* Релиз

## 1.1-beta.1 — 2023-02-02
* Initial release
* рефакторинг
* поддержка shef.problems

[↑ Содержание](README.md)