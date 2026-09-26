<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element\PropertyType;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Contract\Arrayable;
use Shef\InSync\Sync\Model;

class EnumProperty
	extends Property
	implements IProperty, Arrayable
{
	protected null|Model\IBlock\PropertyEnumeration\PropertyEnumerationCollection $enumList;
	protected Model\IBlock\PropertyEnumeration\PropertyEnumerationTable $enum;
	
	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function __construct(
		int $iblockId,
		int $primary,
		string $code,
		string $title,
		bool $isMultiple = false,
		int $sort = 500
	)
	{
		parent::__construct(
			iblockId: $iblockId,
			primary: $primary,
			type: EType::Enum,
			code: $code,
			title: $title,
			isMultiple: $isMultiple,
			sort: $sort
		);
		
		$this->init();
	}
	
	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function init(): void
	{
		$this->enum = new Model\IBlock\PropertyEnumeration\PropertyEnumerationTable();
		$this->enum::setPropertyId($this->getPrimary());
		
		$this->refresh();
	}
	
	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function refresh(): void
	{
		$this->enumList = $this->enum::getList([
			'order' => [
				'SORT' => 'ASC',
				'ID' => 'ASC'
			],
			'filter' => [
				'=PROPERTY_ID' => $this->getPrimary()
			],
			'select' => [
				'ID',
				'XML_ID',
				'VALUE',
				'SORT'
			],
		])->fetchCollection();
	}
	
	/**
	 * @return Model\IBlock\PropertyEnumeration\PropertyEnumerationCollection|null
	 */
	public function getEnumList(): ?Model\IBlock\PropertyEnumeration\PropertyEnumerationCollection
	{
		return $this->enumList;
	}
	
	public function getByValue(PropertyHelper $propertyHelper): null|Model\IBlock\PropertyEnumeration\PropertyEnumeration
	{
		$list = array_filter(
			$this->getEnumList()->getAll(),
			function(\Bitrix\Main\ORM\Entity|Model\IBlock\PropertyEnumeration\PropertyEnumeration $propertyEnumeration)
				use($propertyHelper)
			{
				return mb_strtoupper((string)$propertyEnumeration->getValue()) === mb_strtoupper($propertyHelper->value);
			}
		);
		
		$result = reset($list);
		if(!($result instanceof Model\IBlock\PropertyEnumeration\PropertyEnumeration))
		{
			return null;
		}
		
		return $result;
	}
	
	public function getByXmlId(PropertyHelper $propertyHelper): null|Model\IBlock\PropertyEnumeration\PropertyEnumeration
	{
		$list = array_filter(
			$this->getEnumList()->getAll(),
			function(Model\IBlock\PropertyEnumeration\PropertyEnumeration $propertyEnumeration)
				use($propertyHelper)
			{
				return mb_strtoupper((string)$propertyEnumeration->getXmlId()) === mb_strtoupper($propertyHelper->xmlId);
			}
		);
		
		$result = reset($list);
		if(!($result instanceof Model\IBlock\PropertyEnumeration\PropertyEnumeration))
		{
			return null;
		}
		
		return $result;
	}
	
	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function create(PropertyHelper $propertyHelper): Model\IBlock\PropertyEnumeration\PropertyEnumeration
	{
		/** @var Model\IBlock\PropertyEnumeration\PropertyEnumeration $enum */
		$enum = $this->enum::createObject([
			'VALUE' => $propertyHelper->value,
			'XML_ID' => $propertyHelper->xmlId,
			'PROPERTY_ID' => $propertyHelper->property->getPrimary(),
		]);
		
		$response = $enum->save();
		if(!$response->isSuccess())
		{
			throw new SystemException(sprintf(
				'Error: property:%s {value: %s, xmlId: %s} %s',
				$propertyHelper->property->getCode(),
				$propertyHelper->value,
				$propertyHelper->xmlId,
				join(';', $response->getErrorMessages())
			));
		}
		
		/** @memo we clear all cache */
		Application::getInstance()->getManagedCache()->cleanAll();
		$cache = Cache::createInstance();
		$cache->cleanDir();
		$cache->cleanDir(false, 'stack_cache');
		
		$this->refresh();
		
		return $enum;
	}
	
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'enumList' => array_map(
					function(Model\IBlock\PropertyEnumeration\PropertyEnumeration $enum){
						return $enum->toArray();
					},
					$this->getEnumList()->getAll()
				)
			]
		);
	}
}