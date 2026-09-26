<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\Catalog\Driver;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Catalog\Model as CatalogModel;
use Shef\InSync\Sync;

/**
 * Работа с ценами на товары
 *
 * @memo Стоит проверить как поведет себя при работе с наценкой от базоваой цены
 * @memo Цена закупки делается не через этот класс
 */
class Price
	implements Sync\IDriver, ICatalogModel, ISave
{
	private CatalogModel\Price $obj;
	
	public function __construct()
	{
		$this->obj = new CatalogModel\Price();
	}
	
	public function getObject(): CatalogModel\Price
	{
		return $this->getModel();
	}
	
	public function getOrm(): string|DataManager
	{
		return $this->obj::getTabletClassName();
	}
	
	public function getModel(): CatalogModel\Price
	{
		return $this->obj;
	}
	
	/**
	 * @inheritDoc
	 */
	public function save(
		array $primary,
		array $params
	): Result
	{
		$result = new Result();
		
		$conf = [
			'CATALOG_GROUP_ID' => (int)$primary['CATALOG_GROUP_ID'],
			'PRODUCT_ID' => (int)$primary['PRODUCT_ID'],
			'PRICE' => (float)$params['PRICE'],
			'PRICE_SCALE' => (float)($params['PRICE_SCALE'] ?: $params['PRICE']),
			'CURRENCY' => (string)$params['CURRENCY'],
		];
		
		if((int)$conf['PRODUCT_ID'] < 1)
		{
			return $result->addError(new Error('Wrong primary PRODUCT_ID'));
		}
		elseif((int)$conf['CATALOG_GROUP_ID'] < 1)
		{
			return $result->addError(new Error('Wrong primary CATALOG_GROUP_ID'));
		}
		elseif($conf['CURRENCY'] === '')
		{
			return $result->addError(new Error('Wrong params CURRENCY'));
		}
		
		$entity = $this->getModel()::getRow([
			'filter' => [
				'=CATALOG_GROUP_ID' => $conf['CATALOG_GROUP_ID'],
				'=PRODUCT_ID' => $conf['PRODUCT_ID']
			],
			'select' => [
				'ID'
			]
		]);
		
		if((int)$entity['ID'] < 1)
		{
			$response = $this->getModel()::add($conf);
		}
		else
		{
			unset($conf['CATALOG_GROUP_ID'], $conf['PRODUCT_ID']);
			
			$response = $this->getModel()::update(
				(int)$entity['ID'],
				$params
			);
		}
		
		if(!$response->isSuccess())
		{
			return $result->addError(new Error(
				'Error save product price',
				Sync\IDriver::ErrorSave,
				$conf
			))
			->addErrors($response->getErrors());
		}
		
		return $result;
	}
}