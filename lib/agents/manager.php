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
	 * Параметры восстанавливаются из строки агента — см. parseName(): это
	 * разбор строки, а не исполнение, и на него не стоит полагаться сверх
	 * показа в интерфейсе.
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
			[$name, $paramsList] = static::parseName((string)$curAgent['NAME']);

			$entity = (new Entity(
				(string)$curAgent['MODULE_ID'],
				$name,
				$paramsList,
				($curAgent['IS_PERIOD'] ?? 'N') === 'Y',
				(int)($curAgent['AGENT_INTERVAL'] ?? 0),
				(int)($curAgent['SORT'] ?? 100),
				(int)($curAgent['USER_ID'] ?? 0)
			))
				->setId((int)$curAgent['ID'])
				->setIsActive(($curAgent['ACTIVE'] ?? 'N') === 'Y')
				->setLastExec(static::parseDateTime($curAgent['LAST_EXEC'] ?? null))
				->setNextExec(static::parseDateTime($curAgent['NEXT_EXEC'] ?? null))
				->setOrigin($agent->getOrigin())
				->setTitle($agent->getTitle())
				->setDescription($agent->getDescription())
			;
			
			$list[] = $entity;
		}
		
		return $list;
	}
	
	/**
	 * Разбирает строку агента обратно в имя и параметры.
	 *
	 * «Класс::метод(['a'=>'1','b'=>'2']);» → ['Класс::метод', ['a' => '1', 'b' => '2']].
	 *
	 * Разбор токенайзером, а не explode: раньше строка резалась по «([» и
	 * «,», и агент без параметров («Класс::метод();») давал warning
	 * «Undefined array key 1», запятая или «=>» внутри значения рвали
	 * параметр, а у параметра без ключа терялось значение. Принимаются только
	 * строки и числа — ровно то, что пишет Entity::prepareNameForDb().
	 * Вложенные массивы и выражения пропускаются.
	 *
	 * @return array{0: string, 1: array}
	 */
	public static function parseName(string $agentName): array
	{
		$agentName = trim($agentName);
		$position = strpos($agentName, '(');
		if(false === $position)
		{
			return [rtrim($agentName, "; \t\n\r"), []];
		}
		
		$name = trim(substr($agentName, 0, $position));
		$params = [];
		
		$depth = 0;
		$key = null;
		$value = null;
		$isValue = false;
		
		$flush = static function() use (&$params, &$key, &$value, &$isValue): void
		{
			if($isValue)
			{
				if(null === $key)
				{
					$params[] = $value;
				}
				else
				{
					$params[$key] = $value;
				}
			}
			
			$key = null;
			$value = null;
			$isValue = false;
		};
		
		foreach(token_get_all('<?php '.substr($agentName, $position)) as $token)
		{
			$type = is_array($token) ? $token[0] : $token;
			
			if(in_array($type, [T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true))
			{
				continue;
			}
			
			if('[' === $type || '(' === $type)
			{
				$depth++;
				continue;
			}
			
			if(']' === $type || ')' === $type)
			{
				if(2 === $depth)
				{
					$flush();
				}
				
				$depth--;
				continue;
			}
			
			// Интересен только один уровень: [ внутри ( — сам массив параметров.
			if(2 !== $depth)
			{
				continue;
			}
			
			if(',' === $type)
			{
				$flush();
				continue;
			}
			
			if(T_DOUBLE_ARROW === $type)
			{
				$key = $isValue ? (string)$value : null;
				$value = null;
				$isValue = false;
				continue;
			}
			
			$scalar = static::parseScalarToken($token);
			if(null !== $scalar)
			{
				$value = $scalar;
				$isValue = true;
			}
		}
		
		return [$name, $params];
	}
	
	/**
	 * Строка или число из токена; остальное — null.
	 */
	private static function parseScalarToken(mixed $token): null|string
	{
		if(!is_array($token))
		{
			return null;
		}
		
		[$type, $text] = $token;
		
		if(T_LNUMBER === $type || T_DNUMBER === $type)
		{
			return $text;
		}
		
		if(T_CONSTANT_ENCAPSED_STRING !== $type)
		{
			return null;
		}
		
		$body = substr($text, 1, -1);
		
		return str_starts_with($text, "'")
			? strtr($body, ['\\\\' => '\\', "\\'" => "'"])
			: stripcslashes($body);
	}
	
	/**
	 * Дата из строки CAgent::GetList(): ядро отдаёт её в формате сайта.
	 *
	 * Раньше формат был зашит — 'd.m.Y H:i:s', — и на сайте с другим форматом
	 * даты разбор бросал исключение. Пустое и неразборчивое — null.
	 */
	public static function parseDateTime(mixed $value): null|DateTime
	{
		if(!is_string($value) || trim($value) === '')
		{
			return null;
		}
		
		try
		{
			return new DateTime($value);
		}
		catch(ObjectException $exception)
		{
			return null;
		}
	}
	
	/**
	 * Модуль, которому принадлежит агент, либо null — агента нет.
	 *
	 * Нужен для проверки прав: включать и выключать агент вправе тот, у кого
	 * есть права на ЕГО модуль, а не на shef.insync.
	 */
	public static function getModuleIdById(int $id): null|string
	{
		if($id < 1)
		{
			return null;
		}
		
		$agent = CAgent::GetList([], ['ID' => $id])->Fetch();
		if(!is_array($agent))
		{
			return null;
		}
		
		return (string)($agent['MODULE_ID'] ?? '');
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