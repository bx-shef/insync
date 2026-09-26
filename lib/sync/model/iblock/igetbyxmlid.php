<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;

/**
 * Интерфейс выбира сущности по внешнему коду
 * @see \Shef\InSync\Sync\Model\IBlock\GetByXmlIdTrait
 */
interface IGetByXmlId
{
	/**
	 * Выбираем сущность по XML_ID
	 *
	 * @param string $xmlId
	 * @param array $select
	 * @return EntityObject|null
	 *
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function getByXmlId(
		string $xmlId,
		array $select = []
	): null|\Bitrix\Main\ORM\Objectify\EntityObject;
}