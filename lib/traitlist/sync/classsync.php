<?php declare(strict_types=1);

namespace Shef\InSync\TraitList\Sync;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Objectify\Collection as OrmCollection;
use Bitrix\Main\ORM\Objectify\EntityObject as OrmEntityObject;

/**
 * Трейт для работы с моделью синхронизации
 */

trait ClassSync
{
	/** @var string|null Класс *Table сущности синхронизации */
	private ?string $syncClassSyncTable = null;
	
	/**
	 * Указывает на class *Table сущности синхронизации
	 *
	 * @param string $syncClassSyncTable
	 * @return $this
	 *
	 * @see \Shef\InSync\Sync\Model\SyncTable
	 * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&CHAPTER_ID=011687&LESSON_PATH=3913.3516.5748.11687
	 */
	public function setSyncClassSyncTable(string $syncClassSyncTable): static
	{
		$this->syncClassSyncTable = $syncClassSyncTable;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getSyncClassSyncTable(): string
	{
		if(null === $this->syncClassSyncTable)
		{
			throw new \LogicException('Need init $syncClassSyncTable');
		}
		
		return $this->syncClassSyncTable;
	}
	
	public function getSyncClassSyncTableEntity(): DataManager
	{
		static $entity;
		if(null === $entity)
		{
			$entityClass = $this->getSyncClassSyncTable();
			$entity = new $entityClass;
		}
		
		if(!($entity instanceof DataManager))
		{
			throw new \LogicException('Wrong DataManager from $syncClassSyncTable');
		}
		
		return $entity;
	}
	
	/**
	 * Возвращает объект синхронизации
	 * @return OrmEntityObject
	 *
	 * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=11689&LESSON_PATH=3913.3516.5748.11687.11689
	 */
	public function getSyncEntityObject(): OrmEntityObject
	{
		$entity = call_user_func([$this->getSyncClassSyncTable(), 'createObject']);
		
		if(!($entity instanceof OrmEntityObject))
		{
			throw new \LogicException('Wrong syncEntityObject');
		}
		
		return $entity;
	}
	
	/**
	 * Возвращает коллекцию объекта синхронизации
	 * @return OrmCollection
	 *
	 * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&CHAPTER_ID=011743&LESSON_PATH=3913.3516.5748.11743
	 */
	public function getSyncCollection(): OrmCollection
	{
		$entity = call_user_func([$this->getSyncClassSyncTable(), 'createCollection']);
		
		if(!($entity instanceof OrmCollection))
		{
			throw new \LogicException('Wrong syncCollection');
		}
		
		return $entity;
	}
}