<?php declare(strict_types=1);
namespace Shef\InSync\Sync\FromFile;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Errorable;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use CCSVData;
use Exception;
use Shef\InSync\Sync;
use Shef\Options\Options\SmartStd;

/**
 * Class ACsvProcess
 *
 * Абстракция для импорта CSV
 *
 * Нужно передать на вход файл для импорта
 * @memo Используем AFileProcess::getExistFiles() для получения списка файлов для импорта
 *
 * В Результате файл распарсится, записи попадут в таблицу импорта, файл уйдет в архив
 *
 * @memo Для опций используем AEntityProcess::optionCollection
 *
 * @memo Переопределяем AEntityProcess::OriginatorId
 * @memo Переопределяем ACsvProcess::isUseHeader() тут указываем есть ли заголоко
 * @memo Переопределяем ACsvProcess::getDelim() тут указываем разделитель
 * @memo Переопределяем AEntityProcess::getEncodingFrom() тут указываем кодировку файла
 *
 * @memo Переопределяем ACsvProcess::getMapImportFile()
 *
 * @memo Переопределяем ACsvProcess::test() для теста заголовка
 * @memo Переопределяем AEntityProcess::init() для инициализации строки файла в элемент для записи в таблицу импорта
 *
 */
abstract class ACsvProcess
	extends AFileProcess
	implements IFromCsvFile, IFromFile, Sync\IProcess, Errorable
{
	public const ErrorCodeCountColumns = 101;
	
	protected const OriginatorId = 'demoCsv';

	protected ?CCSVData $csv = null;
	protected array $header = [];
	/** @var array тут хранится строка из файла  */
	protected array $content = [];

	public function __construct()
	{
		parent::__construct();

		$this->csv = new CCSVData('R', static::isUseHeader());
	}
	
	abstract public static function isUseHeader(): bool;
	
	abstract public static function getDelim(): string;
	
	abstract public function getMapImportFile(): array;
	
	// region Content ////
	/**
	 * @inheritDoc
	 */
	protected function clearContent(): self
	{
		parent::clearContent();
		$this->header = [];
		return $this;
	}
	// endregion ////

	// region Process ////
	/**
	 * @inheritDoc
	 * @throws ArgumentNullException
	 */
	protected function parseFile(): void
	{
		$this->clearContent();
		try
		{
			$this->clearFile();
			if(!$this->getErrorCollection()->isEmpty())
			{
				return;
			}

			$this->csv->LoadFile($this->getFile()->getPath());
			$this->csv->SetDelimiter(static::getDelim());

			$this->csv->SetFirstHeader();
			if($item = $this->csv->Fetch())
			{
				$needHeaders = static::getMapImportFile();
				$item = static::convertEncoding($item);
				// Колонок в файле больше, чем в карте, — лишним имя по номеру;
				// расхождение числа колонок ловит test().
				for($i = 0, $itmCount = count($item); $i < $itmCount; $i++)
				{
					$this->header[$i] = $needHeaders[$i] ?? (string)$i;
				}
			}
			else
			{
				$this->addError(new Error('Empty content', static::ErrorCodeFileEmptyContent));
				return;
			}

			$this->test();
			if(!$this->getErrorCollection()->isEmpty())
			{
				return;
			}
		}
		catch(Exception $exception)
		{
			$this->addError(new Error($exception->getMessage(), static::ErrorCodeFileFailParse));
			return;
		}
	}

	/**
	 * Предварительная очистка файла импорта
	 *
	 * @return void
	 * @throws ArgumentNullException
	 * @throws ObjectNotFoundException
	 */
	protected function clearFile(): void
	{
		$content = file_get_contents($this->getFile()->getPath());

		$content = static::convertEncoding($content);

		$content = str_replace("\n\n".'"', ' "',$content);
		$content = str_replace('  ', ' ',$content);

		$content = static::unConvertEncoding($content);

		file_put_contents($this->getFile()->getPath(), $content);

		unset($content);
	}

	/**
	 * Тестирует файл импорта
	 *
	 * @return void
	 * @throws ArgumentNullException
	 */
	protected function test(): void
	{
		$needHeaders = static::getMapImportFile();

		if(count($needHeaders) !== count($this->header))
		{
			$this->addError(new Error('Error count columns', static::ErrorCodeCountColumns));
		}
	}
	
	/**
	 * Проходим по файлу и каждую строку складываем в таблицу импорта
	 * @return Result
	 * @throws ArgumentNullException
	 * @throws Exception
	 */
	protected function import(): Result
	{
		$result = new Result();
		
		$this->csv->SetPos();
		$row = $this->csv->Fetch();

		if(static::isUseHeader())
		{
			$row = $this->csv->Fetch();
		}
		
		$info = new SmartStd();
		$info->tmpId = static::getXmlIdIdempotence();
		$info->rowNum = 0;
		$info->fails = [];
		$info->cntHeaders = count($this->header);

		// Цикл с предусловием: в файле из одного заголовка строк нет. Было
		// do/while, и такой файл давал одну «строку» из false.
		while(is_array($row))
		{
			$info->rowNum++;

			$row = static::convertEncoding($row);

			// Короткая строка (меньше колонок, чем в заголовке) — пустые
			// значения, а не warning на каждую недостающую колонку.
			$this->content = [];
			for ($j = 0; $j < $info->cntHeaders; $j++)
			{
				$this->content[$this->header[$j]] = $row[$j] ?? '';
			}
			
			$response = $this->processRow($info->tmpId.'.'.$info->rowNum);
			if(!$response->isSuccess())
			{
				$result->addErrors($response->getErrors());
				$this->logger->error($response);
				
				$this->content = [];
			}
			
			$row = $this->csv->Fetch();
		}

		$this->csv->CloseFile();
		
		$data = [
			'rows' => $info->rowNum,
			'errors' => $result->getErrorCollection()->count()
		];
		$this->logger->info(('Import Statistic'), $data);
		
		return $result->setData($data);
	}
}