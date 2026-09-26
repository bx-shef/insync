<?php declare(strict_types=1);

/**
 * Агент импорта: сущность, строка для b_agent и разбор обратно.
 *
 * ЦЕЛЬ
 *   Показать, как Agents\Entity описывает агент и какой строкой он ляжет в
 *   b_agent. Ядро исполняет эту строку как PHP-код, поэтому параметры
 *   экранируются, а Manager::parseName() разбирает строку обратно — так
 *   Manager::findAll() восстанавливает параметры запущенных агентов для
 *   страницы статистики.
 *
 * ГДЕ ПРИМЕНЯТЬ
 *   В buildAgentsEntity() своего агента (наследник Agents\AAgent или
 *   Sync\FromFile\AAgent) и при установке агента из установщика модуля:
 *   Manager::install($entity). Параметры — только строки и числа.
 *
 * ЧТО ДОЛЖНО ПОЛУЧИТЬСЯ
 *   Все строки «ok», последняя — «ГОТОВО: agent», код возврата 0.
 *   По сути:
 *     * строка агента — «Класс::метод(['ключ'=>'значение']);», как в 1.x;
 *     * кавычка в значении не ломает строку и не становится кодом;
 *     * разбор строки возвращает те же параметры.
 *
 * ЗАПУСК
 *   php examples/agent.php
 *   DOCUMENT_ROOT=/var/www/portal php examples/agent.php
 *
 * НА ПОРТАЛЕ ОТЛИЧАЕТСЯ
 *   Сущность при создании ищет агент с такой строкой в b_agent (только
 *   чтение): агента \Shef\Demo\Import\Agent на портале нет, ID будет 0.
 *   Пример ничего не устанавливает и не меняет.
 */

require_once __DIR__.'/_bootstrap.php';

title('Агент импорта: строка для b_agent');

use Shef\InSync\Agents\Entity;
use Shef\InSync\Agents\Manager;

step('Сущность агента');

$entity = new Entity(
	module: 'shef.demo',
	name: '\\Shef\\Demo\\Import\\Agent::process',
	params: [
		'code' => 'price',
		'limit' => 50,
	],
	isPeriodic: true,
	period: 600
);

note('Строка агента: '.$entity->prepareNameForDb());
check(
	'строка агента — как в 1.x',
	$entity->prepareNameForDb(),
	"\\Shef\\Demo\\Import\\Agent::process(['code'=>'price','limit'=>'50']);"
);
check('агента с такой строкой на портале нет', $entity->getId(), 0);

step('Кавычка в значении');

$entity->setParams(['title' => "O'Neil"]);
note('Строка агента: '.$entity->prepareNameForDb());
check(
	'кавычка экранирована',
	$entity->prepareNameForDb(),
	"\\Shef\\Demo\\Import\\Agent::process(['title'=>'O\\'Neil']);"
);

step('Разбор строки обратно');

[$name, $params] = Manager::parseName($entity->prepareNameForDb());
check('имя', $name, '\\Shef\\Demo\\Import\\Agent::process');
check('параметры те же', $params, ['title' => "O'Neil"]);

[, $params] = Manager::parseName('\\Shef\\Demo\\Import\\Agent::process();');
check('агент без параметров — пустой список', $params, []);

done('agent');
