<?php
/**
 * Tests to verify package file structure and existence of key files.
 *
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category SSL
 */

namespace Detain\MyAdminGlobalSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

class FileExistenceTest extends TestCase
{
    /** @var string */
    private $packageRoot;

    protected function setUp(): void
    {
        $this->packageRoot = dirname(__DIR__, 2);
    }

    /**
     * Verify the GlobalSign.php source file exists.
     */
    public function testGlobalSignSourceExists(): void
    {
        $this->assertFileExists($this->packageRoot . '/src/GlobalSign.php');
    }

    /**
     * Verify the Plugin.php source file exists.
     */
    public function testPluginSourceExists(): void
    {
        $this->assertFileExists($this->packageRoot . '/src/Plugin.php');
    }

    /**
     * Verify the composer.json file exists.
     */
    public function testComposerJsonExists(): void
    {
        $this->assertFileExists($this->packageRoot . '/composer.json');
    }

    /**
     * Verify composer.json is valid JSON.
     */
    public function testComposerJsonIsValidJson(): void
    {
        $content = file_get_contents($this->packageRoot . '/composer.json');
        $decoded = json_decode($content, true);
        $this->assertNotNull($decoded, 'composer.json should be valid JSON');
    }

    /**
     * Verify composer.json has the correct package name.
     */
    public function testComposerJsonPackageName(): void
    {
        $content = file_get_contents($this->packageRoot . '/composer.json');
        $decoded = json_decode($content, true);
        $this->assertSame('detain/myadmin-globalsign-ssl', $decoded['name']);
    }

    /**
     * Verify composer.json has PSR-4 autoload for the correct namespace.
     */
    public function testComposerJsonAutoload(): void
    {
        $content = file_get_contents($this->packageRoot . '/composer.json');
        $decoded = json_decode($content, true);
        $this->assertArrayHasKey('psr-4', $decoded['autoload']);
        $this->assertArrayHasKey('Detain\\MyAdminGlobalSign\\', $decoded['autoload']['psr-4']);
        $this->assertSame('src/', $decoded['autoload']['psr-4']['Detain\\MyAdminGlobalSign\\']);
    }

    /**
     * Verify the README file exists.
     */
    public function testReadmeExists(): void
    {
        $this->assertFileExists($this->packageRoot . '/README.md');
    }

    /**
     * Verify the src directory exists.
     */
    public function testSrcDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->packageRoot . '/src');
    }

    /**
     * Verify the tests directory exists.
     */
    public function testTestsDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->packageRoot . '/tests');
    }

    /**
     * Verify the GlobalSign source file is non-empty.
     */
    public function testGlobalSignSourceIsNonEmpty(): void
    {
        $content = file_get_contents($this->packageRoot . '/src/GlobalSign.php');
        $this->assertNotEmpty($content);
        $this->assertGreaterThan(1000, strlen($content));
    }

    /**
     * Verify the Plugin source file is non-empty.
     */
    public function testPluginSourceIsNonEmpty(): void
    {
        $content = file_get_contents($this->packageRoot . '/src/Plugin.php');
        $this->assertNotEmpty($content);
        $this->assertGreaterThan(500, strlen($content));
    }

    /**
     * Verify the license is declared in composer.json.
     */
    public function testComposerJsonHasLicense(): void
    {
        $content = file_get_contents($this->packageRoot . '/composer.json');
        $decoded = json_decode($content, true);
        $this->assertArrayHasKey('license', $decoded);
        $this->assertSame('LGPL-2.1-only', $decoded['license']);
    }

    /**
     * Verify the package type is myadmin-plugin.
     */
    public function testComposerJsonType(): void
    {
        $content = file_get_contents($this->packageRoot . '/composer.json');
        $decoded = json_decode($content, true);
        $this->assertSame('myadmin-plugin', $decoded['type']);
    }
}
