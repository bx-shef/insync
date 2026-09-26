<?php declare(strict_types=1);

namespace Shef\InSync\Sync;

use Bitrix\Main\Result;

/**
 * Интерфейс сущности для сохранения результата
 */
interface IEntitySave
{
	public const ErrorSave = 'ERROR_SAVE';
	
	public function saveResult(): Result;
}