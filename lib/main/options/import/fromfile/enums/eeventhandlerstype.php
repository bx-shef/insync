<?php declare(strict_types=1);

namespace Shef\Insync\Main\Options\Import\FromFile\Enums;

/**
 * Типы js.Событий которые можно кастомизировать
 */
enum EEventHandlersType
{
	case StateChanged;
	case RequestStart;
	case RequestStop;
	case RequestFinalize;
}