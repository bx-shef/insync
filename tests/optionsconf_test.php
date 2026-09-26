<?php declare(strict_types=1);

/**
 * Страница настроек: options_conf.php собирается против API shef.options 3.x.
 *
 * Что держит:
 *
 * * options_conf.php зовёт ShOptionsConfig так, как его понимает shef.options
 *   3.x. В 1.2.9 он передавал indexDoc — параметр ушёл в 3.0.0, и страница
 *   настроек падала «Unknown named parameter». Ни php -l, ни остальные тесты
 *   этого не видели;
 * * у каждой подписи есть перевод: setName() и setTitle() принимают строку,
 *   и пропущенный ключ языкового файла — это TypeError, то есть снова
 *   неоткрывающаяся страница;
 * * в списке «сколько дней хранить файл» — только целые > 0: их и примет
 *   Constants::parseDays().
 *
 * API shef.options подменяет tests/stub/options.php, файлы модуля настоящие.
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/options.php';
require_once $root.'/tests/stub/autoload.php';
require_once $root.'/tests/assert.php';

use Bitrix\Main\Localization\Loc;
use Shef\InSync\Main\Constants;
use Shef\Options\Main\Options;

Loc::loadLangFile($root.'/lang/ru/options.php');

Check::group('options_conf.php собирается');

$tabs = require $root.'/options_conf.php';

Check::same('вернул список вкладок', is_array($tabs), true);
Check::same('вкладка «Общие»', array_map(static fn(Options\Tab $tab): string => $tab->getCode(), $tabs), ['DEF']);
Check::same('у вкладки есть название', $tabs[0]->getName(), 'Общие');

$options = [];
foreach($tabs[0]->getOptionList() as $option)
{
	$options[$option->getCode()] = $option;
}

Check::group('срок хранения файлов импорта');

$days = $options['maxdaydonefile'] ?? null;
Check::same('опция есть и это список', $days instanceof Options\Enum, true);
Check::same('у неё есть подпись', (string)$days?->getTitle() !== '', true);

$values = array_keys((array)$days?->list);
Check::same(
	'каждое значение списка разбирается как есть',
	array_map(static fn($value): int => Constants::parseDays((string)$value), $values),
	array_map('intval', $values)
);

$unlabeled = array_keys(array_filter((array)$days?->list, static fn($label): bool => '' === (string)$label || str_contains((string)$label, '#VALUE#')));
Check::same('у каждого значения подпись с числом', $unlabeled, []);

Check::finish();
