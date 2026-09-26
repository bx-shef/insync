<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element;

class ElementCollection
	extends \Bitrix\Iblock\EO_Element_Collection
{
	/**
	 * @var string
	 * @memo обязательно нужно указать свой кастомный таблет
	 */
	static public $dataClass = ElementTable::class;
}