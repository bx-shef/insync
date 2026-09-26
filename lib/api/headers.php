<?php declare(strict_types=1);

namespace Shef\InSync\Api;

/**
 * Заголовки HTTP-запроса для лога.
 *
 * AConnector пишет отправленный запрос в лог при каждой ошибке, а лог читают
 * не только те, кому положен ключ API. Секретным считается заголовок, в
 * имени которого есть authorization, token, key, secret, password, cookie или
 * session: его значение заменяется маской.
 */
final class Headers
{
	public const MASK = '***';
	
	public static function mask(array $headers): array
	{
		$masked = [];
		foreach($headers as $name => $value)
		{
			$masked[$name] = static::isSecret((string)$name)
				? static::MASK
				: $value;
		}
		
		return $masked;
	}
	
	public static function isSecret(string $name): bool
	{
		return 1 === preg_match('/authorization|token|key|secret|password|cookie|session/i', $name);
	}
}
