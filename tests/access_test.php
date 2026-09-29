<?php declare(strict_types=1);

/**
 * Кто вправе управлять импортом — и что проверяют ajax-действия.
 *
 * До 2.0.0 все действия модуля стояли на Actions\Normal — умолчаниях ядра:
 * вход на портал и csrf. Вошедший сотрудник включал и выключал ЛЮБОЙ агент
 * портала по ID (контроллер страницы настроек, компонент статистики),
 * загружал файлы в импорт, открывал страницы импорта в левом меню.
 *
 * Держится:
 *
 * 1. Access::canManage(): администратор — да; право «W» и выше на модуль —
 *    да; «R» и гость — нет;
 * 2. контроллер агентов проверяет права В ДЕЙСТВИИ и на модуль агента из
 *    b_agent, а не из запроса: свой модуль не открывает чужие агенты, а
 *    право на модуль ядра — его штатные агенты: трогать можно только агенты
 *    импорта (наследники AAgent);
 * 3. контроллер импорта со страницы настроек не пускает в действие без прав;
 * 4. левое меню показывает страницы модуля только тем, кому они разрешены.
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/autoload.php';
require_once $root.'/tests/stub/agent.php';
require_once $root.'/tests/assert.php';

use Bitrix\Main\Engine\CurrentUser;
use Shef\InSync\Main\Access;

$as = static function(null|int $id, bool $isAdmin = false, array $rights = []): void
{
	CurrentUser::$id = $id;
	CurrentUser::$isAdmin = $isAdmin;
	CMain::$rights = $rights;
};

Check::group('Access::canManage()');

$as(1, true);
Check::same('администратор', Access::canManage('shef.demo'), true);

$as(5, false, ['shef.demo' => 'W']);
Check::same('право W на модуль', Access::canManage('shef.demo'), true);
Check::same('…но не на соседний', Access::canManage('shef.other'), false);

$as(5, false, ['shef.insync' => 'X']);
Check::same('X — выше W', Access::canManage(), true);

$as(5, false, ['shef.insync' => 'R']);
Check::same('только чтение', Access::canManage(), false);

$as(null, false, ['shef.insync' => 'W']);
Check::same('гость — нет, какие бы права ни были у группы', Access::canManage(), false);

$as(5, false, ['' => 'W']);
Check::same('пустой модуль — нет', Access::canManage(''), false);

Check::group('контроллер агентов на странице настроек');

// Агент импорта — наследник AAgent, как у модуля на shef.insync.
eval('namespace Shef\\Demo;
final class ImportAgent extends \\Shef\\InSync\\Agents\\AAgent
{
	public static function buildAgentsEntity(): \\Shef\\InSync\\Agents\\Entity { throw new \\LogicException("не должен вызываться"); }
	public function action(): \\Bitrix\\Main\\Result { throw new \\LogicException("не должен вызываться"); }
}');

// Агенты ядра — существующие классы, не наследники AAgent: без них тест
// зеленел бы и на проверке «класс есть», а на портале классы ядра есть.
eval('class CEvent { public static function CheckEvents(): string { return ""; } }');
eval('namespace Bitrix\\Sale; class Recurring { public static function doRecurring(): string { return ""; } }');

CAgent::$agents = [
	7 => ['ID' => '7', 'MODULE_ID' => 'shef.demo', 'NAME' => '\\Shef\\Demo\\ImportAgent::process();', 'ACTIVE' => 'N'],
	8 => ['ID' => '8', 'MODULE_ID' => 'main', 'NAME' => 'CEvent::CheckEvents();', 'ACTIVE' => 'Y'],
	9 => ['ID' => '9', 'MODULE_ID' => 'sale', 'NAME' => '\\Bitrix\\Sale\\Recurring::doRecurring();', 'ACTIVE' => 'Y'],
];

$controller = new \Shef\InSync\Main\Options\Agent\Controller();
$codes = static function() use (&$controller): array
{
	return array_map(static fn($error): string => (string)$error->getCode(), $controller->getErrors());
};

$as(5);
Check::same('без прав — действия нет', $controller->run('startAgent', [7, 'shef.demo']), null);
Check::same('…и агент не тронут', CAgent::$agents[7]['ACTIVE'], 'N');
Check::same('…ошибка — ACCESS_DENIED', $codes(), ['ACCESS_DENIED']);

$controller = new \Shef\InSync\Main\Options\Agent\Controller();
$as(5, false, ['shef.demo' => 'W']);
Check::same('права на свой модуль не открывают чужой агент', $controller->run('stopAgent', [8, 'shef.demo']), null);
Check::same('…агент ядра работает дальше', CAgent::$agents[8]['ACTIVE'], 'Y');
Check::same('…ошибка — агента «нет»', $codes(), ['AGENT_NOT_FOUND']);

$controller = new \Shef\InSync\Main\Options\Agent\Controller();
$as(5, false, ['sale' => 'W']);
Check::same('право на модуль ядра не открывает его агенты', $controller->run('stopAgent', [9, 'sale']), null);
Check::same('…агент sale работает дальше', CAgent::$agents[9]['ACTIVE'], 'Y');
Check::same('…ошибка — агента «нет»', $codes(), ['AGENT_NOT_FOUND']);

$controller = new \Shef\InSync\Main\Options\Agent\Controller();
$as(1, true);
Check::same('…и администратору здесь тоже нет: это страница импорта', $controller->run('stopAgent', [8, 'main']), null);
Check::same('…агент ядра работает дальше', CAgent::$agents[8]['ACTIVE'], 'Y');

$controller = new \Shef\InSync\Main\Options\Agent\Controller();
$as(5, false, ['shef.demo' => 'W']);
$redirect = $controller->run('startAgent', [7, 'shef.demo']);
Check::same('с правами на модуль агента — включён', CAgent::$agents[7]['ACTIVE'], 'Y');
Check::same('…и обратно на страницу настроек модуля', $redirect?->url?->params['mid'] ?? null, 'shef.demo');

Check::group('компонент статистики: агенты и очистка таблицы');

if(!defined('B_PROLOG_INCLUDED'))
{
	define('B_PROLOG_INCLUDED', true);
}
require_once $root.'/install/components/shef.insync/import.stat.local/class.php';

$stat = static fn() => new \Local\Component\Shef\InSync\ShefInSyncImportStatLocalComponent();
$statCodes = static fn(object $component): array => array_map(static fn($error): string => (string)$error->getCode(), $component->getErrors());

CAgent::$agents[7]['ACTIVE'] = 'N';

$as(5, false, ['shef.demo' => 'W']);
$component = $stat();
Check::same('без прав на shef.insync — действия нет', $component->startAgentAction(7), null);
Check::same('…агент не тронут', CAgent::$agents[7]['ACTIVE'], 'N');
Check::same('…ошибка — ACCESS_DENIED', $statCodes($component), ['ACCESS_DENIED']);

$as(5, false, ['shef.insync' => 'W']);
$component = $stat();
Check::same('права на shef.insync не открывают агент модуля импорта', $component->startAgentAction(7), null);
Check::same('…агент не тронут', CAgent::$agents[7]['ACTIVE'], 'N');

$as(5, false, ['shef.insync' => 'W', 'main' => 'W', 'sale' => 'W']);
$component = $stat();
Check::same('агент ядра — «не найден»', $component->stopAgentAction(8), null);
Check::same('…работает дальше', CAgent::$agents[8]['ACTIVE'], 'Y');
Check::same('…ошибка — агента «нет»', $statCodes($component), ['AGENT_NOT_FOUND']);

$as(5, false, ['shef.insync' => 'W', 'shef.demo' => 'W']);
$component = $stat();
Check::same('права на оба модуля — включён', $component->startAgentAction(7), []);
Check::same('…агент включён', CAgent::$agents[7]['ACTIVE'], 'Y');

\Bitrix\Main\DB\Connection::$queries = [];
$component = $stat();
Check::same('очистка без кода импорта — отказ', $component->clearRowAction(['dateInsertTs' => 1]), null);
Check::same('…и ни одного запроса: иначе стёрлись бы строки всех импортов', \Bitrix\Main\DB\Connection::$queries, []);

$component = $stat();
Check::same('без даты загрузки — отказ', $component->clearRowAction(['originatorId' => 'DemoPrice']), null);
Check::same('…и ни одного запроса', \Bitrix\Main\DB\Connection::$queries, []);

$as(5, false, ['shef.demo' => 'W']);
$component = $stat();
Check::same('очистка без прав на shef.insync — отказ', $component->clearRowAction(['originatorId' => 'DemoPrice', 'dateInsertTs' => 1]), null);
Check::same('…и ни одного запроса', \Bitrix\Main\DB\Connection::$queries, []);

$as(5, false, ['shef.insync' => 'W']);
$component = $stat();
$component->clearRowAction(['originatorId' => 'DemoPrice', 'dateInsertTs' => 1]);
Check::same('с правами и кодом — удаляются строки одного импорта', count(\Bitrix\Main\DB\Connection::$queries) === 1 && str_contains(\Bitrix\Main\DB\Connection::$queries[0], "'DemoPrice'"), true);

Check::group('ссылки на действие несут sessid');

// Действие по ссылке — GET, csrf ядро проверяет и для него: без sessid
// кнопка на странице настроек не работала бы вовсе.
$source = (string)file_get_contents($root.'/lib/main/options/agent/option.php');
Check::same('startAgent и stopAgent — с sessid', substr_count($source, "'sessid' => bitrix_sessid()"), 2);

Check::group('контроллер импорта со страницы настроек');

// Модуль импорта — из namespace контроллера: Shef\Demo\... -> shef.demo.
eval('namespace Shef\\Demo\\Import;
class Controller extends \\Shef\\InSync\\Main\\Options\\Import\\FromFile\\AController
{
	public bool $called = false;
	protected static function getProcessor(): \\Shef\\InSync\\Sync\\FromFile\\AFileProcess { throw new \\LogicException("не должен вызываться"); }
	protected static function getAgentProcess(): \\Shef\\InSync\\Agents\\AAgent { throw new \\LogicException("не должен вызываться"); }
	public function cancelAction(): null|\\Shef\\InSync\\Main\\Options\\Import\\FromFile\\Response { $this->called = true; return null; }
}');

$as(5, false, ['shef.insync' => 'W']);
$import = new \Shef\Demo\Import\Controller();
$import->run('cancel', []);
Check::same('права на shef.insync не открывают чужой импорт', $import->called, false);

$as(5, false, ['shef.demo' => 'W']);
$import = new \Shef\Demo\Import\Controller();
$import->run('cancel', []);
Check::same('права на модуль импорта — действие выполнено', $import->called, true);

Check::group('левое меню');

$provider = new \Shef\InSync\Integration\Intranet\CustomSectionProvider();

$as(5, false, []);
Check::same('статистика — не всем', $provider->isAvailable('shef.insync:import.stat.local', 5), false);
Check::same('загрузка файла — не всем', $provider->isAvailable('shef.insync:import.from.file~\\Shef\\Demo\\File~shef.demo', 5), false);
Check::same('чужая страница в разделе отвечает за себя', $provider->isAvailable('shef.demo:list', 5), true);

$as(5, false, ['shef.demo' => 'W']);
Check::same('загрузка файла — с правами на модуль импорта', $provider->isAvailable('shef.insync:import.from.file~\\Shef\\Demo\\File~shef.demo', 5), true);
Check::same('статистика — нет, это права на shef.insync', $provider->isAvailable('shef.insync:import.stat.local', 5), false);

Check::same('настройки без параметров — без warning', $provider->isAvailable('', 5), true);

unset($GLOBALS['SH_INSYNC_TEST_REDIRECT']);
$provider->resolveComponent('shef.insync:redirect~https://evil.example/', new \Bitrix\Main\Web\Uri('/page/'));
Check::same('переход наружу не выполняется', $GLOBALS['SH_INSYNC_TEST_REDIRECT'] ?? null, null);
$provider->resolveComponent('shef.insync:redirect~//evil.example/', new \Bitrix\Main\Web\Uri('/page/'));
Check::same('и без схемы тоже', $GLOBALS['SH_INSYNC_TEST_REDIRECT'] ?? null, null);
$provider->resolveComponent('shef.insync:redirect~/\\evil.example/', new \Bitrix\Main\Web\Uri('/page/'));
Check::same('и «/\\» — браузер читает его как «//»', $GLOBALS['SH_INSYNC_TEST_REDIRECT'] ?? null, null);
$provider->resolveComponent("shef.insync:redirect~/\t/evil.example/", new \Bitrix\Main\Web\Uri('/page/'));
Check::same('и «/<tab>/» — браузер вырезает табуляцию', $GLOBALS['SH_INSYNC_TEST_REDIRECT'] ?? null, null);
$provider->resolveComponent('shef.insync:redirect~/bitrix/admin/perfmon_table.php', new \Bitrix\Main\Web\Uri('/page/'));
Check::same('внутри портала — да', $GLOBALS['SH_INSYNC_TEST_REDIRECT'] ?? null, '/bitrix/admin/perfmon_table.php');

Check::finish();
