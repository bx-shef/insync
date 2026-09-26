<?php declare(strict_types=1);

namespace Shef\InSync\Sync\FromFile;

use Bitrix\Main\IO;

/**
 * Interface IFromFile
 *
 * Интерфейс для пошагового импорта из файла
 *
 */
interface IFromFile
{
	/**
	 * Указывает в какой кодировке исходные данные
	 * @return string
	 */
	public static function getEncodingFrom(): string;
	/**
	 * Указывает какое расширение можно указывать в html.form
	 *
	 * @example .csv
	 * @return string
	 */
	public static function getImportFileAccept(): string;
	
	/**
	 * Возвращает файл примера
	 *
	 * @return ?IO\File
	 */
	public static function getDemoFile(): ?IO\File;
	
	/**
	 * Возвращает по маске список доступных файлов.
	 * Использовать нужно в момент определения файла для импорта
	 *
	 * @memo сортируем по времени изменения по возврастан
	 *
	 * @param string $name
	 * @param string $extension
	 * @return IO\File[]
	 */
	public static function getExistFiles(
		string $name,
		string $extension
	): array;
	
	/**
	 * Возвращает папку импорта
	 * @return string
	 */
	public static function getImportFolder(): string;
	/**
	 * Возвращает папку архива
	 * @return string
	 */
	public static function getDoneFolder(): string;
	
	/**
	 * Возвращает папку архива
	 * @return string
	 */
	public static function getProblemFolder(): string;
	
	/**
	 * Файл для импорта
	 * @param IO\File $file
	 * @return $this
	 */
	public function setFile(IO\File $file): static;
	
	/**
	 * Возвращает обрабатываемый файл
	 *
	 * @memo Файл будет копироваться по стадиям: Новый -> Обработка -> Архив
	 *
	 * @return IO\File
	 */
	public function getFile(): IO\File;
	
	/**
	 * Возвращает путь для файла в стадии Обработка
	 * @return string
	 */
	public function getProcessFilePath(): string;
	
	/**
	 * Возвращает путь для файла в стадии Архив
	 * @return string
	 */
	public function getDoneFilePath(): string;
	
	/**
	 * Возвращает путь для файла в стадии Проблема
	 * @return string
	 */
	public function getProblemFilePath(): string;
}