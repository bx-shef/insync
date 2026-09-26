<?php declare(strict_types=1);

namespace Shef\InSync\Integration\Intranet;

use Bitrix\Intranet\CustomSection;
use Bitrix\Main\Web\Uri;
use Shef\InSync\Main\Access;

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
		return (string)($params[0] ?? '');
	}
	
	protected static function getComponentParamsFromParams(array $params = []): array
	{
		$componentName = static::getComponentNameFromParams($params);
		
		return match ($componentName)
		{
			'shef.insync:import.from.file' => [
				'CLASS' => (string)($params[1] ?? ''),
				'MODULE' => (string)($params[2] ?? '')
			],
			'shef.insync:redirect' => [
				'URL' => (string)($params[1] ?? ''),
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
		
		// Страницы этого модуля — тому, кто вправе управлять импортом; сами
		// компоненты проверяют права ещё раз. Чужие страницы в разделе
		// (installLeftMenu[].moduleId другого модуля) отвечают за себя сами.
		return match ($componentName)
		{
			'shef.insync:import.stat.local',
			'shef.insync:redirect' => Access::canManage(),
			'shef.insync:import.from.file' => Access::canManage(
				(string)(static::getComponentParamsFromParams($params)['MODULE'] ?? '')
			),
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
		
		// Переход — только внутри портала: адрес берётся из настроек страницы.
		if($componentName === 'shef.insync:redirect')
		{
			$target = (string)$componentParameters['URL'];
			if(str_starts_with($target, '/') && !str_starts_with($target, '//'))
			{
				LocalRedirect($target);
			}
			
			return null;
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
