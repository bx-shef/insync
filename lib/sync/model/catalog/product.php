<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\Catalog;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\Contract\Arrayable;
use Shef\Insync\Main\Utils;

class Product
	extends EO_Product
	implements Arrayable
{
	/**
	 * @var \Shef\InSync\Sync\Model\Catalog\ProductTable
	 * @memo обязательно нужно указать свой кастомный таблет
	 */
	static public $dataClass = ProductTable::class;
	
	/**
	 * @throws ArgumentException
	 */
	public function toArray(): array
	{
		return Utils::prepareCollectValues($this);
	}
}