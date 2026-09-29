<?php declare(strict_types=1);

/**
 * Классы shef.options и shef.problems, на которых стоят агент импорта
 * \Shef\InSync\Agents\AAgent и клиент API \Shef\InSync\Api\AConnector, —
 * и процесс импорта из файла, — ровно столько, сколько нужно их тестам:
 * aagent_test.php, access_test.php (агент импорта против агента ядра),
 * connector_test.php (что уходит в лог), upload_test.php (загрузка файла),
 * fromfileagent_test.php (агент разбора таблицы импорта).
 */

namespace Bitrix\Main
{
	if(!interface_exists(Errorable::class))
	{
		interface Errorable
		{
			public function getErrors();

			public function getErrorByCode($code);
		}
	}
}

namespace Shef\Options\Options
{
	if(!class_exists(Singleton::class))
	{
		abstract class Singleton
		{
			private static array $instanceList = [];

			protected function __construct() {}

			public static function getInstance(): static
			{
				return self::$instanceList[static::class] ??= new static();
			}
		}
	}

	if(!class_exists(SmartStd::class))
	{
		class SmartStd extends \stdClass
		{
			public function toArray(): array
			{
				return array_map(
					static fn($value) => $value instanceof self ? $value->toArray() : $value,
					get_object_vars($this)
				);
			}
		}
	}
}

namespace Shef\Options\Main
{
	if(!class_exists(Context::class))
	{
		class Context
		{
			public const SCOPE_TASK = 'task';

			private int $userId = 0;

			public function setUserId(int $userId): static
			{
				$this->userId = $userId;
				return $this;
			}

			public function setScope(string $scope): static
			{
				return $this;
			}

			public function getUserId(): int
			{
				return $this->userId;
			}
		}
	}
}

namespace Shef\Options\TraitList\Tools
{
	trait DateTime
	{
		protected null|\Bitrix\Main\Type\DateTime $curDateTime = null;

		protected function initDateTime(): void
		{
			$this->curDateTime = new \Bitrix\Main\Type\DateTime('2026-09-29 10:00:00');
		}
	}
	trait SelfClass {}

	trait IsDebug
	{
		private bool $isDebugMode = false;

		public function setIsDebug(bool $isDebug): static
		{
			$this->isDebugMode = $isDebug;
			return $this;
		}

		public function isDebug(): bool
		{
			return $this->isDebugMode;
		}
	}

	trait Encoding {}

	trait PrepareFields {}

	trait ErrorCollection
	{
		final public function getErrors(): array
		{
			return [];
		}

		final public function getErrorByCode($code): ?\Bitrix\Main\Error
		{
			return null;
		}
	}

	trait OptionCollection
	{
		private \Bitrix\Main\Type\Dictionary $optionCollection;

		protected function initOptionCollection(): void
		{
			$this->optionCollection = new \Bitrix\Main\Type\Dictionary();
		}

		final public function addOptionCollection(string $name, mixed $value): static
		{
			$this->optionCollection->set($name, $value);
			return $this;
		}

		final public function getOptionCollection(): \Bitrix\Main\Type\Dictionary
		{
			return $this->optionCollection;
		}
	}
}

namespace Shef\Options\TraitList\Security
{
	trait FixUser
	{
		protected static function initUser(): void {}

		protected static function closeUser(): void {}
	}
}

namespace Shef\Problems\Factory\Trait
{
	/** Логгер: запоминает, что записано. */
	final class TestLogger
	{
		public static array $records = [];

		public function critical(\Bitrix\Main\Result $result): void
		{
			static::$records[] = $result->getErrorMessages();
		}

		public function error(mixed $record): void
		{
			static::$records[] = $record;
		}
	}

	trait LoggerProblems
	{
		protected TestLogger $logger;

		protected static function createLogger(): TestLogger
		{
			return new TestLogger();
		}

		protected function initLogger(): void
		{
			$this->logger = static::createLogger();
		}

		public function configureLogger(TestLogger $logger): static
		{
			return $this;
		}
	}

	trait DebuggerProblems
	{
		protected TestLogger $debugger;

		protected static function createDebugger(): TestLogger
		{
			return new TestLogger();
		}

		protected function initDebugger(): void
		{
			$this->debugger = static::createDebugger();
		}

		public function configureDebugger(TestLogger $debugger): static
		{
			return $this;
		}
	}
}

namespace Shef\Problems\Throwable
{
	if(!class_exists(Manager::class))
	{
		class Manager
		{
			public static function buildError(\Throwable $throwable, bool $isTrace = false): \Bitrix\Main\Error
			{
				return new \Bitrix\Main\Error($throwable->getMessage());
			}
		}
	}
}

namespace
{
	if(!class_exists('CAdminMessage'))
	{
		class CAdminMessage
		{
			public static array $shown = [];

			public static function ShowMessage(array $message): void
			{
				static::$shown[] = $message['MESSAGE'];
			}
		}
	}
}
