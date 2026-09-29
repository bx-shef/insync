<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\EnumField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\SystemException;
use Shef\Options\TraitList;
use Shef\InSync\Sync\EStatus;

/**
 * Class SyncTable
 *
 * DO NOT WRITE ANYTHING BELOW THIS
 *
 * <<< ORMENTITYANNOTATION: Shef\InSync\Sync\Model\SyncTable
 * @method static EO_Sync_Query query()
 * @method static EO_Sync_Result getByPrimary($primary, array $parameters = [])
 * @method static EO_Sync_Result getById($id)
 * @method static EO_Sync_Result getList(array $parameters = [])
 * @method static EO_Sync_Entity getEntity()
 * @method static \Shef\InSync\Sync\Model\Sync createObject($setDefaultValues = true)
 * @method static \Shef\InSync\Sync\Model\SyncCollection createCollection()
 * @method static \Shef\InSync\Sync\Model\Sync wakeUpObject($row)
 * @method static \Shef\InSync\Sync\Model\SyncCollection wakeUpCollection($rows)
 */

class SyncTable extends DataManager
{
	use TraitList\Tools\XmlId;
	
	public static function getTableName(): string
	{
		return 'shef_insync_model';
	}
	
	public static function getObjectClass(): string
	{
		return Sync::class;
	}
	
	public static function getCollectionClass(): string
	{
		return SyncCollection::class;
	}
	
	/**
	 * Строка импорта: ID — свой, внешний код (ORIGIN_ID) уникален в пределах
	 * кода импорта (ORIGINATOR_ID), а не во всей таблице.
	 *
	 * До 2.0.0 первичным ключом был один ORIGIN_ID: два импорта с одинаковым
	 * внешним кодом (артикул в прайсе и в остатках) сталкивались на INSERT.
	 * Составным ключом не сделано: стратегия MarkFail меняет код импорта у
	 * существующей строки, а часть первичного ключа ORM не меняет.
	 *
	 * @throws SystemException
	 */
	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete()
			,
			(new StringField('ORIGIN_ID', [
				'required' => true
			]))
			->configureDefaultValue([static::class, 'getDefaultOriginatorId'])
			,
			new StringField('ORIGINATOR_ID', [
				'required' => true
			]),
			new DatetimeField('DATE_INSERT'),
			new StringField('TITLE'),
			
			(new EnumField('STATUS'))
				->configureRequired()
				->configureValues(EStatus::getValues())
				->configureDefaultValue([EStatus::class, 'getDefault'])
				->addSaveDataModifier(function (string|EStatus $status): string
				{
					if($status instanceof EStatus)
					{
						return $status->value;
					}
					
					return $status;
				})
			,
			new TextField('MESSAGE'),
			(new ArrayField('ADDITIONAL'))
				->configureSerializationPhp()
		];
	}
	
	public static function getDefaultOriginatorId(): string
	{
		return static::getXmlIdIdempotence();
	}
	/**
	 * Таблица импорта: создать, а таблицу 1.x — перевести на ключ 2.x.
	 *
	 * Вызывается установщиком и безопасна повторно: готовую таблицу 2.x не
	 * трогает. Порталу, обновлённому заменой файлов, её зовут руками
	 * (docs/portal-check.md, шаг B). DDL — MySQL, как и у 1.x.
	 *
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function init(): void
	{
		$connection = Application::getConnection();
		$tableName = self::getTableName();
		
		if($connection->isTableExists($tableName))
		{
			if(static::isLegacyPrimary())
			{
				// Строки 1.x уникальны по ORIGIN_ID, значит и по паре с кодом
				// импорта: уникальный индекс встанет на любые данные 1.x.
				$connection->queryExecute(sprintf(
					'ALTER TABLE %1$s DROP PRIMARY KEY, '
					.'ADD COLUMN ID INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST, '
					.'ADD UNIQUE INDEX %1$s_origin (ORIGINATOR_ID, ORIGIN_ID);',
					$tableName
				));
			}
			
			return;
		}
		
		static::getEntity()->createDbTable();
		
		// Таблица без поля MEDIUMTEXT и индекса — не та таблица: повторная
		// установка увидела бы, что она «есть», и пропустила бы всё ниже.
		// Упало — убираем свою недоделанную и отдаём ошибку дальше.
		try
		{
			$connection->queryExecute(sprintf(
				'ALTER TABLE %s MODIFY ADDITIONAL MEDIUMTEXT;',
				$tableName
			));
			
			$connection->queryExecute(sprintf(
				'CREATE UNIQUE INDEX %1$s_origin ON %1$s (ORIGINATOR_ID, ORIGIN_ID);',
				$tableName
			));
		}
		catch(\Throwable $throwable)
		{
			$connection->queryExecute(sprintf('DROP TABLE %s', $tableName));
			throw $throwable;
		}
	}
	
	/**
	 * Первичный ключ таблицы — ORIGIN_ID, как в 1.x.
	 */
	public static function isLegacyPrimary(): bool
	{
		$columns = array_column(
			Application::getConnection()
				->query(sprintf("SHOW KEYS FROM %s WHERE Key_name = 'PRIMARY'", self::getTableName()))
				->fetchAll(),
			'Column_name'
		);
		
		return $columns === ['ORIGIN_ID'];
	}
	
	/**
	 * @throws SqlQueryException
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public static function drop(): void
	{
		$connection = Application::getConnection();
		
		$documentsEntity = static::getEntity();
		if($connection->isTableExists($documentsEntity->getDBTableName()))
		{
			$sql = sprintf(
				'DROP TABLE %s;',
				self::getTableName()
			);
			$connection->queryExecute($sql);
			
			unset($sql);
		}
	}
}