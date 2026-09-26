<?php declare(strict_types=1);

namespace Shef\InSync\Main\Options\Import\FromFile\Model\OptionField;

/**
 * Поле.Select выводимое на стартовом диалоге
 */
class Select
	extends ABase
{
	public function __construct(
		string $name,
		string $title,
		public readonly array $list,
		public readonly string $value,
		public readonly bool $multiple = false,
		public readonly int $size = 1,
		bool $obligatory = false,
		string $emptyMessage = ''
	)
	{
		parent::__construct(
			$name,
			$title,
			$obligatory,
			$emptyMessage
		);
		
		$this->type = EOptionFieldsType::Checkbox;
	}
	
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'list' => $this->list,
				'value' => $this->value,
				'multiple' => $this->multiple,
				'size' => (
					$this->multiple
					? $this->size
					: 1
				)
			]
		);
	}
}