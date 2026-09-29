<?php declare(strict_types=1);

/**
 * Статусы строк импорта и то, что уходит в лог вместе с запросом к API.
 *
 * EStatus: цвета до 2.0.0 брались из перечисления модуля shef.uiclear,
 * которого не будет, — теперь литералы, и каждый статус обязан их иметь.
 * Коды статусов лежат в таблице импорта: менять их нельзя.
 *
 * Headers: AConnector пишет отправленный запрос в лог при каждой ошибке. Ключ
 * API в заголовке уходил туда как есть.
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/autoload.php';
require_once $root.'/tests/assert.php';

use Shef\InSync\Api\Headers;
use Shef\InSync\Sync\EStatus;

Check::group('EStatus');

Check::same('коды статусов — те, что лежат в таблице', EStatus::getValues(), ['U', 'N', 'P', 'S', 'F']);
Check::same('по умолчанию — Undefined', EStatus::getDefault(), 'U');

$badColors = [];
foreach(EStatus::cases() as $status)
{
	if(!preg_match('/^#[0-9A-F]{6}$/', $status->getColor()))
	{
		$badColors[] = $status->name.': '.$status->getColor();
	}
}
Check::same('у каждого статуса цвет #RRGGBB', $badColors, []);

Check::group('Headers');

Check::same(
	'секретные заголовки — маской, остальные как есть',
	Headers::mask([
		'Authorization' => 'Bearer abc',
		'X-Api-Key' => 'k',
		'X-Auth-Token' => 't',
		'Cookie' => 'PHPSESSID=1',
		'Content-Type' => 'application/json',
		'Accept' => '*/*',
	]),
	[
		'Authorization' => '***',
		'X-Api-Key' => '***',
		'X-Auth-Token' => '***',
		'Cookie' => '***',
		'Content-Type' => 'application/json',
		'Accept' => '*/*',
	]
);
Check::same('пусто — пусто', Headers::mask([]), []);

$secret = ['authorization', 'X-Client-Secret', 'X-Password', 'X-Session-Id', 'X-Auth', 'X-Api-Sign', 'Signature', 'X-Access'];
Check::same(
	'каждое слово шаблона — маской, регистр не важен',
	array_keys(array_filter(Headers::mask(array_fill_keys($secret, 'v')), static fn($value): bool => $value !== Headers::MASK)),
	[]
);

Check::finish();
