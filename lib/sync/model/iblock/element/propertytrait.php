<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type;
use Shef\InSync\Sync\Model\IBlock as SyncIblock;

/**
 * Свойства инфоблока по коду — для драйверов и процессов импорта.
 *
 * Пришёл из рабочей копии 1.2.12. Класс, который использует трейт, задаёт
 * static::$dataClass — свой *Table, наследник ElementTable (от него берётся
 * IblockId). Описание свойства кешируется в объекте: одно свойство — один
 * запрос на весь импорт.
 *
 * <code>
 * $enum = $this->getEnum($this->getPropertyEnum('COLOR'), 'Красный', 'red');
 * </code>
 */
trait PropertyTrait
{
	protected null|Type\Dictionary $propCollection = null;

	protected function getPropertyFromCollection(
		string $propertyCode
	): null|PropertyType\IProperty
	{
		if(!$this->propCollection)
		{
			$this->propCollection = new Type\Dictionary();
		}

		$result = $this->propCollection->get($propertyCode);
		if($result instanceof PropertyType\IProperty)
		{
			return $result;
		}
		return null;
	}

	protected function addPropertyToCollection(
		PropertyType\IProperty $property
	): void
	{
		if(!$this->propCollection)
		{
			$this->propCollection = new Type\Dictionary();
		}

		$this->propCollection->set(
			$property->getCode(),
			$property
		);
	}

	/**
	 * Описание свойства из инфоблока static::$dataClass.
	 *
	 * @throws ArgumentException свойства с таким кодом в инфоблоке нет
	 * @return array{ID: int, IBLOCK_ID: int, CODE: string, SORT: int, NAME: string, MULTIPLE: bool}
	 */
	protected function getPropertyInfo(string $propertyCode): array
	{
		/** @var ElementTable $dataClass */
		$dataClass = static::$dataClass;

		$propertyInfo = PropertyType\PropertyHelper::getPropertyByCode(
			$dataClass::getIblockId(),
			$propertyCode
		);

		if(!is_array($propertyInfo))
		{
			throw new ArgumentException(
				sprintf('Property %s not found at iblock %d', $propertyCode, $dataClass::getIblockId()),
				'propertyCode'
			);
		}

		return $propertyInfo;
	}

	/**
	 * Свойство из кеша объекта либо построенное $build и положенное в кеш.
	 *
	 * @param callable(array): PropertyType\IProperty $build
	 */
	private function getPropertyCached(string $propertyCode, callable $build): PropertyType\IProperty
	{
		$property = $this->getPropertyFromCollection($propertyCode);

		if(!$property)
		{
			$property = $build($this->getPropertyInfo($propertyCode));
			$this->addPropertyToCollection($property);
		}

		return $property;
	}

	private function buildScalarProperty(array $propertyInfo, PropertyType\EType $type): PropertyType\Property
	{
		return new PropertyType\Property(
			iblockId: (int)$propertyInfo['IBLOCK_ID'],
			primary: (int)$propertyInfo['ID'],
			type: $type,
			code: (string)$propertyInfo['CODE'],
			title: (string)$propertyInfo['NAME'],
			isMultiple: (bool)$propertyInfo['MULTIPLE'],
			sort: (int)$propertyInfo['SORT']
		);
	}

	protected function getPropertyString(string $propertyCode): PropertyType\Property
	{
		return $this->getPropertyCached(
			$propertyCode,
			fn(array $info) => $this->buildScalarProperty($info, PropertyType\EType::String)
		);
	}

	protected function getPropertyInt(string $propertyCode): PropertyType\Property
	{
		return $this->getPropertyCached(
			$propertyCode,
			fn(array $info) => $this->buildScalarProperty($info, PropertyType\EType::Int)
		);
	}

	protected function getPropertyFloat(string $propertyCode): PropertyType\Property
	{
		return $this->getPropertyCached(
			$propertyCode,
			fn(array $info) => $this->buildScalarProperty($info, PropertyType\EType::Float)
		);
	}

	protected function getPropertyBool(string $propertyCode): PropertyType\BoolProperty
	{
		return $this->getPropertyCached(
			$propertyCode,
			static fn(array $info) => new PropertyType\BoolProperty(
				iblockId: (int)$info['IBLOCK_ID'],
				primary: (int)$info['ID'],
				code: (string)$info['CODE'],
				title: (string)$info['NAME'],
				sort: (int)$info['SORT']
			)
		);
	}

	protected function getPropertyFile(string $propertyCode): PropertyType\FileProperty
	{
		return $this->getPropertyCached(
			$propertyCode,
			static fn(array $info) => new PropertyType\FileProperty(
				iblockId: (int)$info['IBLOCK_ID'],
				primary: (int)$info['ID'],
				code: (string)$info['CODE'],
				title: (string)$info['NAME'],
				isMultiple: (bool)$info['MULTIPLE'],
				sort: (int)$info['SORT']
			)
		);
	}

	protected function getPropertyEnum(string $propertyCode): PropertyType\EnumProperty
	{
		return $this->getPropertyCached(
			$propertyCode,
			static fn(array $info) => new PropertyType\EnumProperty(
				iblockId: (int)$info['IBLOCK_ID'],
				primary: (int)$info['ID'],
				code: (string)$info['CODE'],
				title: (string)$info['NAME'],
				isMultiple: (bool)$info['MULTIPLE'],
				sort: (int)$info['SORT']
			)
		);
	}

	/**
	 * Значение списка: найти по XML_ID (или по значению), нет — создать.
	 */
	public function getEnum(
		PropertyType\EnumProperty $property,
		string $value,
		string $xmlId,
		bool $isInitByXmlId = true
	): SyncIblock\PropertyEnumeration\PropertyEnumeration
	{
		$propertyHelper = new PropertyType\PropertyHelper(
			property: $property,
			value: $value,
			xmlId: $xmlId
		);

		/** @var null|SyncIblock\PropertyEnumeration\PropertyEnumeration $enum */
		$enum = $isInitByXmlId
			? $propertyHelper->getByXmlId()
			: $propertyHelper->getByValue();

		if(null === $enum)
		{
			$enum = $propertyHelper->create();
		}

		return $enum;
	}
}
