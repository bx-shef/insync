<?php declare(strict_types=1);

namespace Shef\Insync\Main\Options\Import\FromFile\Enums;

/**
 * Статус выполнения текущего задания
 */
enum EResponseStatus
{
	case Progress;
	case Completed;
	
	public function getValue(): string
	{
		return mb_strtoupper($this->name);
	}
}