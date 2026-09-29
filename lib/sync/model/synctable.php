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
	
	/**
	 * Уникальность внешнего кода в пределах кода импорта. Префиксы — чтобы
	 * индекс влез везде, где влезал первичный ключ 1.x (ORIGIN_ID целиком,
	 * 255 символов): 64 + 191 = 255, в utf8 — 765 байт при пределе 767 для
	 * старого формата строк. Код импорта длиннее 64 символов или внешний код
	 * длиннее 191 сравниваются по началу — и код импорта от 64 символов не
	 * отличил бы «<код>.error» стратегии MarkFail от «<код>».
	 */
	public const UNIQUE_COLUMNS = '(ORIGINATOR_ID(64), ORIGIN_ID(191))';
	
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
	 * трогает, таблицу с незнакомым ключом — тоже (исключение). Порталу, обновлённому заменой файлов, её зовут руками
	 * (docs/portal-check.md, шаг B). DDL — MySQL, как и у 1.x.
	 *
	 * @throws ArgumentException
	 * @throws SqlQueryException
	 * @throws SystemException
	 */
	public static function init(): void
	{
		$connection = Application::getConnection();
		$tableName = static::getTableName();
		
		if($connection->isTableExists($tableName))
		{
			$primary = static::getPrimaryColumns();
			if($primary === ['ID'])
			{
				return;
			}
			
			// Не ключ 2.x и не ключ 1.x — таблицу делал не модуль: не трогаем
			// и говорим об этом, а не перестраиваем чужую схему.
			if($primary !== ['ORIGIN_ID'])
			{
				throw new SystemException(sprintf(
					'Unknown primary key of %s: [%s]',
					$tableName,
					implode(', ', $primary)
				));
			}
			
			// Строки 1.x уникальны по ORIGIN_ID целиком, а индекс — по первым
			// 191 символу: внешние коды 1.x, совпадающие в начале, не дадут
			// индексу встать — ALTER откажет целиком, строки не тронет
			// (проверка до перевода — docs/portal-check.md, шаг B). Индексы
			// 1.x по префиксам колонок новый индекс покрывает — снимаются.
			// ADDITIONAL — ещё раз: установщик 1.x глушил сбой DDL, и поле
			// могло остаться TEXT (64 КБ).
			$legacyIndexList = array_intersect(
				[$tableName.'_origs', $tableName.'_orig_id', $tableName.'_originator_id'],
				static::getIndexNames()
			);
			
			$connection->queryExecute(sprintf(
				'ALTER TABLE %1$s DROP PRIMARY KEY, %2$s'
				.'ADD COLUMN ID INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST, '
				.'MODIFY ADDITIONAL MEDIUMTEXT, '
				.'ADD UNIQUE INDEX %1$s_origin %3$s;',
				$tableName,
				implode('', array_map(static fn(string $name): string => 'DROP INDEX '.$name.', ', $legacyIndexList)),
				static::UNIQUE_COLUMNS
			));
			
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
				'CREATE UNIQUE INDEX %1$s_origin ON %1$s %2$s;',
				$tableName,
				static::UNIQUE_COLUMNS
			));
		}
		catch(\Throwable $throwable)
		{
			$connection->queryExecute(sprintf('DROP TABLE %s', $tableName));
			throw $throwable;
		}
	}
	
	/**
	 * Колонки первичного ключа таблицы по порядку: 2.x — ['ID'], 1.x —
	 * ['ORIGIN_ID'].
	 */
	public static function getPrimaryColumns(): array
	{
		$rows = Application::getConnection()
			->query(sprintf("SHOW KEYS FROM %s WHERE Key_name = 'PRIMARY'", static::getTableName()))
			->fetchAll();
		
		usort($rows, static fn(array $a, array $b): int => (int)($a['Seq_in_index'] ?? 0) <=> (int)($b['Seq_in_index'] ?? 0));
		
		return array_values(array_map(static fn(array $row): string => (string)($row['Column_name'] ?? ''), $rows));
	}
	
	/**
	 * Имена индексов таблицы.
	 */
	protected static function getIndexNames(): array
	{
		$rows = Application::getConnection()
			->query(sprintf('SHOW INDEX FROM %s', static::getTableName()))
			->fetchAll();
		
		return array_values(array_unique(array_map(static fn(array $row): string => (string)($row['Key_name'] ?? ''), $rows)));
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
				static::getTableName()
			);
			$connection->queryExecute($sql);
			
			unset($sql);
		}
	}
}