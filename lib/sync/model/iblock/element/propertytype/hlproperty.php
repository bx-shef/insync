<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element\PropertyType;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Contract\Arrayable;
use Shef\InSync\Sync\Model;

class HlProperty
	extends Property
	implements IProperty, Arrayable
{
	public readonly DataManager|string $entityDataClass;
	
	protected null|Collection $enumList;
	
	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function __construct(
		int $hlId,
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
			type: EType::Hl,
			code: $code,
			title: $title,
			isMultiple: $isMultiple,
			sort: $sort
		);
		
		$this->init($hlId);
	}
	
	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function init(int $hlId): void
	{
		$arHLBlock = \Bitrix\Highloadblock\HighloadBlockTable::getById($hlId)->fetch();
		$obEntity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($arHLBlock);
		$this->entityDataClass = $obEntity->getDataClass();
		
		$this->refresh();
	}
	
	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function refresh(): void
	{
		$this->enumList = $this->entityDataClass::getList([
			'order' => [
				'ID' => 'ASC'
			],
			'filter' => [
			],
			'select' => [
				'ID',
				'UF_NAME',
				'UF_XML_ID',
			],
		])->fetchCollection();
	}
	
	/**
	 * @return Collection|null
	 */
	public function getEnumList(): ?Collection
	{
		return $this->enumList;
	}
	
	public function getByValue(PropertyHelper $propertyHelper): null|EntityObject
	{
		$list = array_filter(
			$this->getEnumList()->getAll(),
			function(EntityObject $propertyEnumeration)
			use($propertyHelper)
			{
				return $propertyEnumeration->require('UF_NAME') === $propertyHelper->xmlId;
			}
		);
		
		$result = reset($list);
		if(!($result instanceof EntityObject))
		{
			return null;
		}
		
		return $result;
	}
	
	public function getByXmlId(PropertyHelper $propertyHelper): null|EntityObject
	{
		$list = array_filter(/**
		 * @throws SystemException
		 * @throws ArgumentException
		 */ $this->getEnumList()->getAll(),
			function(EntityObject $propertyEnumeration)
				use($propertyHelper)
			{
				return $propertyEnumeration->require('UF_XML_ID') === $propertyHelper->xmlId;
			}
		);
		
		$result = reset($list);
		if(!($result instanceof EntityObject))
		{
			return null;
		}
		
		return $result;
	}
	
	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function create(PropertyHelper $propertyHelper): EntityObject
	{
		/** @var EntityObject $enum */
		$enum = $this->entityDataClass::createObject([
			'UF_NAME' => $propertyHelper->value,
			'UF_XML_ID' => $propertyHelper->xmlId,
		]);
		
		$response = $enum->save();
		if(!$response->isSuccess())
		{
			throw new SystemException(join(';', $response->getErrorMessages()));
		}
		
		$this->refresh();
		
		return $enum;
	}
	
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'enumList' => array_map(
					function(EntityObject $enum){
						return $enum->collectValues(
							ORM\Objectify\Values::ALL,
							ORM\Fields\FieldTypeMask::SCALAR
						);
					},
					$this->getEnumList()->getAll()
				)
			]
		);
	}
}