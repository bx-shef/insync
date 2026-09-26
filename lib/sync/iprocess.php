<?php declare(strict_types=1);

namespace Shef\InSync\Sync;

use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Result;

/**
 * Интерфейс для пошагового импорта
 */
interface IProcess
{
	/**
	 * Возвращает название процесса импорта
	 * @return string
	 */
	public static function getProcessTitle(): string;

	/**
	 * Возвращает описание процесса импорта
	 * @return string
	 */
	public static function getProcessDescription(): string;
	
	/**
	 * Возвращает объект синхронизации
	 * @return EntityObject
	 *
	 * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=11689&LESSON_PATH=3913.3516.5748.11687.11689
	 */
	public function getSyncEntityObject(): EntityObject;
	
	/**
	 * Возвращает коллекцию объекта синхронизации
	 * @return Collection
	 *
	 * @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&CHAPTER_ID=011743&LESSON_PATH=3913.3516.5748.11743
	 */
	public function getSyncCollection(): Collection;
	
	/**
	 * Делает обход элементов
	 *
	 * @return Result
	 */
	public function run(): Result;

	/**
	 * Создаем элемент
	 * @param array $params
	 * @return IElement
	 */
	public function build(array $params = []): IElement;

	/**
	 * Инициализируем элемент
	 * @param IElement $element
	 * @return Result
	 */
	public function init(IElement $element): Result;

	/**
	 * Обрабатываем элемент
	 * @param IElement $element
	 * @return Result
	 */
	public function process(IElement $element): Result;
}