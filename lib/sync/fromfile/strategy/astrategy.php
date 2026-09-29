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
	 * Код импорта, под которым стратегия держит ошибочные строки отдельно
	 * (MarkFail: «<код>.error»), либо null — отдельно не держит.
	 *
	 * Внешний код уникален в пределах кода импорта, поэтому строка «123» может
	 * лежать и под «<код>», и под «<код>.error». Та, что под меткой, —
	 * вчерашний сбой: агент убирает её, когда есть свежая, иначе повторная
	 * удачная попытка вчерашней строки записала бы старые данные поверх новых.
	 * Не в IStrategy, чтобы не ломать свои стратегии модулей импорта.
	 */
	public function getSupersededOriginatorId(string $originatorId): ?string
	{
		return null;
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