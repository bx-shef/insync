<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Section;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM;
use Bitrix\Main\Type\Contract\Arrayable;
use Shef\Options\TraitList;
use Shef\InSync\Main\Utils;

class Section
	extends \Bitrix\Iblock\EO_Section
	implements Arrayable
{
	use TraitList\Tools\XmlId;
	
	public const EmptyName = 'Empty';
	
	/**
	 * @var string
	 * @memo обязательно нужно указать свой кастомный таблет
	 */
	static public $dataClass = SectionTable::class;
	
	/**
	 * Построение Обекта раздела
	 *
	 * @param array $params
	 * @return static
	 * @throws ArgumentException
	 * @throws SystemException
	 */
	public static function build(
		array $params
	): static
	{
		$params['XML_ID'] = $params['XML_ID'] ?: static::getXmlIdIdempotence();
		
		if(empty($params['NAME']))
		{
			$params['CODE'] = $params['CODE'] ?: $params['XML_ID'];
			$params['ACTIVE'] = false;
			$params['NAME'] = static::EmptyName;
		}
		else
		{
			$params['CODE'] = $params['CODE'] ?: Utils::translation($params['NAME']);
			$params['ACTIVE'] = $params['ACTIVE'] ?: false;
			$params['NAME'] = trim($params['NAME']);
		}
		
		/** @var SectionTable $dataClass */
		$dataClass = static::$dataClass;
		
		/** @var Section $entity */
		$entity = $dataClass::createObject(true);
		
		$entity
			->setIblockId($dataClass::getIblockId())
			->setXmlId($params['XML_ID'])
			->setName($params['NAME'])
			->setActive($params['ACTIVE'])
			->setGlobalActive($params['ACTIVE'])
			->setCode($dataClass::checkCode($params['CODE']))
			->setIblockSectionId(new SqlExpression('NULL'))
			->setDateCreate(new \Bitrix\Main\Type\DateTime)
			->setTimestampX(new \Bitrix\Main\Type\DateTime)
		;
		
		return $entity;
	}
	
	/**
	 * Устанавливаем родителя по внешнему коду
	 * Если пустой передали - сбрасываем
	 *
	 * @throws ArgumentException|SystemException
	 */
	public function setParentByXmlId(
		string $parentXmlId
	): static
	{
		if(
			$parentXmlId === ''
			&& $this->getIblockSectionId() !== 0
		)
		{
			$this->setIblockSectionId(new SqlExpression('NULL'));
		}
		elseif($parentXmlId !== '')
		{
			/** @var SectionTable $dataClass */
			$dataClass = static::$dataClass;
			
			/** @var Section $sectionParent */
			$sectionParent = $dataClass::getByXmlId($parentXmlId);
			
			if($sectionParent)
			{
				$this->setIblockSectionId($sectionParent->getId());
			}
			else
			{
				throw new ArgumentException(
					sprintf(
						'Not find parent section by xmlId: %s',
						$parentXmlId
					),
					'parentXmlId'
				);
			}
		}
		
		return $this;
	}
	
	/**
	 * @throws ArgumentException
	 */
	public function toArray(): array
	{
		return Utils::prepareCollectValues($this);
	}
}