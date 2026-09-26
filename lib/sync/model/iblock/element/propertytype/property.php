<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element\PropertyType;

use Bitrix\Main\Type\Contract\Arrayable;

class Property
	implements IProperty, Arrayable
{
	public function __construct(
		readonly public int $iblockId,
		readonly public int $primary,
		readonly public EType $type,
		readonly public string $code,
		readonly public string $title,
		readonly public bool $isMultiple = false,
		readonly public int $sort = 500
	)
	{
	}
	
	/**
	 * @param PropertyHelper $propertyHelper
	 * @return mixed
	 */
	public function getByValue(PropertyHelper $propertyHelper): mixed
	{
		return (string)$propertyHelper->value;
	}
	
	/**
	 * @param PropertyHelper $propertyHelper
	 * @return mixed
	 */
	public function getByXmlId(PropertyHelper $propertyHelper): mixed
	{
		throw new \LogicException('We can`t select by XML_ID for scalar property');
	}
	
	/**
	 * @param PropertyHelper $propertyHelper
	 * @return mixed
	 */
	public function create(PropertyHelper $propertyHelper): mixed
	{
		throw new \LogicException('We can`t create for scalar property');
	}
	
	public function getIblockId(): int
	{
		return $this->iblockId;
	}
	
	public function getPrimary(): int
	{
		return $this->primary;
	}
	
	public function getFormattedPrimary(): string
	{
		return 'PROPERTY_'.$this->primary;
	}
	
	public function getType(): EType
	{
		return $this->type;
	}
	
	public function getCode(): string
	{
		return $this->code;
	}
	
	public function getTitle(): string
	{
		return $this->title;
	}
	
	public function isMultiple(): bool
	{
		return $this->isMultiple;
	}
	
	public function getSort(): int
	{
		return $this->sort;
	}
	
	public function toArray(): array
	{
		return [
			'iblockId' => $this->iblockId,
			'primary' => $this->primary,
			'type' => $this->type->value,
			'code' => $this->code,
			'title' => $this->title,
			'isMultiple' => $this->isMultiple,
			'sort' => $this->sort,
		];
	}
}