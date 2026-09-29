<?php declare(strict_types=1);

namespace Shef\InSync\Main;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\ORM;
use CPullStack;
use Shef\Problems\Main\Utils as ProblemsUtils;

class Utils
{
	public static function checkExistXMLReader(): bool
	{
		return extension_loaded('xmlreader');
	}
	
	/**
	 * @throws LoaderException
	 */
	public static function sendPullShared(
		string $command,
		?array $params = []
	): Result
	{
		$result = new Result();

		if(!Loader::includeModule('pull'))
		{
			return $result->addError(new Error('module pull not loaded'));
		}
		
		CPullStack::AddShared([
			'module_id' => Constants::MODULE_ID,
			'command' => $command,
			'params' => $params
		]);
		
		return $result;
	}
	
	/**
	 * Транслитерация строк
	 *
	 * @param string $value
	 * @param array $options
	 * @param callable|null $callback
	 * @return string
	 */
	public static function translation(string $value, array $options = [], callable|null $callback = null): string
	{
		$options = array_merge(
			[
				'replace_space' => '-',
				'replace_other' => '-'
			],
			$options
		);
		
		if(is_callable($callback))
		{
			$value = $callback($value);
		}
		
		$value = str_replace(['ё', 'Ё'], ['yo', 'Yo'], $value);
		
		return \CUtil::translit(
			$value,
			($options['lang'] ?? '') ?: 'ru',
			$options
		);
	}
	
	/**
	 * Преобразует данные \Bitrix\Main\ORM\Objectify\EntityObject в массив
	 *
	 * @param ORM\Objectify\EntityObject $entity
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function prepareCollectValues(
		\Bitrix\Main\ORM\Objectify\EntityObject $entity
	): array
	{
		$list = $entity->collectValues(
			ORM\Objectify\Values::ALL,
			ORM\Fields\FieldTypeMask::SCALAR|ORM\Fields\FieldTypeMask::RELATION
		);
		
		array_walk(
			$list,
			function(&$row)
			{
				if($row instanceof ORM\Objectify\EntityObject)
				{
					$row = $row->collectValues(
						ORM\Objectify\Values::ALL,
						ORM\Fields\FieldTypeMask::SCALAR
					);
					
					array_walk(
						$row,
						function(&$innerRow)
						{
							if($innerRow instanceof ORM\Objectify\EntityObject)
							{
								$innerRow = $innerRow->collectValues(
									ORM\Objectify\Values::ALL,
									ORM\Fields\FieldTypeMask::SCALAR
								);
							}
							elseif($innerRow instanceof \Bitrix\Main\Type\DateTime)
							{
								$innerRow = $innerRow->toString();
							}
							elseif($innerRow instanceof \Bitrix\Main\Type\Date)
							{
								$innerRow = $innerRow->toString();
							}
						}
					);
				}
				elseif($row instanceof \Bitrix\Main\Type\DateTime)
				{
					$row = $row->toString();
				}
				elseif($row instanceof \Bitrix\Main\Type\Date)
				{
					$row = $row->toString();
				}
				elseif($row instanceof ORM\Objectify\Collection)
				{
					$row = $row->getAll();
					array_walk(
						$row,
						function(&$innerRow)
						{
							if($innerRow instanceof ORM\Objectify\EntityObject)
							{
								$innerRow = $innerRow->collectValues(
									ORM\Objectify\Values::ALL,
									ORM\Fields\FieldTypeMask::SCALAR
								);
								array_walk(
									$innerRow,
									function(&$innerInnerRow)
									{
										if($innerInnerRow instanceof ORM\Objectify\EntityObject)
										{
											$innerInnerRow = $innerInnerRow->collectValues(
												ORM\Objectify\Values::ALL,
												ORM\Fields\FieldTypeMask::SCALAR
											);
										}
										elseif($innerInnerRow instanceof \Bitrix\Main\Type\DateTime)
										{
											$innerInnerRow = $innerInnerRow->toString();
										}
										elseif($innerInnerRow instanceof \Bitrix\Main\Type\Date)
										{
											$innerInnerRow = $innerInnerRow->toString();
										}
									}
								);
							}
							elseif($innerRow instanceof \Bitrix\Main\Type\DateTime)
							{
								$innerRow = $innerRow->toString();
							}
							elseif($innerRow instanceof \Bitrix\Main\Type\Date)
							{
								$innerRow = $innerRow->toString();
							}
							else
							{
								$innerRow = ProblemsUtils::getAllParents(
									$innerRow
								);
							}
						}
					);
				}
				elseif(is_object($row))
				{
					$row = ProblemsUtils::getAllParents($row);
				}
			}
		);
		
		return $list;
	}
}