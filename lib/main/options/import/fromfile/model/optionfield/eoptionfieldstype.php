<?php declare(strict_types=1);

namespace Shef\InSync\Main\Options\Import\FromFile\Model\OptionField;

/**
 * Типы полей, выводимых на стартовом диалоге
 */
enum EOptionFieldsType
{
	case Text;
	case File;
	case Select;
	case Checkbox;
	case Radio;
	
	public function getValue(): string
	{
		return mb_strtolower($this->name);
	}
}