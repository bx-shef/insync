<?php declare(strict_types=1);

namespace Shef\InSync\TraitList\Xml;

use Generator;
use XMLReader;
use Bitrix\Main\InvalidOperationException;
use SbWereWolf\XmlNavigator;
use Shef\Insync\Main\Utils;

/**
 * Трейт для обработки XML
 */
trait ToYield
{
	/**
	 * @param string $xml
	 * @param string $tag
	 * @return array|Generator
	 * @throws InvalidOperationException
	 */
	public static function toYieldConvert(
		string $xml,
		string $tag
	): array|Generator
	{
		if(!Utils::checkExistXMLReader())
		{
			throw new InvalidOperationException('Not exist XmlReader');
		}
		
		$reader = XMLReader::XML($xml);
		if(!($reader instanceof XMLReader))
		{
			throw new InvalidOperationException('Not open XmlReader');
		}
		
		$mayRead = true;
		while(
			$mayRead
			&& $reader->name !== $tag
		)
		{
			$mayRead = $reader->read();
		}
		
		while(
			$mayRead
			&& $reader->name === $tag
		)
		{
			$row = XmlNavigator\Extraction\HierarchyComposer::compose($reader);
			
			yield $row;
			
			while (
				$mayRead
				&& $reader->nodeType !== XMLReader::ELEMENT
			)
			{
				$mayRead = $reader->read();
			}
		}
		
		$reader->close();
	}
}