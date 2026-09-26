<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

use Bitrix\Main\ORM\Query\Result as QueryResult;
use Shef\Options\TraitList;

/**
 * Делает символьный код уникальным
 * @see \Shef\InSync\Sync\Model\IBlock\ICheckCode
 */
trait CheckCodeTrait
{
	use TraitList\Tools\XmlId;
	
	abstract public static function getList(array $parameters = []): QueryResult;
	
	/**
	 * @inheritDoc
	 */
	public static function checkCode(
		string $code
	): string
	{
		$row = static::getList([
			'select' => [
				'ID'
			],
			'filter' => [
				'=CODE' => $code
			],
			'limit' => 1
		])->fetchRaw();
		
		if(is_array($row))
		{
			$code = static::getCodeIdempotence($code);
		}
		
		return $code;
	}
}