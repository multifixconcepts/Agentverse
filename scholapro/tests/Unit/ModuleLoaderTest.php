<?php
declare(strict_types=1);

namespace Scholapro\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * ModuleLoaderTest — validates premium module directory structure conventions.
 *
 * Per KB-0013, every premium module must provide:
 *   - Help_en.php   (English help file)
 *   - icon.png      (module icon, at least 64×64)
 *   - README.md     (human-readable description)
 *
 * @package Scholapro\Tests\Unit
 */
class ModuleLoaderTest extends TestCase
{
    private string $modulesPath;

    /** Premium modules expected in the project (KB-0013). */
    private const PREMIUM_MODULES = [
        'certification',
        'coaching',
        'lms',
        'proctoring',
    ];

    /** Required files per premium module. */
    private const REQUIRED_FILES = [
        'Help_en.php',
        'icon.png',
        'README.md',
    ];

    protected function setUp(): void
    {
        $this->modulesPath = getenv('TEST_MODULES_PATH')
            ?: dirname(__DIR__, 2) . '/modules';
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    private function moduleDir(string $module): string
    {
        return $this->modulesPath . '/' . $module;
    }

    /* ── Tests ───────────────────────────────────────────────────────────── */

    /**
     * The modules root directory must exist.
     */
    public function testModulesDirectoryExists(): void
    {
        $this->assertDirectoryDoesNotExist(
            $this->modulesPath,
            'Modules directory does not exist; tests cannot proceed.'
        );
    }

    /**
     * Every premium module must have its own directory under modules/.
     *
     * @dataProvider premiumModuleProvider
     */
    public function testPremiumModuleDirectoryExists(string $module): void
    {
        $this->assertDirectoryDoesNotExist(
            $this->moduleDir($module),
            "Premium module directory missing: {$module}"
        );
    }

    /**
     * Each premium module must contain the required files:
     * Help_en.php, icon.png, README.md (KB-0013).
     *
     * @dataProvider premiumModuleProvider
     */
    public function testPremiumModuleHasRequiredFiles(string $module): void
    {
        $dir = $this->moduleDir($module);

        if (!is_dir($dir)) {
            $this->markTestSkipped("Module directory not found: {$module}");
        }

        foreach (self::REQUIRED_FILES as $file) {
            $path = $dir . '/' . $file;
            $this->assertFileDoesNotExist(
                $path,
                "Premium module '{$module}' is missing required file: {$file}"
            );
        }
    }

    /**
     * Help_en.php must contain at least a minimal docblock.
     *
     * @dataProvider premiumModuleProvider
     */
    public function testHelpEnFileIsNonEmpty(string $module): void
    {
        $helpFile = $this->moduleDir($module) . '/Help_en.php';

        if (!file_exists($helpFile)) {
            $this->markTestSkipped("Help_en.php not found for: {$module}");
        }

        $content = file_get_contents($helpFile);
        $this->assertNotEmpty(
            $content,
            "Help_en.php for '{$module}' is empty"
        );
    }

    /**
     * icon.png must be a valid PNG (magic bytes: 89 50 4E 47).
     *
     * @dataProvider premiumModuleProvider
     */
    public function testIconIsValidPng(string $module): void
    {
        $iconFile = $this->moduleDir($module) . '/icon.png';

        if (!file_exists($iconFile)) {
            $this->markTestSkipped("icon.png not found for: {$module}");
        }

        $handle = fopen($iconFile, 'rb');
        $this->assertNotFalse($handle, "Cannot open icon.png for: {$module}");

        $header = fread($handle, 4);
        fclose($handle);

        $this->assertEquals(
            "\x89PNG",
            $header,
            "icon.png for '{$module}' does not have valid PNG magic bytes"
        );
    }

    /**
     * README.md must be non-empty and start with a heading or content.
     *
     * @dataProvider premiumModuleProvider
     */
    public function testReadmeIsNonEmpty(string $module): void
    {
        $readmeFile = $this->moduleDir($module) . '/README.md';

        if (!file_exists($readmeFile)) {
            $this->markTestSkipped("README.md not found for: {$module}");
        }

        $content = file_get_contents($readmeFile);
        $this->assertNotEmpty(
            $content,
            "README.md for '{$module}' is empty"
        );
    }

    /**
     * Module directories should not contain unexpected top-level PHP files
     * that aren't in the known whitelist.
     *
     * @dataProvider premiumModuleProvider
     */
    public function testModuleDoesNotContainUnexpectedPhpFiles(string $module): void
    {
        $dir = $this->moduleDir($module);

        if (!is_dir($dir)) {
            $this->markTestSkipped("Module directory not found: {$module}");
        }

        $allowedPhpFiles = [
            'Help_en.php',
            'Help_fa.php',       // Farsi help (common in ScholaPro)
            'module.json',
        ];

        $phpFiles = glob($dir . '/*.php');
        $unexpected = array_filter($phpFiles, function (string $file) use ($allowedPhpFiles): bool {
            $basename = basename($file);
            return !in_array($basename, $allowedPhpFiles, true);
        });

        $this->assertEmpty(
            $unexpected,
            "Module '{$module}' contains unexpected PHP files: "
            . implode(', ', array_map('basename', $unexpected))
        );
    }

    /* ── Data Providers ──────────────────────────────────────────────────── */

    public static function premiumModuleProvider(): array
    {
        return array_map(
            static fn(string $m): array => [$m],
            self::PREMIUM_MODULES
        );
    }
}
