<?php declare(strict_types=1);

namespace Shef\InSync\Sync\FromFile\Strategy;

use Bitrix\Main\ORM;
use Shef\InSync\Sync\IElement;
use Shef\InSync\Agents;

abstract class AStrategy
	implements IStrategy
{
	public function __construct(
		public readonly int $limitRows = 100
	)
	{
	}
	
	/**
	 * @inheritDoc
	 */
	public function getMaxStep(Agents\AAgent $agent): int
	{
		$result = $this->limitRows;
		
		if($agent->isDebug())
		{
			$result = 1;
		}
		
		return $result;
	}
	
	/**
	 * @inheritDoc
	 */
	public function isUseOneRowDebug(Agents\AAgent $agent): bool
	{
		if(
			$agent->isDebug()
			&& $agent->getParams()->get('ORIGIN_ID')
		)
		{
			return true;
		}
		
		return false;
	}
	
	/**
	 * @inheritDoc
	 */
	abstract public function getListConf(string $originatorId, Agents\AAgent $agent): array;
	
	/**
	 * @inheritDoc
	 */
	abstract public function processFail(IElement $row): ORM\Data\AddResult|ORM\Data\UpdateResult|ORM\Data\Result;
}