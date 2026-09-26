<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Model\IBlock\Element;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Shef\Options\TraitList;
use Shef\Insync\Main\Utils;

/**
 * Трейт элемента инфоблока
 * поддерживает работу черз старое Api
 */
trait ElementTrait
{
	use TraitList\Tools\XmlId;
	
	protected null|\CIBlockElement $oldApi = null;
	
	public static function getEmptyName(): string
	{
		return 'Empty';
	}
	
	/**
	 * Построение Обекта элемента
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
			$params['NAME'] = static::getEmptyName();
		}
		else
		{
			$params['CODE'] = $params['CODE'] ?: Utils::translation($params['NAME']);
			$params['ACTIVE'] = $params['ACTIVE'] ?: false;
			$params['NAME'] = trim($params['NAME']);
		}
		
		/** @var ElementTable $dataClass */
		$dataClass = static::$dataClass;
		
		/** @var Element $entity */
		$entity = $dataClass::createObject(true);
		
		$entity
			->setIblockId($dataClass::getIblockId())
			->setXmlId($params['XML_ID'])
			->setName($params['NAME'])
			->setActive($params['ACTIVE'])
			->setCode($dataClass::checkCode($params['CODE']))
			->setDateCreate(new \Bitrix\Main\Type\DateTime)
			->setTimestampX(new \Bitrix\Main\Type\DateTime)
			->setIblockSectionId(new SqlExpression('NULL'))
			->setInSections(false)
		;
		
		return $entity;
	}
	
	/**
	 * Очистка кешей
	 * 
	 * @return $this
	 */
	public function clearCache(): static
	{
		\CIBlock::clearIblockTagCache($this->getIblockId());
		
		\Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex(
			$this->getIblockId(),
			$this->getId()
		);
		
		return $this;
	}
	
	/**
	 * Конфигурирует поле (не свойство) типа картинка
	 * через старое Api
	 * 
	 * @return $this
	 * @throws \LogicException
	 */
	public function configurePicture(
		string $fieldName,
		null|array $file
	): static
	{
		if(null === $file)
		{
			return $this;
		}
		
		$response = $this->oldApiUpdate([
			$fieldName => $file
		]);
		
		if(!$response->isSuccess())
		{
			throw new \Bitrix\Main\InvalidOperationException(
				sprintf(
					'field %s: %s',
					$fieldName,
					join('; ', $response->getErrorMessages())
				)
			);
		}
		
		$this->fill($fieldName);
		return $this;
	}
	
	// region Old.Api ////
	/**
	 * Возвращает объект старого Api
	 */
	public function getOldApi(): \CIBlockElement
	{
		if(null === $this->oldApi)
		{
			$this->oldApi = new \CIBlockElement();
		}
		
		return $this->oldApi;
	}
	
	/**
	 * Проверяет наличие важных первичных ключей
	 * для работы со старым Api
	 *
	 * @return void
	 * @throws \LogicException
	 */
	protected function oldApiCheckPrimary(): void
	{
		if($this->getId() < 1)
		{
			throw new \LogicException('id not set');
		}
		elseif($this->getIblockId() < 1)
		{
			throw new \LogicException('iblockId not set');
		}
	}
	
	/**
	 * Сохраняет через старое Api элемент
	 * 
	 * @param array $fields
	 * 
	 * @return Result
	 * @throws \LogicException
	 */
	public function oldApiUpdate(array $fields = []): Result
	{
		$result = new Result;
		
		$this->oldApiCheckPrimary();
		
		if(isset($fields['IBLOCK_ID']))
		{
			$fields['IBLOCK_ID'] = $this->getIblockId();
		}
		
		$response = $this->getOldApi()->Update(
			$this->getId(),
			$fields,
			false,
			true,
			true,
			false
		);
		
		if(!$response)
		{
			return $result->addError(new Error(
				sprintf(
					'Error update element [%s]: %s',
					$this->getId(),
					$this->getOldApi()->LAST_ERROR
				)
			));
		}
		
		return $result->setData([
			'primary' => $this->primary,
			'iblockId' => $this->getIblockId(),
			'fields' => $fields,
		]);
	}
	
	/**
	 * Устанавливает свойства элемента через старое API
	 * 
	 * @param array $fields
	 * 
	 * @return Result
	 * @throws \LogicException
	 */
	public function oldApiUpdateProps(array $fields): Result
	{
		$result = new Result;
		
		$this->oldApiCheckPrimary();
		
		$this->getOldApi()::SetPropertyValuesEx(
			$this->getId(),
			$this->getIblockId(),
			$fields
		);
		
		return $result->setData([
			'primary' => $this->primary,
			'iblockId' => $this->getIblockId(),
			'fields' => $fields,
		]);
	}
	// endregion ////
	
	// region Tools ////
	/**
	 * @throws ArgumentException
	 */
	public function toArray(): array
	{
		return Utils::prepareCollectValues($this);
	}
	// endregion ////
}