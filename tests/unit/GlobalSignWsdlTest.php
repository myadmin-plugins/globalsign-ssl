<?php
/**
 * Unit tests for GlobalSign WSDL property default values.
 * Uses ReflectionClass to read default property values without
 * constructing the class (which would require SOAP connections).
 *
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category SSL
 */

namespace Detain\MyAdminGlobalSign\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class GlobalSignWsdlTest extends TestCase
{
    /** @var array */
    private $defaults;

    protected function setUp(): void
    {
        $ref = new ReflectionClass(\Detain\MyAdminGlobalSign\GlobalSign::class);
        $this->defaults = $ref->getDefaultProperties();
    }

    /**
     * Verify the production functions WSDL URL.
     */
    public function testProductionFunctionsWsdl(): void
    {
        $this->assertSame(
            'https://system.globalsign.com/kb/ws/v1/ServerSSLService?wsdl',
            $this->defaults['functionsWsdl']
        );
    }

    /**
     * Verify the production query WSDL URL.
     */
    public function testProductionQueryWsdl(): void
    {
        $this->assertSame(
            'https://system.globalsign.com/kb/ws/v1/GASService?wsdl',
            $this->defaults['queryWsdl']
        );
    }

    /**
     * Verify the production account WSDL URL.
     */
    public function testProductionAccountWsdl(): void
    {
        $this->assertSame(
            'https://system.globalsign.com/kb/ws/v1/AccountService?wsdl',
            $this->defaults['accountWsdl']
        );
    }

    /**
     * Verify the test functions WSDL URL.
     */
    public function testTestFunctionsWsdl(): void
    {
        $this->assertSame(
            'https://test-gcc.globalsign.com/kb/ws/v1/ServerSSLService?wsdl',
            $this->defaults['testFunctionsWsdl']
        );
    }

    /**
     * Verify the test query WSDL URL.
     */
    public function testTestQueryWsdl(): void
    {
        $this->assertSame(
            'https://test-gcc.globalsign.com/kb/ws/v1/GASService?wsdl',
            $this->defaults['testQueryWsdl']
        );
    }

    /**
     * Verify the test account WSDL URL.
     */
    public function testTestAccountWsdl(): void
    {
        $this->assertSame(
            'https://test-gcc.globalsign.com/kb/ws/v1/AccountService?wsdl',
            $this->defaults['testAccountWsdl']
        );
    }

    /**
     * Verify the default testing flag is false.
     */
    public function testDefaultTestingFlag(): void
    {
        $this->assertFalse($this->defaults['testing']);
    }

    /**
     * Verify the default connection timeout value.
     */
    public function testDefaultConnectionTimeout(): void
    {
        $this->assertSame(1000, $this->defaults['connectionTimeout']);
    }

    /**
     * Verify the default user agent string.
     */
    public function testDefaultUserAgent(): void
    {
        $this->assertSame('MyAdmin GlobalSign Plugin', $this->defaults['userAgent']);
    }

    /**
     * Verify the default trace connections setting.
     */
    public function testDefaultTraceConnections(): void
    {
        $this->assertSame(1, $this->defaults['traceConnections']);
    }

    /**
     * Verify username defaults to empty string.
     */
    public function testDefaultUsername(): void
    {
        $this->assertSame('', $this->defaults['username']);
    }

    /**
     * Verify password defaults to empty string.
     */
    public function testDefaultPassword(): void
    {
        $this->assertSame('', $this->defaults['password']);
    }

    /**
     * Verify all production WSDLs point to system.globalsign.com.
     */
    public function testProductionWsdlsShareHost(): void
    {
        $productionWsdls = [
            $this->defaults['functionsWsdl'],
            $this->defaults['queryWsdl'],
            $this->defaults['accountWsdl'],
        ];

        foreach ($productionWsdls as $wsdl) {
            $this->assertStringContainsString(
                'system.globalsign.com',
                $wsdl
            );
        }
    }

    /**
     * Verify all test WSDLs point to test-gcc.globalsign.com.
     */
    public function testTestWsdlsShareHost(): void
    {
        $testWsdls = [
            $this->defaults['testFunctionsWsdl'],
            $this->defaults['testQueryWsdl'],
            $this->defaults['testAccountWsdl'],
        ];

        foreach ($testWsdls as $wsdl) {
            $this->assertStringContainsString(
                'test-gcc.globalsign.com',
                $wsdl
            );
        }
    }

    /**
     * Verify all WSDLs use HTTPS.
     */
    public function testAllWsdlsUseHttps(): void
    {
        $wsdls = [
            $this->defaults['functionsWsdl'],
            $this->defaults['queryWsdl'],
            $this->defaults['accountWsdl'],
            $this->defaults['testFunctionsWsdl'],
            $this->defaults['testQueryWsdl'],
            $this->defaults['testAccountWsdl'],
        ];

        foreach ($wsdls as $wsdl) {
            $this->assertStringStartsWith('https://', $wsdl);
        }
    }

    /**
     * Verify all WSDLs end with ?wsdl.
     */
    public function testAllWsdlsEndWithWsdlParam(): void
    {
        $wsdls = [
            $this->defaults['functionsWsdl'],
            $this->defaults['queryWsdl'],
            $this->defaults['accountWsdl'],
            $this->defaults['testFunctionsWsdl'],
            $this->defaults['testQueryWsdl'],
            $this->defaults['testAccountWsdl'],
        ];

        foreach ($wsdls as $wsdl) {
            $this->assertStringEndsWith('?wsdl', $wsdl);
        }
    }
}
