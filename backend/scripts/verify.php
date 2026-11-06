<?php

/**
 * Verification script: php -l every backend .php file, validate JSON files,
 * and check .env.example keys against docker-compose placeholders.
 * Exit code 0 means everything passed.
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$backend = $root . '/backend';
$failures = 0;

function fail(string $message): void
{
    global $failures;
    $failures++;
    echo "FAIL: {$message}\n";
}

// 1. php -l on every backend .php file.
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($backend));
$phpFiles = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $phpFiles[] = $file->getPathname();
    }
}
sort($phpFiles);
echo 'Checking ' . count($phpFiles) . " PHP files with php -l...\n";
foreach ($phpFiles as $path) {
    $output = [];
    $code = 0;
    exec('php -l ' . escapeshellarg($path) . ' 2>&1', $output, $code);
    if ($code !== 0) {
        fail("php -l failed for {$path}: " . implode("\n", $output));
    }
}
echo 'PASS: php -l clean for ' . count($phpFiles) . " files.\n";

// 2. JSON files must parse.
foreach ([$backend . '/composer.json', $root . '/frontend/package.json'] as $jsonPath) {
    $decoded = json_decode((string) file_get_contents($jsonPath), true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        fail("Invalid JSON in {$jsonPath}: " . json_last_error_msg());
    } else {
        echo "PASS: valid JSON: {$jsonPath}\n";
    }
}

// 3. .env.example keys should cover the placeholders docker-compose uses.
$compose = (string) file_get_contents($root . '/docker-compose.yml');
preg_match_all('/\$\{([A-Z_]+)/', $compose, $matches);
$placeholders = array_unique($matches[1]);
// Compose-only keys (ports and the frontend URL) are not backend env vars.
$composeOnly = ['API_PORT', 'FRONTEND_PORT', 'VITE_API_URL'];
$placeholders = array_values(array_diff($placeholders, $composeOnly));
$envExample = (string) file_get_contents($backend . '/.env.example');
foreach ($placeholders as $key) {
    if (! str_contains($envExample, $key . '=')) {
        fail(".env.example is missing key used by docker-compose.yml: {$key}");
    }
}
echo 'PASS: .env.example covers docker-compose placeholders (' . implode(', ', $placeholders) . ").\n";

// 4. Frontend .env.example must define VITE_API_URL.
$frontendEnv = (string) file_get_contents($root . '/frontend/.env.example');
if (! str_contains($frontendEnv, 'VITE_API_URL=')) {
    fail('frontend/.env.example is missing VITE_API_URL');
} else {
    echo "PASS: frontend/.env.example defines VITE_API_URL.\n";
}

if ($failures > 0) {
    echo "\n{$failures} verification failure(s).\n";
    exit(1);
}

echo "\nAll verification checks passed.\n";
exit(0);
