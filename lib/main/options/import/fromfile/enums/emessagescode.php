<?php declare(strict_types=1);

namespace Shef\InSync\Main\Options\Import\FromFile\Enums;

/**
 * Типы сообщений которые можно кастомизировать
 */
enum EMessagesCode
{
	case DialogTitle;
	case DialogSummary;
	case DialogStartButton;
	case DialogStopButton;
	case DialogCloseButton;
	case RequestCanceling;
	case RequestCanceled;
	case RequestCompleted;
}