<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element;

use LogicException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Shef\InSync\Sync\Model;
use Shef\Options\TraitList;

/**
 * Таблет для Элементов
 * знает про iblockId
 *
 * Это голый таблет без привязки к элементу инфоблока
 *
 * Для нормальной работы нужно
 * - в настройках инфоблока прописать Символьный код API [API_CODE]
 * - унаследовать Bitrix\Iblock\Elements\Element{API_CODE}Table
 * - унаследовать Bitrix\Iblock\Elements\EO_Element{API_CODE}
 * - унаследовать Bitrix\Iblock\Elements\EO_Element{API_CODE}_Collection
 *
 *
 * @see \Shef\Demosync\FromFile\Xml\IBlock\Element\AgentProcess::processRowElement
 * @see \Shef\Demosync\FromFile\Xml\IBlock\Element\Model\ElementTable
 *
 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=12866&LESSON_PATH=3913.3516.5748.12864.12866 Концепция и архитектура
 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=23152&LESSON_PATH=3913.3516.5748.12864.23152 Наследование
 **/
class ElementTable
	extends \Bitrix\Iblock\ElementTable
	implements
		Model\IBlock\IIBlockId,
		Model\IBlock\IFixGetList,
		Model\IBlock\ICheckCode,
		Model\IBlock\IGetByXmlId
{
	use TraitList\Tools\XmlId;
	use Model\IBlock\IBlockIdTrait;
	use Model\IBlock\GetListTrait;
	use Model\IBlock\CheckCodeTrait;
	use Model\IBlock\GetByXmlIdTrait;
	
	public static function getObjectClass(): string|EntityObject
	{
		return Element::class;
	}
	
	public static function getCollectionClass(): string|Collection
	{
		return ElementCollection::class;
	}
	
	/**
	 * Подмена getList
	 *
	 * @param array $parameters
	 *
	 * @return QueryResult
	 * @throws ArgumentException
	 * @throws SystemException
	 * @throws LogicException
	 *
	 * @memo Это нужно тк сущность компилируется без учета инфоблока
	 * @see \Bitrix\Main\ORM\Data\DataManager::getList
	 */
	public static function getList(array $parameters = []): QueryResult
	{
		return parent::getList(static::prepareParamsForGetList($parameters));
	}
}