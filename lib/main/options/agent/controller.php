<?php declare(strict_types=1);

namespace Shef\InSync\Main\Options\Agent;

use Bitrix\Main\Engine;
use Bitrix\Main\Error;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Web\Uri;
use Shef\Options\Components\Actions;
use Shef\Options\TraitList;
use Shef\InSync\Agents\Manager;
use Shef\InSync\Main\Access;

/**
 * Ajax контроллер для запуска агентов из страницы настроек модуля
 * @see Option
 */
class Controller
	extends Engine\Controller
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

	/**
	 * Включает агент и возвращает на страницу настроек его модуля.
	 *
	 * Права проверяются здесь, в действии: кнопку видит только тот, кому
	 * открыта страница настроек, но адрес действия вызывается и напрямую.
	 * До 2.0.0 любой вошедший на портал включал и выключал любой агент по ID.
	 */
	public function startAgentAction(int $agentId, string $moduleId): null|Engine\Response\Redirect
	{
		if(!$this->checkAgent($agentId, $moduleId))
		{
			return null;
		}

		return $this->redirectBack($moduleId, Manager::startById($agentId), 'start_done');
	}

	/**
	 * Выключает агент и возвращает на страницу настроек его модуля.
	 *
	 * @see startAgentAction()
	 */
	public function stopAgentAction(int $agentId, string $moduleId): null|Engine\Response\Redirect
	{
		if(!$this->checkAgent($agentId, $moduleId))
		{
			return null;
		}

		return $this->redirectBack($moduleId, Manager::stopById($agentId), 'stop_done');
	}

	/**
	 * Агент существует, это агент импорта (наследник AAgent), принадлежит
	 * названному модулю, и у пользователя есть права на этот модуль.
	 *
	 * Модуль из запроса сверяется с модулем агента в b_agent: иначе права на
	 * свой модуль открывали бы чужие агенты.
	 */
	protected function checkAgent(int $agentId, string $moduleId): bool
	{
		$agentModuleId = Manager::getImportAgentModuleId($agentId);

		if(null === $agentModuleId || $agentModuleId !== $moduleId)
		{
			$this->addError(new Error('Agent not found', 'AGENT_NOT_FOUND'));
			return false;
		}

		if(!Access::canManage($agentModuleId))
		{
			$this->addError(new Error('Access denied', 'ACCESS_DENIED'));
			return false;
		}

		return true;
	}

	protected function redirectBack(string $moduleId, \Bitrix\Main\Result $response, string $message): Engine\Response\Redirect
	{
		$isError = !$response->isSuccess();

		$url = new Uri('/bitrix/admin/settings.php');
		$url->addParams([
			'lang' => LANGUAGE_ID,
			'mid' => $moduleId,
			'isError' => $isError ? 'Y' : 'N',
			'msg' => $isError ? implode(';', $response->getErrorMessages()) : $message,
		]);

		return new Engine\Response\Redirect($url);
	}
}
