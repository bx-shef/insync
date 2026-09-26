<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element\PropertyType;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Contract\Arrayable;
use Shef\InSync\Sync\Model;

class BoolProperty
	extends Property
	implements IProperty, Arrayable
{
	protected null|Model\IBlock\PropertyEnumeration\PropertyEnumerationCollection $enumList;
	protected Model\IBlock\PropertyEnumeration\PropertyEnumerationTable $enum;
	
	public function __construct(
		int $iblockId,
		int $primary,
		string $code,
		string $title,
		int $sort = 500
	)
	{
		parent::__construct(
			iblockId: $iblockId,
			primary: $primary,
			type: EType::Bool,
			code: $code,
			title: $title,
			isMultiple: false,
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
	
	public function getEnumY(): Model\IBlock\PropertyEnumeration\PropertyEnumeration
	{
		$cnt = $this->enumList->count();
		if($cnt !== 1)
		{
			throw new \LogicException(
				sprintf(
					'Need use 1 value for enum at property %s. Now set %s',
					$this->getCode(),
					$cnt
				)
			);
		};
		
		$list = $this->enumList->getAll();
		/** @var Model\IBlock\PropertyEnumeration\PropertyEnumeration $enum */
		$enum = reset($list);
		return $enum;
	}
	
	/**
	 * @param PropertyHelper $propertyHelper
	 * @return mixed
	 */
	public function getByValue(PropertyHelper $propertyHelper): null|Model\IBlock\PropertyEnumeration\PropertyEnumeration
	{
		if((int)$propertyHelper->value === 1)
		{
			return $this->getEnumY();
		}
		return null;
	}
	
	/**
	 * @return Model\IBlock\PropertyEnumeration\PropertyEnumerationCollection|null
	 */
	public function getEnumList(): ?Model\IBlock\PropertyEnumeration\PropertyEnumerationCollection
	{
		return $this->enumList;
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