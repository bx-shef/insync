<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element\PropertyType;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\IO;
use Shef\InSync\Sync\Model;

class FileProperty
	extends Property
	implements IProperty, Arrayable
{
	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	public function __construct(
		int $iblockId,
		int $primary,
		string $code,
		string $title,
		bool $isMultiple = false,
		int $sort = 500
	)
	{
		parent::__construct(
			iblockId: $iblockId,
			primary: $primary,
			type: EType::File,
			code: $code,
			title: $title,
			isMultiple: $isMultiple,
			sort: $sort
		);
		
		$this->init();
	}
	
	/**
	 * @throws SystemException
	 * @throws ArgumentException
	 */
	private function init(): void
	{
	}
	
	/**
	 * @inheritDoc
	 */
	public function getByValue(PropertyHelper $propertyHelper): null|array
	{
		return static::makeFile($propertyHelper->value);
	}
	
	/**
	 * Создает массив картинки|файла для сохранения
	 *
	 * @param string|null $path
	 * @return null|array
	 */
	public static function makeFile(null|string $path = null): null|array
	{
		if(
			null !== $path
			&& (IO\File::isFileExists($path)))
		{
			return \CFile::MakeFileArray($path);
		}
		
		return null;
	}
}