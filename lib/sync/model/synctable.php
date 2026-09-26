<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\ORM\Data\DataManager;
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
	 * @throws SystemException
	 */
	public static function getMap(): array
	{
		return [
			(new StringField('ORIGIN_ID', [
				'primary' => true,
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
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws SystemException
	 * 
	 * @memo SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'shef_insync_model'
	 */
	public static function init(): void
	{
		$connection = Application::getConnection();
		
		$documentsEntity = static::getEntity();
		
		if(!$connection->isTableExists($documentsEntity->getDBTableName())) 
		{
			$documentsEntity->createDbTable();
			
			$sql = sprintf(
				'ALTER TABLE %s MODIFY ADDITIONAL MEDIUMTEXT;',
				self::getTableName()
			);
			$connection->queryExecute($sql);
			
			$sql = sprintf(
				'CREATE INDEX %s_origs ON %s ( ORIGIN_ID(40), ORIGINATOR_ID(18) );',
				self::getTableName(),
				self::getTableName()
			);
			$connection->queryExecute($sql);
			
			$sql = sprintf(
				'CREATE INDEX %s_orig_id ON %s ( ORIGIN_ID(40) );',
				self::getTableName(),
				self::getTableName()
			);
			$connection->queryExecute($sql);
			
			$sql = sprintf(
				'CREATE INDEX %s_originator_id ON %s ( ORIGINATOR_ID(18) );',
				self::getTableName(),
				self::getTableName()
			);
			$connection->queryExecute($sql);
			
			unset($sql);
		}
		
		unset($documentsEntity);
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