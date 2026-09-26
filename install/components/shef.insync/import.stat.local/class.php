<?php declare(strict_types=1);

namespace Local\Component\Shef\InSync;

defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Shef\Options\Components;
use Shef\Options\Options\SmartStd;
use Shef\Options\Main\Utils as OptionsUtils;
use Shef\InSync\Main\Constants;
use Shef\InSync\Agents;
use Shef\InSync\Main\Access;
use Bitrix\Main\ObjectException;

Loc::loadMessages(__FILE__);

// Базовый класс — из shef.options: без него класс компонента не объявить.
// Ошибку покажет страница («… is not a component»), а не die() посреди вывода.
if(!\Bitrix\Main\Loader::includeModule('shef.options'))
{
	return;
}

/**
 * Статистика таблицы импорта и агенты импорта.
 *
 * Страница — администратору либо с правом «W» на shef.insync; кнопки агентов
 * — тому, у кого есть права на модуль агента. Всё проверяется и в
 * ajax-действиях: до 2.0.0 они требовали только входа на портал, и любой
 * сотрудник включал и выключал любой агент портала по ID и чистил таблицу
 * импорта.
 */
class ShefInSyncImportStatLocalComponent
	extends Components\AControllerable
{
	protected const GRID_ID = 'SHEF_INSYNC_IMPORT_STAT_LOCAL_GRID';
	
	// region Modules ////
	protected static function getModulesList(): array
	{
		return [
			'shef.insync'
		];
	}
	// endregion ////
	
	// region Ajax ////
	
	public function configureActions(): array
	{
		return [
			'startAgent' => Components\Actions\Normal::get(),
			'stopAgent' => Components\Actions\Normal::get(),
			'getAgents' => Components\Actions\Normal::get(),
			'clearRow' => Components\Actions\Normal::get(),
		];
	}
	
	public function startAgentAction(int $id): ?array
	{
		$this->initAjax();
		if(!$this->getErrorCollection()->isEmpty())
		{
			return null;
		}
		
		if(!$this->checkAgentAccess($id))
		{
			return null;
		}
		
		$response = Agents\Manager::startById($id);
		if(!$response->isSuccess())
		{
			$this->addErrors($response->getErrors());
			return null;
		}

		return [];
	}

	public function stopAgentAction(int $id): ?array
	{
		$this->initAjax();
		if(!$this->getErrorCollection()->isEmpty())
		{
			return null;
		}
		
		if(!$this->checkAgentAccess($id))
		{
			return null;
		}

		$response = Agents\Manager::stopById($id);
		if(!$response->isSuccess())
		{
			$this->addErrors($response->getErrors());
			return null;
		}

		return [];
	}
	
	public function getAgentsAction(): ?array
	{
		$this->initAjax();
		if(!$this->getErrorCollection()->isEmpty())
		{
			return null;
		}
		
		$response = $this->getAgentRunAll();
		if($response->isSuccess())
		{
			$this->arResult['AGENTS_LIST'] = $response->getData();
		}
		
		ob_start();
		$this->includeComponentTemplate('agentList');
		$content = ob_get_contents();
		ob_end_clean();

		return [
			'content' => $content
		];
	}
	
	public function clearRowAction(array $rowData = []): ?array
	{
		$this->initAjax();
		if(!$this->getErrorCollection()->isEmpty())
		{
			return null;
		}
		
		// Ошибка — выход. Раньше ошибка добавлялась, а удаление шло дальше:
		// без кода импорта clear() стирал строки ВСЕХ импортов.
		$originatorId = trim((string)($rowData['originatorId'] ?? ''));
		if($originatorId === '')
		{
			$this->addError(new Error('Wrong originatorId'));
			return null;
		}
		
		$dateInsert = (int)($rowData['dateInsertTs'] ?? 0);
		if($dateInsert < 1)
		{
			$this->addError(new Error('Wrong dateInsertTs'));
			return null;
		}
		
		$dateInsert = \Bitrix\Main\Type\DateTime::createFromTimestamp($dateInsert);
		
		$syncCollection = \Shef\InSync\Sync\Model\SyncTable::createCollection();
		$syncCollection->clear($originatorId, $dateInsert);
		
		return [
			'rowData' => [
				$originatorId,
				$dateInsert
			]
		];
	}
	// endregion ////
	
	// region Tools.Page ////
	/**
	 * @inheritDoc
	 */
	protected function setPageProperty(): void
	{
		OptionsUtils::getCMainApplication()->SetTitle(Loc::getMessage('PAGE_TITLE'));
	}
	// endregion ////
	
	// region Params ////
	/**
	 * @return null|array
	 */
	protected function listKeysSignedParameters(): ?array
	{
		return [];
	}

	protected function initParams(): void
	{
		$this->arParams['GRID_ID'] = $this->arParams['GRID_ID'] ?? static::GRID_ID;
	}
	
	/**
	 * Права — и для страницы, и для каждого ajax-действия.
	 */
	protected function checkRequiredParams(): void
	{
		if(!Access::canManage())
		{
			$this->addError(new Error(Loc::getMessage('ACCESS_DENIED') ?: 'Access denied', 'ACCESS_DENIED'));
		}
	}
	
	/**
	 * Агент есть, и у пользователя права на ЕГО модуль.
	 */
	protected function checkAgentAccess(int $id): bool
	{
		$moduleId = Agents\Manager::getModuleIdById($id);
		if(null === $moduleId)
		{
			$this->addError(new Error('Agent not found'));
			return false;
		}
		
		if(!Access::canManage($moduleId))
		{
			$this->addError(new Error(Loc::getMessage('ACCESS_DENIED') ?: 'Access denied', 'ACCESS_DENIED'));
			return false;
		}
		
		return true;
	}
	
	public function initResult(): void
	{
		$this->arResult = [
			'ERRORS' => [],
			'ROWS' => [],
			'TOTAL_ROWS_COUNT' => 0,
			'AGENTS_LIST' => [],
			'COLUMNS' => $this->getGridColumns()
		];
	}
	// endregion ////
	
	// region Work ////
	protected function process(): void
	{
		$syncCollection = \Shef\InSync\Sync\Model\SyncTable::createCollection();
		$this->arResult['ROWS'] = $syncCollection->getStatistic();

		$sum = array_sum(array_map(function($row) {
			return (int)$row['CNT'];
		}, $this->arResult['ROWS']));
		
		$this->arResult['TOTAL_ROWS_COUNT'] = $sum;

		$response = $this->getAgentRunAll();
		if($response->isSuccess())
		{
			$this->arResult['AGENTS_LIST'] = $response->getData();
		}
	}
	// endregion ////
	
	// region Agents ////
	/**
	 * @throws ObjectException
	 */
	public function getAgentRunAll(): Result
	{
		$result = new Result();
		$listAgents = [];

		$event = new \Bitrix\Main\Event(
			Constants::MODULE_ID,
			'onComponentStatLocal',
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
					)
					{
						foreach($handlerRes['items'] as $agent)
						{
							if($agent instanceof \Shef\InSync\Agents\Entity)
							{
								$agentEntity = (new SmartStd());
								$listAgents[] = $agentEntity;
								
								$agentEntity->entity = $agent;
								$agentEntity->list = [];
							}
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
		
		foreach($listAgents as $agentEntity)
		{
			$agentEntity->list = Agents\Manager::findAll($agentEntity->entity);
		}

		return $result->setData($listAgents);
	}
	// endregion ////
	
	// region Grid ////
	protected function getGridColumns(): array
	{
		return [
			[
				"id" => "ORIGINATOR_ID",
				"name" => Loc::getMessage('GRID_COL_ORIGINATOR_ID'),
				"default" => true,
			],
			[
				"id" => "DATE_INSERT",
				"name" => Loc::getMessage('GRID_COL_DATE_INSERT'),
				"default" => true,
			],
			[
				"id" => "CNT",
				"name" => Loc::getMessage('GRID_COL_CNT'),
				"default" => true
			]
		];
	}
	// endregion ////
}