<?php declare(strict_types=1);

/**
 * Разбор XML по элементам: трейты ToYield и ToArray.
 *
 * ЦЕЛЬ
 *   Показать, как модуль разбирает XML: потоково, элемент за элементом, через
 *   XMLReader и HierarchyComposer из sbwerewolf/xml-navigator. Каждый
 *   элемент с заданным тегом превращается в массив «n — имя, v — значение,
 *   a — атрибуты, s — вложенные элементы». Так же разбирает файл
 *   Sync\FromFile\AXmlProcess перед тем, как положить строки в таблицу
 *   импорта.
 *
 * ГДЕ ПРИМЕНЯТЬ
 *   Ответ API в XML, выгрузка поставщика, файл обмена. ToYield — для
 *   средних и больших объёмов: в памяти один элемент, а не весь документ.
 *   ToArray — когда элементов немного и нужен массив сразу.
 *
 * ЧТО ДОЛЖНО ПОЛУЧИТЬСЯ
 *   Все строки «ok», последняя — «ГОТОВО: xml», код возврата 0.
 *   По сути:
 *     * два элемента <item> — два массива, остальное в документе пропущено;
 *     * атрибут и вложенные теги на месте, кириллица цела;
 *     * ToYield и ToArray отдают одно и то же.
 *
 * ЗАПУСК
 *   php examples/xml.php
 *   DOCUMENT_ROOT=/var/www/portal php examples/xml.php
 *
 * НА ПОРТАЛЕ ОТЛИЧАЕТСЯ
 *   Ничем: библиотеку разбора даёт .settings.php — из vendor проекта, если
 *   она там есть, иначе своя копия модуля.
 */

require_once __DIR__.'/_bootstrap.php';

title('Разбор XML по элементам');

use Shef\InSync\TraitList\Xml\ToArray;
use Shef\InSync\TraitList\Xml\ToYield;

/** Свой класс разбора: трейты модуля подключаются в него. */
final class PriceXml
{
	use ToYield;
	use ToArray;
}

$xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<price date="2026-09-26">
	<currency>BYN</currency>
	<item code="A-1"><name>Стол письменный</name><price>250.00</price></item>
	<item code="A-2"><name>Стул</name><price>80.50</price></item>
</price>
XML;

step('Потоково: ToYield');

$rows = [];
foreach(PriceXml::toYieldConvert($xml, 'item') as $row)
{
	$rows[] = $row;
}

check('элементов <item> два', count($rows), 2);
check('имя элемента', $rows[0]['n'] ?? null, 'item');
check('атрибут code', $rows[0]['a']['code'] ?? null, 'A-1');
check('вложенный <name>', $rows[0]['s'][0]['v'] ?? null, 'Стол письменный');
check('вложенный <price> второго', $rows[1]['s'][1]['v'] ?? null, '80.50');

step('Сразу массивом: ToArray');

check('то же самое', PriceXml::toArrayConvert($xml, 'item'), $rows);

done('xml');
