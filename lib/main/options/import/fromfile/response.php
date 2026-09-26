<?php declare(strict_types=1);

namespace Shef\Insync\Main\Options\Import\FromFile;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\Dictionary;



/**
 * Ответ для контроллера
 */
class Response
	implements \JsonSerializable, Arrayable
{
	private int $totalItems = 0;
	private int $processedItems = 0;
	private Enums\EResponseStatus $status;
	private string $summary = '';
	private bool $finalize = false;
	private null|string $warning = null;
	private null|string $nextController = null;
	private null|string $nextAction = null;
	private null|string $downloadLink = null;
	private null|string $fileName = null;
	private null|string $downloadLinkName = null;
	private null|string $clearLinkName = null;
	private Dictionary $options;
	
	public function __construct()
	{
		$this->status = Enums\EResponseStatus::Progress;
		
		$this->options = new Dictionary();
	}
	
	// region Get/Set ////
	/**
	 * @param int $totalItems
	 * @return $this
	 */
	public function setTotalItems(int $totalItems): static
	{
		$this->totalItems = $totalItems;
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getTotalItems(): int
	{
		return $this->totalItems;
	}
	
	/**
	 * @param int $processedItems
	 * @return $this
	 */
	public function setProcessedItems(int $processedItems): static
	{
		$this->processedItems = $processedItems;
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getProcessedItems(): int
	{
		return $this->processedItems;
	}
	
	/**
	 * @param Enums\EResponseStatus $status
	 * @return $this
	 */
	public function setStatus(Enums\EResponseStatus $status): static
	{
		$this->status = $status;
		return $this;
	}
	
	/**
	 * @return Enums\EResponseStatus
	 */
	public function getStatus(): Enums\EResponseStatus
	{
		return $this->status;
	}
	
	/**
	 * @param string $summary
	 * @return $this
	 */
	public function setSummary(string $summary): static
	{
		$this->summary = $summary;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getSummary(): string
	{
		return $this->summary;
	}
	
	/**
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
	
	/**
	 * @param string|null $warning
	 * @return $this
	 */
	public function setWarning(?string $warning): static
	{
		$this->warning = $warning;
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getWarning(): ?string
	{
		return $this->warning;
	}
	
	/**
	 * @param string|null $nextController
	 * @return $this
	 */
	public function setNextController(?string $nextController): static
	{
		$this->nextController = $nextController;
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getNextController(): ?string
	{
		return $this->nextController;
	}
	
	/**
	 * @param string|null $nextAction
	 * @return $this
	 */
	public function setNextAction(?string $nextAction): static
	{
		$this->nextAction = $nextAction;
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getNextAction(): ?string
	{
		return $this->nextAction;
	}
	
	/**
	 * @param string|null $downloadLink
	 * @return $this
	 */
	public function setDownloadLink(?string $downloadLink): static
	{
		$this->downloadLink = $downloadLink;
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getDownloadLink(): ?string
	{
		return $this->downloadLink;
	}
	
	/**
	 * @param string|null $fileName
	 * @return $this
	 */
	public function setFileName(?string $fileName): static
	{
		$this->fileName = $fileName;
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getFileName(): ?string
	{
		return $this->fileName;
	}
	
	/**
	 * @param string|null $downloadLinkName
	 * @return $this
	 */
	public function setDownloadLinkName(?string $downloadLinkName): static
	{
		$this->downloadLinkName = $downloadLinkName;
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getDownloadLinkName(): ?string
	{
		return $this->downloadLinkName;
	}
	
	/**
	 * @param string|null $clearLinkName
	 * @return $this
	 */
	public function setClearLinkName(?string $clearLinkName): static
	{
		$this->clearLinkName = $clearLinkName;
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getClearLinkName(): ?string
	{
		return $this->clearLinkName;
	}
	
	/**
	 * Дополнительные поля для обработки в обработчиках событий и функции колбек StateChanged
	 * @param string $code
	 * @param mixed $value
	 * @return $this
	 */
	public function addOption(string $code, mixed $value): static
	{
		$this->options->set($code, $value);
		return $this;
	}
	
	/**
	 * @return Dictionary
	 */
	public function getOptions(): Dictionary
	{
		return $this->options;
	}
	// endregion ////
	
	/**
	 * JsonSerializable::jsonSerialize
	 * Specify data which should be serialized to JSON
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
				'TOTAL_ITEMS' => $this->getTotalItems(),
				'PROCESSED_ITEMS' => $this->getProcessedItems(),
				'STATUS' => $this->getStatus()->getValue(),
				'SUMMARY' => $this->getSummary(),
				'OPTIONS' => $this->getOptions()->toArray()
			],
			(
				null === $this->isFinalize()
				? []
				: [
					'FINALIZE' => $this->isFinalize()
				]
			),
			(
				null === $this->getWarning()
				? []
				: [
					'WARNING' => $this->getWarning()
				]
			),
			(
				null === $this->getNextController()
				? []
				: [
					'NEXT_CONTROLLER' => $this->getNextController()
				]
			),
			(
				null === $this->getNextAction()
				? []
				: [
					'NEXT_ACTION' => $this->getNextAction()
				]
			),
			(
				null === $this->getDownloadLink()
				? []
				: [
					'DOWNLOAD_LINK' => $this->getDownloadLink(),
					'FILE_NAME' => $this->getFileName(),
					'DOWNLOAD_LINK_NAME' => $this->getDownloadLinkName(),
					'CLEAR_LINK_NAME' => $this->getClearLinkName(),
				]
			),
		);
	}
}