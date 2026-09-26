<?php declare(strict_types=1);

namespace Shef\InSync\Integration\Intranet;

use Bitrix\Intranet\CustomSection;
use Bitrix\Main\Web\Uri;
use Shef\Options\Main\Security;

/**
 * @link https://dev.1c-bitrix.ru/api_d7/bitrix/intranet/custom_section.php
 * @see \Bitrix\Crm\Integration\Intranet\CustomSectionProvider
 */
class CustomSectionProvider
	extends CustomSection\Provider
{
	
	protected static function prepareSettingsRow(string $pageSettings = ''): array
	{
		return explode('~', $pageSettings);
	}
	
	protected static function getComponentNameFromParams(array $params = []): string
	{
		return (string)$params[0];
	}
	
	protected static function getComponentParamsFromParams(array $params = []): array
	{
		$componentName = static::getComponentNameFromParams($params);
		
		return match ($componentName)
		{
			'shef.insync:import.from.file' => [
				'CLASS' => (string)$params[1],
				'MODULE' => (string)$params[2]
			],
			'shef.insync:redirect' => [
				'URL' => (string)$params[1],
			],
			default => [],
		};
	}
	
	/**
	 * @inheritDoc
	 */
	public function isAvailable(
		string $pageSettings,
		int $userId
	): bool
	{
		$params = static::prepareSettingsRow($pageSettings);
		
		$componentName = static::getComponentNameFromParams($params);
		
		return match ($componentName)
		{
			'shef.insync:import.stat.local' => Security::isAdmin(),
			default => true,
		};
	}

	/**
	 * @inheritDoc
	 */
	public function resolveComponent(
		string $pageSettings,
		Uri $url
	): null|CustomSection\Provider\Component
	{
		$params = static::prepareSettingsRow($pageSettings);
		
		
		$componentTemplate = '';
		$componentName = static::getComponentNameFromParams($params);
		$componentParameters = static::getComponentParamsFromParams($params);
		
		if($componentName === 'shef.insync:redirect')
		{
			LocalRedirect($componentParameters['URL']);
		}
		
		$isSliderMode = false;
		if(stripos($url->getQuery(), 'IFRAME_TYPE=SIDE_SLIDER') !== false)
		{
			$isSliderMode = true;
		}
		
		if($isSliderMode)
		{
			$componentParameters = [
				'POPUP_COMPONENT_NAME' => $componentName,
				'POPUP_COMPONENT_TEMPLATE_NAME' => $componentTemplate,
				'POPUP_COMPONENT_PARAMS' => $componentParameters,
				'USE_UI_TOOLBAR' => 'Y',
				'POPUP_COMPONENT_USE_BITRIX24_THEME' => 'Y',
			];
			
			$componentName = 'bitrix:ui.sidepanel.wrapper';
			$componentTemplate = '';
		}
		
		return (new CustomSection\Provider\Component())
			->setComponentName($componentName)
			->setComponentTemplate($componentTemplate)
			->setComponentParams($componentParameters)
		;
	}
}
