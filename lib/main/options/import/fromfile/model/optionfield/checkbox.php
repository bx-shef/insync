<?php declare(strict_types=1);

namespace Shef\Insync\Main\Options\Import\FromFile\Model\OptionField;

/**
 * Поле.Checkbox выводимое на стартовом диалоге
 */
class Checkbox
	extends ABase
{
	public function __construct(
		string $name,
		string $title,
		public readonly array $list,
		public readonly string $value,
		public readonly bool $multiple = false,
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
			]
		);
	}
}