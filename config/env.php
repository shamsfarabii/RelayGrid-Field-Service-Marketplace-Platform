<?php

if (!function_exists('env')) {
    function env(string $key, $default = null)
    {
        static $data = null;

        if ($data === null) {
            $data = [];
            $file = __DIR__ . '/../.env';

            if (is_file($file)) {
                foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }

                    [$k, $v] = array_map('trim', explode('=', $line, 2));
                    $data[$k] = $v;
                }
            }
        }

        return $data[$key] ?? $default;
    }
}
