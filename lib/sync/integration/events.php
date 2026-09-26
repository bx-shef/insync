<?php declare(strict_types=1);

namespace Shef\InSync\Sync\Integration;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Shef\Options\TraitList;
use Shef\InSync\Main\Constants;
use Bitrix\Main\Localization\Loc;
use Shef\UiClear\Css\Color;

Loc::loadMessages(__FILE__);

/**
 * Class Events
 * @package Shef\InSync\Sync\Integration
 *
 * Ссылки на импорт
 *
 */
class Events
{
	use TraitList\Events;
	use TraitList\EventResponse;

	protected static function getModuleId(): string
	{
		return Constants::MODULE_ID;
	}

	public static function onBitrixMenuExtInitTopPanelUserMenu(\Bitrix\Main\Event $event): EventResult
	{
		return static::returnMainEventSuccess(
			(new Result())->setData([
				'items' => [
					[
						'TITLE' => Loc::getMessage('SHEF_INSYNC_TOOLS_IMPORT'),
						'ITEMS' => static::getItems(),
						'IS_FOR_ADMIN' => true
					]
				]
			]),
			__FUNCTION__
		);
	}
	
	protected static function getItems(): array
	{
		$list = [
		
		];
		
		$event = new \Bitrix\Main\Event(
			Constants::MODULE_ID,
			'onShefInSyncSyncMenuExtInitTopPanelUserMenu',
			[]
		);
		$event->send();
		foreach ($event->getResults() as $eventResult)
		{
			switch($eventResult->getType())
			{
				case \Bitrix\Main\EventResult::SUCCESS:
					$handlerRes = $eventResult->getParameters();
					if(
						isset($handlerRes['items'])
						&& is_array($handlerRes['items'])
					){
						foreach($handlerRes['items'] as $item)
						{
							$list[] = $item;
						}
						unset($agent);
					}
				break;
				case \Bitrix\Main\EventResult::ERROR:
				case \Bitrix\Main\EventResult::UNDEFINED:
				default:
				break;
			}
		}
		unset($event);
		
		return $list;
	}
}