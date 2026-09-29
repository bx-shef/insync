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
		$target = $originatorIdClear.static::Marker;
		
		if($row->getInterfaceOriginatorId() !== $target)
		{
			static::removeMarked($row, $target);
		}
		
		$row->setInterfaceOriginatorId($target);
		
		return $row->saveInterface();
	}
	
	/**
	 * Та же строка, упавшая раньше, уступает место новой.
	 *
	 * Внешний код уникален в пределах кода импорта: строка «123», упавшая
	 * вчера, уже лежит под «<код>.error», и переименование сегодняшней «123»
	 * упёрлось бы в уникальный индекс.
	 */
	protected static function removeMarked(IElement $row, string $target): void
	{
		if(!property_exists($row, 'dataClass'))
		{
			return;
		}
		
		/** @var class-string<ORM\Data\DataManager> $dataClass */
		$dataClass = $row::$dataClass;
		
		$list = $dataClass::getList([
			'filter' => [
				'=ORIGINATOR_ID' => $target,
				'=ORIGIN_ID' => $row->getInterfaceOriginId(),
			],
			'select' => [
				'ID'
			]
		]);
		
		while($item = $list->fetch())
		{
			$dataClass::delete($item['ID']);
		}
	}
	
	
}