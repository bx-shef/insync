<?php declare(strict_types=1);

namespace Shef\InSync\Sync\FromFile\Strategy;

use Bitrix\Main\ORM;
use Shef\InSync\Agents\AAgent;
use Shef\InSync\Sync\IElement;
use Shef\InSync\Agents;

interface IStrategy
{
	/**
	 * Возвращает количество на выборку
	 * @param AAgent $agent
	 * @return int
	 */
	public function getMaxStep(Agents\AAgent $agent): int;
	
	/**
	 * Возвращает фильтр для отбора
	 * @param string $originatorId
	 * @param AAgent $agent
	 * @return array
	 */
	public function getListConf(string $originatorId, Agents\AAgent $agent): array;
	
	/**
	 * Определяет используем ли сценарий пошаговой отладки
	 * @param AAgent $agent
	 * @return bool
	 */
	public function isUseOneRowDebug(Agents\AAgent $agent): bool;
	
	/**
	 * Обработка плохих записей
	 *
	 * @param IElement $row
	 * @return ORM\Data\AddResult|ORM\Data\UpdateResult|ORM\Data\Result
	 */
	public function processFail(IElement $row): ORM\Data\AddResult|ORM\Data\UpdateResult|ORM\Data\Result;
}