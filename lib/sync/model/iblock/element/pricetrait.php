<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Shef\InSync\Sync\Model\Catalog\Driver;

/**
 * Трейт цены
 *
 * @see Driver\Price
 */
trait PriceTrait
{
	protected null|Driver\Price $priceDriver = null;
	
	public function getPriceDriver(): Driver\Price
	{
		if(null === $this->priceDriver)
		{
			$this->priceDriver = new Driver\Price();
		}
		
		return $this->priceDriver;
	}
	
	/**
	 * Устанавливает данные по товару
	 *
	 * @param array $params
	 * @return Result
	 */
	public function configurePrice(array $params): Result
	{
		$result = new Result;
		
		if((int)$params['CATALOG_GROUP_ID'] < 1)
		{
			return $result->addError(new Error('Wrong params CATALOG_GROUP_ID'));
		}
		
		$response = $this->getPriceDriver()->save(
			[
				'CATALOG_GROUP_ID' => $params['CATALOG_GROUP_ID'],
				'PRODUCT_ID' => $this->getId()
			],
			$params
		);
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		
		return $result;
	}
}