<?php declare(strict_types=1);

namespace Shef\InSync\Agents;

use Bitrix\Main\ObjectException;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Type\DateTime;
use CAgent;

/**
 * Class Manager
 * @package Shef\InSync\Agents
 *
 * Управляет агентами на основании сущности Агента
 *
 */
final class Manager
{
	public const FormatDateTime = 'd.m.Y H:i:s';

	/**
	 * Активирует агент
	 * @param Entity $agent
	 * @return Result
	 */
	public static function start(Entity $agent): Result
	{
		$result = new Result();
		if($agent->getId() < 1)
		{
			return $result->addError(new Error('Agent not installed'));
		}

		$response = Manager::startById($agent->getId());
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}

		$agent->setIsActive(true);

		return $result;
	}

	/**
	 * Активирует агент по Id
	 * @param int $id
	 * @return Result
	 */
	public static function startById(int $id): Result
	{
		$result = new Result();

		$response = CAgent::Update($id, ['ACTIVE' => 'Y']);
		if($response === false)
		{
			return $result->addError(new Error('Error start agent id='.$id));
		}

		return $result;
	}

	/**
	 * Деактивирует агента
	 * @param Entity $agent
	 * @return Result
	 */
	public static function stop(Entity $agent): Result
	{
		$result = new Result();
		if($agent->getId() < 1)
		{
			return $result->addError(new Error('Agent not installed'));
		}

		$response = Manager::stopById($agent->getId());
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}

		$agent->setIsActive(false);

		return $result;
	}

	/**
	 * Деактивирует агента по Id
	 * @param int $id
	 * @return Result
	 */
	public static function stopById(int $id): Result
	{
		$result = new Result();

		$response = CAgent::Update($id, ['ACTIVE' => 'N']);
		if($response === false)
		{
			return $result->addError(new Error('Error stop agent id='.$id));
		}

		return $result;
	}

	/**
	 * Удаляет агент
	 * @param Entity $agent
	 * @return Result
	 */
	public static function delete(Entity $agent): Result
	{
		$result = new Result();

		/*/
		$response = static::get($agentName, $params);
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		//*/

		if($agent->getId() < 1)
		{
			return $result->addError(new Error('Agent not installed'));
		}

		$response = Manager::deleteById($agent->getId());
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}

		$agent->setIsActive(false);
		$agent->setId(0);

		return $result;
	}

	/**
	 * Удаляет агент по Id
	 * @param int $id
	 * @return Result
	 */
	public static function deleteById(int $id): Result
	{
		$result = new Result();

		$response = CAgent::Delete($id);
		if($response === false)
		{
			return $result->addError(new Error('Error delete agent id='.$id));
		}


		return $result;
	}
	
	/**
	 * Ищет агенты с одинаковым названием
	 *
	 * @param Entity $agent
	 * @return array|Entity[]
	 * @throws ObjectException
	 */
	public static function findAll(Entity $agent): array
	{
		/** @var Entity[] $list */
		$list = [];

		$filter = [
			'MODULE_ID' => $agent->getModule(),
			'NAME' => $agent->getName().'(%',
		];
		$cursor = CAgent::GetList([], $filter);
		while($curAgent = $cursor->Fetch())
		{
			$paramsList = [];
			[$name, $params] = explode('([', $curAgent['NAME']);
			$params = trim(str_replace(['\'', '"', '])', ';'], '',$params));
			if(mb_strlen($params) > 0)
			{
				$params = explode(',', $params);
				
				foreach($params as $param)
				{
					$tmp = explode('=>', $param);
					if(is_set($tmp[1]))
					{
						$key = trim($tmp[0]);
						$value = trim($tmp[1]);
						$paramsList[$key] = $value;
					}
					else
					{
						$value = trim($tmp[1]);
						$paramsList[] = $value;
					}
				}
			}

			$entity = (new Entity(
				(string)$curAgent['MODULE_ID'],
				(string)$name,
				$paramsList,
				$curAgent['IS_PERIOD'] === 'Y',
				(int)$curAgent['AGENT_INTERVAL'],
				(int)$curAgent['SORT'],
				(int)$curAgent['USER_ID']
			))
				->setId((int)$curAgent['ID'])
				->setIsActive($curAgent['ACTIVE'] === 'Y')
				->setLastExec(
					$curAgent['LAST_EXEC']
					? new DateTime($curAgent['LAST_EXEC'], 'd.m.Y H:i:s')
					: null
				)
				->setNextExec(
					$curAgent['NEXT_EXEC']
					? new DateTime($curAgent['NEXT_EXEC'], 'd.m.Y H:i:s')
					: null
				)
				->setOrigin($agent->getOrigin())
				->setTitle($agent->getTitle())
				->setDescription($agent->getDescription())
			;
			
			$list[] = $entity;
		}
		
		return $list;
	}
	
	/**
	 * Устанавливает агент
	 * @param Entity $agent
	 * @param DateTime|null $nextExec
	 * @return Result
	 *
	 * @memo set $nextExec if you need set custom date for isPeriodic = true
	 * @throws ObjectException
	 */
	public static function install(
		Entity $agent,
		?DateTime $nextExec = null
	): Result
	{
		$result = new Result();

		if($agent->getId() > 0)
		{
			return $result;
		}

		$date = new DateTime();
		$date->setTime(0, 0);

		$response = CAgent::AddAgent(
			$agent->prepareNameForDb(),
			$agent->getModule(),
			$agent->isPeriodic() ? 'Y' : 'N',
			$agent->getPeriod(),
			$date->format(Manager::FormatDateTime),
			$agent->isActive() ? 'Y' : 'N',
			$nextExec ? $nextExec->format(Manager::FormatDateTime) : $date->format(Manager::FormatDateTime),
			$agent->getSort(),
			$agent->getUserId(),
			false
		);

		if($response === false)
		{
			return $result->addError(new Error('Error install agent'));
		}

		$agent->reInit();

		if($agent->getId() < 1)
		{
			return $result->addError(new Error('not install agent'));
		}

		return $result;
	}
}