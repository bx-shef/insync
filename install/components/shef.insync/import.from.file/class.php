<?php declare(strict_types=1);

namespace Local\Component\Shef\InSync;

defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\IO;
use Bitrix\Main\Type\Dictionary;
use Shef\Options\Components;
use Shef\Options\Main\Utils as OptionsUtils;
use Shef\UiClear\Css;
use Shef\InSync\Sync;

Loc::loadMessages(__FILE__);

if(!\Bitrix\Main\Loader::includeModule('shef.options'))
{
	die('Can\'t include module shef.options');
}

class ShefInSyncImportFromFileComponent
	extends Components\AControllerable
{
	protected ?Sync\FromFile\AFileProcess $objectProcess;
	// region Modules ////
	protected static function getModulesList(): array
	{
		return [
			'shef.insync',
		];
	}
	// endregion ////
	
	public function configureActions(): array
	{
		return [
			'importFile' => Components\Actions\Normal::get(),
			'getDemoFile' => Components\Actions\Free::get(),
		];
	}
	
	/**
	 * @throws ArgumentNullException
	 */
	public function importFileAction(): ?array
	{
		$this->initAjax();
		if(!$this->getErrorCollection()->isEmpty())
		{
			return null;
		}
		
		$fileUpload = $this->request->getFile($this->arParams['INPUT_NAME']['FILE']);
		if(!is_array($fileUpload))
		{
			$this->addError(new Error('Not upload file'));
			return null;
		}
		else
		{
			$fileUpload['resultPath'] = $this->getObjectProcess()->getImportFolder().basename($fileUpload['name']);
		}
		
		$response = move_uploaded_file($fileUpload['tmp_name'], $fileUpload['resultPath']);
		if($response === false)
		{
			$this->addError(new Error('Not upload file for import'));
			return null;
		}
		
		$response = $this->getObjectProcess()
			->setFile(
				new IO\File($fileUpload['resultPath'])
			)
			->run()
		;
		if(!$response->isSuccess())
		{
			$this->addErrors($response->getErrors());
			return null;
		}
		
		$errors = [];
		$data = [];
		$data[] = Loc::getMessage('importFileAction_TITLE', [
			'#COLOR#' => Css\Color::info->value
		]);
		
		$resultImport = $response->getData();
		$data[] = Loc::getMessage('importFileAction_STAT');
		$data[] = Loc::getMessage('importFileAction_LIST_START');
		
		if(isset($resultImport['rows']))
		{
			$data[] = Loc::getMessage('importFileAction_TTL', [
				'#VALUE#' => (int)$resultImport['rows']
			]);
		}
		
		if(isset($resultImport['errors']))
		{
			$data[] = Loc::getMessage('importFileAction_FAIL', [
				'#COLOR#' => Css\Color::danger->value,
				'#VALUE#' => (int)$resultImport['errors']
			]);
			
			
			if(
				is_array($resultImport['errorsImport'])
				&& !empty($resultImport['errorsImport'])
			)
			{
				foreach($resultImport['errorsImport'] as $problem)
				{
					if($problem instanceof Error)
					{
						$error = sprintf(
							'[row %s] message: %s',
							$problem->getCustomData()['xmlId'] ?? '?',
							$problem->getMessage()
						);
						
						$errors[] = $error;
					}
					else
					{
						$errors[] = (string)$problem;
					}
				}
			}
		}
		
		$data[] = Loc::getMessage('importFileAction_LIST_STOP');
		
		return [
			'content' => (new \CTextParser)->convertText(implode("\n", $data)),
			'errors' => implode(PHP_EOL, array_map(function(string $error)
			{
				return '<div class="error">'.$error.'</div>';
			}, $errors))
		];
	}
	
	/**
	 *
	 * @throws ArgumentNullException
	 * @throws FileNotFoundException
	 */
	public function getDemoFileAction(string $module, string $className): ?\Bitrix\Main\Engine\Response\File
	{
		$this->arParams['MODULE'] = trim($module);
		$this->arParams['CLASS'] = trim($className);
		
		$this->initAjax();
		if(!$this->getErrorCollection()->isEmpty())
		{
			return null;
		}
		
		$demoFile = $this->getObjectProcess()::getDemoFile();
		if(!($demoFile instanceof IO\File))
		{
			$this->addError(new Error('Demo File Not Support'));
			return null;
		}
		
		return (new \Bitrix\Main\Engine\Response\File(
			$demoFile->getPath(),
			$demoFile->getName(),
			$demoFile->getContentType()
		))->showInline(false);
	}
	
	/**
	 * @return null|array
	 */
	protected function listKeysSignedParameters(): ?array
	{
		return [
			'MODULE',
			'CLASS',
		];
	}
	// endregion ////

	// region Tools.Page ////
	/**
	 * @inheritDoc
	 */
	protected function setPageProperty(): void
	{
		OptionsUtils::getCMainApplication()->SetTitle(Loc::getMessage('PAGE_TITLE', [
			'#CLASS_TITLE#' => $this->getObjectProcess()::getProcessTitle() ?? '??'
		]));
	}
	// endregion ////

	// region arParams ////
	/**
	 * Инициализация $this->arParams
	 * @return void
	 */
	protected function initParams(): void
	{
		$response = $this->initObjectProcess();
		if(!$response->isSuccess())
		{
			$this->addErrors($response->getErrors());
			return;
		}
		
		$this->arParams['INPUT_NAME'] = [
			'FILE' => 'IMPORT_FILE'
		];
	}

	/**
	 * Выполняет проверку обязательных параметром
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentNullException
	 * <code>
	 * if((int)$this->arParams['DEMO'] < 1)
	 * {
	 * 	$this->addError(new Error('test Error'));
	 * 	return;
	 * }
	 * </code>
	 */
	protected function checkRequiredParams(): void
	{}
	// endregion ////

	// region arResult ////
	/**
	 * Инициализация $this->arResult
	 * @return void
	 */
	protected function initResult(): void
	{
		$this->arResult = [];
	}
	// endregion ////

	// region Work ////
	protected function process(): void
	{
		
	}

	protected function processAjax(): void
	{
		
	}
	// endregion ////

	// region FileProcess ////
	protected function initObjectProcess(): Result
	{
		$result = new Result();
		
		if(empty($this->arParams['MODULE']))
		{
			return $result->addError(new Error('>> Module not set at params'));
		}
		$this->arParams['MODULE'] = trim($this->arParams['MODULE']);
		
		if(empty($this->arParams['CLASS']))
		{
			return $result->addError(new Error('>> Class not set at params'));
		}
		$this->arParams['CLASS'] = trim($this->arParams['CLASS']);
		
		// region Module ////
		if(!Loader::includeModule($this->arParams['MODULE']))
		{
			return $result->addError(new Error('module '.$this->arParams['MODULE'].' not loaded'));
		}
		// endregion ////
		
		// region Class ////
		if(!class_exists($this->arParams['CLASS']))
		{
			return $result->addError(new Error('>> Class '.$this->arParams['CLASS'].' not exist'));
		}
		elseif(!is_subclass_of($this->arParams['CLASS'], Sync\FromFile\AFileProcess::class, true))
		{
			return $result->addError(new Error('>> Class '.$this->arParams['CLASS'].' not instanceof Shef\InSync\Sync\FromFile\AFileProcess'));
		}
		// endregion ////
		
		$this->objectProcess = new $this->arParams['CLASS'];
		$this->objectProcess->setSyncClassSyncTable(\Shef\InSync\Sync\Model\SyncTable::class);
		
		return $result;
	}
	
	/**
	 * @return Sync\FromFile\AFileProcess
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function getObjectProcess(): Sync\FromFile\AFileProcess
	{
		if(!($this->objectProcess instanceof Sync\FromFile\AFileProcess))
		{
			throw new \Bitrix\Main\ArgumentNullException('objectProcess');
		}

		return $this->objectProcess;
	}
	// endregion ////
}