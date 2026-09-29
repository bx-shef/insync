<?php declare(strict_types=1);

/**
 * Клиент внешнего API: что уходит в лог при ошибке запроса.
 *
 * При любой ошибке AConnector пишет в лог проблем запрос целиком — адрес,
 * параметры, заголовки. Заголовки авторизации — только маской: лог читают
 * не те, кому положено видеть ключ API. Headers::mask() проверяет
 * status_test.php, здесь — что sendRequest() действительно пишет в лог её
 * результат, а не заголовки как есть.
 *
 * HttpClient ядра — заглушка здесь же: отвечает тем статусом, что задал тест.
 */

namespace Bitrix\Main\Web
{
	if(!class_exists(HttpClient::class))
	{
		class HttpClient
		{
			public const HTTP_GET = 'GET';
			public const HTTP_POST = 'POST';

			public static int $status = 200;
			public static string $result = '';

			/** @var array<string, string> что выставили */
			public array $headers = [];

			public function __construct(array $options = []) {}

			public function setHeader(string $name, string $value): static
			{
				$this->headers[$name] = $value;
				return $this;
			}

			public function setTimeout(int $timeout): static
			{
				return $this;
			}

			public function setStreamTimeout(int $timeout): static
			{
				return $this;
			}

			public function waitResponse(bool $wait): static
			{
				return $this;
			}

			public function query(string $method, string $url, mixed $entityBody = null): bool
			{
				return true;
			}

			public function getStatus(): int
			{
				return static::$status;
			}

			public function getError(): array
			{
				return [];
			}

			public function getResult(): string
			{
				return static::$result;
			}
		}
	}
}

namespace
{
	$root = dirname(__DIR__);

	require_once $root.'/tests/stub/autoload.php';
	require_once $root.'/tests/stub/agent.php';
	require_once $root.'/tests/assert.php';

	use Bitrix\Main\Web\HttpClient;
	use Shef\InSync\Api\AConnector;
	use Shef\Problems\Factory\Trait\TestLogger;

	final class DemoConnector extends AConnector
	{
		public static function getModuleId(): string
		{
			return 'shef.demo';
		}

		protected function getPath(string $functionName): string
		{
			return 'https://api.example/'.$functionName;
		}
	}

	Check::group('ошибка запроса — в лог, заголовки маской');

	HttpClient::$status = 403;
	HttpClient::$result = 'forbidden';
	TestLogger::$records = [];

	$response = (new DemoConnector())->sendRequest(
		'orders',
		['since' => '2026-09-01'],
		HttpClient::HTTP_POST,
		['Authorization' => 'Bearer secret-value', 'X-Api-Key' => 'k-123', 'Accept' => 'application/json']
	);

	Check::same('ответ — ошибка', $response->isSuccess(), false);
	Check::same('в лог записано одно событие', count(TestLogger::$records), 1);

	$record = TestLogger::$records[0] ?? null;
	$logged = $record instanceof \Shef\Options\Options\SmartStd ? $record->toArray() : [];

	Check::same(
		'заголовки — маской, кроме безобидных',
		$logged['send']['headers'] ?? null,
		['Authorization' => '***', 'X-Api-Key' => '***', 'Accept' => 'application/json']
	);
	Check::same(
		'ключа нет нигде в записи',
		str_contains(var_export($logged, true), 'secret-value') || str_contains(var_export($logged, true), 'k-123'),
		false
	);
	Check::same('адрес и параметры на месте — по ним разбирают сбой', [$logged['send']['url'] ?? null, $logged['send']['params'] ?? null], ['https://api.example/orders', ['since' => '2026-09-01']]);

	Check::group('успешный ответ — в лог ничего');

	HttpClient::$status = 200;
	TestLogger::$records = [];
	(new DemoConnector())->sendRequest('orders', [], HttpClient::HTTP_POST, ['Authorization' => 'Bearer x']);
	Check::same('записей нет', TestLogger::$records, []);

	Check::finish();
}
