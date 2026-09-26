<?php

/**
 * Заглушка классов shef.options 3.x, от которых наследуются классы модуля.
 *
 * Трейт или базовый класс нужен уже в момент объявления класса модуля, и
 * без него настоящий класс не подключить. Логика — только та, от которой
 * зависит проверяемое поведение (Modules::includeModules() зовёт
 * Loader::includeModule, как настоящий); остальное — пустые оболочки с
 * сигнатурами shef.options 3.x.
 *
 * Поменяется API в shef.options — заглушка обязана поменяться вместе с ним.
 */

namespace
{
	require_once __DIR__.'/bitrix.php';
}

namespace Shef\Options\TraitList
{
	trait Modules
	{
		abstract protected static function getModulesList(): array;

		protected static function includeModules(): \Bitrix\Main\Result
		{
			$result = new \Bitrix\Main\Result();

			foreach(static::getModulesList() as $module)
			{
				if(!\Bitrix\Main\Loader::includeModule($module))
				{
					return $result->addError(new \Bitrix\Main\Error('module '.$module.' not loaded'));
				}
			}

			return $result;
		}
	}
}

namespace Shef\Options\TraitList\Tools
{
	trait XmlId
	{
		protected static function getXmlIdIdempotence(): string
		{
			return 'xml-id-test';
		}
	}
}

namespace Shef\Options\Main
{
	if(!class_exists(Constants::class, false))
	{
		class Constants
		{
			public static function getSystemUserId(): int
			{
				return 1;
			}
		}
	}
}

namespace Shef\Options\Components\Actions
{
	class Normal
	{
		public static function get(): array
		{
			return [];
		}
	}

	class Free
	{
		public static function get(): array
		{
			return ['-prefilters' => ['Csrf', 'Authentication']];
		}
	}
}

namespace Shef\Options\Components
{
	/**
	 * Жизненный цикл AComponent/AControllerable из shef.options 3.x:
	 * initAjax() = initParams() + checkRequiredParams(), ошибки — в коллекцию.
	 */
	abstract class AControllerable
	{
		public array $arParams = [];
		public array $arResult = [];
		public object $request;

		/** @var \Bitrix\Main\Error[] */
		protected array $errors = [];

		abstract public function configureActions(): array;

		abstract protected function process(): void;

		protected function initParams(): void {}

		protected function checkRequiredParams(): void {}

		protected function initResult(): void {}

		protected function initAjax(): void
		{
			$this->initParams();
			$this->checkRequiredParams();
			if(!empty($this->errors))
			{
				return;
			}

			$this->initResult();
		}

		public function addError(\Bitrix\Main\Error $error): static
		{
			$this->errors[] = $error;
			return $this;
		}

		public function addErrors(array $errors): static
		{
			foreach($errors as $error)
			{
				$this->addError($error);
			}
			return $this;
		}

		public function getErrors(): array
		{
			return $this->errors;
		}

		public function getErrorCollection(): object
		{
			return new class($this->errors)
			{
				public function __construct(private readonly array $errors) {}

				public function isEmpty(): bool
				{
					return empty($this->errors);
				}
			};
		}
	}
}
