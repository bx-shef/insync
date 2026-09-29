<?php declare(strict_types=1);

/**
 * Помощники импорта: какие файлы берутся, транслит, трейт ID свойства.
 *
 * Держится:
 *
 * 1. getExistFiles() берёт файлы по началу имени и с нужным расширением.
 *    Было: маска без якоря и экранирования — брались old-price.csv, файл
 *    без расширения и process_<код>_… (файл, уже взятый в обработку);
 * 2. Utils::translation() без ключа lang — без warning. Было: warning на
 *    каждом новом элементе и разделе с именем;
 * 3. MarkFail ставит метку ошибки один раз и называет свой код устаревших
 *    строк — их убирает агент (fromfileagent_test.php);
 * 4. трейт ID свойства до настройки бросает обещанный
 *    LogicException, а не Error «must not be accessed before initialization».
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/autoload.php';
require_once $root.'/tests/assert.php';

use Shef\InSync\Main\Utils;
use Shef\InSync\Sync\FromFile\FileMask;
use Shef\InSync\Sync\Model\IBlock\IPropertyIdTrait;

Check::group('какие файлы забирает getExistFiles()');

Check::same('по префиксу', FileMask::isMatch('price-2026.csv', 'price-', 'csv'), true);
Check::same('регистр расширения не важен', FileMask::isMatch('price-2026.CSV', 'price-', 'csv'), true);
Check::same('префикс в середине имени — нет', FileMask::isMatch('old-price-1.csv', 'price-', 'csv'), false);
Check::same('без расширения — нет', FileMask::isMatch('price-1.', 'price-', 'csv'), false);
Check::same('другое расширение — нет', FileMask::isMatch('price-1.csv.bak', 'price-', 'csv'), false);
Check::same(
	'файл в обработке — нет',
	FileMask::isMatch('process_DemoPrice_29-09-2026_10-00_abc.csv', 'price', 'csv'),
	false
);
Check::same('точка в префиксе — буквально', FileMask::isMatch('priceX1.csv', 'price.', 'csv'), false);
Check::same('несколько расширений через «|»', FileMask::isMatch('cart-1.zip', 'cart-', 'xml|zip'), true);

try
{
	FileMask::build('price-', '');
	$emptyExtension = 'нет исключения';
}
catch(\Bitrix\Main\ArgumentException)
{
	$emptyExtension = 'ArgumentException';
}
Check::same('пустое расширение — исключение, а не «файл с точкой в конце»', $emptyExtension, 'ArgumentException');

Check::group('MarkFail: метка ошибки');

// Строка импорта: только код импорта и сохранение.
final class MarkFailRow implements \Shef\InSync\Sync\IElement
{
	public bool $saved = false;

	public function __construct(private string $originatorId, private readonly string $originId = '') {}

	public function setInterfaceOriginId(string $originId): static { return $this; }
	public function getInterfaceOriginId(): string { return $this->originId; }
	public function setInterfaceOriginatorId(string $originatorId): static { $this->originatorId = $originatorId; return $this; }
	public function getInterfaceOriginatorId(): string { return $this->originatorId; }
	public function setInterfaceStatus(\Shef\InSync\Sync\EStatus $status): static { return $this; }
	public function getInterfaceStatus(): \Shef\InSync\Sync\EStatus { return \Shef\InSync\Sync\EStatus::Fail; }
	public function setInterfaceMessage(string $message): static { return $this; }
	public function getInterfaceMessage(): string { return ''; }
	public function setInterfaceDateInsert(\Bitrix\Main\Type\DateTime $dateInsert): static { return $this; }
	public function getInterfaceDateInsert(): \Bitrix\Main\Type\DateTime { return new \Bitrix\Main\Type\DateTime(); }
	public function setInterfaceTitle(string $title): static { return $this; }
	public function getInterfaceTitle(): string { return ''; }
	public function setInterfaceAdditional(array $additional): static { return $this; }
	public function getInterfaceAdditional(): array { return []; }
	public function clearInterfaceSyncStatus(): void {}
	public function saveInterface(): \Bitrix\Main\ORM\Data\Result { $this->saved = true; return new \Bitrix\Main\ORM\Data\Result(); }
	public function deleteInterface(): \Bitrix\Main\ORM\Data\Result { return new \Bitrix\Main\ORM\Data\Result(); }
}

$strategy = new \Shef\InSync\Sync\FromFile\Strategy\MarkFail();

$row = new MarkFailRow('price');
$strategy->processFail($row);
Check::same('строка уходит под метку и сохраняется', [$row->getInterfaceOriginatorId(), $row->saved], ['price.error', true]);

$row = new MarkFailRow('price.error');
$strategy->processFail($row);
Check::same('метка не удваивается', $row->getInterfaceOriginatorId(), 'price.error');

Check::same('устаревшие строки MarkFail — под меткой', $strategy->getSupersededOriginatorId('price'), 'price.error');
Check::same('у Simple отдельных нет', (new \Shef\InSync\Sync\FromFile\Strategy\Simple())->getSupersededOriginatorId('price'), null);

Check::group('транслит');

Check::same('без lang — без warning', Utils::translation('Прайс'), 'Прайс');

Check::group('трейт ID свойства до настройки');

final class PropertyDemo
{
	use IPropertyIdTrait;
}

$error = static function(callable $call): string
{
	try
	{
		$call();
	}
	catch(\Throwable $throwable)
	{
		return get_class($throwable);
	}

	return 'нет исключения';
};

Check::same('ID не задан — LogicException', $error(static fn() => PropertyDemo::getPropertyId()), LogicException::class);

PropertyDemo::setPropertyId(12);
Check::same('заданный ID', PropertyDemo::getPropertyId(), 12);

Check::finish();
