<?php declare(strict_types=1);

namespace Shef\Insync\Main\Options\Import\FromFile;

use CUserOptions;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\IO;
use Bitrix\Main\Engine;
use Bitrix\Main\LoaderException;
use Shef\Options\Components\Actions;
use Shef\Options\TraitList;
use Shef\InSync\Agents;
use Shef\InSync\Sync;

Loc::loadMessages(__FILE__);

/**
 * Абстракция контроллера для импорта данных из страницы настроек модуля
 */
abstract class AController
	extends Engine\Controller
{
	use TraitList\Modules;
	
	protected string $processToken;
	
	protected static function getModulesList(): array
	{
		return [
			'shef.insync'
		];
	}
	
	/**
	 * @throws LoaderException
	 */
	protected function init(): void
	{
		parent::init();
		
		$response = $this->includeModules();
		if(!$response->isSuccess())
		{
			$this->addErrors($response->getErrors());
		}
		
		$this->processToken = (string)$this->request->get('PROCESS_TOKEN');
	}
	
	public function configureActions(): array
	{
		return [
			'checkFile' => Actions\Normal::get(),
			'import' => Actions\Normal::get(),
			'cancel' => Actions\Normal::get(),
		];
	}
	
	/**
	 * Возвращает экземпляр класса для Импорта файлов
	 * @return Sync\FromFile\AFileProcess
	 */
	abstract protected static function getProcessor(): Sync\FromFile\AFileProcess;
	
	/**
	 * Возвращает агент импорта
	 * @return Agents\AAgent
	 */
	abstract protected static function getAgentProcess(): Agents\AAgent;
	
	/**
	 * Копирует файл демо для внесения
	 * @param Sync\FromFile\AFileProcess $objectProcess
	 * @return IO\File
	 */
	protected function makeCopyFile(Sync\FromFile\AFileProcess $objectProcess): IO\File
	{
		$importFilePath = $objectProcess::getImportFolder().$objectProcess->getDemoFile()->getName();
		copy(
			$objectProcess->getDemoFile()->getPath(),
			$importFilePath
		);
		
		return new IO\File($importFilePath);
	}
	
	/**
	 * Подсчет текущего значения импорта
	 *
	 * @param Sync\FromFile\AFileProcess $objectProcess
	 * @return array
	 *
	 * @memo покажет все что по контроллеру импорта в таблице есть
	 */
	protected function getStatistic(Sync\FromFile\AFileProcess $objectProcess): array
	{
		$collection = new Sync\Model\SyncCollection();
		return $collection->getStatisticByOriginator($objectProcess::getOriginatorId());
	}
	
	/**
	 * Загружает данные из демо файла в таблицу импорта
	 *
	 * @return Response|null
	 * @throws ArgumentNullException
	 */
	public function checkFileAction(): null|Response
	{
		$this->clearProgressParameters();
		
		$result = new Response();
		
		$objectProcess = $this->getProcessor();
		
		$objectProcess->setSyncClassSyncTable(Sync\Model\SyncTable::class);
		$objectProcess->setFile($this->makeCopyFile($objectProcess));
		
		$response = $objectProcess->run();
		Sync\Integration\Manager::sendPullForImportStatLocal();
		
		if(!$response->isSuccess())
		{
			$this->addErrors($response->getErrors());
			return null;
		}
		
		$result->setNextAction('import');
		
		$statistic = $this->getStatistic($objectProcess);
		
		$result->setTotalItems((int)$statistic['CNT']);
		$result->setProcessedItems(0);
		
		$this->saveProgressParameters($result->toArray());
		
		return $result;
	}
	
	/**
	 * Импортирует данные из таблицы импорта
	 */
	public function importAction(): null|Response
	{
		$result = new Response();
		$params = $this->getProgressParameters();
		$result->setTotalItems((int)$params['TOTAL_ITEMS']);
		
		static::getAgentProcess()::process([
			'fromFileImport' => 'Y'
		]);
		
		$objectProcess = $this->getProcessor();
		$collection = new Sync\Model\SyncCollection();
		$statistic = $collection->getStatisticByOriginator($objectProcess::getOriginatorId());
		$cnt = (int)$statistic['CNT'];
		
		if($cnt === 0)
		{
			$result->setProcessedItems($result->getTotalItems());
			$result->setStatus(Enums\EResponseStatus::Completed);
			$this->clearProgressParameters();
			return $result;
		}
		
		$result->setProcessedItems($result->getTotalItems() - $cnt);
		
		$this->saveProgressParameters($result->toArray());
		
		return $result;
	}
	
	public function cancelAction(): null|Response
	{
		return (new Response())
			->setStatus(Enums\EResponseStatus::Completed)
			->setSummary(Loc::getMessage('shef.insync_OPTION_IMPORT_FROMFILE_CONTROLLER_CANCEL'))
		;
	}
	
	protected function getProgressParameterOptionName(): string
	{
		return $this->getModuleId(). '_fileImport';
	}
	
	protected function clearProgressParameters(): void
	{
		CUserOptions::DeleteOption(
			$this->getModuleId(),
			$this->getProgressParameterOptionName()
		);
	}
	
	protected function getProgressParameters(): array
	{
		$progressData = CUserOptions::GetOption(
			$this->getModuleId(),
			$this->getProgressParameterOptionName()
		);
		if (!is_array($progressData))
		{
			$progressData = [];
		}
		
		return $progressData;
	}
	
	protected function saveProgressParameters(array $values): void
	{
		// store state
		$progressData = array_merge(
			[
				'processToken' => $this->processToken,
			],
			$values
		);
		
		CUserOptions::SetOption(
			$this->getModuleId(),
			$this->getProgressParameterOptionName(),
			$progressData
		);
	}
}