<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\Store;

use Shef\Options\TraitList;
use Bitrix\Main\ORM;
use Bitrix\Main\Type\Contract\Arrayable;

class Store
	extends \Bitrix\Catalog\EO_Store
	implements Arrayable
{
	use TraitList\Tools\XmlId;
	
	public const EmptyName = 'Empty';
	
	public static function build(array $params): static
	{
		return new static([
			'XML_ID' => ($params['XML_ID'] ?? null) ?: static::getXmlIdIdempotence(),
			'TITLE' => ($params['TITLE'] ?? null) ?: static::EmptyName,
			'ADDRESS' => ($params['ADDRESS'] ?? null) ?: '',
		]);
	}
	
	public function toArray(): array
	{
		return $this->collectValues(
			ORM\Objectify\Values::ALL,
			ORM\Fields\FieldTypeMask::SCALAR
		);
	}
}