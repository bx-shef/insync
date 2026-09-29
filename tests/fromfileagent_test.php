<?php declare(strict_types=1);

/**
 * Агент разбора таблицы импорта: что он делает со строками пачки.
 *
 * Внешний код строки уникален в пределах кода импорта, поэтому одна и та же
 * строка «123» может лежать и под «<код>», и — после сбоя со стратегией
 * MarkFail — под «<код>.error». Держится:
 *
 * 1. устаревшая строка под меткой ошибки, у которой есть свежая, удаляется и
 *    не разбирается: иначе её удачный повтор записал бы вчерашние данные
 *    поверх сегодняшних;
 * 2. свежая строка упала — её прежняя копия под меткой удаляется, и
 *    переименование не упирается в уникальный индекс;
 * 3. сбой сохранения ошибочной строки и сбой удаления удачной — в результате
 *    агента, и пачка не обрывается. Было: терялись молча, исключение
 *    обрывало пачку, и удачные строки разбирались снова.
 *
 * Таблица импорта и строки — заглушки здесь же; агент, стратегии и
 * FromFile\AAgent — настоящие.
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/autoload.php';
require_once $root.'/tests/stub/agent.php';
require_once $root.'/tests/assert.php';

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Shef\InSync\Sync\EStatus;
use Shef\InSync\Sync\IElement;
use Shef\InSync\Sync\FromFile\Strategy;

if(!class_exists('CPullStack'))
{
	class CPullStack
	{
		public static function AddShared(array $message): void {}
	}
}

/** Строка таблицы импорта. */
final class FakeRow implements IElement
{
	public null|string $failSave = null;
	public null|string $throwDelete = null;

	public function __construct(
		private string $originatorId,
		private readonly string $originId,
		private EStatus $status = EStatus::New
	) {}

	public function setInterfaceOriginId(string $originId): static { return $this; }
	public function getInterfaceOriginId(): string { return $this->originId; }
	public function setInterfaceOriginatorId(string $originatorId): static { $this->originatorId = $originatorId; return $this; }
	public function getInterfaceOriginatorId(): string { return $this->originatorId; }
	public function setInterfaceStatus(EStatus $status): static { $this->status = $status; return $this; }
	public function getInterfaceStatus(): EStatus { return $this->status; }
	public function setInterfaceMessage(string $message): static { return $this; }
	public function getInterfaceMessage(): string { return ''; }
	public function setInterfaceDateInsert(\Bitrix\Main\Type\DateTime $dateInsert): static { return $this; }
	public function getInterfaceDateInsert(): \Bitrix\Main\Type\DateTime { return new \Bitrix\Main\Type\DateTime(); }
	public function setInterfaceTitle(string $title): static { return $this; }
	public function getInterfaceTitle(): string { return ''; }
	public function setInterfaceAdditional(array $additional): static { return $this; }
	public function getInterfaceAdditional(): array { return []; }
	public function clearInterfaceSyncStatus(): void {}

	public function saveInterface(): \Bitrix\Main\ORM\Data\Result
	{
		$result = new \Bitrix\Main\ORM\Data\Result();
		if(null !== $this->failSave)
		{
			$result->addError(new Error($this->failSave));
		}

		return $result;
	}

	public function deleteInterface(): \Bitrix\Main\ORM\Data\Result
	{
		if(null !== $this->throwDelete)
		{
			throw new \RuntimeException($this->throwDelete);
		}

		FakeTable::$rows = array_values(array_filter(FakeTable::$rows, fn(FakeRow $row): bool => $row !== $this));

		return new \Bitrix\Main\ORM\Data\Result();
	}

	public function key(): string
	{
		return $this->originatorId.'/'.$this->originId;
	}
}

/** Коллекция ORM пачки. */
final class FakeCollection implements \IteratorAggregate
{
	public function __construct(private array $rows) {}

	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->rows);
	}

	public function save(): Result
	{
		return new Result();
	}

	public function remove(FakeRow $row): void
	{
		$this->rows = array_values(array_filter($this->rows, fn(FakeRow $item): bool => $item !== $row));
	}

	public function getAll(): array
	{
		return $this->rows;
	}
}

/** Таблица импорта: строки в памяти, фильтр по коду импорта и внешнему коду, limit. */
final class FakeTable extends \Bitrix\Main\ORM\Data\DataManager
{
	/** @var FakeRow[] */
	public static array $rows = [];

	public static function getList(array $parameters): object
	{
		$filter = $parameters['filter'] ?? [];
		$match = static function(null|string|array $expected, string $actual): bool
		{
			return null === $expected || in_array($actual, (array)$expected, true);
		};

		$rows = array_values(array_filter(
			static::$rows,
			static fn(FakeRow $row): bool => $match($filter['=ORIGINATOR_ID'] ?? null, $row->getInterfaceOriginatorId())
				&& $match($filter['=ORIGIN_ID'] ?? null, $row->getInterfaceOriginId())
		));

		if(isset($parameters['limit']))
		{
			$rows = array_slice($rows, 0, (int)$parameters['limit']);
		}

		return new class($rows)
		{
			public function __construct(private array $rows) {}

			public function fetchCollection(): FakeCollection
			{
				return new FakeCollection($this->rows);
			}

			public function fetch(): array|false
			{
				$row = array_shift($this->rows);

				return null === $row ? false : ['ORIGIN_ID' => $row->getInterfaceOriginId()];
			}
		};
	}
}

final class PriceAgent extends \Shef\InSync\Sync\FromFile\AAgent
{
	/** @var string[] внешние коды — какие строки дошли до разбора */
	public array $processed = [];

	/** @var string[] внешние коды, на которых разбор падает */
	public array $fail = [];

	public static function getOriginatorId(): string { return 'price'; }
	public static function getModuleId(): string { return 'shef.demo'; }

	public static function buildAgentsEntity(): \Shef\InSync\Agents\Entity
	{
		return new \Shef\InSync\Agents\Entity(module: 'shef.demo', name: '\\PriceAgent::process', params: [], userId: 1);
	}

	protected function processRow(IElement $row): Result
	{
		$this->processed[] = $row->getInterfaceOriginatorId().'/'.$row->getInterfaceOriginId();

		$result = new Result();
		if(in_array($row->getInterfaceOriginId(), $this->fail, true))
		{
			$result->addError(new Error('bad row '.$row->getInterfaceOriginId()));
		}

		return $result;
	}
}

$agent = PriceAgent::getInstance();
$agent->setSyncClassSyncTable(FakeTable::class);

$run = static function(int $limit = 100) use ($agent): Result
{
	$agent->processed = [];
	$agent->setStrategy(new Strategy\MarkFail($limit));

	return $agent->action();
};
$keys = static fn(): array => array_map(static fn(FakeRow $row): string => $row->key(), FakeTable::$rows);

Check::group('устаревшая строка под меткой ошибки');

FakeTable::$rows = [
	new FakeRow('price', '123'),
	new FakeRow('price.error', '123', EStatus::Fail),
	new FakeRow('price.error', '456', EStatus::Fail),
];
$run();
Check::same('вчерашняя «123» не разбирается, её повтор без свежей — да', $agent->processed, ['price/123', 'price.error/456']);
Check::same('в таблице ничего не осталось', FakeTable::$rows, []);

Check::group('свежая строка упала — прежняя копия под меткой уходит');

FakeTable::$rows = [
	new FakeRow('price', '789'),
	new FakeRow('price.error', '789', EStatus::Fail),
];
$agent->fail = ['789'];
$result = $run(1);
Check::same('в пачке — только свежая', $agent->processed, ['price/789']);
Check::same('одна строка «789» — сегодняшняя, под меткой', $keys(), ['price.error/789']);
Check::same('ошибка разбора — в результате', $result->getErrorMessages(), ['bad row 789']);
$agent->fail = [];

Check::group('сбои сохранения и удаления — в результате, пачка не обрывается');

$broken = new FakeRow('price', '1');
$broken->failSave = 'save failed';
$stuck = new FakeRow('price', '2');
$stuck->throwDelete = 'delete failed';
FakeTable::$rows = [$broken, $stuck, new FakeRow('price', '3')];
$agent->fail = ['1'];
$result = $run();
Check::same('ошибки разбора, сохранения и удаления', $result->getErrorMessages(), ['bad row 1', 'save failed', 'delete failed']);
Check::same('строка после сбоя удаления тоже удалена', in_array('price/3', $keys(), true), false);
$agent->fail = [];

Check::finish();
