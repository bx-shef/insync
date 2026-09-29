<?php declare(strict_types=1);

namespace Shef\InSync\Main\Options\Import\FromFile\Model;

use Bitrix\Main\Type\Contract\Arrayable;

/**
 * Задание очереди импорта
 */
class QueueItem
	implements \JsonSerializable, Arrayable
{
	protected null|string $controller = null;
	protected null|array $params = null;
	protected bool $finalize = false;
	
	public function __construct(
		public readonly string $action,
		public readonly string $title,
		public readonly string $progressBarTitle
	)
	{
	}
	
	// region Get/Set ////
	/**
	 * Отдельный контроллер на шаге
	 *
	 * @param string|null $controller
	 * @return $this
	 */
	public function setController(?string $controller): static
	{
		$this->controller = $controller;
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getController(): ?string
	{
		return $this->controller;
	}
	
	/**
	 * Дополнительные параметры, добавляемые в запрос
	 * @param array|null $params
	 * @return $this
	 */
	public function setParams(?array $params): static
	{
		$this->params = $params;
		return $this;
	}
	
	/**
	 * @return array|null
	 */
	public function getParams(): ?array
	{
		return $this->params;
	}
	
	/**
	 * Финальный шаг не отображается пользователю
	 * @param bool $finalize
	 * @return $this
	 */
	public function setFinalize(bool $finalize): static
	{
		$this->finalize = $finalize;
		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function isFinalize(): bool
	{
		return $this->finalize;
	}
	// endregion ////
	
	/**
	 * JsonSerializable::jsonSerialize
	 * Данные для json_encode() — массив toArray()
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
	
	public function toArray(): array
	{
		return array_merge(
			[
				'action' => $this->action,
				'title' => $this->title,
				'progressBarTitle' => $this->progressBarTitle,
				'finalize' => $this->isFinalize()
			],
			(
				null === $this->controller
				? []
				: [
					'controller' => $this->controller
				]
			),
			(
				null === $this->params
					? []
					: [
					'params' => $this->params
				]
			)
		);
	}
}