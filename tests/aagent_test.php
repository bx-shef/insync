<?php declare(strict_types=1);

/**
 * Агент импорта: что он возвращает ядру и чей у него контекст.
 *
 * Держится:
 *
 * 1. строка, которую process() отдаёт ядру, — всегда строка агента, пока
 *    агент сам не попросил остановиться. Было: не подключились модули —
 *    пустая строка, и ядро удаляло агент из b_agent насовсем, молча;
 * 2. контекст CRM у каждого класса агента свой. Было: static внутри
 *    метода — одна переменная на AAgent и всех наследников, и правка
 *    контекста одним агентом доставалась другому.
 *
 * Классы shef.options и shef.problems, на которых стоит AAgent, — заглушки
 * из tests/stub/agent.php.
 */

namespace
{
	$root = dirname(__DIR__);

	require_once $root.'/tests/stub/autoload.php';
	require_once $root.'/tests/stub/agent.php';
	require_once $root.'/tests/assert.php';

	use Bitrix\Main\Result;
	use Bitrix\Main\Error;
	use Shef\InSync\Agents\AAgent;
	use Shef\InSync\Agents\Entity;
	use Shef\Problems\Factory\Trait\TestLogger;

	abstract class DemoAgentBase extends AAgent
	{
		public static bool $modulesFail = false;
		public static bool $stop = false;
		public static null|string $throw = null;

		protected static function includeModules(): Result
		{
			$result = new Result();
			if(static::$modulesFail)
			{
				$result->addError(new Error('module shef.problems not loaded'));
			}

			return $result;
		}

		public static function buildAgentsEntity(): Entity
		{
			return new Entity(module: 'shef.demo', name: '\\'.static::class.'::process', params: [], userId: 1);
		}

		public function action(): Result
		{
			if(null !== static::$throw)
			{
				throw new \RuntimeException(static::$throw);
			}

			$this->setIsNeedStop(static::$stop);

			return new Result();
		}
	}

	final class DemoAgent extends DemoAgentBase {}
	final class OtherAgent extends DemoAgentBase {}

	$name = "\\DemoAgent::process(['code'=>'price']);";

	Check::group('строка агента для ядра');

	TestLogger::$records = [];
	Check::same('отработал — строка агента', DemoAgent::process(['code' => 'price']), $name);
	Check::same('…и без ошибок', TestLogger::$records, []);
	Check::same('debug в строку не попадает', DemoAgent::process(['code' => 'price', 'debug' => 'Y']), $name);

	DemoAgentBase::$throw = 'boom';
	TestLogger::$records = [];
	Check::same('упал в action() — агент остаётся', DemoAgent::process(['code' => 'price']), $name);
	Check::same('…и падение записано', TestLogger::$records, [['boom']]);
	DemoAgentBase::$throw = null;

	DemoAgentBase::$modulesFail = true;
	TestLogger::$records = [];
	Check::same('не подключились модули — агент остаётся', DemoAgent::process(['code' => 'price']), $name);
	Check::same('…и причина записана', TestLogger::$records, [['module shef.problems not loaded']]);
	DemoAgentBase::$modulesFail = false;

	DemoAgentBase::$stop = true;
	Check::same('попросил остановиться — пустая строка', DemoAgent::process(['code' => 'price']), '');
	DemoAgentBase::$stop = false;

	Check::group('контекст — свой у каждого класса');

	DemoAgent::getContext()->setUserId(42);
	Check::same('правка контекста одного агента', DemoAgent::getContext()->getUserId(), 42);
	Check::same('другому не достаётся', OtherAgent::getContext()->getUserId() !== 42, true);
	Check::same('объект — один на класс', DemoAgent::getContext() === DemoAgent::getContext(), true);

	Check::finish();
}
