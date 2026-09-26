<?php declare(strict_types=1);

namespace Shef\InSync\Api;

use Throwable;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Web\HttpClient;
use Shef\Options\Options\SmartStd;
use Shef\Options\TraitList;
use Shef\Problems;
use Shef\Problems\Factory\Trait as ProblemsTraitList;

abstract class AConnector
{
	use TraitList\Tools\IsDebug;
	use TraitList\Tools\OptionCollection;
	use TraitList\Tools\Encoding;
	use TraitList\Tools\SelfClass;
	use ProblemsTraitList\LoggerProblems;
	use ProblemsTraitList\DebuggerProblems;
	
	public const ErrorCodeApiFailSend = 1;
	
	protected null|HttpClient $httpClient = null;
	
	// region ProblemsTraitList\LoggerProblems ////
	/**
	 * @inheritDoc
	 */
	protected static function getAuditType(): string
	{
		return \Shef\Problems\Main\Constants::AuditTypeSync;
	}
	// endregion ////
	/**
	 * @throws ArgumentNullException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public function __construct()
	{
		$this->initOptionCollection();
		
		$this->initHttp();
		$this->initLogger();
		$this->initDebugger();
	}
	
	/**
	 * @throws ArgumentNullException
	 */
	protected function initHttp(): void
	{
		$this->httpClient = new HttpClient([
			'version' => '1.1',
			'redirect' => true,
			'redirectMax' => 5,
		]);

		$this->reInitHttpParams();
	}
	
	/**
	 * Устанавливаем время ожидания ответов от удаленного сервера
	 *
	 * @return $this
	 * @throws ArgumentNullException
	 */
	public function reInitHttpParams(): self
	{
		$this->httpClient->setTimeout(
			$this->getOptionCollection()->get('socketTimeout') ?? 30
		);
		
		$this->httpClient->setStreamTimeout(
			$this->getOptionCollection()->get('streamTimeout') ?? 60
		);
		
		$this->httpClient->waitResponse(
			$this->getOptionCollection()->get('waitResponse') ?? 2
		);
		
		return $this;
	}
	
	// region Work /////
	
	/**
	 * Преобразует API.функцию в адрес
	 * @param string $functionName
	 *
	 * @return string
	 */
	abstract protected function getPath(string $functionName): string;
	
	/**
	 * Отправляет запрос
	 *
	 * @memo: поддерживаем GET|POST|PUT|HEAD|PATCH|DELETE|OPTIONS
	 * @param string $functionName
	 * @param array $params
	 * @param string $method
	 * @param array $headers
	 *
	 * @return Result
	 * @throws ArgumentNullException
	 *
	 * @memo 200 ответ сервера счиаем успешным ответом
	 * @memo иногда придется при наличии 200 ответа считать это ошибкой
	 * @memo [500, 504, 0] ответ сервера счиаем ошибкой 50x
	 * @see HttpClient
	 *
	 */
	final public function sendRequest(
		string $functionName,
		array $params,
		string $method = HttpClient::HTTP_POST,
		array $headers = []
	): Result
	{
		$result = new Result();
		
		$url = $this->getPath($functionName);
	  
		if($method === HttpClient::HTTP_GET)
		{
			$url = $url.'?'.http_build_query($params);
			$params = [];
		}

		foreach($headers as $name => $value)
		{
			$this->httpClient->setHeader($name, $value);
		}

		$this->reInitHttpParams();
		
		$info = new SmartStd();
		$info->send = new SmartStd();
		$info->send->method = $method;
		$info->send->url = $url;
		$info->send->params = $params;
		$info->send->headers = $headers;
		
		$info->data = null;
		
		try
		{
			$response = $this->httpClient->query(
				$method,
				$url,
				$params
			);
	
			if($response && $this->httpClient->getStatus() === 200)
			{
				$response = $this->processSuccess();
				if(!$response->isSuccess())
				{
					$result->addErrors($response->getErrors());
				}
				$info->data = $response->getData();
			}
			else
			{
				if(in_array($this->httpClient->getStatus(), [500, 504, 0]))
				{
					$response = $this->processError50x();
				}
				else
				{
					$response = $this->processError();
				}
				
				if(!$response->isSuccess())
				{
					$result->addErrors($response->getErrors());
				}
				$result->addError(new Error(implode(';', $this->httpClient->getError())));
			}
			
			
		}
		catch(Throwable $throwable)
		{
			$result->addError(Problems\Throwable\Manager::buildError(
				$throwable,
				true,
				static::ErrorCodeApiFailSend,
				$info->toArray()
			));
		}
		
		if(!$result->isSuccess())
		{
			$info->httpResponse = $this->httpClient->getResult();
			$info->errors = $result->getErrorMessages();
			
			$this->logger->error($info);

			if($this->isDebug())
			{
				$this->debugger->error($info);
			}
		}
		
		return $result->setData($info->toArray());
	}
	
	protected function getHttpClient(): null|HttpClient
	{
		return $this->httpClient;
	}
	
	/**
	 * Обработчка успешного ответа
	 *
	 * @memo Помним что 200 ответ сервера - это еще не успешный ответ бизнеслогики -> проверять нужно
	 *
	 * @memo Тут нужно менять кодировку, парсить результат и тп
	 *
	 * @return Result
	 */
	public function processSuccess(): Result
	{
		return (new Result())->setData(['response' => $this->httpClient->getResult()]);
	}
	
	/**
	 * Обработчка плохого ответа
	 *
	 * @memo Тут нужно менять кодировку, парсить результат и тп
	 *
	 * @return Result
	 */
	public function processError(): Result
	{
		return (new Result())
			->addError(new Error(
				'status: '.$this->httpClient->getStatus(),
				'HTTP_'.$this->httpClient->getStatus()
			));
	}
	
	/**
	 * Обработчка 50x ответа
	 *
	 * Используется для отдельной обработки проблем соединений с удаленным сервером
	 *
	 * @memo Тут нужно менять кодировку, парсить результат и тп
	 *
	 * @return Result
	 */
	public function processError50x(): Result
	{
		return $this->processError();
	}
	// endregion /////
}