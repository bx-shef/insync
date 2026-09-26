<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

use LogicException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree as Filter;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Bitrix\Main\SystemException;

/**
 * Добавляем IBlockId в фильтр
 *
 * @see \Shef\InSync\Sync\Model\IBlock\IFixGetList
 * @see \Shef\InSync\Sync\Model\IBlock\IIBlockId
 */
trait GetListTrait
{
	abstract public static function getIblockId(): int;
	
	/**
	 * @inheritDoc
	 */
	public static function prepareParamsForGetList(array $parameters = []): array
	{
		if(!isset($parameters['filter']))
		{
			$parameters['filter'] = [];
		}
		
		if($parameters['filter'] instanceof Filter)
		{
			$parameters['filter']->where('IBLOCK_ID', '=', static::getIblockId());
		}
		else
		{
			$parameters['filter']['=IBLOCK_ID'] = static::getIblockId();
		}
		
		$parameters['select'][] = 'IBLOCK_ID';
		
		return $parameters;
	}
}