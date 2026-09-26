<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element\PropertyType;

interface IProperty
{
	public function getByValue(PropertyHelper $propertyHelper): mixed;
	
	public function getByXmlId(PropertyHelper $propertyHelper): mixed;
	
	public function create(PropertyHelper $propertyHelper): mixed;
	
	public function getIblockId(): int;
	
	public function getPrimary(): int;
	
	public function getFormattedPrimary(): string;
	
	public function getType(): EType;
	
	public function getCode(): string;
	
	public function getTitle(): string;
	
	public function isMultiple(): bool;
	
	public function getSort(): int;
}