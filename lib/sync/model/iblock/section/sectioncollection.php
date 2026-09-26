<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Section;

class SectionCollection
	extends \Bitrix\Iblock\EO_Section_Collection
{
	/**
	 * @var string
	 * @memo обязательно нужно указать свой кастомный таблет
	 */
	static public $dataClass = SectionTable::class;

}