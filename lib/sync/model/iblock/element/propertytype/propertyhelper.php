<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element\PropertyType;

use Bitrix\Main\DB\SqlExpression;
use Shef\Insync\Main\Utils;

class PropertyHelper
{
	public const Empty = 'Empty';

	public static function generateXmlId(string $value): string
	{
		if($value === '')
		{
			$value = static::Empty;
		}
		
		return Utils::translation($value);
	}
	
	public function __construct(
		public readonly Property $property,
		public readonly string $value,
		public readonly string $xmlId,
	)
	{
	}
	
	public function getEmpty(): SqlExpression
	{
		return new SqlExpression('NULL');
	}
	
	public function getByValue(): mixed
	{
		return $this->property->getByValue($this);
	}
	
	public function getByXmlId(): mixed
	{
		return $this->property->getByXmlId($this);
	}
	
	public function create(): mixed
	{
		return $this->property->create($this);
	}
	
	public static function getPropertyByCode(
		int $iblockId,
		string $code
	): null|array
	{
		static $list;
		if(null === $list[$iblockId][$code])
		{
			$rows = \Bitrix\Iblock\PropertyTable::getList([
				'filter' => [
					'=IBLOCK_ID' => $iblockId
				],
				'select' => [
					'ID', 'CODE', 'IBLOCK_ID', 'SORT',
					'NAME', 'MULTIPLE'
				]
			])->fetchAll();
			
			foreach($rows as $row)
			{
				$row['ID'] = (int)$row['ID'];
				$row['IBLOCK_ID'] = (int)$row['IBLOCK_ID'];
				$row['SORT'] = (int)$row['SORT'];
				$row['CODE'] = trim((string)$row['CODE']);
				$row['NAME'] = trim((string)$row['NAME']);
				$row['MULTIPLE'] = ((string)$row['MULTIPLE']) === 'Y';
				
				if(empty($row['CODE']))
				{
					$row['CODE'] = 'PROP_'.$row['ID'];
				}
				
				$list[$row['IBLOCK_ID']][$row['CODE']] = [
					'ID' => $row['ID'],
					'IBLOCK_ID' => $row['IBLOCK_ID'],
					'CODE' => $row['CODE'],
					'SORT' => $row['SORT'],
					'NAME' => $row['NAME'],
					'MULTIPLE' => $row['MULTIPLE'],
				];
			}
			
			unset($rows);
		}
		
		return $list[$iblockId][$code] ?: null;
	}
}