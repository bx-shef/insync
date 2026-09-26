<?php declare(strict_types=1);

namespace Shef\InSync\Sync\FromFile\Strategy;

use Bitrix\Main\ORM;
use Shef\InSync\Sync\IElement;
use Shef\InSync\Agents;
use Shef\InSync\Sync;

/**
 * Выбирает не плохие элементы, плохие помечает и меняет источник
 * При следующей итерации плохие элементы НЕ будут снова выбраны
 */
class HideFail
	extends MarkFail
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
				'=ORIGINATOR_ID' => $originatorId,
				'!=STATUS' => Sync\EStatus::Fail->value
			]
		];
		
		if($this->isUseOneRowDebug($agent))
		{
			$conf['filter']['=ORIGIN_ID'] = $agent->getParams()->get('ORIGIN_ID');
		}
		
		return $conf;
	}
}