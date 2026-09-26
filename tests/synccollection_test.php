<?php declare(strict_types=1);

/**
 * Таблица импорта: запросы со строками снаружи экранированы.
 *
 * SyncCollection::clear() и getStatisticByOriginator() собирают SQL сами. До
 * 2.0.0 код импорта вставлялся в запрос как есть, а в clear() он приходит
 * параметром ajax-запроса компонента статистики. Это была SQL-инъекция для
 * любого вошедшего на портал: «x' OR '1'='1» стирал всю таблицу.
 *
 * Класс подключается настоящий. Его родитель EO_Sync_Collection ядро
 * компилирует на лету из SyncTable, поэтому здесь он — пустая оболочка с
 * сущностью, которая отдаёт соединение-заглушку.
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/autoload.php';
require_once $root.'/tests/assert.php';

use Bitrix\Main\DB\Connection;
use Bitrix\Main\DB\SqlHelper;
use Shef\InSync\Sync\Model\SyncCollection;

// Коллекцию ORM ядро собирает само: EO_Sync_Collection с сущностью таблицы.
eval('namespace Shef\\InSync\\Sync\\Model;
class EO_Sync_Collection
{
	public object $entity;
	public function __construct() { $this->entity = SyncTable::getEntity(); }
}');

$collection = new SyncCollection();

Check::group('условие запроса');

Check::same('без условий — все строки', SyncCollection::buildWhere(new SqlHelper()), '1 = 1');
Check::same(
	'код импорта в кавычках и экранирован',
	SyncCollection::buildWhere(new SqlHelper(), "x' OR '1'='1"),
	"1 = 1 AND ORIGINATOR_ID = 'x\\' OR \\'1\\'=\\'1'"
);

$date = new \Bitrix\Main\Type\DateTime('2026-09-26 10:00:00');
Check::same(
	'дата загрузки тоже',
	SyncCollection::buildWhere(new SqlHelper(), 'price', $date),
	"1 = 1 AND ORIGINATOR_ID = 'price' AND DATE_INSERT = '2026-09-26 10:00:00'"
);

Check::group('clear() отдаёт в базу экранированный запрос');

Connection::$queries = [];
$collection->clear("x'; DROP TABLE b_user; --");

Check::same('запрос один', count(Connection::$queries), 1);
Check::same(
	'значение не вышло из кавычек',
	Connection::$queries[0] ?? null,
	"DELETE FROM shef_insync_model WHERE 1 = 1 AND ORIGINATOR_ID = 'x\\'; DROP TABLE b_user; --'"
);

Check::group('getStatisticByOriginator()');

Connection::$queries = [];
Connection::$row = ['CNT' => '42'];
Check::same('число строк — целым', $collection->getStatisticByOriginator('price'), ['ORIGINATOR_ID' => 'price', 'CNT' => 42]);
Check::same('без ORDER BY в агрегате (ONLY_FULL_GROUP_BY)', str_contains(Connection::$queries[0] ?? '', 'ORDER BY'), false);

Connection::$row = null;
Check::same('пустая таблица — ноль, а не ошибка типа', $collection->getStatisticByOriginator('price')['CNT'], 0);

Check::finish();
