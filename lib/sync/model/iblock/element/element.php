<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element;

use Bitrix\Main\Type\Contract\Arrayable;

class Element
	extends \Bitrix\Iblock\EO_Element
	implements Arrayable
{
	use ElementTrait;
	
	/**
	 * @var string
	 * @memo обязательно нужно указать свой кастомный таблет
	 */
	static public $dataClass = ElementTable::class;
	
	
	public function sysPostSave()
	{
		parent::sysPostSave();
		
		$this->clearCache();
	}
}