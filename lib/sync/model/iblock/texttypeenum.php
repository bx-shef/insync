<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock;

enum TextTypeEnum: string
{
	case Text = 'text';
	case Html = 'html';
}