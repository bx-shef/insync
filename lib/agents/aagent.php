<?php declare(strict_types=1);

namespace Shef\InSync\Agents;

use Bitrix\Main\LoaderException;
use Throwable;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Type\Dictionary;
use CAdminMessage;
use Shef\Options\Main\Context;
use Shef\Options\Options\Singleton;
use Shef\Options\Options\SmartStd;
use Shef\Options\TraitList;
use Shef\Problems;
use Shef\Problems\Factory\Trait as ProblemsTraitList;
use Shef\InSync\Main\Constants;
use Shef\InSync\Agents;


/**
 * Class AAgent
 * @package Shef\InSync\Agents
 *
 * Родительский класс для агентов. Поддерживает учет проблем.
 *
 * @const LogLevel - Определяет уровень логирования @see \Shef\Problems\ILog
 *
 * @param bool $isNeedStop - Указывает что не стоит возобновлять работу агента
 * @param Dictionary $params - Хранит переданные в агент параметры
 */
abstract class AAgent
	extends Singleton
{
	use TraitList\Modules;
	use TraitList\Tools\DateTime;
	use TraitList\Tools\IsDebug;
	use TraitList\Tools\SelfClass;
	use TraitList\Security\FixUser;
	use ProblemsTraitList\LoggerProblems;
	use ProblemsTraitList\DebuggerProblems;
	
	private bool $isNeedStop = false;

	private ?Dictionary $params;
	
	public static function getContext(): Context
	{
		static $context;
		if(null === $context)
		{
			$context = (new Context())
				->setUserId(
					\Shef\Options\Main\Constants::getSystemUserId()
				)
				->setScope(Context::SCOPE_TASK)
			;
		}
		
		return $context;
	}
	
	// region ProblemsTraitList\LoggerProblems ////
	/**
	 * @inheritDoc
	 */
	protected static function getAuditType(): string
	{
		return \Shef\Problems\Main\Constants::AuditTypeSync;
	}
	// endregion ////
	
	// region TraitList\Security\FixUser ////
	protected static function getInitedUserId(): int
	{
		return static::getContext()->getUserId();
	}
	// endregion ////
	
	// region Modules ////
	/**
	 * @inheritDoc
	 */
	protected static function getModulesList(): array
	{
		return [
			'shef.options',
			'shef.problems',
			'shef.insync',
		];
	}
	// endregion ////

	// region Process /////
	/**
	 * Внешний обработчик агента.
	 *
	 * @memo: $params['debug'] = Y - включает режим отладки
	 *
	 * @param array $params
	 * @return string
	 * @throws LoaderException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	final public static function process(array $params = []): string
	{
		$result = new Result();
		
		$logger = static::createLogger();
		$debugger = static::createDebugger();
		
		$info = new SmartStd();
		$info->isNeedStop = false;
		$info->isDebug = false;

		if((string)($params['debug'] ?? '') === 'Y')
		{
			$info->isDebug = true;
		}
		
		$response = static::includeModules();
		if(!$response->isSuccess())
		{
			$result->addErrors($response->getErrors());
		}
		
		if($result->isSuccess())
		{
			$agent = null;
			try
			{
				static::initUser();
				
				/** @var AAgent $agent */
				$agent = (static::getInstance());
				$agent->setParams($params)
					->setIsDebug($info->isDebug)
					->configureLogger($logger)
					->configureDebugger($debugger);
				
				$response = $agent->action();
				if(!$response->isSuccess())
				{
					$result->addErrors($response->getErrors());
				}
				else
				{
					$info->isNeedStop = $agent->isNeedStop();
				}
			}
			catch(Throwable $throwable)
			{
				// С трассировкой: агент падает без свидетелей, и место падения
				// потом искать только по журналу.
				$result->addError(Problems\Throwable\Manager::buildError($throwable, true));
			}
			finally
			{
				static::closeUser();
			}
		}

		if(!$result->isSuccess())
		{
			$logger->critical($result);
			if($info->isDebug)
			{
				$debugger->critical($result);
				foreach($result->getErrors() as $error)
				{
					// Текст ошибки — данные (строка импорта, ответ API), не разметка.
					CAdminMessage::ShowMessage([
						'MESSAGE' => nl2br(htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE)),
						'TYPE' => 'ERROR',
						'HTML' => true
					]);
				}
			}
		}

		if($info->isNeedStop)
		{
			return '';
		}

		return ($agent?->getName($params) ?? '');
	}

	/**
	 * Возвращает имя агента для следующего запуска
	 *
	 * @param array $params
	 * @return string
	 */
	public static function getName(array $params = []): string
	{
		$entity = static::buildAgentsEntity();
		unset($params['debug']);
		
		$entity->setParams($params);
		
		return $entity->prepareNameForDb();
	}
	
	/**
	 * Создает сущность описывающую агент и устанавливает ее
	 * @return Entity
	 */
	abstract public static function buildAgentsEntity(): Agents\Entity;
	// endregion /////
	
	// region Agent /////
	// region Descr ////
	protected static function getIconBattery(
		int $count,
		int $limit,
		bool $isAgentActive = true
	): string
	{
		if(!$isAgentActive)
		{
			return 'ui-btn-icon-no-battery';
		}
		
		$delta = ($limit * 40 / 100);
		
		if($count > $limit)
		{
			return 'ui-btn-icon-battery';
		}
		elseif($count <= $limit && $count > $delta)
		{
			return 'ui-btn-icon-half-battery';
		}
		elseif($count > 0 && $count <= $delta)
		{
			return 'ui-btn-icon-low-battery';
		}
		
		return 'ui-btn-icon-crit-battery';
	}
	// endregion /////
	
	// region Init /////
	protected function __construct()
	{
		parent::__construct();
		$this->params = new Dictionary();
		$this->initDateTime();
		$this->init();
	}

	/**
	 * Используется для дополнительной инициализации объекта агента
	 */
	protected function init(): void
	{
	}
	// endregion /////

	// region Get|Set /////
	
	/**
	 * Указывает нужно ли остановить работу агента
	 *
	 * @see AAgent::process
	 *
	 * @return bool
	 */
	final public function isNeedStop(): bool
	{
		return $this->isNeedStop;
	}

	protected function setIsNeedStop(bool $value): self
	{
		$this->isNeedStop = $value;
		return $this;
	}
	
	final public function setParams(array $params): self
	{
		$this->getParams()->setValues($params);
		
		return $this;
	}
	
	final public function getParams(): Dictionary
	{
		return $this->params;
	}
	// endregion /////

	// region Work /////
	/**
	 * Обработчик агента
	 *
	 * @return Result
	 */
	abstract public function action(): Result;
	// endregion /////
	// endregion /////
}