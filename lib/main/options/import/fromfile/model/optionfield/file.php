<?php declare(strict_types=1);

namespace Shef\Insync\Main\Options\Import\FromFile\Model\OptionField;

/**
 * Поле.File выводимое на стартовом диалоге
 *
 * @memo obligatory - обязателен для заполнения
 * @memo emptyMessage - текст ошибки если не заполнено
 */
class File
	extends ABase
{
	public function __construct(
		string $name,
		string $title,
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
		
		$this->type = EOptionFieldsType::File;
	}
}