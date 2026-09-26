<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Shef\InSync\Sync\Model\Catalog\Driver;

/**
 * Трейт остатков
 *
 * @see Driver\Amount
 */
trait AmountTrait
{
	protected null|Driver\Amount $amountDriver = null;
	
	public function getAmountDriver(): Driver\Amount
	{
		if(null === $this->amountDriver)
		{
			$this->amountDriver = new Driver\Amount();
		}
		
		return $this->amountDriver;
	}
	
	/**
	 * Устанавливает данные по товару
	 *
	 * @param array $params
	 * @return Result
	 */
	public function configureAmount(array $params): Result
	{
		$result = new Result;
		
		$response = $this->getAmountDriver()->save(
			['PRODUCT_ID' => $this->getId()],
			$params
		);
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		
		return $result;
	}
}