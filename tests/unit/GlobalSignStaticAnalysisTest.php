<?php
/**
 * Static analysis tests for the GlobalSign source files.
 * Uses file_get_contents to verify source code patterns without
 * instantiating classes that depend on external SOAP services.
 *
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category SSL
 */

namespace Detain\MyAdminGlobalSign\Tests\Unit;

use PHPUnit\Framework\TestCase;

class GlobalSignStaticAnalysisTest extends TestCase
{
    /** @var string */
    private $globalSignSource;

    /** @var string */
    private $pluginSource;

    /** @var string */
    private $srcDir;

    protected function setUp(): void
    {
        $this->srcDir = dirname(__DIR__, 2) . '/src';
        $this->globalSignSource = file_get_contents($this->srcDir . '/GlobalSign.php');
        $this->pluginSource = file_get_contents($this->srcDir . '/Plugin.php');
    }

    /**
     * Verify the GlobalSign source file exists and is readable.
     */
    public function testGlobalSignSourceFileExists(): void
    {
        $this->assertFileExists($this->srcDir . '/GlobalSign.php');
        $this->assertNotEmpty($this->globalSignSource);
    }

    /**
     * Verify the Plugin source file exists and is readable.
     */
    public function testPluginSourceFileExists(): void
    {
        $this->assertFileExists($this->srcDir . '/Plugin.php');
        $this->assertNotEmpty($this->pluginSource);
    }

    /**
     * Verify the GlobalSign class declares the correct namespace.
     */
    public function testGlobalSignNamespaceDeclaration(): void
    {
        $this->assertStringContainsString(
            'namespace Detain\\MyAdminGlobalSign;',
            $this->globalSignSource
        );
    }

    /**
     * Verify the Plugin class declares the correct namespace.
     */
    public function testPluginNamespaceDeclaration(): void
    {
        $this->assertStringContainsString(
            'namespace Detain\\MyAdminGlobalSign;',
            $this->pluginSource
        );
    }

    /**
     * Verify GlobalSign class contains production WSDL URLs.
     */
    public function testProductionWsdlUrls(): void
    {
        $this->assertStringContainsString(
            'https://system.globalsign.com/kb/ws/v1/ServerSSLService?wsdl',
            $this->globalSignSource
        );
        $this->assertStringContainsString(
            'https://system.globalsign.com/kb/ws/v1/GASService?wsdl',
            $this->globalSignSource
        );
        $this->assertStringContainsString(
            'https://system.globalsign.com/kb/ws/v1/AccountService?wsdl',
            $this->globalSignSource
        );
    }

    /**
     * Verify GlobalSign class contains test WSDL URLs.
     */
    public function testTestWsdlUrls(): void
    {
        $this->assertStringContainsString(
            'https://test-gcc.globalsign.com/kb/ws/v1/ServerSSLService?wsdl',
            $this->globalSignSource
        );
        $this->assertStringContainsString(
            'https://test-gcc.globalsign.com/kb/ws/v1/GASService?wsdl',
            $this->globalSignSource
        );
        $this->assertStringContainsString(
            'https://test-gcc.globalsign.com/kb/ws/v1/AccountService?wsdl',
            $this->globalSignSource
        );
    }

    /**
     * Verify the GlobalSign class uses SoapClient.
     */
    public function testUsesSoapClient(): void
    {
        $this->assertStringContainsString('new \\SoapClient(', $this->globalSignSource);
    }

    /**
     * Verify the constructor uses WSDL_CACHE_BOTH.
     */
    public function testUsesWsdlCacheBoth(): void
    {
        $this->assertStringContainsString('WSDL_CACHE_BOTH', $this->globalSignSource);
    }

    /**
     * Verify all SSL product codes are present in the source.
     */
    public function testProductCodesExist(): void
    {
        $productCodes = [
            'DV_LOW_SHA2',
            'DV_SHA2',
            'DV_SKIP_SHA2',
            'OV_SHA2',
            'OV_SKIP_SHA2',
            'EV_SHA2',
        ];

        foreach ($productCodes as $code) {
            $this->assertStringContainsString(
                $code,
                $this->globalSignSource,
                "Product code {$code} should be present in GlobalSign source"
            );
        }
    }

    /**
     * Verify all order kinds are present.
     */
    public function testOrderKindsExist(): void
    {
        $this->assertStringContainsString("'OrderKind' => 'new'", $this->globalSignSource);
        $this->assertStringContainsString("'OrderKind' => 'renewal'", $this->globalSignSource);
    }

    /**
     * Verify the default validity period is 12 months.
     */
    public function testValidityPeriodDefault(): void
    {
        $this->assertStringContainsString("'Months' => '12'", $this->globalSignSource);
    }

    /**
     * Verify AuthToken structure is used in API calls.
     */
    public function testAuthTokenStructure(): void
    {
        $this->assertStringContainsString("'AuthToken'", $this->globalSignSource);
        $this->assertStringContainsString("'UserName' => \$this->username", $this->globalSignSource);
        $this->assertStringContainsString("'Password' => \$this->password", $this->globalSignSource);
    }

    /**
     * Verify wildcard support is implemented via BaseOption.
     */
    public function testWildcardBaseOption(): void
    {
        $this->assertStringContainsString("'BaseOption'] = 'wildcard'", $this->globalSignSource);
    }

    /**
     * Verify the Plugin class uses GenericEvent.
     */
    public function testPluginUsesGenericEvent(): void
    {
        $this->assertStringContainsString(
            'use Symfony\\Component\\EventDispatcher\\GenericEvent;',
            $this->pluginSource
        );
    }

    /**
     * Verify the Plugin class references GlobalSign class.
     */
    public function testPluginReferencesGlobalSign(): void
    {
        $this->assertStringContainsString(
            'use Detain\\MyAdminGlobalSign\\GlobalSign;',
            $this->pluginSource
        );
    }

    /**
     * Verify the Plugin defines event handler methods for the hooks.
     */
    public function testPluginDefinesEventHandlers(): void
    {
        $handlers = [
            'function getActivate',
            'function getMenu',
            'function getRequirements',
            'function getSettings',
        ];

        foreach ($handlers as $handler) {
            $this->assertStringContainsString(
                $handler,
                $this->pluginSource,
                "Plugin should define {$handler}"
            );
        }
    }

    /**
     * Verify the Plugin uses stopPropagation in getActivate.
     */
    public function testPluginStopsPropagation(): void
    {
        $this->assertStringContainsString(
            '$event->stopPropagation()',
            $this->pluginSource
        );
    }

    /**
     * Verify the Plugin defines settings for GlobalSign credentials.
     */
    public function testPluginDefinesCredentialSettings(): void
    {
        $this->assertStringContainsString('globalsign_username', $this->pluginSource);
        $this->assertStringContainsString('globalsign_password', $this->pluginSource);
        $this->assertStringContainsString('globalsign_test_username', $this->pluginSource);
        $this->assertStringContainsString('globalsign_test_password', $this->pluginSource);
    }

    /**
     * Verify the Plugin defines testing mode setting.
     */
    public function testPluginDefinesTestingModeSetting(): void
    {
        $this->assertStringContainsString('globalsign_testing', $this->pluginSource);
    }

    /**
     * Verify the Plugin defines out of stock setting.
     */
    public function testPluginDefinesOutOfStockSetting(): void
    {
        $this->assertStringContainsString('outofstock_globalsign_ssl', $this->pluginSource);
    }

    /**
     * Verify the GlobalSign source file has proper PHP opening tag.
     */
    public function testGlobalSignStartsWithPhpTag(): void
    {
        $this->assertStringStartsWith('<?php', $this->globalSignSource);
    }

    /**
     * Verify the Plugin source file has proper PHP opening tag.
     */
    public function testPluginStartsWithPhpTag(): void
    {
        $this->assertStringStartsWith('<?php', $this->pluginSource);
    }

    /**
     * Verify the source uses SHA256 hash algorithm for ReIssue.
     */
    public function testReIssueUsesSha256(): void
    {
        $this->assertStringContainsString("'HashAlgorithm' =>'SHA256'", $this->globalSignSource);
    }

    /**
     * Verify the source references StatisticClient for telemetry.
     */
    public function testStatisticClientUsage(): void
    {
        $this->assertStringContainsString('\\StatisticClient::tick(', $this->globalSignSource);
        $this->assertStringContainsString('\\StatisticClient::report(', $this->globalSignSource);
    }

    /**
     * Verify the user agent string is set.
     */
    public function testUserAgentString(): void
    {
        $this->assertStringContainsString(
            "public \$userAgent = 'MyAdmin GlobalSign Plugin'",
            $this->globalSignSource
        );
    }

    /**
     * Verify renewal methods include RenewalTargetOrderID parameter.
     */
    public function testRenewalTargetOrderIdPresent(): void
    {
        $this->assertStringContainsString('RenewalTargetOrderID', $this->globalSignSource);
    }

    /**
     * Verify the SSL type array mapping in Plugin.
     */
    public function testSslTypeArrayMapping(): void
    {
        $this->assertStringContainsString("'AlphaSSL'", $this->pluginSource);
        $this->assertStringContainsString("'DomainSSL'", $this->pluginSource);
        $this->assertStringContainsString("'OrganizationSSL'", $this->pluginSource);
        $this->assertStringContainsString("'ExtendedSSL'", $this->pluginSource);
    }

    /**
     * Verify ResendEmail uses APPROVEREMAIL type.
     */
    public function testResendEmailType(): void
    {
        $this->assertStringContainsString("'ResendEmailType' =>'APPROVEREMAIL'", $this->globalSignSource);
    }
}
