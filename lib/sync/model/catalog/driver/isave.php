<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\Catalog\Driver;

use Bitrix\Main\Result;

/**
 * Интерфейс указывает что данные можно сохранить через save -> подмена add || update
 */
interface ISave
{
	/**
	 * Сохраняет данные
	 *
	 * @param array $primary
	 * @param array $params
	 * @return Result
	 */
	public function save(array $primary, array $params): Result;
}