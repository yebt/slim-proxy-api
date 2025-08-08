<?php

declare(strict_types=1);

# -------------------------------------------------

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

function env(string $key, mixed $default = null)
{

    $val = $_ENV[$key] ?? $default;
    return match ($val) {
        'false' => false,
        'true' => true,
        'null' => null,
        default => $val ?? null
    };
}

