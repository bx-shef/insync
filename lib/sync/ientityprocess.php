<?php declare(strict_types=1);

namespace Shef\InSync\Sync;

use Bitrix\Main\Result;

/**
 * Интерфейс для пошагового импорта
 */
interface IEntityProcess
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