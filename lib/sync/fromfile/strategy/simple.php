<?php declare(strict_types=1);

namespace Shef\InSync\Sync\FromFile\Strategy;

use Bitrix\Main\ORM;
use Shef\InSync\Sync\IElement;
use Shef\InSync\Agents;

/**
 * Просто выбирает все элементы и если ошибка, помечает их плохими.
 * При следующей итерации плохие элементы будут снова выбраны
 */
class Simple
	extends AStrategy
	implements IStrategy
{
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
				'=ORIGINATOR_ID' => $originatorId
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
		return $row->saveInterface();
	}
}