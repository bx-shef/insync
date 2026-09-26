<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\Store;

use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;

/**
 * Склады
 */
class StoreTable
	extends \Bitrix\Catalog\StoreTable
{
	public static function getObjectClass(): string|EntityObject
	{
		return Store::class;
	}
	
	public static function getCollectionClass(): string|Collection
	{
		return StoreCollection::class;
	}
}