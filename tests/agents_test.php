<?php declare(strict_types=1);

/**
 * Строка агента: как она пишется в b_agent и как читается обратно.
 *
 * Ядро исполняет строку агента как PHP-код. До 2.0.0 параметры вставлялись
 * в неё как есть — кавычка в значении ломала агент, а значение снаружи
 * становилось кодом. Обратный разбор (Manager::findAll) резал строку по
 * «([» и «,» и давал warning на агенте без параметров.
 *
 * Держится:
 *
 * 1. для обычных значений строка та же, что писала 1.x, — агенты, уже
 *    лежащие в b_agent, находятся по имени;
 * 2. кавычки и обратные слэши экранированы, и строка — корректный PHP;
 * 3. parseName() — обратная функция к prepareNameForDb(), в том числе для
 *    строк 1.x и агентов без параметров, и без warning;
 * 4. не скаляр в параметрах — исключение, а не «Array» в строке агента.
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/autoload.php';
require_once $root.'/tests/assert.php';

use Shef\InSync\Agents\Entity;
use Shef\InSync\Agents\Manager;

$entity = static fn(array $params): Entity => new Entity(
	module: 'shef.demo',
	name: '\\Shef\\Demo\\Agent::process',
	params: $params,
	userId: 1
);

Check::group('строка агента — та же, что в 1.x, для обычных значений');

Check::same(
	'два параметра',
	$entity(['code' => 'price', 'limit' => '10'])->prepareNameForDb(),
	"\\Shef\\Demo\\Agent::process(['code'=>'price','limit'=>'10']);"
);
Check::same('без параметров', $entity([])->prepareNameForDb(), '\\Shef\\Demo\\Agent::process([]);');
Check::same('число и логическое — строкой, как раньше', $entity(['n' => 5, 'y' => true])->prepareNameForDb(), "\\Shef\\Demo\\Agent::process(['n'=>'5','y'=>'1']);");

Check::group('кавычки не превращают значение в код');

$evil = "x'];echo 'owned';//";
$name = $entity(['code' => $evil])->prepareNameForDb();

Check::same('строка агента — корректный PHP', (static function(string $code): bool
{
	try
	{
		token_get_all('<?php '.$code, TOKEN_PARSE);
		return true;
	}
	catch(ParseError $error)
	{
		return false;
	}
})($name), true);

Check::same('значение целиком внутри одного строкового литерала', str_contains($name, "echo 'owned'"), false);
Check::same('и возвращается разбором как было', Manager::parseName($name)[1], ['code' => $evil]);
Check::same('обратный слэш тоже', Manager::parseName($entity(['path' => 'C:\\tmp\\'])->prepareNameForDb())[1], ['path' => 'C:\\tmp\\']);

Check::throws('массив в параметрах', \Bitrix\Main\ArgumentException::class, static fn() => $entity(['list' => [1, 2]])->prepareNameForDb());

Check::group('разбор строки агента');

Check::same(
	'строка 1.x',
	Manager::parseName("\\Shef\\Demo\\Agent::process(['code'=>'price','limit'=>'10']);"),
	['\\Shef\\Demo\\Agent::process', ['code' => 'price', 'limit' => '10']]
);
Check::same(
	'без параметров — пустой массив, без warning',
	Manager::parseName('\\Shef\\Demo\\Agent::process();'),
	['\\Shef\\Demo\\Agent::process', []]
);
Check::same('без скобок вовсе', Manager::parseName('CEvent::CheckEvents;'), ['CEvent::CheckEvents', []]);
Check::same(
	'запятая и «=>» внутри значения не рвут параметр',
	Manager::parseName("A::b(['q'=>'a,b=>c']);")[1],
	['q' => 'a,b=>c']
);
Check::same('параметры без ключей', Manager::parseName("A::b(['x', 'y']);")[1], ['x', 'y']);
Check::same('array() и двойные кавычки', Manager::parseName('A::b(array("k" => "v\\n"));')[1], ['k' => "v\n"]);
Check::same('вложенный массив пропускается', Manager::parseName("A::b(['a'=>['x'],'b'=>'1']);")[1], ['b' => '1']);

$roundTrip = ['code' => 'demo', 'debug' => 'N', 'quote' => "it's", 'slash' => '\\'];
Check::same('разбор — обратная функция к записи', Manager::parseName($entity($roundTrip)->prepareNameForDb())[1], $roundTrip);

Check::group('поиск агентов модуля');

CAgent::$agents = [
	7 => ['ID' => '7', 'MODULE_ID' => 'shef.demo', 'NAME' => "\\Shef\\Demo\\Agent::process(['code'=>'price']);", 'ACTIVE' => 'Y', 'IS_PERIOD' => 'N', 'AGENT_INTERVAL' => '600', 'SORT' => '100', 'USER_ID' => '1', 'LAST_EXEC' => '', 'NEXT_EXEC' => '01.01.2027 10:00:00'],
	8 => ['ID' => '8', 'MODULE_ID' => 'shef.demo', 'NAME' => '\\Shef\\Demo\\Agent::process();', 'ACTIVE' => 'N'],
	9 => ['ID' => '9', 'MODULE_ID' => 'shef.other', 'NAME' => '\\Shef\\Demo\\Agent::process();', 'ACTIVE' => 'Y'],
];

$found = Manager::findAll($entity([]));

Check::same('нашлись агенты только своего модуля', array_map(static fn(Entity $agent): int => $agent->getId(), $found), [7, 8]);
Check::same('параметры восстановлены', $found[0]->getParams()->toArray(), ['code' => 'price']);
Check::same('пустая дата — null, а не исключение', $found[0]->getLastExec(), null);
Check::same('агент без полей в строке — без warning, выключен', $found[1]->isActive(), false);

Check::group('чей агент');

Check::same('модуль агента по ID', Manager::getModuleIdById(9), 'shef.other');
Check::same('несуществующий агент — null', Manager::getModuleIdById(100), null);
Check::same('ID меньше единицы — null без запроса', Manager::getModuleIdById(0), null);

Check::finish();
