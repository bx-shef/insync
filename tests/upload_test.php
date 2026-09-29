<?php declare(strict_types=1);

/**
 * Загрузка файла в импорт: что ложится в каталог и кто вообще доходит до него.
 *
 * Имя файла приходит от браузера. До 2.0.0 оно шло в путь как есть, а
 * каталог импорта лежал под /upload, под корнем сайта: в него ложились .php
 * и .htaccess. Каталог теперь вне корня, но проект может задать свой. Компонент при этом пускал любого вошедшего
 * на портал, а действие «скачать пример» — даже гостя, и подключало модуль
 * и создавало объект класса, названные в запросе.
 *
 * Держится:
 *
 * 1. prepareUploadName(): только имя без пути, без исполняемых расширений и
 *    скрытых файлов, с расширением из accept импорта;
 * 2. без прав компонент не подключает модуль импорта и не создаёт объект —
 *    конструктор AFileProcess уже работает с каталогами;
 * 3. модуль — id модуля Битрикса, класс — из namespace этого модуля.
 *
 * Класс компонента подключается настоящий, из install/components.
 */

$root = dirname(__DIR__);

require_once $root.'/tests/stub/autoload.php';
require_once $root.'/tests/stub/agent.php';
require_once $root.'/tests/assert.php';

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Local\Component\Shef\InSync\ShefInSyncImportFromFileComponent as Component;

define('B_PROLOG_INCLUDED', true);
require_once $root.'/install/components/shef.insync/import.from.file/class.php';

Check::group('имя загруженного файла');

Check::same('обычный csv', Component::prepareUploadName('price-2026.csv', '.csv'), 'price-2026.csv');
Check::same('кириллица и пробелы', Component::prepareUploadName('Прайс лист (1).xml', '.xml'), 'Прайс лист (1).xml');
Check::same('путь отрезается', Component::prepareUploadName('../../bitrix/price.csv', '.csv'), 'price.csv');
Check::same('путь Windows тоже', Component::prepareUploadName('C:\\tmp\\price.csv', '.csv'), 'price.csv');
Check::same('.php — нет', Component::prepareUploadName('shell.php', ''), null);
Check::same('.PHTML — нет, регистр не спасает', Component::prepareUploadName('shell.PHTML', ''), null);
Check::same('.htaccess — нет', Component::prepareUploadName('.htaccess', ''), null);
foreach(['x.svg', 'x.html', 'x.xhtml', 'x.shtm', 'x.php7', 'x.phtm', 'x.hta', 'x.mht', 'x.js'] as $dangerous)
{
	Check::same($dangerous.' без accept — нет', Component::prepareUploadName($dangerous, ''), null);
}
Check::same('двойное расширение .php.csv — нет', Component::prepareUploadName('price.php.csv', '.csv'), null);
Check::same('точка в имени не мешает', Component::prepareUploadName('price.v2.csv', '.csv'), 'price.v2.csv');
Check::same('скрытый файл — нет', Component::prepareUploadName('.price.csv', '.csv'), null);
Check::same('без расширения — нет', Component::prepareUploadName('price', ''), null);
Check::same('расширение не из accept — нет', Component::prepareUploadName('price.xml', '.csv'), null);
Check::same('accept со списком и MIME', Component::prepareUploadName('price.zip', '.xml, .zip, text/xml'), 'price.zip');
Check::same('без accept — любое не исполняемое', Component::prepareUploadName('price.txt', ''), 'price.txt');
Check::same('кавычки и угловые скобки — нет', Component::prepareUploadName('a"<b>.csv', '.csv'), null);

Check::group('загрузка: имя от браузера проходит prepareUploadName()');

// Процесс импорта модуля shef.demo; конструктор пуст — каталоги не нужны.
eval('namespace Shef\\Demo;
final class Upload extends \\Shef\\InSync\\Sync\\FromFile\\ACsvProcess
{
	protected const OriginatorId = "DemoUpload";
	public function __construct() {}
	public static function getModuleId(): string { return "shef.demo"; }
	public static function getProcessTitle(): string { return ""; }
	public static function getProcessDescription(): string { return ""; }
	public static function getEncodingFrom(): string { return "UTF-8"; }
	public static function getImportFileAccept(): string { return ".csv"; }
	public static function isUseHeader(): bool { return false; }
	public static function getDelim(): string { return ";"; }
	public function getMapImportFile(): array { return []; }
	public function init(\\Shef\\InSync\\Sync\\IElement $element): \\Bitrix\\Main\\Result { return new \\Bitrix\\Main\\Result(); }
}');

$upload = static function(string $name): array
{
	$component = new Component();
	$component->arParams = ['MODULE' => 'shef.demo', 'CLASS' => '\\Shef\\Demo\\Upload'];
	$component->request = new class($name)
	{
		public function __construct(private readonly string $name) {}

		public function getFile(string $field): array
		{
			// tmp_name — не загруженный файл: move_uploaded_file() его не тронет.
			return ['name' => $this->name, 'tmp_name' => __FILE__, 'error' => UPLOAD_ERR_OK];
		}
	};
	$component->importFileAction();

	return array_map(static fn($error) => $error->getMessage(), $component->getErrors());
};

CurrentUser::$id = 5;
CurrentUser::$isAdmin = false;
CMain::$rights = ['shef.demo' => 'W'];

Check::same('shell.php — отказ до записи на диск', $upload('shell.php'), ['Wrong file type']);
Check::same('../x.csv — путь отрезан, дальше к записи', $upload('../x.csv'), ['Not upload file for import']);
Check::same('price.xml при accept .csv — отказ', $upload('price.xml'), ['Wrong file type']);
Check::same('price.csv — к записи на диск', $upload('price.csv'), ['Not upload file for import']);

Check::group('права — раньше, чем модуль и объект импорта');

$component = new Component();
$component->arParams = ['MODULE' => 'shef.demo', 'CLASS' => '\\Shef\\Demo\\File'];

CurrentUser::$id = 5;
CurrentUser::$isAdmin = false;
CMain::$rights = [];
Loader::$included = [];

$component->getDemoFileAction('shef.demo', '\\Shef\\Demo\\File');

Check::same('без прав — отказ', array_map(static fn($error) => $error->getCode(), $component->getErrors()), ['ACCESS_DENIED']);
Check::same('модуль импорта не подключали', in_array('shef.demo', Loader::$included, true), false);

$attempt = static function(string $module, string $class): array
{
	$component = new Component();
	$component->getDemoFileAction($module, $class);

	return array_map(static fn($error) => $error->getMessage(), $component->getErrors());
};

CMain::$rights = ['shef.demo' => 'W', 'shef.demo.x' => 'W', 'main' => 'W'];

Check::same('класс чужого модуля — нет', $attempt('shef.demo', '\\Bitrix\\Main\\UserTable'), ['>> Class not from module shef.demo']);
Check::same('не id модуля — нет', $attempt('main', '\\Bitrix\\Main\\UserTable'), ['>> Wrong module id']);
Check::same('несуществующий класс своего модуля — нет', $attempt('shef.demo', '\\Shef\\Demo\\File'), ['>> Class Shef\\Demo\\File not exist']);

Check::finish();
