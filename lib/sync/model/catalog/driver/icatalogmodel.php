<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\Catalog\Driver;

use Bitrix\Main\Result;

/**
 * Интерфейс указывает на работу с моделью каталога
 * @see \Bitrix\Catalog\Model\Entity
 */
interface ICatalogModel
{
	/**
	 * Получить модель каталога сущности
	 *
	 * @return \Bitrix\Catalog\Model\Entity
	 */
	public function getModel(): \Bitrix\Catalog\Model\Entity;
}