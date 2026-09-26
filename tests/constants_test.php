<?php declare(strict_types=1);

/**
 * Настройка «сколько дней хранить файл импорта»: строгий разбор.
 *
 * Значение приходит из b_option строкой. До 2.0.0 его брали через (int), и
 * «0», пустая строка или мусор давали 0 дней: clearDoneFolder() при каждом
 * запуске импорта стирал архив загруженных файлов целиком. Теперь всё, что
 * не целое > 0, — умолчание, и умолчание одно на код и default_option.php.
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/autoload.php';
require_once $root.'/tests/assert.php';

use Bitrix\Main\Config\Option;
use Shef\InSync\Main\Constants;

Check::group('разбор');

Check::same('строка из цифр', Constants::parseDays('15'), 15);
Check::same('целое', Constants::parseDays(30), 30);
Check::same('ноль — умолчание, а не «стереть всё»', Constants::parseDays('0'), Constants::DEFAULT_MAX_DAY_DONE_FILE);
Check::same('пусто — умолчание', Constants::parseDays(''), Constants::DEFAULT_MAX_DAY_DONE_FILE);
Check::same('мусор — умолчание, а не 5', Constants::parseDays('5 дней'), Constants::DEFAULT_MAX_DAY_DONE_FILE);
Check::same('отрицательное — умолчание', Constants::parseDays(-3), Constants::DEFAULT_MAX_DAY_DONE_FILE);
Check::same('массив — умолчание, а не 1', Constants::parseDays([5]), Constants::DEFAULT_MAX_DAY_DONE_FILE);

Check::group('из настроек');

Option::$values = [];
Check::same('не сохраняли — умолчание', Constants::getMaxDayOffDoneFile(), 3);

Option::set('shef.insync', 'DEF_maxdaydonefile', '30');
Check::same('сохранили 30', Constants::getMaxDayOffDoneFile(), 30);

Check::group('умолчание одно');

$shef_insync_default_option = [];
require $root.'/default_option.php';
Check::same(
	'default_option.php совпадает с кодом',
	$shef_insync_default_option['DEF_maxdaydonefile'] ?? null,
	(string)Constants::DEFAULT_MAX_DAY_DONE_FILE
);

$conf = (string)file_get_contents($root.'/options_conf.php');
preg_match("/->setDefValue\('(\d+)'\)/", $conf, $defValue);
Check::same('и со страницей настроек', $defValue[1] ?? null, (string)Constants::DEFAULT_MAX_DAY_DONE_FILE);

Check::finish();
