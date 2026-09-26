<?php declare(strict_types=1);

namespace Local\Component\Shef\InSync;

defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\IO;
use Shef\Options\Components;
use Shef\Options\Main\Utils as OptionsUtils;
use Shef\InSync\Main\Access;
use Shef\InSync\Sync;

Loc::loadMessages(__FILE__);

// Базовый класс — из shef.options: без него класс компонента не объявить.
// Ошибку покажет страница («… is not a component»), а не die() посреди вывода.
if(!Loader::includeModule('shef.options'))
{
	return;
}

/**
 * Загрузка файла в таблицу импорта из публичной части.
 *
 * Параметры MODULE и CLASS — модуль и класс импорта (наследник
 * \Shef\InSync\Sync\FromFile\AFileProcess). Страницы с этим компонентом
 * добавляют в левое меню модули импорта, см. CustomSectionProvider.
 *
 * Права — администратор либо «W» на модуль импорта (Access::canManage()),
 * проверяются в initParams(): он срабатывает и для страницы, и для каждого
 * ajax-действия.
 */
class ShefInSyncImportFromFileComponent
	extends Components\AControllerable
{
	protected null|Sync\FromFile\AFileProcess $objectProcess = null;

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
		// getDemoFile был Free — без входа на портал и без csrf, и при этом
		// подключал модуль и создавал объект класса, названные в запросе.
		return [
			'importFile' => Components\Actions\Normal::get(),
			'getDemoFile' => Components\Actions\Normal::get(),
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
		if(
			!is_array($fileUpload)
			|| (int)($fileUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
		)
		{
			$this->addError(new Error('Not upload file'));
			return null;
		}

		$fileName = static::prepareUploadName(
			(string)($fileUpload['name'] ?? ''),
			$this->getObjectProcess()::getImportFileAccept()
		);
		if(null === $fileName)
		{
			$this->addError(new Error('Wrong file type'));
			return null;
		}

		$resultPath = $this->getObjectProcess()::getImportFolder().$fileName;

		$response = move_uploaded_file((string)$fileUpload['tmp_name'], $resultPath);
		if($response === false)
		{
			$this->addError(new Error('Not upload file for import'));
			return null;
		}

		$response = $this->getObjectProcess()
			->setFile(
				new IO\File($resultPath)
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
			'#COLOR#' => Sync\EStatus::Process->getColor()
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
				'#COLOR#' => Sync\EStatus::Fail->getColor(),
				'#VALUE#' => (int)$resultImport['errors']
			]);

			if(
				is_array($resultImport['errorsImport'] ?? null)
				&& !empty($resultImport['errorsImport'])
			)
			{
				foreach($resultImport['errorsImport'] as $problem)
				{
					if($problem instanceof Error)
					{
						$customData = $problem->getCustomData();

						$errors[] = sprintf(
							'[row %s] message: %s',
							is_array($customData) ? ($customData['xmlId'] ?? '?') : '?',
							$problem->getMessage()
						);
					}
					else
					{
						$errors[] = (string)$problem;
					}
				}
			}
		}

		$data[] = Loc::getMessage('importFileAction_LIST_STOP');

		// Текст ошибок — данные из строк файла: разметкой он не становится.
		return [
			'content' => (new \CTextParser)->convertText(implode("\n", $data)),
			'errors' => implode(PHP_EOL, array_map(function(string $error)
			{
				return '<div class="error">'.htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE).'</div>';
			}, $errors))
		];
	}

	/**
	 * Пример файла импорта.
	 *
	 * Модуль и класс приходят из запроса — ссылка открывается в новом окне,
	 * мимо подписанных параметров. Поэтому здесь те же проверки, что и для
	 * страницы: вход и csrf (Normal), права на модуль, класс — наследник
	 * AFileProcess и принадлежит названному модулю.
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
	 * Имя загруженного файла для каталога импорта либо null — такой файл не
	 * принимаем.
	 *
	 * Имя приходит от браузера. До 2.0.0 каталог импорта лежал под /upload,
	 * под корнем сайта, и без проверки в него ложились .php и .htaccess.
	 * Каталог теперь вне корня, но проект может задать свой — проверка
	 * остаётся. Поэтому: только имя без пути, без исполняемых расширений и —
	 * если импорт объявил accept с расширениями — только с ними.
	 *
	 * @param string $name имя от браузера
	 * @param string $accept AFileProcess::getImportFileAccept(), например «.csv» или «.xml,.zip»
	 */
	public static function prepareUploadName(string $name, string $accept = ''): null|string
	{
		$name = basename(str_replace('\\', '/', trim($name)));

		if($name === '' || $name === '.' || $name === '..' || str_starts_with($name, '.'))
		{
			return null;
		}

		if(!preg_match('/^[\p{L}\p{N} ._()\-]+$/u', $name))
		{
			return null;
		}

		$extension = mb_strtolower((string)pathinfo($name, PATHINFO_EXTENSION));
		if($extension === '')
		{
			return null;
		}

		if(preg_match('/^(php\d*|phtml|phar|pht|phps|inc|cgi|pl|py|sh|asp|aspx|jsp|shtml|htaccess|htm|html|svg|js)$/', $extension))
		{
			return null;
		}

		// Ядро знает и свои исполняемые расширения — из настроек портала.
		if(function_exists('HasScriptExtension') && HasScriptExtension($name))
		{
			return null;
		}

		$allowed = [];
		foreach(explode(',', mb_strtolower($accept)) as $item)
		{
			$item = trim($item);
			if(str_starts_with($item, '.') && mb_strlen($item) > 1)
			{
				$allowed[] = mb_substr($item, 1);
			}
		}

		if(!empty($allowed) && !in_array($extension, $allowed, true))
		{
			return null;
		}

		return $name;
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
		$this->arParams['INPUT_NAME'] = [
			'FILE' => 'IMPORT_FILE'
		];

		// Права — до того, как подключать модуль и создавать объект импорта:
		// его конструктор уже работает с каталогами импорта.
		if(!Access::canManage((string)($this->arParams['MODULE'] ?? '')))
		{
			$this->addError(new Error(Loc::getMessage('ACCESS_DENIED') ?: 'Access denied', 'ACCESS_DENIED'));
			return;
		}

		$response = $this->initObjectProcess();
		if(!$response->isSuccess())
		{
			$this->addErrors($response->getErrors());
		}
	}

	/**
	 * Права проверяет initParams(): он срабатывает и для страницы, и для
	 * каждого ajax-действия, и раньше, чем создаётся объект импорта.
	 *
	 * @return void
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
	/**
	 * Модуль и класс импорта из параметров.
	 *
	 * Модуль подключается и объект создаётся только после проверок: имя
	 * модуля — id модуля Битрикса, класс — наследник AFileProcess и лежит в
	 * namespace этого модуля. Иначе параметр (а для getDemoFile — запрос)
	 * решал бы, какой модуль подключить и чей класс создать.
	 */
	protected function initObjectProcess(): Result
	{
		$result = new Result();

		$module = trim((string)($this->arParams['MODULE'] ?? ''));
		$class = ltrim(trim((string)($this->arParams['CLASS'] ?? '')), '\\');

		if($module === '')
		{
			return $result->addError(new Error('>> Module not set at params'));
		}

		if(!preg_match('/^[a-z0-9_]+\.[a-z0-9_]+$/', $module))
		{
			return $result->addError(new Error('>> Wrong module id'));
		}

		if($class === '')
		{
			return $result->addError(new Error('>> Class not set at params'));
		}

		// Класс модуля vendor.name живёт в Vendor\Name\... — так его ищет ядро.
		[$vendor, $name] = explode('.', $module);
		if(!str_starts_with(mb_strtolower($class), $vendor.'\\'.$name.'\\'))
		{
			return $result->addError(new Error('>> Class not from module '.$module));
		}

		$this->arParams['MODULE'] = $module;
		$this->arParams['CLASS'] = $class;

		// region Module ////
		if(!Loader::includeModule($module))
		{
			return $result->addError(new Error('module '.$module.' not loaded'));
		}
		// endregion ////

		// region Class ////
		if(!class_exists($class))
		{
			return $result->addError(new Error('>> Class '.$class.' not exist'));
		}
		elseif(!is_subclass_of($class, Sync\FromFile\AFileProcess::class, true))
		{
			return $result->addError(new Error('>> Class '.$class.' not instanceof Shef\InSync\Sync\FromFile\AFileProcess'));
		}
		// endregion ////

		$this->objectProcess = new $class;
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
