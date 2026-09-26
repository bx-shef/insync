<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\Hl\Demo;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\ArgumentException;
use Shef\Insync\Main\Utils;
use Shef\Options\TraitList;

class Card
	extends EO_Card
	implements Arrayable
{
	use TraitList\Tools\XmlId;
	
	public static function build(array $params): static
	{
		return new static([
			'UF_XML_ID' => $params['UF_XML_ID'] ?: static::getXmlIdIdempotence(),
			'UF_SUM' => $params['UF_SUM'] ?: 0.0,
		]);
	}
	
	/**
	 * @throws ArgumentException
	 */
	public function toArray(): array
	{
		return Utils::prepareCollectValues($this);
	}
}