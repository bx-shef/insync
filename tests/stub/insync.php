<?php

/**
 * Заглушки ядра сверх общих (tests/stub/bitrix.php) — то, что зовёт именно
 * этот модуль: агенты (CAgent), права на модуль (CMain::GetGroupRight),
 * соединение с БД и ORM-таблица, ajax-контроллер, провайдер левого меню.
 *
 * Правило то же: логику модуля здесь не повторяем, только запоминаем, что
 * у ядра попросили, и отдаём то, что задал тест.
 */

namespace
{
	require_once __DIR__.'/bitrix.php';
}

namespace Bitrix\Main\Type
{
	if(!class_exists(Dictionary::class))
	{
		class Dictionary implements \IteratorAggregate, \Countable
		{
			protected array $values = [];

			public function __construct(array $values = [])
			{
				$this->values = $values;
			}

			public function get($name)
			{
				return $this->values[$name] ?? null;
			}

			public function set($name, $value = null): void
			{
				if(is_array($name))
				{
					$this->values = $name;
					return;
				}

				$this->values[$name] = $value;
			}

			public function setValues(array $values): void
			{
				$this->values = array_replace($this->values, $values);
			}

			public function clear(): void
			{
				$this->values = [];
			}

			public function toArray(): array
			{
				return $this->values;
			}

			public function getIterator(): \ArrayIterator
			{
				return new \ArrayIterator($this->values);
			}

			public function count(): int
			{
				return count($this->values);
			}
		}
	}
}

namespace Bitrix\Main\DB
{
	if(!class_exists(SqlHelper::class))
	{
		/** Экранирование — как у MySQL-помощника ядра: обратный слэш и кавычки. */
		class SqlHelper
		{
			public function forSql($value, $maxLength = 0): string
			{
				return strtr((string)$value, [
					'\\' => '\\\\',
					"'" => "\\'",
					'"' => '\\"',
					"\0" => '\\0',
					"\n" => '\\n',
					"\r" => '\\r',
					"\x1a" => '\\Z',
				]);
			}
		}

		/** Соединение: запоминает запросы, таблицы «есть» по списку теста. */
		class Connection
		{
			/** @var string[] */
			public static array $queries = [];

			/** @var string[] */
			public static array $tables = [];

			/** Что вернёт query()->fetch(). */
			public static null|array $row = null;

			public function getSqlHelper(): SqlHelper
			{
				return new SqlHelper();
			}

			public function isTableExists($tableName): bool
			{
				return in_array($tableName, static::$tables, true);
			}

			public function queryExecute($sql, array $binds = []): void
			{
				static::$queries[] = $sql;

				if(preg_match('/^DROP TABLE (\w+)/', $sql, $match))
				{
					static::$tables = array_values(array_diff(static::$tables, [$match[1]]));
				}
			}

			public function query($sql): object
			{
				static::$queries[] = $sql;

				return new class(static::$row)
				{
					public function __construct(private readonly null|array $row) {}

					public function fetch(): array|false
					{
						return $this->row ?? false;
					}

					public function fetchAll(): array
					{
						return null === $this->row ? [] : [$this->row];
					}
				};
			}
		}
	}
}

namespace Bitrix\Main\ORM\Data
{
	if(!class_exists(DataManager::class))
	{
		/** ORM-сущность: имя таблицы и создание — то, что зовёт установщик. */
		class Entity
		{
			public function __construct(private readonly string $dataClass) {}

			public function getDBTableName(): string
			{
				return ($this->dataClass)::getTableName();
			}

			public function createDbTable(): void
			{
				\Bitrix\Main\DB\Connection::$queries[] = 'CREATE TABLE '.$this->getDBTableName();
				\Bitrix\Main\DB\Connection::$tables[] = $this->getDBTableName();
			}

			public function getConnection(): \Bitrix\Main\DB\Connection
			{
				return \Bitrix\Main\Application::getConnection();
			}
		}

		abstract class DataManager
		{
			public static function getTableName(): ?string
			{
				return null;
			}

			public static function getEntity(): Entity
			{
				return new Entity(static::class);
			}
		}
	}
}

namespace Bitrix\Main\Web
{
	if(!class_exists(Uri::class))
	{
		class Uri
		{
			public array $params = [];

			public function __construct(public string $path = '') {}

			public function addParams(array $params): static
			{
				$this->params = array_replace($this->params, $params);
				return $this;
			}

			public function getUri(): string
			{
				return $this->path.(empty($this->params) ? '' : '?'.http_build_query($this->params));
			}

			public function getQuery(): string
			{
				return http_build_query($this->params);
			}
		}
	}
}

namespace Bitrix\Main\Engine
{
	if(!class_exists(Controller::class))
	{
		class Action
		{
			public function __construct(public readonly string $name = '') {}
		}

		/** Ajax-контроллер ядра: ошибки и хук перед действием. */
		class Controller
		{
			/** @var \Bitrix\Main\Error[] */
			protected array $errors = [];

			/** Параметры запроса — задаёт тест. */
			public static array $requestValues = [];

			protected object $request;

			public function __construct()
			{
				$this->request = new class(static::$requestValues)
				{
					public function __construct(private readonly array $values) {}

					public function get(string $name): mixed
					{
						return $this->values[$name] ?? null;
					}
				};

				$this->init();
			}

			protected function init(): void {}

			protected function processBeforeAction(Action $action): bool
			{
				return true;
			}

			/** Ровно то, что делает ядро: сначала хук, потом действие. */
			public function run(string $actionName, array $arguments): mixed
			{
				if(!$this->processBeforeAction(new Action($actionName)))
				{
					return null;
				}

				return $this->{$actionName.'Action'}(...$arguments);
			}

			public function addError(\Bitrix\Main\Error $error): static
			{
				$this->errors[] = $error;
				return $this;
			}

			public function addErrors(array $errors): static
			{
				foreach($errors as $error)
				{
					$this->addError($error);
				}
				return $this;
			}

			public function getErrors(): array
			{
				return $this->errors;
			}

			public function getModuleId(): string
			{
				return '';
			}
		}
	}
}

namespace Bitrix\Main\Engine\Response
{
	if(!class_exists(Redirect::class))
	{
		class Redirect
		{
			public function __construct(public readonly mixed $url) {}
		}
	}
}

namespace Bitrix\Intranet\CustomSection
{
	if(!class_exists(Provider::class))
	{
		abstract class Provider
		{
			abstract public function isAvailable(string $pageSettings, int $userId): bool;

			abstract public function resolveComponent(string $pageSettings, \Bitrix\Main\Web\Uri $url): ?Provider\Component;
		}
	}
}

namespace Bitrix\Intranet\CustomSection\Provider
{
	if(!class_exists(Component::class))
	{
		class Component
		{
			public string $name = '';
			public string $template = '';
			public array $params = [];

			public function setComponentName(string $name): static
			{
				$this->name = $name;
				return $this;
			}

			public function setComponentTemplate(string $template): static
			{
				$this->template = $template;
				return $this;
			}

			public function setComponentParams(array $params): static
			{
				$this->params = $params;
				return $this;
			}
		}
	}
}

namespace
{
	if(!class_exists('CAgent'))
	{
		/** Агенты: строки b_agent — массив, который задаёт тест. */
		class CAgent
		{
			/** @var array<int, array<string, string>> ID => строка b_agent */
			public static array $agents = [];

			/** @var array<int, array> что обновляли */
			public static array $updated = [];

			public static function GetList($order = [], $filter = []): object
			{
				$rows = array_values(array_filter(
					static::$agents,
					static function(array $agent) use ($filter): bool
					{
						foreach($filter as $key => $value)
						{
							$field = ltrim((string)$key, '=');
							$actual = (string)($agent[$field] ?? '');

							if($field === 'NAME' && !str_starts_with((string)$key, '='))
							{
								// LIKE: «%» — любой хвост.
								$pattern = '/^'.str_replace('%', '.*', preg_quote((string)$value, '/')).'$/s';
								if(!preg_match($pattern, $actual))
								{
									return false;
								}
								continue;
							}

							if($actual !== (string)$value)
							{
								return false;
							}
						}

						return true;
					}
				));

				return new class($rows)
				{
					public function __construct(private array $rows) {}

					public function Fetch(): array|false
					{
						return array_shift($this->rows) ?? false;
					}
				};
			}

			public static function Update($id, $fields): bool
			{
				if(!isset(static::$agents[(int)$id]))
				{
					return false;
				}

				static::$updated[] = ['ID' => (int)$id] + $fields;
				static::$agents[(int)$id] = array_replace(static::$agents[(int)$id], $fields);
				return true;
			}
		}
	}

	if(!class_exists('CMain'))
	{
		/** Права на модули: «модуль => буква» задаёт тест. */
		class CMain
		{
			/** @var array<string, string> */
			public static array $rights = [];

			public function GetGroupRight($moduleId): string
			{
				return static::$rights[$moduleId] ?? 'D';
			}
		}

		$GLOBALS['APPLICATION'] = new CMain();
	}

	if(!defined('LANGUAGE_ID'))
	{
		define('LANGUAGE_ID', 'ru');
	}

	if(!function_exists('bitrix_sessid'))
	{
		function bitrix_sessid(): string
		{
			return 'sessid-test';
		}
	}

	if(!function_exists('LocalRedirect'))
	{
		function LocalRedirect($url): void
		{
			$GLOBALS['SH_INSYNC_TEST_REDIRECT'] = $url;
		}
	}
}
