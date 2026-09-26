<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element\PropertyType;

enum EType: string
{
	case String = 'string';
	case Text = 'text';
	case Int = 'int';
	case Float = 'float';
	case Enum = 'enum';
	case Bool = 'bool';
	case File = 'file';
	case Hl = 'hl';
}