<?php declare(strict_types=1);

namespace Shef\InSync\Sync;

use Shef\UiClear\Css\Color;

/**
 * Перечисление статусов синхронизации
 */
enum EStatus: string
{
	case Undefined = 'U';
	case New = 'N';
	case Process = 'P';
	case Success = 'S';
	case Fail = 'F';
	
	public function getColor(): string
	{
		return match($this)
		{
			self::Undefined => Color::gray100->value,
			self::New => Color::gray800->value,
			self::Process => Color::blue->value,
			self::Success => Color::success->value,
			self::Fail => Color::danger->value
		};
	}
	
	public static function getValues(): array
	{
		return array_map(
			function(EStatus $status)
			{
				return $status->value;
			},
			EStatus::cases()
		);
	}
	
	public static function getDefault(): string
	{
		return EStatus::Undefined->value;
	}
}
