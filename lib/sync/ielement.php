<?php declare(strict_types=1);

namespace Shef\InSync\Sync;

use Bitrix\Main\ORM;

/**
 * Интерфейс сущности для пошагового импорта
 */
interface IElement
{
	/**
	 * XmlId внешней сущности
	 *
	 * @param string $originId
	 * @return IElement
	 */
	public function setInterfaceOriginId(string $originId): IElement;
	public function getInterfaceOriginId(): string;
	
	/**
	 * Код внешней системы
	 *
	 * @param string $originatorId
	 * @return IElement
	 */
	public function setInterfaceOriginatorId(string $originatorId): IElement;
	public function getInterfaceOriginatorId(): string;
	
	/**
	 * Статус синхронизации сущности
	 * @param EStatus $status
	 * @return IElement
	 */
	public function setInterfaceStatus(EStatus $status): IElement;
	public function getInterfaceStatus(): EStatus;
	
	/**
	 * Сообщение синхронизации сущности
	 * @param string $message
	 * @return IElement
	 */
	public function setInterfaceMessage(string $message): IElement;
	public function getInterfaceMessage(): string;
	
	/**
	 * Дата внесения в таблицу синхронизации
	 * @param \Bitrix\Main\Type\DateTime $dateInsert
	 * @return IElement
	 */
	public function setInterfaceDateInsert(\Bitrix\Main\Type\DateTime $dateInsert): IElement;
	public function getInterfaceDateInsert(): \Bitrix\Main\Type\DateTime;
	
	/**
	 * Заголовок внешней сущности
	 * @param string $title
	 * @return IElement
	 */
	public function setInterfaceTitle(string $title): IElement;
	public function getInterfaceTitle(): string;
	
	/**
	 * Дополнительная информация внешней сущности
	 *
	 * Посути хранит массив полей для импорта
	 *
	 * @param array $additional
	 * @return IElement
	 */
	public function setInterfaceAdditional(array $additional): IElement;
	public function getInterfaceAdditional(): array;

	/**
	 * Очищает в списке сущностей статус
	 *
	 * @example в списке компаний UF_SYNC -> N
	 *
	 * @return void
	 */
	public function clearInterfaceSyncStatus(): void;
	
	/**
	 * save Entity
	 * @return ORM\Data\AddResult|ORM\Data\UpdateResult|ORM\Data\Result
	 */
	public function saveInterface(): ORM\Data\AddResult|ORM\Data\UpdateResult|ORM\Data\Result;
	/**
	 * delete Entity
	 * @return ORM\Data\DeleteResult|ORM\Data\Result
	 */
	public function deleteInterface(): ORM\Data\DeleteResult|ORM\Data\Result;
}