<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\SystemException;
use Shef\InSync\Sync\Model\Catalog as SyncCatalog;
use Shef\InSync\Sync\Model\Catalog\Driver;

/**
 * Трейт продукта
 *
 * @see Driver\Product
 */
trait ProductTrait
{
	protected null|Driver\Product $productDriver = null;
	
	public function getProductDriver(): Driver\Product
	{
		if(null === $this->productDriver)
		{
			$this->productDriver = new Driver\Product();
		}
		
		return $this->productDriver;
	}
	
	/**
	 * Устанавливает данные по товару
	 *
	 * @param array $params
	 * @return Result
	 */
	public function configureProduct(array $params): Result
	{
		$result = new Result;
		
		$response = $this->getProductDriver()->save(
			['ID' => $this->getId()],
			$params
		);
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		
		return $result;
	}
	
	/**
	 * Возвращает объект каталога с информацией
	 *
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function getCatalogObject(
		array $select = []
	): null|SyncCatalog\Product
	{
		
		return $this->getProductDriver()->getOrm()::getList([
			'filter' => [
				'=ID' => $this->getId()
			],
			'select' => array_merge(
				[
					'ID',
					
					'QUANTITY',
					'QUANTITY_TRACE',
					'CAN_BUY_ZERO',
					
					'MEASURE',
					'WIDTH',
					'LENGTH',
					'HEIGHT',
						
					'WEIGHT',
						
					'VAT_ID',
					'VAT_INCLUDED',
					'SH_VAT'
				],
				$select
			),
		])->fetchObject();
	}
	
	/**
	 * Обновляет остатки и устанавливает доступность
	 *
	 * @param float $quantity
	 * @return Result
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function updateQuantity(float $quantity): Result
	{
		$result = new Result;
		
		$response = $this->prepareQuantity($quantity);
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		
		$response = $this->configureProduct($response->getData());
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		
		return $result;
	}
	
	/**
	 * Рассчитывает доступность по остаткам
	 *
	 * @param float $quantity
	 * @return Result
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function prepareQuantity(float $quantity): Result
	{
		$result = new Result;
		
		$catalog = $this->getCatalogObject();
		
		if(null === $catalog)
		{
			return $result->addError(new Error(sprintf(
				'Catalog info not init for sku=%s',
				$this->getId()
			)));
		}
		
		return $result->setData([
			'QUANTITY' => $quantity,
			'AVAILABLE' => ($catalog::$dataClass)::calculateAvailable([
				'QUANTITY' => $quantity,
				'QUANTITY_TRACE' => $catalog->getQuantityTrace(),
				'CAN_BUY_ZERO' => $catalog->getCanBuyZero()
			])
		]);
	}
}