<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\Catalog\Driver;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\DataManager;
use Shef\InSync\Sync;

/**
 * Остатки по складам
 *
 * @memo при работе со складски учетом - нужно импортировать остатки через документы складского учета
 */
class Amount
	implements Sync\IDriver, ISave
{
	private DataManager $obj;
	
	public function __construct()
	{
		$this->obj = new \Bitrix\Catalog\StoreProductTable();
	}
	
	public function getObject(): \Bitrix\Catalog\StoreProductTable
	{
		return $this->obj;
	}
	
	public function getOrm(): DataManager
	{
		return $this->getObject();
	}
	
	private function updateFromForm(array $conf): Result
	{
		$result = new Result();
		
		$response = \CCatalogStoreProduct::UpdateFromForm($conf);
		if($response === false)
		{
			return $result->addError(new Error(
				'Error update store amount',
				Sync\IDriver::ErrorSave,
				$conf
			));
		}
		
		return $result;
	}
	
	/**
	 * @inheritDoc
	 */
	public function save(
		array $primary,
		array $params
	): Result
	{
		if((int)$primary['PRODUCT_ID'] < 1)
		{
			return (new Result)->addError(new Error('Wrong primary PRODUCT_ID'));
		}
		elseif((int)$params['STORE_ID'] < 1)
		{
			return (new Result)->addError(new Error('Wrong params STORE_ID'));
		}
		elseif(!isset($params['AMOUNT']))
		{
			return (new Result)->addError(new Error('Wrong params AMOUNT'));
		}
		
		return $this->updateFromForm(array_merge(
			$params,
			['PRODUCT_ID' => $primary['PRODUCT_ID']]
		));
	}
}