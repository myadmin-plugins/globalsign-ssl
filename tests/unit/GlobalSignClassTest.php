<?php
/**
 * Unit tests for the GlobalSign class structure and properties.
 *
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category SSL
 */

namespace Detain\MyAdminGlobalSign\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class GlobalSignClassTest extends TestCase
{
    /** @var ReflectionClass */
    private $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(\Detain\MyAdminGlobalSign\GlobalSign::class);
    }

    /**
     * Verify the GlobalSign class exists and is instantiable.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(\Detain\MyAdminGlobalSign\GlobalSign::class));
    }

    /**
     * Verify the class resides in the correct namespace.
     */
    public function testClassNamespace(): void
    {
        $this->assertSame(
            'Detain\\MyAdminGlobalSign',
            $this->reflection->getNamespaceName()
        );
    }

    /**
     * Verify the class is not abstract and not an interface.
     */
    public function testClassIsInstantiable(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
        $this->assertFalse($this->reflection->isInterface());
        $this->assertTrue($this->reflection->isInstantiable());
    }

    /**
     * Verify all expected public WSDL properties exist.
     */
    public function testWsdlPropertiesExist(): void
    {
        $expectedProperties = [
            'functionsWsdl',
            'queryWsdl',
            'accountWsdl',
            'testFunctionsWsdl',
            'testQueryWsdl',
            'testAccountWsdl',
        ];

        foreach ($expectedProperties as $prop) {
            $this->assertTrue(
                $this->reflection->hasProperty($prop),
                "Missing property: {$prop}"
            );
            $this->assertTrue(
                $this->reflection->getProperty($prop)->isPublic(),
                "Property {$prop} should be public"
            );
        }
    }

    /**
     * Verify the private credential properties exist.
     */
    public function testCredentialPropertiesArePrivate(): void
    {
        $prop = $this->reflection->getProperty('username');
        $this->assertTrue($prop->isPrivate(), 'username should be private');

        $prop = $this->reflection->getProperty('password');
        $this->assertTrue($prop->isPrivate(), 'password should be private');
    }

    /**
     * Verify the testing property exists and is public.
     */
    public function testTestingPropertyExists(): void
    {
        $prop = $this->reflection->getProperty('testing');
        $this->assertTrue($prop->isPublic());
    }

    /**
     * Verify the connectionTimeout property exists and is public.
     */
    public function testConnectionTimeoutPropertyExists(): void
    {
        $prop = $this->reflection->getProperty('connectionTimeout');
        $this->assertTrue($prop->isPublic());
    }

    /**
     * Verify the SOAP client properties exist.
     */
    public function testSoapClientPropertiesExist(): void
    {
        $expected = ['functionsClient', 'accountClient', 'queryClient'];
        foreach ($expected as $prop) {
            $this->assertTrue(
                $this->reflection->hasProperty($prop),
                "Missing SOAP client property: {$prop}"
            );
        }
    }

    /**
     * Verify the userAgent property exists and is public.
     */
    public function testUserAgentPropertyExists(): void
    {
        $prop = $this->reflection->getProperty('userAgent');
        $this->assertTrue($prop->isPublic());
    }

    /**
     * Verify the extra property exists and is public.
     */
    public function testExtraPropertyExists(): void
    {
        $prop = $this->reflection->getProperty('extra');
        $this->assertTrue($prop->isPublic());
    }

    /**
     * Verify the constructor accepts the expected parameters.
     */
    public function testConstructorSignature(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('username', $params[0]->getName());
        $this->assertSame('password', $params[1]->getName());
        $this->assertSame('testing', $params[2]->getName());
        $this->assertTrue($params[2]->isDefaultValueAvailable());
        $this->assertFalse($params[2]->getDefaultValue());
    }

    /**
     * Verify all expected public API methods exist.
     */
    public function testPublicApiMethodsExist(): void
    {
        $expectedMethods = [
            'queryCall',
            'functionsCall',
            'accountCall',
            'soapCall',
            'GetOrderByOrderID',
            'GetOrderByDateRange',
            'GetCertificateOrders',
            'ValidateOrderParameters',
            'GetDVApproverList',
            'renewValidateOrderParameters',
            'ResendEmail',
            'ChangeApproverEmail',
            'ReIssue',
            'DVOrder',
            'DVOrderWithoutCSR',
            'OVOrder',
            'OVOrderWithoutCSR',
            'EVOrder',
            'create_alphassl',
            'create_domainssl',
            'create_domainssl_autocsr',
            'create_organizationssl',
            'create_organizationssl_autocsr',
            'create_extendedssl',
            'renewAlphaDomain',
            'renewDVOrder',
            'renewOVOrder',
            'renewOrganizationSSL',
            'renewEVOrder',
            'renewExtendedSSL',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $this->reflection->hasMethod($method),
                "Missing method: {$method}"
            );
            $this->assertTrue(
                $this->reflection->getMethod($method)->isPublic(),
                "Method {$method} should be public"
            );
        }
    }

    /**
     * Verify the total count of public methods matches expectations.
     */
    public function testPublicMethodCount(): void
    {
        $publicMethods = array_filter(
            $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn(ReflectionMethod $m) => $m->getDeclaringClass()->getName() === \Detain\MyAdminGlobalSign\GlobalSign::class
        );
        $this->assertGreaterThanOrEqual(30, count($publicMethods));
    }

    /**
     * Verify the soapCall method signature.
     */
    public function testSoapCallSignature(): void
    {
        $method = $this->reflection->getMethod('soapCall');
        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('client', $params[0]->getName());
        $this->assertTrue($params[0]->isPassedByReference());
        $this->assertSame('function', $params[1]->getName());
        $this->assertSame('params', $params[2]->getName());
    }

    /**
     * Verify DVOrder method has the correct parameter count.
     */
    public function testDVOrderSignature(): void
    {
        $method = $this->reflection->getMethod('DVOrder');
        $params = $method->getParameters();
        $this->assertCount(10, $params);
        $this->assertSame('wildcard', $params[9]->getName());
        $this->assertTrue($params[9]->isDefaultValueAvailable());
        $this->assertFalse($params[9]->getDefaultValue());
    }

    /**
     * Verify EVOrder method has the correct parameter count.
     */
    public function testEVOrderSignature(): void
    {
        $method = $this->reflection->getMethod('EVOrder');
        $params = $method->getParameters();
        $this->assertCount(13, $params);
    }

    /**
     * Verify OVOrder method has the correct parameter count.
     */
    public function testOVOrderSignature(): void
    {
        $method = $this->reflection->getMethod('OVOrder');
        $params = $method->getParameters();
        $this->assertCount(14, $params);
        $this->assertSame('wildcard', $params[13]->getName());
    }

    /**
     * Verify create_alphassl method signature.
     */
    public function testCreateAlphaSslSignature(): void
    {
        $method = $this->reflection->getMethod('create_alphassl');
        $params = $method->getParameters();
        $this->assertCount(8, $params);
        $this->assertSame('fqdn', $params[0]->getName());
        $this->assertSame('csr', $params[1]->getName());
        $this->assertSame('wildcard', $params[7]->getName());
        $this->assertFalse($params[7]->getDefaultValue());
    }

    /**
     * Verify renewAlphaDomain method signature.
     */
    public function testRenewAlphaDomainSignature(): void
    {
        $method = $this->reflection->getMethod('renewAlphaDomain');
        $params = $method->getParameters();
        $this->assertCount(10, $params);
        $this->assertSame('oldOrderId', $params[9]->getName());
    }

    /**
     * Verify renewExtendedSSL method signature.
     */
    public function testRenewExtendedSslSignature(): void
    {
        $method = $this->reflection->getMethod('renewExtendedSSL');
        $params = $method->getParameters();
        $this->assertCount(14, $params);
        $this->assertSame('oldOrderId', $params[13]->getName());
    }

    /**
     * Verify GetCertificateOrders has all optional parameters.
     */
    public function testGetCertificateOrdersSignature(): void
    {
        $method = $this->reflection->getMethod('GetCertificateOrders');
        $params = $method->getParameters();
        $this->assertCount(4, $params);
        foreach ($params as $param) {
            $this->assertTrue(
                $param->isDefaultValueAvailable(),
                "Parameter {$param->getName()} should have a default value"
            );
            $this->assertSame('', $param->getDefaultValue());
        }
    }
}
