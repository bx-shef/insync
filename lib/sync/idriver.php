<?php declare(strict_types=1);

namespace Shef\InSync\Sync;

use Bitrix\Main\Result;

/**
 * Интерфейс драйвера обработки данных
 */
interface IDriver
{
	public const ErrorSave = 'ERROR_DRIVER_SAVE';
	
	/**
	 * Получить объект сущности
	 * @return object
	 */
	public function getObject(): object;
	
	/**
	 * Получить ORM.Tablet сущности
	 * @return string|\Bitrix\Main\ORM\Data\DataManager
	 */
	public function getOrm(): string|\Bitrix\Main\ORM\Data\DataManager;
}