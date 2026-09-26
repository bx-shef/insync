<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;

class SyncCollection
	extends EO_Sync_Collection
{
	/**
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function getStatistic(): array
	{
		$sql = "
			SELECT ORIGINATOR_ID, DATE_INSERT, COUNT(ORIGIN_ID) as CNT
			FROM ".$this->entity->getDBTableName()."
			GROUP BY ORIGINATOR_ID, DATE_INSERT
			ORDER BY DATE_INSERT ASC
		";
		
		$result = $this->entity->getConnection()->query($sql)->fetchAll();
		
		return $result;
	}
	
	/**
	 * Сколько строк в таблице импорта у одного импорта.
	 *
	 * @return array{ORIGINATOR_ID: string, CNT: int}
	 */
	public function getStatisticByOriginator(string $importCode): array
	{
		$connection = $this->entity->getConnection();
		
		$sql = sprintf(
			'SELECT COUNT(ORIGIN_ID) AS CNT FROM %s WHERE %s',
			$this->entity->getDBTableName(),
			static::buildWhere($connection->getSqlHelper(), $importCode)
		);
		
		$row = $connection->query($sql)->fetch();
		
		return [
			'ORIGINATOR_ID' => $importCode,
			'CNT' => (int)(is_array($row) ? ($row['CNT'] ?? 0) : 0),
		];
	}
	
	/**
	 * Удаляет строки импорта: одного импорта, одной загрузки или все.
	 */
	public function clear(
		string $importCode = '',
		null|\Bitrix\Main\Type\DateTime $dateTime = null
	): void
	{
		$connection = $this->entity->getConnection();
		
		$sql = sprintf(
			'DELETE FROM %s WHERE %s',
			$this->entity->getDBTableName(),
			static::buildWhere($connection->getSqlHelper(), $importCode, $dateTime)
		);
		
		$connection->queryExecute($sql);
	}
	
	/**
	 * Условие по коду импорта и дате загрузки.
	 *
	 * Значения экранируются. До 2.0.0 код импорта вставлялся в SQL как есть,
	 * а в clear() он приходит из ajax-действия компонента статистики —
	 * параметром запроса. Это была SQL-инъекция для любого вошедшего на
	 * портал.
	 *
	 * @param object $sqlHelper \Bitrix\Main\DB\SqlHelper
	 */
	public static function buildWhere(
		object $sqlHelper,
		string $importCode = '',
		null|\Bitrix\Main\Type\DateTime $dateTime = null
	): string
	{
		$where = ['1 = 1'];
		
		if($importCode !== '')
		{
			$where[] = "ORIGINATOR_ID = '".$sqlHelper->forSql($importCode)."'";
		}
		
		if($dateTime instanceof \Bitrix\Main\Type\DateTime)
		{
			$where[] = "DATE_INSERT = '".$sqlHelper->forSql($dateTime->format('Y-m-d H:i:s'))."'";
		}
		
		return implode(' AND ', $where);
	}
}