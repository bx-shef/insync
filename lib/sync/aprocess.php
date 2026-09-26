<?php declare(strict_types=1);

namespace Shef\InSync\Sync;

use Bitrix\Main\Context;
use Bitrix\Main\Errorable;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\ORM\Objectify\Collection as OrmCollection;
use Bitrix\Main\ORM\Objectify\EntityObject as OrmEntityObject;
use Bitrix\Main\Result;
use Shef\Options\TraitList;
use Shef\Problems\Factory\Trait as ProblemsTraitList;
use Shef\InSync\TraitList as InSyncTraitList;

/**
 * Абстракция для пошагового импорта.
 */
abstract class AProcess
	implements IProcess, Errorable
{
	use TraitList\Tools\XmlId;
	use TraitList\Tools\DateTime;
	use TraitList\Tools\PrepareFields;
	use TraitList\Tools\OptionCollection;
	use TraitList\Tools\ErrorCollection;
	use TraitList\Tools\SelfClass;
	use ProblemsTraitList\LoggerProblems;
	use ProblemsTraitList\DebuggerProblems;
	use InSyncTraitList\Sync\ClassSync;
	
	protected const OriginatorId = 'demo';

	protected HttpRequest $request;

	/** @var string При импорте в каком формате Дата приходит */
	protected string $importDateFormat = 'd.m.Y';

	/** @var string При импорте в каком формате ДатаВремя приходит */
	protected string $importDateTimeFormat = 'd.m.Y H:i:s';
	
	final public static function getOriginatorId(): string
	{
		return static::OriginatorId;
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
	
	/**
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public function __construct()
	{
		$this->request = Context::getCurrent()->getRequest();
		
		$this->initOptionCollection();
		$this->initErrorCollection();
		$this->initDateTime();
		
		$this->setCustomPhp();
		$this->initLogger();
		$this->initDebugger();
	}

	// region Custom.Php ////
	/**
	 * Устанавливает кастомные настройки для PHP
	 *
	 * @example set_time_limit(0);
	 * @return void
	 */
	protected function setCustomPhp(): void
	{
	}
	// endregion ////
}