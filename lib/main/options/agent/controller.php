<?php declare(strict_types=1);

namespace Shef\Insync\Main\Options\Agent;

use Bitrix\Main\LoaderException;
use Bitrix\Main\Web\Uri;
use Shef\Options\Components\Actions;
use Shef\Options\TraitList;

/**
 * Ajax контроллер для запуска агентов из страницы настроек модуля
 * @see Option
 */
class Controller
	extends \Bitrix\Main\Engine\Controller
{
	use TraitList\Modules;
	
	protected static function getModulesList(): array
	{
		return [];
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
	}
	
	public function configureActions(): array
	{
		return [
			'startAgent' => Actions\Normal::get(),
			'stopAgent' => Actions\Normal::get(),
		];
	}
	
	public function startAgentAction(int $agentId, string $moduleId): ?\Bitrix\Main\Engine\Response\Redirect
	{
		$response = \Shef\InSync\Agents\Manager::startById($agentId);
		$isError = false;
		$message = 'start_done';
		if(!$response->isSuccess())
		{
			$isError = true;
			$message = implode(';', $response->getErrorMessages());
		}
		
		$url = new Uri('/bitrix/admin/settings.php');
		$url->addParams([
			'lang' => LANGUAGE_ID,
			'mid' => $moduleId,
			'isError' => $isError ? 'Y' : 'N',
			'msg' => $message,
		]);
		
		return new \Bitrix\Main\Engine\Response\Redirect($url);
	}
	
	public function stopAgentAction(int $agentId, string $moduleId): ?\Bitrix\Main\Engine\Response\Redirect
	{
		$response = \Shef\InSync\Agents\Manager::stopById($agentId);
		
		$isError = false;
		$message = 'stop_done';
		if(!$response->isSuccess())
		{
			$isError = true;
			$message = implode(';', $response->getErrorMessages());
		}
		
		
		$url = new Uri('/bitrix/admin/settings.php');
		$url->addParams([
			'lang' => LANGUAGE_ID,
			'mid' => $moduleId,
			'isError' => $isError ? 'Y' : 'N',
			'msg' => $message,
		]);
		
		return new \Bitrix\Main\Engine\Response\Redirect($url);
	}
}