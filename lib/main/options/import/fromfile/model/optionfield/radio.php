<?php declare(strict_types=1);

namespace Shef\InSync\Main\Options\Import\FromFile\Model\OptionField;

/**
 * Поле.Radio выводимое на стартовом диалоге
 */
class Radio
	extends ABase
{
	public function __construct(
		string $name,
		string $title,
		public readonly array $list,
		public readonly string $value,
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
		
		$this->type = EOptionFieldsType::Radio;
	}
	
	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'list' => $this->list,
				'value' => $this->value,
			]
		);
	}
}