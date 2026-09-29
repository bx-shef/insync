<?php declare(strict_types=1);

namespace Shef\InSync\Sync\FromFile;

use Generator;
use Throwable;
use XMLReader;
use Bitrix\Main\Errorable;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Result;
use SbWereWolf\XmlNavigator;
use Shef\Options\Options\SmartStd;
use Shef\Problems;
use Shef\InSync\Sync;
use Shef\InSync\Main\Utils;

/**
 * Class AXmlProcess
 *
 * Абстракция для Импорта XML
 *
 * Нужно передать на вход файл для импорта
 * @memo Используем AFileProcess::getExistFiles() для получения списка файлов для импорта
 *
 * В Результате файл распарсится, записи попадут в таблицу импорта, файл уйдет в архив
 *
 * @memo Переопределяем Sync\AProcess::OriginatorId
 * @memo Переопределяем getEncodingFrom() тут указываем кодировку файла
 *
 * @memo Переопределяем Sync\IProcess::init() для инициализации строки файла в элемент для записи в таблицу импорта
 *
 */
abstract class AXmlProcess
	extends AFileProcess
	implements IFromXmlFile, IFromFile, Sync\IProcess, Errorable
{
	protected const OriginatorId = 'demoXml';
	protected null|bool|XMLReader $xml = null;
	
	// region Process ////
	
	/**
	 * @inheritDoc
	 */
	abstract public static function getItemTag(): string;
	
	/**
	 * @inheritDoc
	 * @throws ArgumentNullException
	 */
	protected function parseFile(): void
	{
		$this->clearContent();
		try
		{
			if(!Utils::checkExistXMLReader())
			{
				throw new InvalidOperationException('Not exist XmlReader');
			}
			
			$this->xml = XMLReader::open(
				$this->getFile()->getPath(),
				static::getEncodingFrom()
			);
			
			if(!($this->xml instanceof XMLReader))
			{
				throw new InvalidOperationException('Not open file for XmlReader');
			}
		}
		catch(Throwable $throwable)
		{
			$this->addError(Problems\Throwable\Manager::buildError(
				$throwable,
				false,
				static::ErrorCodeFileFailParse
			));
			return;
		}
	}

	/**
	 * Проходим по файлу и каждую строку складываем в таблицу импорта
	 * @return Result
	 * @throws ArgumentNullException
	 */
	protected function import(): Result
	{
		$result = new Result();
		
		$info = new SmartStd();
		$info->tmpId = static::getXmlIdIdempotence();
		$info->rowNum = 0;
		$info->fails = [];
		
		foreach($this->getList() as &$row)
		{
			$info->rowNum++;
			
			$this->content = $row;
			
			$response = $this->processRow($info->tmpId.'.'.$info->rowNum);
			if(!$response->isSuccess())
			{
				$result->addErrors($response->getErrors());
				$this->logger->error($response);
				
				$this->content = [];
			}
		}
		if(isset($row)){ unset($row); }
		$this->xml->close();
		
		$data = [
			'rows' => $info->rowNum,
			'errors' => $result->getErrorCollection()->count()
		];
		$this->logger->info(('Import Statistic'), $data);
		
		return $result->setData($data);
	}
	
	/**
	 * Получим список данных из структуры
	 * @return array|Generator
	 */
	protected function & getList(): array|Generator
	{
		$mayRead = true;
		while(
			$mayRead
			&& $this->xml->name !== static::getItemTag()
		)
		{
			$mayRead = $this->xml->read();
		}
		
		while(
			$mayRead
			&& $this->xml->name === static::getItemTag()
		)
		{
			$row = XmlNavigator\Extraction\HierarchyComposer::compose($this->xml);
			
			yield $row;
			
			while (
				$mayRead &&
				$this->xml->nodeType !== XMLReader::ELEMENT
			)
			{
				$mayRead = $this->xml->read();
			}
		}
	}
	// endregion /////
}