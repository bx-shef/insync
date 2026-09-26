<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\Catalog\Driver;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Catalog\Model as CatalogModel;
use Shef\InSync\Sync;

/**
 * Работа с данными по товару
 *
 * - вес
 * - габариты
 * - единица измерения
 * - цена/валюта закупки
 * - НДС
 * - общий остаток
 * - и тп.
 *
 * @see \Bitrix\Catalog\ProductTable
 *
 * @memo стоит смотреть как поведет себя доступность у элемента каталога
 * @memo при работе со складски учетом - нужно импортировать остатки через документы складского учета
 */
class Product
	implements Sync\IDriver, ICatalogModel, ISave
{
	private CatalogModel\Product $obj;
	
	public function __construct()
	{
		$this->obj = new CatalogModel\Product();
	}
	
	public function getObject(): CatalogModel\Product
	{
		return $this->getModel();
	}
	
	public function getOrm(): string|\Bitrix\Catalog\ProductTable|Sync\Model\Catalog\ProductTable
	{
		return Sync\Model\Catalog\ProductTable::class;
		// return $this->obj::getTabletClassName(); ////
	}
	
	public function getModel(): CatalogModel\Product
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
		
		if((int)$primary['ID'] < 1)
		{
			return $result->addError(new Error('Wrong primary ID'));
		}
		
		$entity = $this->getModel()::getRow([
			'filter' => [
				'=ID' => $primary['ID']
			]
		]);
		
		if(null === $entity)
		{
			$response = $this->getModel()::add(array_merge(
				$params,
				['ID' => $primary['ID']]
			));
		}
		else
		{
			$response = $this->getModel()::update(
				$primary['ID'],
				$params
			);
		}
		
		if(!$response->isSuccess())
		{
			return $result->addError(new Error(
				'Error save product info',
				Sync\IDriver::ErrorSave,
				array_merge(
					$params,
					['ID' => $primary['ID']]
				)
			))
			->addErrors($response->getErrors());
		}
		
		return $result;
	}
}