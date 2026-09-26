<?php declare(strict_types=1);

namespace Shef\InSync\Main\Options\Import\FromFile\Model\OptionField;

/**
 * Поле.SingleCheckbox выводимое на стартовом диалоге
 */
class SingleCheckbox
	extends ABase
{
	public function __construct(
		string $name,
		string $title,
		public readonly bool $value = true,
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
				'value' => $this->value,
			]
		);
	}
}