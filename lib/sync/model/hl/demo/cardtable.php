<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\Hl\Demo;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;

/**
 * Демо-модель highload-блока (таблица list_cards).
 *
 * Поля:
 * - ID int, обязательное
 * - UF_XML_ID text
 * - UF_SUM float
 *
 * @memo Делаем HL в админке руками/программно, идем в таблицу БД и генерируем ORM, потом делаем анатацию
 * @memo use shef-cli
 *
 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=2410&LESSON_PATH=3913.3516.5748.2410
 * @link https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=11733&LESSON_PATH=3913.3516.5748.11733
 *
 **/
Loc::loadMessages(__FILE__);

class CardTable
	extends DataManager
{
	/**
	 * Таблица highload-блока в БД.
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return 'list_cards';
	}
	
	public static function getObjectClass(): string|EntityObject
	{
		return Card::class;
	}
	
	public static function getCollectionClass(): string|Collection
	{
		return CardsCollection::class;
	}
	
	/**
	 * Поля сущности.
	 *
	 * @return array
	 * @throws SystemException
	 */
	public static function getMap(): array
	{
		return [
			'ID' => (new Fields\IntegerField('ID', []))
				->configureTitle(Loc::getMessage('CARDS_ENTITY_ID_FIELD'))
				->configurePrimary()
				->configureAutocomplete(),
			'UF_XML_ID' => (new Fields\TextField('UF_XML_ID', []))
				->configureTitle(Loc::getMessage('CARDS_ENTITY_UF_XML_ID_FIELD')),
			'UF_SUM' => (new Fields\FloatField('UF_SUM', []))
				->configureTitle(Loc::getMessage('CARDS_ENTITY_UF_SUM_FIELD')),
		];
	}
}