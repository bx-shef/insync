<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\Catalog;

class ProductCollection
	extends EO_Product_Collection
{
	/**
	 * @var string
	 * @memo обязательно нужно указать свой кастомный таблет
	 */
	static public $dataClass = ProductTable::class;
}