<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model;

use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;

/*/
//title: Model\SyncCollection::getStatistic()
	\Bitrix\Main\Loader::includeModule('shef.insync');
	\Bitrix\Main\UI\Extension::load([
		'shef-problems-monolog-pr-html'
	]);
	$syncCollection = \Shef\InSync\Sync\Model\SyncTable::createCollection();
	$response = $syncCollection->getStatistic();
	\Shef\Problems\Logger::PrHtml->getLogger()->debug($response);
	
//title: Model\SyncCollection::clear()
	\Bitrix\Main\UI\Extension::load([
		'shef-problems-monolog-pr-html'
	]);
	\Bitrix\Main\Loader::includeModule('shef.insync');
	$syncCollection = \Shef\InSync\Sync\Model\SyncTable::createCollection();
	$importCode = 'ShefDemosyncFromFileCsv';
	$syncCollection->clear($importCode);
	$response = $syncCollection->getStatistic();
	\Shef\Problems\Logger::PrHtml->getLogger()->debug($response);
//*/

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
	
	public function getStatisticByOriginator(string $importCode): array
	{
		$sql = "
			SELECT ORIGINATOR_ID, COUNT(ORIGIN_ID) as CNT
			FROM ".$this->entity->getDBTableName()."
			WHERE `ORIGINATOR_ID` = '".$importCode."'
			ORDER BY DATE_INSERT ASC
		";
		
		$result = $this->entity->getConnection()->query($sql)->fetchRaw();
		
		return $result;
	}
	
	/**
	 * @throws SqlQueryException|SystemException
	 */
	public function clear(
		string $importCode = '',
		null|\Bitrix\Main\Type\DateTime $dateTime = null
	): void
	{
		$sql = sprintf(
			'DELETE FROM %s WHERE 1 = 1 %s',
			$this->entity->getDBTableName(),
			join(' ', [
				(
					$importCode <> ''
					? 'AND `ORIGINATOR_ID` = \''.$importCode.'\''
					: ''
				),
				(
				$dateTime instanceof \Bitrix\Main\Type\DateTime
					? 'AND `DATE_INSERT` = \''.($dateTime->format('Y-m-d H:i:s')).'\''
					: ''
				),
			])
		);
		$this->entity->getConnection()->queryExecute($sql);
		
		unset($sql);
	}
}