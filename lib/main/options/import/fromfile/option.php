<?php declare(strict_types=1);

namespace Shef\InSync\Main\Options\Import\FromFile;

use Bitrix\Main\Engine\Resolver;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Type\Dictionary;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web;
use Shef\Options\Main\Options as ShefOptions;
use Shef\InSync\Main\Options\Import\FromFile\Model\OptionField\ABase;

/**
 * Для вывода опции импорта файла на страницы параметров модуля
 *
 * @link https://dev.1c-bitrix.ru/api_d7/bitrix/ui/stepprocessing/index.php Bitrix.Api.Диалог для пошагового процесса
 */
class Option
	extends ShefOptions\RowInfo
{
	private string $controller;
	
	private Dictionary $messages;
	private Dictionary $queue;
	private Dictionary $params;
	private Dictionary $optionsFields;
	private Dictionary $eventHandlers;
	
	private bool $isShowButtonStart = true;
	private bool $isShowButtonStop = true;
	private bool $isShowButtonClose = true;
	
	/**
	 * @throws LoaderException
	 */
	public function __construct(string $code)
	{
		parent::__construct($code);
		
		$this->setType(ShefOptions\TypeUIAlert::Warning);
		
		Extension::load([
			'ui.buttons',
			'ui.buttons.icons',
			'ui.stepprocessing',
		]);
		
		$this->messages = new Dictionary();
		$this->queue = new Dictionary();
		$this->params = new Dictionary();
		$this->optionsFields = new Dictionary();
		$this->eventHandlers = new Dictionary();
	}
	
	// region Get/Set ////
	// region Controller ////
	public function setController(Controller $controller): static
	{
		$this->controller = Resolver::getNameByController($controller);
		if(!$controller->isLocatedUnderPsr4())
		{
			$this->controller = mb_strtolower($this->controller);
		}
		
		return $this;
	}
	public function setControllerString(string $controllerName): static
	{
		$this->controller = $controllerName;
		
		return $this;
	}
	
	public function getController(): string
	{
		return $this->controller;
	}
	// endregion ////
	
	// region Messages ////
	/**
	 * Сообщение
	 * Для всех сообщений уже имеются фразы по-умолчанию.
	 * Переопределение фразы необходимо для кастомизации под конкретную задачу.
	 *
	 * @param Enums\EMessagesCode $code
	 * @param string $message
	 *
	 * @return $this
	 */
	public function addMessage(Enums\EMessagesCode $code, string $message): static
	{
		$this->messages->set($code->name, $message);
		return $this;
	}
	
	/**
	 * @return Dictionary
	 */
	public function getMessages(): Dictionary
	{
		return $this->messages;
	}
	// endregion ////
	
	// region Queue ////
	/**
	 * Очередь заданий
	 * @param Model\QueueItem $queueItem
	 *
	 * @return $this
	 */
	public function addQueueItem(Model\QueueItem $queueItem): static
	{
		$this->queue->set($queueItem->action, $queueItem);
		return $this;
	}
	
	/**
	 * @return Dictionary
	 */
	public function getQueue(): Dictionary
	{
		return $this->queue;
	}
	
	public function getQueueList(): array
	{
		return array_values(array_map(
			function(Model\QueueItem $queueItem)
			{
				return $queueItem->toArray();
			},
			$this->getQueue()->toArray()
		));
	}
	// endregion ////
	
	// region Params ////
	/**
	 * Параметр, добавляемый в запрос на всех хитах к контроллеру
	 * @param string $code
	 * @param mixed $value
	 * @return $this
	 */
	public function addParam(string $code, mixed $value): static
	{
		$this->params->set($code, $value);
		return $this;
	}
	
	/**
	 * @return Dictionary
	 */
	public function getParams(): Dictionary
	{
		return $this->params;
	}
	// endregion ////
	
	// region OptionsFields ////
	/**
	 * Поле, выводимое на стартовом диалоге
	 * @param ABase $optionsField
	 * @return $this
	 */
	public function addOptionsFields(Model\OptionField\ABase $optionsField): static
	{
		$this->optionsFields->set($optionsField->name, $optionsField);
		return $this;
	}
	
	/**
	 * @return Dictionary
	 */
	public function getOptionsFields(): Dictionary
	{
		return $this->optionsFields;
	}
	
	public function getOptionsFieldsList(): array
	{
		return array_map(
			function(Model\OptionField\ABase $optionsField)
			{
				return $optionsField->toArray();
			},
			$this->getOptionsFields()->toArray()
		);
	}
	// endregion ////
	
	// region ShowButtons ////
	/**
	 * @param bool $isShowButtonStart
	 * @return $this
	 */
	public function setIsShowButtonStart(bool $isShowButtonStart): static
	{
		$this->isShowButtonStart = $isShowButtonStart;
		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function isShowButtonStart(): bool
	{
		return $this->isShowButtonStart;
	}
	
	/**
	 * @param bool $isShowButtonStop
	 * @return $this
	 */
	public function setIsShowButtonStop(bool $isShowButtonStop): static
	{
		$this->isShowButtonStop = $isShowButtonStop;
		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function isShowButtonStop(): bool
	{
		return $this->isShowButtonStop;
	}
	
	/**
	 * @param bool $isShowButtonClose
	 * @return $this
	 */
	public function setIsShowButtonClose(bool $isShowButtonClose): static
	{
		$this->isShowButtonClose = $isShowButtonClose;
		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function isShowButtonClose(): bool
	{
		return $this->isShowButtonClose;
	}
	// endregion ////
	
	// region EventHandlers ////
	/**
	 * js-function обработчик на события
	 * @param Enums\EEventHandlersType $type
	 * @param string $eventHandler
	 *
	 * @return $this
	 */
	public function addEventHandler(Enums\EEventHandlersType $type, string $eventHandler): static
	{
		$this->eventHandlers->set(
			$type->name,
			[
				'event' => $type->name,
				'code' => $eventHandler
			]
		);
		return $this;
	}
	
	/**
	 * @return Dictionary
	 */
	public function getEventHandlers(): Dictionary
	{
		return $this->eventHandlers;
	}
	
	public function getEventHandlersCodeList(): array
	{
		return array_map(
			function(array $functionMap)
			{
				return sprintf(
					'.setHandler(
						BX.UI.StepProcessing.ProcessCallback.%s,
						%s
					)',
					$functionMap['event'],
					$functionMap['code']
				);
			},
			$this->getEventHandlers()->toArray()
		);
	}
	// endregion ////
	// endregion ////
	
	// region Render /////
	public function getJsConfig(): array
	{
		return [
			'id' => $this->getCode(),
			'controller' => $this->getController(),
			'messages' => $this->getMessages()->toArray(),
			'queue' => $this->getQueueList(),
			'params' => $this->getParams()->toArray(),
			'optionsFields' => $this->getOptionsFieldsList(),
			'showButtons' => [
				'start' => $this->isShowButtonStart(),
				'stop' => $this->isShowButtonStop(),
				'close' => $this->isShowButtonClose(),
			]
		];
	}
	
	protected function renderValue(string $moduleId): string
	{
		$confJs = $this->getJsConfig();
		$action = sprintf(
			'BX.UI.StepProcessing.ProcessManager.get(\'%s\').showDialog()',
			$confJs['id']
		);
		
		/*/
		_pr([
			$confJs
		]);
		//*/
		
		ob_start();?>
		<script>
			BX.ready(() => {
				BX.UI.StepProcessing.ProcessManager.create(<?=Web\Json::encode($confJs)?>)
				<?=join(PHP_EOL, $this->getEventHandlersCodeList());?>
				;
			});
		</script>
		<?php
		$js = ob_get_contents();
		ob_end_clean();
		
		return sprintf(
			join('', [
				'<div class="ui-alert %s"><span class="ui-alert-message">',
				'%s',
				'&nbsp;<button type="button" onclick="%s" class="ui-btn ui-btn-xs ui-btn-success ui-btn-icon-start"></button>',
				'</span></div>',
				'%s',
			]),
			$this->getType(),
			$this->getDescription(),
			$action,
			$js
		);
	}
	// endregion ////
}