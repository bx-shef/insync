<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\ORM\Query\Result as QueryResult;

/**
 * Выбираем сущность по внешнему коду
 *
 * @see \Shef\InSync\Sync\Model\IBlock\IGetByXmlId
 */
trait GetByXmlIdTrait
{
	abstract public static function getList(array $parameters = []): QueryResult;
	
	/**
	 * @inheritDoc
	 */
	public static function getByXmlId(
		string $xmlId,
		array $select = []
	): null|\Bitrix\Main\ORM\Objectify\EntityObject
	{
		return static::getList([
			'select' => array_merge(
				[
					'ID',
					'XML_ID'
				],
				$select
			),
			'filter' => [
				'=XML_ID' => $xmlId
			],
			'limit' => 1
		])->fetchObject();
	}
}