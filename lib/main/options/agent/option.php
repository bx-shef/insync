<?php
declare(strict_types=1);

namespace Shef\InSync\Main\Options\Agent;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc;
use Shef\Options\Main\Options as ShefOptions;
use Shef\InSync\Agents;
use Shef\InSync\Main\Access;

Loc::loadMessages(__FILE__);

/**
 * Для вывода опции настройки Агетн на страницы параметров модуля
 *
 * @see Controller
 */
class Option
	extends ShefOptions\RowInfo
{
	private ?Agents\Entity $agentEntity = null;
	
	public function __construct(string $code)
	{
		parent::__construct($code);
		
		$this->setType(ShefOptions\TypeUIAlert::Warning);
		
		\Bitrix\Main\UI\Extension::load([
			'ui.buttons',
			'ui.buttons.icons',
		]);
	}
	
	// region Get/Set ////
	/**
	 * @param Agents\Entity $agentEntity
	 * @return $this
	 */
	public function setAgentEntity(Agents\Entity $agentEntity): self
	{
		$this->agentEntity = $agentEntity;
		return $this;
	}
	
	/**
	 * @return Agents\Entity
	 * @throws ArgumentNullException
	 */
	public function getAgentEntity(): Agents\Entity
	{
		if(!($this->agentEntity instanceof Agents\Entity))
		{
			throw new \Bitrix\Main\ArgumentNullException('agentEntity');
		}
		
		return $this->agentEntity;
	}
	
	public function getDescription(): string
	{
		$descr = Loc::getMessage('shef.insync_OPTION_Agent', [
			'#LANG#' => LANGUAGE_ID,
			'#ID#' => $this->getAgentEntity()->getId(),
			'#NAME#' => $this->getAgentEntity()->getName(),
			'#IS_ACTIVE#' => $this->getAgentEntity()->isActive()
				? Loc::getMessage('shef.insync_OPTION_AGENT_ACTIVE_Y')
				: Loc::getMessage('shef.insync_OPTION_AGENT_ACTIVE_N'),
		]);
		
		$obParser = new \CTextParser;
		$descr = $obParser->convertText($descr);
		unset($obParser);
		
		return $descr;
	}
	
	// region Controller ////
	public function getController(): \Bitrix\Main\Engine\Controller
	{
		static $controller;
		if(null === $controller)
		{
			$controller = new Controller();
		}
		
		return $controller;
	}
	
	/**
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	public function getAgentEntityUrlStop(): string
	{
		return UrlManager::getInstance()->createByController(
			$this->getController(),
			'stopAgent',
			[
				'agentId' => $this->getAgentEntity()->getId(),
				'moduleId' => $this->getAgentEntity()->getModule(),
				// Действие по ссылке — GET, и csrf ядро проверяет и для него.
				'sessid' => bitrix_sessid(),
			],
			false
		)->getUri();
	}
	// endregion ////
	
	/**
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	public function getAgentEntityUrlStart(): string
	{
		return UrlManager::getInstance()->createByController(
			$this->getController(),
			'startAgent',
			[
				'agentId' => $this->getAgentEntity()->getId(),
				'moduleId' => $this->getAgentEntity()->getModule(),
				// Действие по ссылке — GET, и csrf ядро проверяет и для него.
				'sessid' => bitrix_sessid(),
			],
			false
		)->getUri();
	}
	// endregion ////
	
	// region Render /////
	/**
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 */
	protected function renderValue(string $moduleId): string
	{
		// Кнопку видит только тот, кому действие разрешено; само действие
		// проверяет права ещё раз — см. Controller::checkAgent().
		if(!Access::canManage($this->getAgentEntity()->getModule()))
		{
			$action = '';
		}
		elseif($this->getAgentEntity()->isActive())
		{
			$action = sprintf(
				'<a type="button" href="%s" class="ui-btn ui-btn-xs ui-btn-danger ui-btn-icon-pause"></a>',
				$this->getAgentEntityUrlStop()
			);
		}
		else
		{
			$action = sprintf(
				'<a type="button" href="%s" class="ui-btn ui-btn-xs ui-btn-success ui-btn-icon-start"></a>',
				$this->getAgentEntityUrlStart()
			);
		}
		
		return sprintf(
			join('', [
				'<div class="ui-alert %s"><span class="ui-alert-message">',
				'%s',
				'&nbsp;%s',
				'</span></div>',
			]),
			$this->getType(),
			$this->getDescription(),
			$action
		);
	}
	// endregion ////
}