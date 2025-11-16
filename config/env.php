<?php
/**
 * Lightweight .env loader.
 * Usage: loadEnv(__DIR__ . '/../.env');
 */
if (!function_exists('loadEnv')) {
	function loadEnv(string $path): void {
		static $loaded = false;
		if ($loaded) {
			return;
		}
		if (!is_file($path)) {
			return;
		}
		$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ($lines === false) {
			return;
		}
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '' || str_starts_with($line, '#')) {
				continue;
			}
			if (!str_contains($line, '=')) {
				continue;
			}
			[$key, $value] = explode('=', $line, 2);
			$key = trim($key);
			$value = trim($value);
			if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
				$value = trim($value, "\"'");
			}
			putenv("$key=$value");
			if (!array_key_exists($key, $_ENV)) {
				$_ENV[$key] = $value;
			}
			if (!array_key_exists($key, $_SERVER)) {
				$_SERVER[$key] = $value;
			}
		}
		$loaded = true;
	}
}


