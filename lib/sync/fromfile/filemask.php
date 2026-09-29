<?php declare(strict_types=1);

namespace Shef\InSync\Sync\FromFile;

/**
 * Какие файлы каталога импорта забирает getExistFiles().
 *
 * Имя начинается с префикса, расширение — одно из перечисленных через «|».
 * Было "/$name.+\.($extension)*$/": без якоря и без экранирования — брались
 * old-price.csv, файл без расширения и process_<код>_… того же каталога,
 * то есть файл, который уже в обработке.
 */
final class FileMask
{
	public static function build(string $prefix, string $extension): string
	{
		$extensionList = array_map(
			static fn(string $item): string => preg_quote($item, '/'),
			array_filter(explode('|', $extension), static fn(string $item): bool => $item !== '')
		);

		return '/^'.preg_quote($prefix, '/').'.*\.('.implode('|', $extensionList).')$/i';
	}

	public static function isMatch(string $fileName, string $prefix, string $extension): bool
	{
		return 1 === preg_match(static::build($prefix, $extension), $fileName);
	}
}
