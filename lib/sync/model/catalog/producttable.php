<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\Catalog;

use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;

/**
 * Class Product
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION: \Shef\InSync\Sync\Model\Catalog\ProductTable
 * @method static \Shef\InSync\Sync\Model\Catalog\EO_Product_Query query()
 * @method static \Shef\InSync\Sync\Model\Catalog\EO_Product_Result getByPrimary($primary, array $parameters = [])
 * @method static \Shef\InSync\Sync\Model\Catalog\EO_Product_Result getById($id)
 * @method static \Shef\InSync\Sync\Model\Catalog\EO_Product_Result getList(array $parameters = [])
 * @method static \Shef\InSync\Sync\Model\Catalog\EO_Product_Entity getEntity()
 * @method static \Shef\InSync\Sync\Model\Catalog\Product createObject($setDefaultValues = true)
 * @method static \Shef\InSync\Sync\Model\Catalog\ProductCollection createCollection()
 * @method static \Shef\InSync\Sync\Model\Catalog\Product wakeUpObject($row)
 * @method static \Shef\InSync\Sync\Model\Catalog\ProductCollection wakeUpCollection($rows)
 */
class ProductTable
	extends \Bitrix\Catalog\ProductTable
{
	public static function getMap(): array
	{
		$result = parent::getMap();
		$result['SH_VAT'] = new \Bitrix\Main\ORM\Fields\Relations\Reference(
			'SH_VAT',
			'\Bitrix\Catalog\VatTable',
			['=this.VAT_ID' => 'ref.ID'],
			['join_type' => 'LEFT']
		);
		
		return $result;
	}
	
	public static function getObjectClass(): string|EntityObject
	{
		return Product::class;
	}
	
	public static function getCollectionClass(): string|Collection
	{
		return ProductCollection::class;
	}
}