<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Crm;

use LogicException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Errorable;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Shef\Options\Options\SmartStd;
use Shef\Problems;
use Shef\InSync\Sync;
use Throwable;

Loc::loadMessages(__FILE__);

/**
 * Абстракция для обработки сущносетй CRM
 * В результате сущность попадет в таблицу импорта
 */
abstract class ACrmProcess
	extends Sync\AProcess
	implements Sync\IProcess, Errorable
{
	public const Limit = 10;
	protected null|Crm\Item $row = null;
	private null|Crm\Service\Factory $factory = null;
	
	protected const OriginatorId = 'demoCrm';
	
	// region Init ////
	
	/**
	 * Id сущности crm
	 * @return int
	 */
	abstract public function getEntityTypeId(): int;
	
	/**
	 * Фабрика CRM для типа сущности импорта.
	 *
	 * Хранится в свойстве объекта. Было static внутри метода, а такая
	 * переменная одна на класс и его наследников (PHP 8.1+): второй импорт
	 * CRM в том же хите — скажем, сделки после смарт-процесса — получал
	 * фабрику первого.
	 */
	public function getFactory(): Crm\Service\Factory
	{
		if(null === $this->factory)
		{
			$this->factory = Crm\Service\Container::getInstance()->getFactory($this->getEntityTypeId());
		}
		
		$factory = $this->factory;
		if(null === $factory)
		{
			throw new LogicException(sprintf(
				'No get factory by entityTypeId=%s',
				$this->getEntityTypeId()
			));
		}
		
		return $factory;
	}
	// endregion ////

	// region Process ////
	/**
	 * @inheritDoc
	 * @throws ArgumentNullException
	 */
	final public function run(): Result
	{
		$result = new Result();

		try
		{
			$response = $this->import();
			$result->setData($response->getData());
			
			Sync\Integration\Manager::sendPullForImportStatLocal();
			
			if(!$response->isSuccess())
			{
				$result->setData(array_merge(
					$result->getData(),
					[
						'errorsImport' => $response->getErrors()
					]
				));
				
				if(!$this->getErrorCollection()->isEmpty())
				{
					$result->addErrors($this->getErrors());
				}
				
				return $result;
			}
		}
		catch(Throwable $throwable)
		{
			$this->addError(Problems\Throwable\Manager::buildError(
				$throwable,
				false
			));
		}

		if(!$this->getErrorCollection()->isEmpty())
		{
			return $result->addErrors($this->getErrors());
		}

		return $result;
	}
	
	/**
	 * Возвращает количество элементов которое предстоит загрузить
	 *
	 * @return int
	 */
	abstract public function countItemsForImport(): int;
	
	/**
	 * Возвращает $conf для getList
	 *
	 * @return array
	 */
	abstract protected function getImportConfig(): array;
	
	/**
	 * Проходим по сущностям и каждую строку складываем в таблицу импорта
	 *
	 * @return Result
	 */
	protected function import(): Result
	{
		$result = new Result();
		
		$info = new SmartStd();
		$info->tmpId = static::getXmlIdIdempotence();
		$info->rowNum = 0;
		$info->fails = [];
		
		foreach($this->getFactory()->getItems($this->getImportConfig()) as $this->row)
		{
			$info->rowNum++;

			$response = $this->processRow($info->tmpId.'.'.$info->rowNum);
			
			if(!$response->isSuccess())
			{
				$result->addErrors($response->getErrors());
				$this->logger->error($response);
			}
		}
		
		unset($cursor);
		
		$data = [
			'rows' => $info->rowNum,
			'errors' => $result->getErrorCollection()->count()
		];
		$this->logger->info(('Import Statistic'), $data);
		
		return $result->setData($data);
	}
	
	/**
	 * Обрабытывает Строку
	 *
	 * @param string $originId
	 * @return Result
	 */
	private function processRow(
		string $originId
	): Result
	{
		$result = new Result();
		
		$elementSync = $this->build([
			'ORIGIN_ID' => $originId,
		]);
		
		$response = $this->init($elementSync);
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		
		$response = $this->process($elementSync);
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		
		$response = $this->processCrmEntity($this->row);
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		
		return $result->setData([
			'ENTITY' => $elementSync
		]);
	}
	
	/**
	 * @inheritdoc
	 * 
	 * @memo Указываем статус Undefined.
	 * @memo В последсвии агент при выборке переведет в статус Process -> Success || Problem
	 * 
	 * @see AAgent::action
	 */
	final public function build(array $params = []): Sync\IElement
	{
		$syncEntity =  $this->getSyncEntityObject();
		
		if(!($syncEntity instanceof Sync\IElement))
		{
			throw new LogicException('syncEntityObject not implement IElement');
		}
		$syncEntity
			->setInterfaceOriginatorId(static::getOriginatorId())
			->setInterfaceStatus($params['STATUS'] ?? Sync\EStatus::Undefined)
			->setInterfaceMessage($params['MESSAGE'] ?? '')
			->setInterfaceDateInsert($this->curDateTime)
			->setInterfaceTitle((string)($params['TITLE'] ?? ''))
			->setInterfaceAdditional(
				$params['ADDITIONAL'] ?? []
			)
		;
		
		return $syncEntity;
	}
	
	/**
	 * @inheritDoc
	 */
	abstract public function init(Sync\IElement $element): Result;
	
	/**
	 * Обрабатываем элемент -> Добавляем в таблицу импорта
	 */
	final public function process(Sync\IElement $element): Result
	{
		/** @var Sync\Model\Sync $element */
		
		$result = new Result();
		
		$response = $element->saveInterface();
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		
		$result->setData([
			'primary' => $response->getPrimary(),
			'data' => $response->getData()
		]);
		
		return $result;
	}
	
	/**
	 * Обработка сущности CRM после добавлений ее в таблицу импорта
	 *
	 * @param Crm\Item $row
	 * @return Result
	 */
	protected function processCrmEntity(Crm\Item $row): Result
	{
		return new Result();
	}
	
	// endregion ////
}