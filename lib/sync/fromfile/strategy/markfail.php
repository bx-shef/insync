<?php declare(strict_types=1);

namespace Shef\InSync\Sync\FromFile\Strategy;

use Bitrix\Main\ORM;
use Shef\InSync\Sync\IElement;
use Shef\InSync\Agents;
use Shef\InSync\Sync;

/**
 * Выбирает любые элементы, плохие помечает и меняет источник
 * При следующей итерации плохие элементы будут снова выбраны
 */
class MarkFail
	extends AStrategy
	implements IStrategy
{
	protected const Marker = '.error';
	
	/**
	 * @inheritDoc
	 */
	public function getListConf(string $originatorId, Agents\AAgent $agent): array
	{
		$conf = [
			'order' => [
				'DATE_INSERT' => 'ASC'
			],
			'limit' => $this->getMaxStep($agent),
			'filter' => [
				'=ORIGINATOR_ID' => [
					$originatorId,
					$originatorId.static::Marker
				]
			]
		];
		
		if($this->isUseOneRowDebug($agent))
		{
			$conf['filter']['=ORIGIN_ID'] = $agent->getParams()->get('ORIGIN_ID');
		}
		
		return $conf;
	}
	
	/**
	 * @inheritDoc
	 */
	public function processFail(IElement $row): ORM\Data\AddResult|ORM\Data\UpdateResult|ORM\Data\Result
	{
		$originatorIdClear = str_replace(static::Marker, '', $row->getInterfaceOriginatorId());
		
		$row->setInterfaceOriginatorId($originatorIdClear.static::Marker);
		
		return $row->saveInterface();
	}
	
	/**
	 * Ошибочные строки лежат под «<код>.error»: свежая строка с тем же
	 * внешним кодом делает их устаревшими.
	 */
	public function getSupersededOriginatorId(string $originatorId): ?string
	{
		return $originatorId.static::Marker;
	}
}