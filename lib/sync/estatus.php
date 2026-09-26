<?php declare(strict_types=1);

namespace Shef\InSync\Sync;

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
	
	/**
	 * Цвет статуса для вывода.
	 *
	 * До 2.0.0 цвета брались из перечисления цветов модуля shef.uiclear,
	 * которого не будет. Теперь — литералы той же палитры, и зависимость
	 * ради пяти строк ушла.
	 */
	public function getColor(): string
	{
		return match($this)
		{
			self::Undefined => '#F5F8FA',
			self::New => '#3F4254',
			self::Process => '#009EF7',
			self::Success => '#50CD89',
			self::Fail => '#F1416C',
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
