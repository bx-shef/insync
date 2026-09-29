<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Section;

use LogicException;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\ORM\Event;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Query\Result as QueryResult;
use Shef\Options\TraitList;
use Shef\InSync\Sync\Model;

/**
 * Таблет для разделов
 * знает про iblockId
 *
 * В отличие от элемента эта сущность не компилируется
 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=12866&LESSON_PATH=3913.3516.5748.12864.12866 Концепция и архитектура
 **/
class SectionTable
	extends \Bitrix\Iblock\SectionTable
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
	
	/**
	 * @inheritDoc
	 */
	public static function getObjectClass(): string|EntityObject
	{
		return Section::class;
	}
	
	/**
	 * @inheritDoc
	 */
	public static function getCollectionClass(): string|Collection
	{
		return SectionCollection::class;
	}
	
	/**
	 * Правка для обновления customData
	 * @memo нужно Fetch делать для выборки
	 *
	 * @param Event $event
	 * @return void
	 */
	public static function onUpdate(Event $event): void
	{
		/** @var Section $section */
		$section = $event->getParameter('object');
		
		// запоминаем прежние поля раздела
		$oldValues = \CIBlockSection::GetList([], ["ID" => $section->getId(), "CHECK_PERMISSIONS" => "N"])->Fetch();
		$section->customData->set('RECOUNT_TREE_OLD_VALUES', $oldValues);
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