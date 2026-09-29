<?php declare(strict_types=1);

namespace Shef\InSync\Main\Options\Import\FromFile\Model\OptionField;

use Bitrix\Main\Type\Contract\Arrayable;

/**
 * Абстракция для поля выводимого на стартовом диалоге
 */
abstract class ABase
	implements \JsonSerializable, Arrayable
{
	protected EOptionFieldsType $type;
	
	public function __construct(
		public readonly string $name,
		public readonly string $title,
		public readonly bool $obligatory = false,
		public readonly string $emptyMessage = ''
	)
	{
		$this->type = EOptionFieldsType::Text;
	}
	
	/**
	 * JsonSerializable::jsonSerialize
	 * Данные для json_encode() — массив toArray()
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}
	
	public function toArray(): array
	{
		return [
			'name' => $this->name,
			'title' => $this->title,
			'type' => $this->type->getValue(),
			'obligatory' => $this->obligatory,
			'emptyMessage' => $this->emptyMessage,
		];
	}
}