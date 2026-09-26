<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\PropertyEnumeration;

use LogicException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Filter\ConditionTree as Filter;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Shef\InSync\Sync\Model;

/**
 * Таблет для PropertyEnum
 * знает про iblockId
 **/
class PropertyEnumerationTable
	extends \Bitrix\Iblock\PropertyEnumerationTable
	implements Model\IBlock\IPropertyId, Model\IBlock\IFixGetList
{
	use Model\IBlock\IPropertyIdTrait;
	
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
			$parameters['filter']->where('PROPERTY_ID', '=', static::getPropertyId());
		}
		else
		{
			$parameters['filter']['=PROPERTY_ID'] = static::getPropertyId();
		}
		
		$parameters['select'][] = 'PROPERTY_ID';
		
		return $parameters;
	}
	
	public static function getObjectClass(): string|EntityObject
	{
		return PropertyEnumeration::class;
	}
	
	public static function getCollectionClass(): string|Collection
	{
		return PropertyEnumerationCollection::class;
	}
	
	/**
	 * Подмена getList
	 *
	 * @param array $parameters
	 *
	 * @return QueryResult
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws LogicException
	 *
	 * @memo Это нужно тк сущность компилируется без учета инфоблока
	 * @see \Bitrix\Main\ORM\Data\DataManager::getList
	 */
	public static function getList(array $parameters = []): QueryResult
	{
		return parent::getList(static::prepareParamsForGetList($parameters));
	}
}