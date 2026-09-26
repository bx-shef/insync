<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Shef\InSync\Sync\IElement;
use Shef\InSync\Sync\EStatus;

class Sync
	extends EO_Sync
	implements IElement
{
	public function setInterfaceOriginId(string $originId): IElement
	{
		$this->setOriginId($originId);
		return $this;
	}
	
	public function getInterfaceOriginId(): string
	{
		return $this->getOriginId();
	}
	
	public function setInterfaceOriginatorId(string $originatorId): IElement
	{
		$this->setOriginatorId($originatorId);
		return $this;
	}
	
	public function getInterfaceOriginatorId(): string
	{
		return $this->getOriginatorId();
	}
	
	public function setInterfaceStatus(EStatus $status): IElement
	{
		$this->setStatus($status->value);
		return $this;
	}
	
	public function getInterfaceStatus(): EStatus
	{
		return EStatus::from($this->get('STATUS'));
	}
	
	public function setInterfaceMessage(string $message): IElement
	{
		$this->setMessage($message);
		return $this;
	}
	
	public function getInterfaceMessage(): string
	{
		return $this->getMessage();
	}
	
	public function setInterfaceDateInsert(\Bitrix\Main\Type\DateTime $dateInsert): IElement
	{
		$this->setDateInsert($dateInsert);
		return $this;
	}
	
	public function getInterfaceDateInsert(): \Bitrix\Main\Type\DateTime
	{
		return $this->getDateInsert();
	}
	
	public function setInterfaceTitle(string $title): IElement
	{
		$this->setTitle($title);
		return $this;
	}
	
	public function getInterfaceTitle(): string
	{
		return $this->getTitle();
	}
	
	public function setInterfaceAdditional(array $additional): IElement
	{
		$this->setAdditional($additional);
		return $this;
	}
	
	public function getInterfaceAdditional(): array
	{
		return $this->getAdditional();
	}
	
	public function saveInterface(): ORM\Data\AddResult|ORM\Data\UpdateResult|ORM\Data\Result
	{
		return $this->save();
	}
	
	public function deleteInterface(): ORM\Data\DeleteResult|ORM\Data\Result
	{
		return $this->delete();
	}
	
	public function clearInterfaceSyncStatus(): void
	{
		$this->setInterfaceStatus(EStatus::New);
	}
}