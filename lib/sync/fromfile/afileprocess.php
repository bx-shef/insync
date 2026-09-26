<?php declare(strict_types=1);

namespace Shef\InSync\Sync\FromFile;

use DirectoryIterator;
use Exception;
use LogicException;
use Throwable;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Errorable;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\IO\FileNotFoundException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\IO;
use Bitrix\Main\Application;
use Bitrix\Main\Type\Collection;
use Shef\Options\TraitList;
use Shef\Problems;
use Shef\InSync\Sync;
use Shef\InSync\Sync\IElement;
use Shef\Insync\Main\Constants;

/**
 * Class AFileProcess
 *
 * Абстрактный класс для Импорта файлов
 *
 * Нужно передать на вход файл для импорта
 * @memo Используем AFileProcess::getExistFiles() для получения списка файлов для импорта
 *
 * В Результате файл распарсится, записи попадут в таблицу импорта, файл уйдет в архив
 *
 * @memo Для опций используем AEntityProcess::optionCollection
 *
 * @memo Переопределяем AEntityProcess::OriginatorId
 * @memo Переопределяем AEntityProcess::import
 * @memo Переопределяем AEntityProcess::init() для инициализации строки файла в элемент для записи в таблицу импорта
 */
abstract class AFileProcess
	extends Sync\AProcess
	implements IFromFile, Sync\IProcess, Errorable
{
	use TraitList\Tools\Encoding;

	protected const OriginatorId = 'demoFile';
	
	public const ErrorCodeFileNotExist = 1;
	public const ErrorCodeFileNotCopy = 2;
	public const ErrorCodeFileFailParse = 3;
	public const ErrorCodeFileEmptyContent = 4;

	private ?IO\File $file = null;
	
	protected array $content = [];
	
	public function __construct()
	{
		parent::__construct();
		$this->initPath();
	}
	
	/**
	 * @inheritDoc
	 */
	public static function getImportFileAccept(): string
	{
		return '';
	}
	
	/**
	 * @inheritDoc
	 */
	public static function getDemoFile(): ?IO\File
	{
		return null;
	}

	// region Content ////
	/**
	 * Очищаем импортируемый контент
	 * @return $this
	 */
	protected function clearContent(): self
	{
		$this->content = [];

		return $this;
	}
	// endregion ////

	// region Process ////
	/**
	 * @inheritDoc
	 * @throws ArgumentNullException
	 */
	final public function run(): Result
	{
		$result = new Result();

		$this->clearContent();

		try
		{
			/** @memo: Проверим что бы файл существовал */
			if(
				!($this->file instanceof IO\File)
				|| !$this->file->isExists()
			)
			{
				return $result->addError(new Error('Import file not exist', static::ErrorCodeFileNotExist));
			}

			/** @memo: Файл в Обработку */
			$this->makeProcessFile();
			if(!$this->getErrorCollection()->isEmpty())
			{
				return $result->addErrors($this->getErrors());
			}

			/** @memo: Разбираем файл */
			$this->parseFile();
			if(!$this->getErrorCollection()->isEmpty())
			{
				return $result->addErrors($this->getErrors());
			}

			/** @memo: Импортируем данные */
			$response = $this->import();
			
			$result->setData($response->getData());
			
			Sync\Integration\Manager::sendPullForImportStatLocal();
			
			if(!$response->isSuccess())
			{
				$result->setData(array_merge(
					$result->getData(),
					[
						'errorsImport' => $response->getErrors()
					]
				));
				
				
				$this->makeProblemFile();
				if(!$this->getErrorCollection()->isEmpty())
				{
					$result->addErrors($this->getErrors());
				}
				
				return $result;
			}

			/** @memo: Файл в Архив */
			$this->makeDoneFile();
			if(!$this->getErrorCollection()->isEmpty())
			{
				return $result->addErrors($this->getErrors());
			}
		}
		catch(Throwable $throwable)
		{
			$this->addError(Problems\Throwable\Manager::buildError($throwable, false));
		}

		$this->clearContent();

		if(!$this->getErrorCollection()->isEmpty())
		{
			return $result->addErrors($this->getErrors());
		}

		return $result;
	}

	/**
	 * Разбор файла в $this->content
	 *
	 * @return void
	 */
	abstract protected function parseFile(): void;

	/**
	 * Обработка данных из $this->content
	 * Тут используем IEntityProcess для записи в SyncTable
	 *
	 * @see \Shef\InSync\Sync\Model\SyncTable
	 * @see \Shef\InSync\Sync\IEntityProcess
	 *
	 * @return Result
	 */
	abstract protected function import(): Result;
	
	/**
	 * Обрабытывает Строку файла
	 * @param string $originId
	 * @return Result
	 * @throws Exception
	 */
	protected function processRow(
		string $originId
	): Result
	{
		$result = new Result();
		
		$elementSync = $this->build([
			'ORIGIN_ID' => $originId,
		]);
		
		$response = $this->init($elementSync);
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		
		$response = $this->process($elementSync);
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		
		return $result->setData([
			'ENTITY' => $elementSync
		]);
	}
	
	/**
	 * @inheritdoc
	 * 
	 * @memo Указываем статус Undefined.
	 * @memo В последсвии агент при выборке переведет в статус Process -> Success || Problem
	 * 
	 * @see AAgent::action
	 */
	public function build(array $params = []): IElement
	{
		$syncEntity =  $this->getSyncEntityObject();
		
		if(!($syncEntity instanceof IElement))
		{
			throw new LogicException('syncEntityObject not implement IElement');
		}
		$syncEntity
			->setInterfaceOriginatorId(static::getOriginatorId())
			->setInterfaceStatus($params['STATUS'] ?? Sync\EStatus::Undefined)
			->setInterfaceMessage($params['MESSAGE'] ?? '')
			->setInterfaceDateInsert($this->curDateTime)
			->setInterfaceTitle((string)$params['TITLE'])
			->setInterfaceAdditional(
				$params['ADDITIONAL'] ?? []
			)
		;
		
		return $syncEntity;
	}

	/**
	 * Обрабатываем элемент -> Добавляем в таблицу импорта
	 * @throws Exception
	 */
	public function process(Sync\IElement $element): Result
	{
		/** @var \Shef\InSync\Sync\Model\Sync $element */
		
		$result = new Result();
		
		$response = $element->saveInterface();
		if(!$response->isSuccess())
		{
			return $result->addErrors($response->getErrors());
		}
		
		return $result->setData([
			'primary' => $response->getPrimary(),
			'data' => $response->getData()
		]);
	}
	// endregion ////
	// endregion ////

	// region File ////
	/**
	 * @inheritDoc
	 * @throws FileNotFoundException
	 * @throws ArgumentOutOfRangeException
	 */
	public static function getExistFiles(
		string $name,
		string $extension
	): array
	{
		/** @var DirectoryIterator $item */

		$iterator = new DirectoryIterator(static::getImportFolder());
		$match = "/$name.+\.($extension)*$/i";

		$list = [];
		foreach($iterator as $item)
		{
			if(
				$item->isFile()
				&& preg_match($match, (string)$item, $matches)
			)
			{
				$file = new IO\File($item->getPathname());
				$list[] = [
					'ts' => $file->getModificationTime(),
					'file' => $file
				];
				unset($file);
			}
		}

		Collection::sortByColumn(
			$list,
			[
				'ts' => [SORT_NUMERIC, SORT_ASC]
			]
		);

		return array_column($list, 'file');
	}
	
	/** 
	 * Количество дней хранится файл в результате импорта
	 */
	protected static function getMaxDayOffDoneFile(): int 
	{
		return Constants::getMaxDayOffDoneFile();
	}
	/**
	 * Чистит папку с результатми импорта от старых файлов
	 *
	 * @return void
	 */
	protected function clearDoneFolder(): void
	{
		/** @var DirectoryIterator $item */
		
		$iterator = new DirectoryIterator(substr($this->getDoneFolder(), 0, -1));
		$curDateTime = new \DateTime();
		foreach ($iterator as $item)
		{
			if(!$item->isFile())
			{
				continue;
			}
			$fileDateTime = (new \DateTime())->setTimestamp($item->getCTime());
			$interval = $curDateTime->diff($fileDateTime);
			$daysOff = (int)$interval->format('%a');
			
			if($daysOff > static::getMaxDayOffDoneFile())
			{
				unlink($item->getPathname());
			}
			/*/
			else
			{
				$this->debugger->debug(
					'clear done folder',
					[
						'folder' => substr($this->getDoneFolder(), 0, -1),
						'dayOff' => $daysOff,
						'file' => $item->getPathname()
					]
				);
			}
			//*/
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public static function getImportFolder(): string
	{
		return Application::getDocumentRoot().'/upload/import/'.static::getOriginatorId().'/';
	}
	
	/**
	 * @inheritDoc
	 */
	public static function getDoneFolder(): string
	{
		return Application::getDocumentRoot().'/upload/import/copy/'.static::getOriginatorId().'/';
	}
	
	/**
	 * @inheritDoc
	 */
	public static function getProblemFolder(): string
	{
		return Application::getDocumentRoot().'/upload/import/problem/'.static::getOriginatorId().'/';
	}
	
	/**
	 * Инициализирует папки
	 * @return void
	 */
	protected function initPath(): void
	{
		if(!IO\Directory::isDirectoryExists($this->getImportFolder()))
		{
			IO\Directory::createDirectory($this->getImportFolder());
		}
		
		if(!IO\Directory::isDirectoryExists($this->getDoneFolder()))
		{
			IO\Directory::createDirectory($this->getDoneFolder());
		}
		else
		{
			$this->clearDoneFolder();
		}
		
		if(!IO\Directory::isDirectoryExists($this->getProblemFolder()))
		{
			IO\Directory::createDirectory($this->getProblemFolder());
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function setFile(IO\File $file): static
	{
		$this->file = $file;
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @throws ArgumentNullException
	 * @throws ObjectNotFoundException
	 */
	public function getFile(): IO\File
	{
		if(!($this->file instanceof IO\File))
		{
			throw new ArgumentNullException('file');
		}

		if(!$this->file->isExists())
		{
			throw new ObjectNotFoundException('file not exist');
		}

		return $this->file;
	}
	
	/**
	 * @inheritDoc
	 * @throws ArgumentNullException
	 * @throws ObjectNotFoundException
	 */
	public function getProcessFilePath(): string
	{
		return $this->getImportFolder()
			.'process_'.static::getOriginatorId().'_'.$this->getCurDateTime()->format('d-m-Y_H-i')
			.'.'.$this->getFile()->getExtension();
	}
	
	/**
	 * @inheritDoc
	 * @throws ArgumentNullException
	 * @throws ObjectNotFoundException
	 */
	public function getDoneFilePath(): string
	{
		return $this->getDoneFolder()
			.'done_'.static::getOriginatorId().'_'.$this->getCurDateTime()->format('d-m-Y_H-i')
			.'.'.$this->getFile()->getExtension();
	}
	
	/**
	 * @inheritDoc
	 * @throws ArgumentNullException
	 * @throws ObjectNotFoundException
	 */
	public function getProblemFilePath(): string
	{
		return $this->getProblemFolder()
			.'problem_'.static::getOriginatorId().'_'.$this->getCurDateTime()->format('d-m-Y_H-i')
			.'.'.$this->getFile()->getExtension();
	}
	// endregion ////

	// region File.Operations ////
	/**
	 * Переводит файл в обработку
	 *
	 * @return void
	 * @throws ArgumentNullException|ObjectNotFoundException
	 */
	protected function makeProcessFile(): void
	{
		$processPath = $this->getProcessFilePath();
		
		if(!rename($this->getFile()->getPath(), $processPath))
		{
			$this->addError(new Error('Error make work file', static::ErrorCodeFileNotCopy));
		}

		$this->setFile(new IO\File($processPath));
	}

	/**
	 * Переводит файл в архив
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function makeDoneFile(): void
	{
		$donePath = $this->getDoneFilePath();
		if(!rename($this->getFile()->getPath(), $donePath))
		{
			$this->addError(new Error('Error Move File', static::ErrorCodeFileNotCopy));
		}

		$this->setFile(new IO\File($donePath));
	}
	
	/**
	 * Переводит файл в проблемы
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function makeProblemFile(): void
	{
		$problemPath = $this->getProblemFilePath();
		if(!rename($this->getFile()->getPath(), $problemPath))
		{
			$this->addError(new Error('Error Move File', static::ErrorCodeFileNotCopy));
		}

		$this->setFile(new IO\File($problemPath));
	}
	// endregion ////
}