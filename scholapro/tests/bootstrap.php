<?php
/**
 * PHPUnit bootstrap for ScholaPro.
 *
 * Provides basic autoloading for ScholaPro classes and defines
 * test-scoped constants (DB credentials, paths).
 */

declare(strict_types=1);

/* ── Autoloader ─────────────────────────────────────────────────────────── */

$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

$loaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    // Fallback: PSR-4 style classmap for test-only autoloading
    spl_autoload_register(static function (string $class): void {
        $prefixes = [
            'Scholapro\\'       => __DIR__ . '/../src/',
            'Scholapro\\Tests\\' => __DIR__ . '/',
        ];
        foreach ($prefixes as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    });
}

/* ── Test Constants ─────────────────────────────────────────────────────── */

// Database credentials for the test database
define('TEST_DB_HOST', getenv('TEST_DB_HOST') ?: '127.0.0.1');
define('TEST_DB_PORT', getenv('TEST_DB_PORT') ?: '3306');
define('TEST_DB_NAME', getenv('TEST_DB_NAME') ?: 'scholapro_test');
define('TEST_DB_USER', getenv('TEST_DB_USER') ?: 'scholapro_test');
define('TEST_DB_PASS', getenv('TEST_DB_PASS') ?: 'test_secret');

// Paths
define('TEST_PROJECT_ROOT', dirname(__DIR__));
define('TEST_FIXTURES_PATH', __DIR__ . '/Fixtures');
define('TEST_MODULES_PATH', getenv('TEST_MODULES_PATH') ?: TEST_PROJECT_ROOT . '/modules');
define('TEST_OUTPUT_PATH', sys_get_temp_dir() . '/scholapro_tests');

// Feature flags
define('TEST_PREMIUM_MODULES', ['certification', 'coaching', 'lms', 'proctoring']);
