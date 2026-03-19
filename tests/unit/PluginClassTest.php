<?php
/**
 * Unit tests for the Plugin class structure, hooks, and event handler signatures.
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

class PluginClassTest extends TestCase
{
    /** @var ReflectionClass */
    private $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(\Detain\MyAdminGlobalSign\Plugin::class);
    }

    /**
     * Verify the Plugin class exists.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(\Detain\MyAdminGlobalSign\Plugin::class));
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
     * Verify the class is instantiable.
     */
    public function testClassIsInstantiable(): void
    {
        $this->assertTrue($this->reflection->isInstantiable());
    }

    /**
     * Verify the $name static property exists and has the correct value.
     */
    public function testNameProperty(): void
    {
        $prop = $this->reflection->getProperty('name');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertSame('GlobalSign SSL', $prop->getDefaultValue());
    }

    /**
     * Verify the $description static property exists.
     */
    public function testDescriptionProperty(): void
    {
        $prop = $this->reflection->getProperty('description');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertIsString($prop->getDefaultValue());
        $this->assertNotEmpty($prop->getDefaultValue());
    }

    /**
     * Verify the $help static property exists.
     */
    public function testHelpProperty(): void
    {
        $prop = $this->reflection->getProperty('help');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertIsString($prop->getDefaultValue());
    }

    /**
     * Verify the $module static property is 'ssl'.
     */
    public function testModuleProperty(): void
    {
        $prop = $this->reflection->getProperty('module');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertSame('ssl', $prop->getDefaultValue());
    }

    /**
     * Verify the $type static property is 'service'.
     */
    public function testTypeProperty(): void
    {
        $prop = $this->reflection->getProperty('type');
        $this->assertTrue($prop->isPublic());
        $this->assertTrue($prop->isStatic());
        $this->assertSame('service', $prop->getDefaultValue());
    }

    /**
     * Verify the constructor exists and takes no parameters.
     */
    public function testConstructorSignature(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Verify getHooks is a public static method returning an array.
     */
    public function testGetHooksIsStaticAndPublic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $this->assertCount(0, $method->getParameters());
    }

    /**
     * Verify getHooks returns the expected hook keys.
     */
    public function testGetHooksReturnsCorrectKeys(): void
    {
        $hooks = \Detain\MyAdminGlobalSign\Plugin::getHooks();
        $this->assertIsArray($hooks);
        $this->assertArrayHasKey('ssl.activate', $hooks);
        $this->assertArrayHasKey('ssl.reactivate', $hooks);
        $this->assertArrayHasKey('ssl.settings', $hooks);
        $this->assertArrayHasKey('function.requirements', $hooks);
    }

    /**
     * Verify getHooks maps activate and reactivate to getActivate.
     */
    public function testGetHooksActivateMappings(): void
    {
        $hooks = \Detain\MyAdminGlobalSign\Plugin::getHooks();
        $this->assertSame(
            [\Detain\MyAdminGlobalSign\Plugin::class, 'getActivate'],
            $hooks['ssl.activate']
        );
        $this->assertSame(
            [\Detain\MyAdminGlobalSign\Plugin::class, 'getActivate'],
            $hooks['ssl.reactivate']
        );
    }

    /**
     * Verify getHooks maps settings hook to getSettings.
     */
    public function testGetHooksSettingsMapping(): void
    {
        $hooks = \Detain\MyAdminGlobalSign\Plugin::getHooks();
        $this->assertSame(
            [\Detain\MyAdminGlobalSign\Plugin::class, 'getSettings'],
            $hooks['ssl.settings']
        );
    }

    /**
     * Verify getHooks maps requirements hook to getRequirements.
     */
    public function testGetHooksRequirementsMapping(): void
    {
        $hooks = \Detain\MyAdminGlobalSign\Plugin::getHooks();
        $this->assertSame(
            [\Detain\MyAdminGlobalSign\Plugin::class, 'getRequirements'],
            $hooks['function.requirements']
        );
    }

    /**
     * Verify all hook callbacks are callable method references.
     */
    public function testAllHookCallbacksAreValidMethodReferences(): void
    {
        $hooks = \Detain\MyAdminGlobalSign\Plugin::getHooks();
        foreach ($hooks as $hookName => $callback) {
            $this->assertIsArray($callback, "Callback for {$hookName} should be an array");
            $this->assertCount(2, $callback, "Callback for {$hookName} should have 2 elements");
            $this->assertTrue(
                $this->reflection->hasMethod($callback[1]),
                "Method {$callback[1]} should exist on Plugin class for hook {$hookName}"
            );
        }
    }

    /**
     * Verify getActivate method signature accepts GenericEvent.
     */
    public function testGetActivateSignature(): void
    {
        $method = $this->reflection->getMethod('getActivate');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
    }

    /**
     * Verify getMenu method signature accepts GenericEvent.
     */
    public function testGetMenuSignature(): void
    {
        $method = $this->reflection->getMethod('getMenu');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
    }

    /**
     * Verify getRequirements method signature.
     */
    public function testGetRequirementsSignature(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
    }

    /**
     * Verify getSettings method signature.
     */
    public function testGetSettingsSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
        $params = $method->getParameters();
        $this->assertCount(1, $params);
    }

    /**
     * Verify all event handler methods exist on the class.
     */
    public function testAllEventHandlerMethodsExist(): void
    {
        $handlers = ['getActivate', 'getMenu', 'getRequirements', 'getSettings'];
        foreach ($handlers as $handler) {
            $this->assertTrue(
                $this->reflection->hasMethod($handler),
                "Missing event handler: {$handler}"
            );
        }
    }

    /**
     * Verify that all static properties have the expected count.
     */
    public function testStaticPropertyCount(): void
    {
        $staticProps = array_filter(
            $this->reflection->getProperties(ReflectionProperty::IS_STATIC),
            fn(\ReflectionProperty $p) => $p->getDeclaringClass()->getName() === \Detain\MyAdminGlobalSign\Plugin::class
        );
        $this->assertCount(5, $staticProps);
    }
}
