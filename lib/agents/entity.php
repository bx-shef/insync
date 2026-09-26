<?php declare(strict_types=1);

namespace Shef\InSync\Agents;

use Bitrix\Main\ObjectException;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\Dictionary;
use CAgent;
use Shef\Options\Main\Constants as OptionsConstants;

/**
 * Class Entity
 * @package Shef\InSync\Agents
 *
 * Сущность агента. Используется для хранения данных по агенту.
 */
class Entity
	implements Arrayable
{
	private string $module;

	private int $id;

	private string $name;
	private ?Dictionary $params;

	private bool $isActive;

	private bool $isPeriodic;
	private int $period;

	private int $sort;

	private int $userId;
	
	private ?DateTime $lastExec = null;
	private ?DateTime $nextExec = null;
	
	private string $title = '';
	private string $description = '';
	private string $origin = '';

	// region Construct ////
	
	/**
	 * @throws ObjectException
	 */
	public function __construct(
		string $module,
		string $name,
		array $params,
		bool $isPeriodic = true,
		int $period = 86400,
		int $sort = 100,
		?int $userId = null
	)
	{
		$this->module = $module;
		
		$this->id = 0;
		$this->name = $name;
		$this->params = new Dictionary($params);
		$this->isActive = false;
		
		$this->isPeriodic = $isPeriodic;
		$this->period = $period;
		
		$this->sort = $sort;
		$this->userId = $userId ?? OptionsConstants::getSystemUserId();

		$this->initDb();
	}
	
	/**
	 * @throws ObjectException
	 */
	private function initDb(): void
	{
		$filter = [
			'MODULE_ID' => $this->getModule(),
			'=NAME' =>$this->prepareNameForDb(),
		];

		$cursor = CAgent::GetList([], $filter);
		if($curAgent = $cursor->Fetch())
		{
			$this->setId((int)$curAgent['ID']);
			$this->setIsActive($curAgent['ACTIVE'] === 'Y');
			$this->setIsPeriodic($curAgent['IS_PERIOD'] === 'Y');
			$this->setPeriod((int)$curAgent['AGENT_INTERVAL']);
			$this->setSort((int)$curAgent['SORT']);
			$this->setUserId((int)$curAgent['USER_ID']);
			$this->setLastExec(
			$curAgent['LAST_EXEC']
				? new DateTime($curAgent['LAST_EXEC'], 'd.m.Y H:i:s')
				: null
			);
			$this->setNextExec(
			$curAgent['NEXT_EXEC']
				? new DateTime($curAgent['NEXT_EXEC'], 'd.m.Y H:i:s')
				: null
			);
		}
		
		unset($curAgent, $cursor, $filter);
	}
	// endregion ////

	// region Get|Set ////
	final public function setModule(string $value): self
	{
		$this->module = $value;
		return $this;
	}

	final public function getModule(): string
	{
		return $this->module;
	}

	final public function setId(int $value): self
	{
		$this->id = $value;
		return $this;
	}

	final public function getId(): int
	{
		return $this->id;
	}

	final public function setName(string $value): self
	{
		$this->name = $value;
		return $this;
	}

	final public function getName(): string
	{
		return $this->name;
	}

	final public function addParams(string $name, $value): self
	{
		$this->params->set($name, $value);
		return $this;
	}

	final public function setParams(array $params): self
	{
		$this->params->clear();
		$this->params->setValues($params);
		return $this;
	}

	final public function getParams(): Dictionary
	{
		return $this->params;
	}

	final public function setIsActive(bool $value): self
	{
		$this->isActive = $value;
		return $this;
	}

	final public function isActive(): bool
	{
		return $this->isActive;
	}

	final public function setIsPeriodic(bool $value): self
	{
		$this->isPeriodic = $value;
		return $this;
	}

	final public function isPeriodic(): bool
	{
		return $this->isPeriodic;
	}

	final public function setPeriod(int $value): self
	{
		$this->period = $value;
		return $this;
	}

	final public function getPeriod(): int
	{
		return $this->period;
	}

	final public function setSort(int $value): self
	{
		$this->sort = $value;
		return $this;
	}

	final public function getSort(): int
	{
		return $this->sort;
	}

	final public function setUserId(int $value): self
	{
		$this->userId = $value;
		return $this;
	}

	final public function getUserId(): int
	{
		return $this->userId;
	}
	
	/**
	 * @param DateTime|null $lastExec
	 * @return $this
	 */
	public function setLastExec(?DateTime $lastExec): self
	{
		$this->lastExec = $lastExec;
		return $this;
	}
	
	/**
	 * @return DateTime|null
	 */
	public function getLastExec(): ?DateTime
	{
		return $this->lastExec;
	}
	
	/**
	 * @param DateTime|null $nextExec
	 * @return $this
	 */
	public function setNextExec(?DateTime $nextExec): self
	{
		$this->nextExec = $nextExec;
		return $this;
	}
	
	/**
	 * @return DateTime|null
	 */
	public function getNextExec(): ?DateTime
	{
		return $this->nextExec;
	}
	// endregion ////

	// region Tools ////
	final public function prepareNameForDb(): string
	{
		$agentParams = [];
		foreach($this->getParams()->toArray() as $key => $value)
		{
			$agentParams[] = "'$key'=>'$value'";
		}

		return $this->getName().'(['.implode(',', $agentParams).']);';
	}
	
	/**
	 * @throws ObjectException
	 */
	final public function reInit(): self
	{
		$this->initDb();
		return $this;
	}
	
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'module' => $this->getModule(),
			'name' => $this->getName(),
			'info' => [
				'title' => $this->getTitle(),
				'description' => $this->getDescription(),
				'origin' => $this->getOrigin()
			],
			'params' => $this->getParams()->toArray(),
			'isActive' => $this->isActive(),
			'isPeriodic' => $this->isPeriodic(),
			'period' => $this->getPeriod(),
			'sort' => $this->getSort(),
			'userId' => $this->getUserId(),
			'lastExec' => $this->getLastExec(),
			'nextExec' => $this->getNextExec(),
		];
	}
	// endregion ////
	
	// region Description ////
	/**
	 * @param string $title
	 *
	 * @return $this
	 */
	public function setTitle(string $title): static
	{
		$this->title = $title;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}
	
	/**
	 * @param string $description
	 *
	 * @return $this
	 */
	public function setDescription(string $description): static
	{
		$this->description = $description;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->description;
	}
	// endregion ////
	
	/**
	 * @param string $origin
	 * @return $this
	 */
	public function setOrigin(string $origin): static
	{
		$this->origin = $origin;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getOrigin(): string
	{
		return $this->origin;
	}
}