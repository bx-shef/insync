<?php declare(strict_types=1);

namespace Shef\InSync\Sync\FromFile;

use Exception;
use LogicException;
use Throwable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Objectify\Collection as OrmCollection;
use Bitrix\Main\ORM\Objectify\EntityObject as OrmEntityObject;
use Bitrix\Main\Result;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Shef\Options\Options\SmartStd;
use Shef\Problems;
use Shef\InSync\Sync\IElement;
use Shef\InSync\Agents;
use Shef\InSync\Sync;
use Shef\InSync\TraitList as InSyncTraitList;

Loc::loadMessages(__FILE__);

/**
 * Абстракция Агента для пошагового разбора строк таблицы импорта
 * Использует стратегию Strategy\IStrategy для определения количества отбора, отбора и обработки плохих элементов
 * 
 * @see Strategy\IStrategy
 */
abstract class AAgent
	extends Agents\AAgent
{
	use InSyncTraitList\Sync\ClassSync;
	
	protected const LimitRows = 10;
	
	private Strategy\IStrategy $strategy;
	
	// region Agent.Init /////
	/**
	 * Используется для дополнительной инициализации объекта агента
	 *
	 * @return void
	 */
	protected function init(): void
	{
		$this->setSyncClassSyncTable(Sync\Model\SyncTable::class);
		$this->initStrategy();
	}
	
	/**
	 * Инициализация стратегии отбора и обработки проблем
	 * @return void
	 */
	protected function initStrategy(): void
	{
		$this->setStrategy(new Strategy\Simple(static::LimitRows));
	}
	
	/**
	 * Установка стратегии обработки строк импорта
	 *
	 * @param Strategy\IStrategy $strategy
	 * @return $this
	 */
	public function setStrategy(Strategy\IStrategy $strategy): static
	{
		$this->strategy = $strategy;
		return $this;
	}
	
	/**
	 * Возвращает код обработчика
	 * @return string
	 */
	abstract public static function getOriginatorId(): string;
	// endregion /////
	
	// region Work /////
	/**
	 * @inheritDoc
	 * @throws ArgumentNullException
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	public function action(): Result
	{
		/** @var IElement $row */
		/** @var OrmCollection $listRows */
		
		$result = new Result();
		
		// Результаты обработки строк — для вызывающего (например, шаг
		// импорта со страницы настроек). processRow() кладёт свой в
		// data['data']->result.
		$data = new SmartStd();
		$data->results = [];
		$result->setData([
			'data' => $data
		]);
		
		// region Get Items From SyncTable ////
		$conf = $this->strategy->getListConf(
			static::getOriginatorId(),
			$this
		);
		
		$listRows = $this->getSyncClassSyncTableEntity()::getList($conf)->fetchCollection();
		
		// Отладка одной строки повторяема и ничего не удаляет.
		$staleOriginatorId = (
			$this->strategy instanceof Strategy\AStrategy
			&& !$this->strategy->isUseOneRowDebug($this)
		)
			? $this->strategy->getSupersededOriginatorId(static::getOriginatorId())
			: null;
		
		if(null !== $staleOriginatorId)
		{
			$this->dropSuperseded($listRows, $staleOriginatorId, $result);
		}
		// endregion ////
		
		// region Init Items ////
		foreach($listRows as $row)
		{
			if(!($row instanceof IElement))
			{
				throw new LogicException('$row not implement IElement');
			}
			
			$this->prepareSyncByRow($row);
		}
		// endregion ////
		
		// region Mark Items as Processed ////
		$response = $listRows->save();
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		// endregion ////
		
		// region Process Items ////
		foreach($listRows as $row)
		{
			try
			{
				$response = $this->processRow($row);
				$data->results[] = static::getRowResult($response);
				if(!$response->isSuccess())
				{
					$result->addErrors($response->getErrors());
					
					$row->setInterfaceStatus(Sync\EStatus::Fail);
					$row->setInterfaceMessage(implode(';', $response->getErrorMessages()));
				}
				
			}
			catch(Throwable $throwable)
			{
				$error = Problems\Throwable\Manager::buildError($throwable, true);
				$result->addError($error);
				$row->setInterfaceStatus(Sync\EStatus::Fail);
				$row->setInterfaceMessage($error->getMessage());
			}
		}
		// endregion ////
		
		// region Debug ////
		if($this->isDebug())
		{
			$this->debugger->debug(
				'Agent action result',
				[
					'items' => array_map(
						function(OrmEntityObject $row)
						{
							return $row->collectValues();
						},
						$listRows->getAll()
					)
				]
			);
		}
		// endregion ////
		
		// region Remove Items From SyncTable ////
		if(null !== $staleOriginatorId)
		{
			$this->dropStaleTwins($listRows, $staleOriginatorId, $result);
		}
		
		$isUseOneRowDebug = $this->strategy->isUseOneRowDebug($this);
		foreach($listRows as $row)
		{
			if($row->getInterfaceStatus() === Sync\EStatus::Fail)
			{
				// Результат — в ответ агента: раньше сбой сохранения ошибочной
				// строки терялся молча, а исключение обрывало пачку.
				try
				{
					$response = $this->strategy->processFail($row);
					if(!$response->isSuccess())
					{
						$result->addErrors($response->getErrors());
					}
				}
				catch(Throwable $throwable)
				{
					$result->addError(Problems\Throwable\Manager::buildError($throwable, true));
				}
				continue;
			}
			elseif($isUseOneRowDebug)
			{
				continue;
			}
			
			static::deleteRow($row, $result);
			
		}
		unset($listRows);
		// endregion ////
		
		Sync\Integration\Manager::sendPullForImportStatLocal();
		
		return $result;
	}
	
	/**
	 * Строки пачки под меткой ошибки, у которых в таблице есть свежая строка
	 * с тем же внешним кодом, — устарели: удаляются и не разбираются.
	 *
	 * @param iterable<IElement> $listRows коллекция ORM пачки
	 */
	protected function dropSuperseded(object $listRows, string $staleOriginatorId, Result $result): void
	{
		$staleList = [];
		foreach($listRows as $row)
		{
			if($row->getInterfaceOriginatorId() === $staleOriginatorId)
			{
				$staleList[static::normalizeOriginId($row->getInterfaceOriginId())] = $row;
			}
		}
		
		if(empty($staleList))
		{
			return;
		}
		
		$fresh = $this->getSyncClassSyncTableEntity()::getList([
			'filter' => [
				'=ORIGINATOR_ID' => static::getOriginatorId(),
				'=ORIGIN_ID' => array_map(static fn(IElement $row): string => $row->getInterfaceOriginId(), array_values($staleList)),
			],
			'select' => [
				'ORIGIN_ID'
			]
		]);
		
		while($item = $fresh->fetch())
		{
			$row = $staleList[static::normalizeOriginId((string)$item['ORIGIN_ID'])] ?? null;
			if(null !== $row)
			{
				// Сначала из пачки, потом из таблицы: удалённый объект ORM
				// может потерять первичный ключ, и remove() его не нашёл бы.
				// Не удалилась — строка вне пачки, повтор на следующем запуске.
				$listRows->remove($row);
				static::deleteRow($row, $result);
			}
		}
	}
	
	/**
	 * Свежие строки пачки разобраны — их прежние копии под меткой ошибки
	 * устарели, где бы в таблице они ни лежали: удачный повтор вчерашней
	 * записал бы старые данные, а упавшая свежая, уходя под метку, упёрлась бы
	 * в индекс. Один запрос на пачку.
	 *
	 * @param iterable<IElement> $listRows коллекция ORM пачки
	 */
	protected function dropStaleTwins(object $listRows, string $staleOriginatorId, Result $result): void
	{
		$freshList = [];
		foreach($listRows as $row)
		{
			if($row->getInterfaceOriginatorId() === static::getOriginatorId())
			{
				$freshList[] = $row->getInterfaceOriginId();
			}
		}
		
		if(empty($freshList))
		{
			return;
		}
		
		$twins = $this->getSyncClassSyncTableEntity()::getList([
			'filter' => [
				'=ORIGINATOR_ID' => $staleOriginatorId,
				'=ORIGIN_ID' => $freshList,
			],
			// Без ADDITIONAL: строку удаляем, данные не нужны. Первичный
			// ключ ORM добавляет к выборке объектов сам.
			'select' => [
				'ORIGINATOR_ID',
				'ORIGIN_ID',
			]
		])->fetchCollection();
		
		foreach($twins as $twin)
		{
			static::deleteRow($twin, $result);
		}
	}
	
	/**
	 * Удаление строки: сбой — в результат агента, пачка идёт дальше.
	 */
	protected static function deleteRow(IElement $row, Result $result): bool
	{
		try
		{
			$response = $row->deleteInterface();
			if(!$response->isSuccess())
			{
				$result->addErrors($response->getErrors());
				return false;
			}
		}
		catch(Throwable $throwable)
		{
			$result->addError(Problems\Throwable\Manager::buildError($throwable, true));
			return false;
		}
		
		return true;
	}
	
	/**
	 * Внешний код так, как его сравнивает MySQL: без учёта регистра и
	 * пробелов в конце. Иначе «ABC» из выборки не нашёл бы «abc» пачки.
	 */
	protected static function normalizeOriginId(string $originId): string
	{
		return mb_strtolower(rtrim($originId, ' '));
	}
	
	/**
	 * Результат строки из ответа processRow(): data['data']->result, если он
	 * там есть. Без ключа — null, а не warning.
	 */
	protected static function getRowResult(Result $response): mixed
	{
		$data = $response->getData()['data'] ?? null;
		
		return is_object($data) ? ($data->result ?? null) : null;
	}
	
	/**
	 * Предварительная обработка строки
	 *
	 * @param IElement $row
	 * @return void
	 */
	public function prepareSyncByRow(IElement $row): void
	{
		$row
			->setInterfaceStatus(Sync\EStatus::Process)
			->setInterfaceDateInsert($this->curDateTime)
		;
	}
	
	/**
	 * Обработка строки
	 *
	 * @param IElement $row
	 * @return Result
	 */
	abstract protected function processRow(IElement $row): Result;
	// endregion ////
}