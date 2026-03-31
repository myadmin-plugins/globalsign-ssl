---
name: phpunit-tests
description: Creates PHPUnit 9 unit tests in tests/unit/ following the reflection-based and static analysis patterns from existing tests. Uses bootstrap stubs from tests/bootstrap.php. Use when user says 'add test', 'write test', 'test coverage', or adds new methods. Do NOT use for integration tests requiring live API credentials.
---
# PHPUnit Unit Tests

## Critical

- All unit tests go in `tests/unit/` — NEVER in `tests/` root (that's for integration tests requiring live API credentials).
- NEVER instantiate `GlobalSign` or `Plugin` directly in unit tests — the constructor triggers SOAP connections. Use `ReflectionClass` to inspect structure, or `file_get_contents()` for static analysis.
- The bootstrap at `tests/bootstrap.php` provides stub functions (`myadmin_log`, `obj2array`, `dialog`, `make_csr`, etc.) and a `StatisticClient` stub. All tests rely on this bootstrap — do not duplicate stubs.
- Run tests with `composer exec phpunit` (config: `phpunit.xml.dist`, bootstrap: `tests/bootstrap.php`).

## Instructions

### Step 1: Determine the test category

This project uses four distinct test patterns. Pick the one that matches your goal:

| Pattern | File | When to Use |
|---|---|---|
| **Reflection-based class structure** | `tests/unit/GlobalSignClassTest.php`, `tests/unit/PluginClassTest.php` | Verifying methods exist, signatures, visibility, property types |
| **Static analysis via source reading** | `tests/unit/GlobalSignStaticAnalysisTest.php` | Verifying source contains specific strings (WSDL URLs, product codes, auth patterns) |
| **Default property values** | `tests/unit/GlobalSignWsdlTest.php` | Verifying default values of class properties via `ReflectionClass::getDefaultProperties()` |
| **File/package structure** | `tests/unit/FileExistenceTest.php` | Verifying files exist, composer.json structure, directory layout |

Verify you've chosen the right pattern before proceeding.

### Step 2: Create the test file

Create file in `tests/unit/` with a descriptive name ending in `Test.php`. Follow this exact template:

```php
<?php
/**
 * {One-line description of what these tests verify}.
 *
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category SSL
 */

namespace Detain\MyAdminGlobalSign\Tests\Unit;

use PHPUnit\Framework\TestCase;
```

Add imports based on pattern:
- Reflection-based: `use ReflectionClass;` and optionally `use ReflectionMethod;`, `use ReflectionProperty;`
- Static analysis: no additional imports needed
- Default values: `use ReflectionClass;`

Verify the namespace is exactly `Detain\MyAdminGlobalSign\Tests\Unit` and the class extends `PHPUnit\Framework\TestCase`.

### Step 3: Set up the class with setUp()

Every test class uses `setUp()` to initialize shared state. Use the exact patterns from existing tests:

**For reflection-based tests:**
```php
class {Name}Test extends TestCase
{
    /** @var ReflectionClass */
    private $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(\Detain\MyAdminGlobalSign\GlobalSign::class);
    }
}
```

**For static analysis tests:**
```php
class {Name}Test extends TestCase
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
}
```

**For file existence tests:**
```php
class {Name}Test extends TestCase
{
    /** @var string */
    private $packageRoot;

    protected function setUp(): void
    {
        $this->packageRoot = dirname(__DIR__, 2);
    }
}
```

**For default property value tests:**
```php
class {Name}Test extends TestCase
{
    /** @var array */
    private $defaults;

    protected function setUp(): void
    {
        $ref = new ReflectionClass(\Detain\MyAdminGlobalSign\GlobalSign::class);
        $this->defaults = $ref->getDefaultProperties();
    }
}
```

Verify: Use fully-qualified class names with leading backslash (`\Detain\MyAdminGlobalSign\GlobalSign::class`) — the tests do NOT use `use` imports for the classes under test.

### Step 4: Write test methods

Follow these conventions exactly:

1. **Method naming**: `testDescriptiveName` in camelCase. Examples from codebase:
   - `testClassExists`, `testClassNamespace`, `testClassIsInstantiable`
   - `testConstructorSignature`, `testDVOrderSignature`
   - `testProductionWsdlUrls`, `testAuthTokenStructure`
   - `testPublicApiMethodsExist`, `testSoapClientPropertiesExist`

2. **Return type**: Always `void` — `public function testMethodName(): void`

3. **DocBlock**: Every test method gets a single-line `/** Verify ... */` docblock.

4. **Assertions by pattern**:

   **Reflection — checking methods exist and are public:**
   ```php
   public function testMethodExists(): void
   {
       $expectedMethods = ['methodA', 'methodB'];
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
   ```

   **Reflection — checking method signatures:**
   ```php
   public function testMethodSignature(): void
   {
       $method = $this->reflection->getMethod('methodName');
       $params = $method->getParameters();
       $this->assertCount(3, $params);
       $this->assertSame('paramName', $params[0]->getName());
       $this->assertTrue($params[2]->isDefaultValueAvailable());
       $this->assertFalse($params[2]->getDefaultValue());
   }
   ```

   **Reflection — checking properties:**
   ```php
   public function testPropertyVisibility(): void
   {
       $prop = $this->reflection->getProperty('propName');
       $this->assertTrue($prop->isPublic());
       // or: $this->assertTrue($prop->isPrivate());
       // for static: $this->assertTrue($prop->isStatic());
   }
   ```

   **Static analysis — checking source contains strings:**
   ```php
   public function testSourceContainsPattern(): void
   {
       $this->assertStringContainsString(
           'expected string',
           $this->globalSignSource
       );
   }
   ```

   **Default values:**
   ```php
   public function testDefaultValue(): void
   {
       $this->assertSame('expected', $this->defaults['propertyName']);
       // or: $this->assertFalse($this->defaults['boolProp']);
   }
   ```

5. **Batch assertions**: When checking multiple similar items (e.g., "all these methods exist"), use a `foreach` loop with a failure message that includes the item name.

Verify: Each test method tests exactly one concern. Method names start with `test`.

### Step 5: Run and validate

```bash
composer exec phpunit tests/unit/
```

To run a single test file:
```bash
composer exec phpunit tests/unit/GlobalSignClassTest.php
```

To run with coverage:
```bash
composer exec phpunit -- --coverage-text
```

Verify all tests pass with zero errors and zero warnings. The `phpunit.xml.dist` has `failOnRisky="true"` and `failOnWarning="true"`, so tests that produce warnings or are flagged as risky will fail.

## Examples

### Example: User adds a new public method `cancelOrder()` to `src/GlobalSign.php`

**User says:** "Add tests for the new cancelOrder method"

**Actions taken:**
1. Read `src/GlobalSign.php` to find the `cancelOrder` method signature.
2. Determine this needs reflection-based testing (method existence + signature).
3. Add tests to the existing `tests/unit/GlobalSignClassTest.php` or create a new focused test file.

**If adding to existing file** (`tests/unit/GlobalSignClassTest.php`):
- Add `'cancelOrder'` to the `$expectedMethods` array in `testPublicApiMethodsExist()`
- Add a new signature test:

```php
/**
 * Verify cancelOrder method signature.
 */
public function testCancelOrderSignature(): void
{
    $method = $this->reflection->getMethod('cancelOrder');
    $params = $method->getParameters();
    $this->assertCount(1, $params);
    $this->assertSame('orderId', $params[0]->getName());
    $this->assertTrue($method->isPublic());
}
```

**If the method uses a new product code or SOAP pattern**, also add a static analysis test in `tests/unit/GlobalSignStaticAnalysisTest.php`:

```php
/**
 * Verify cancelOrder uses the ModifyOrder SOAP action.
 */
public function testCancelOrderSoapAction(): void
{
    $this->assertStringContainsString('ModifyOrder', $this->globalSignSource);
}
```

**Result:** Run `composer exec phpunit tests/unit/` — all tests pass.

### Example: User adds a new source file `src/CertificateValidator.php`

**User says:** "Write tests for the new CertificateValidator class"

**Actions taken:**
1. Read `src/CertificateValidator.php` to understand its structure.
2. Create `tests/unit/CertificateValidatorClassTest.php` with reflection-based tests.
3. Add file existence check to `tests/unit/FileExistenceTest.php`.

**New file** `tests/unit/CertificateValidatorClassTest.php`:
```php
<?php
/**
 * Unit tests for the CertificateValidator class structure.
 *
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category SSL
 */

namespace Detain\MyAdminGlobalSign\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CertificateValidatorClassTest extends TestCase
{
    /** @var ReflectionClass */
    private $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(\Detain\MyAdminGlobalSign\CertificateValidator::class);
    }

    /**
     * Verify the class exists and is instantiable.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(\Detain\MyAdminGlobalSign\CertificateValidator::class));
        $this->assertTrue($this->reflection->isInstantiable());
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

    // ... additional method/property tests based on the class
}
```

**Add to** `tests/unit/FileExistenceTest.php`:
```php
public function testCertificateValidatorSourceExists(): void
{
    $this->assertFileExists($this->packageRoot . '/src/CertificateValidator.php');
}
```

## Common Issues

### `Class "Detain\MyAdminGlobalSign\GlobalSign" not found`
The Composer autoloader isn't loaded. Verify:
1. `composer install` has been run in the package root.
2. `phpunit.xml.dist` has `bootstrap="tests/bootstrap.php"`.
3. `tests/bootstrap.php` correctly resolves the autoload path (`__DIR__ . '/../vendor/autoload.php'`).

### `Class "StatisticClient" not found`
The `tests/bootstrap.php` stub for `StatisticClient` wasn't loaded. This happens if you run PHPUnit without the bootstrap. Fix: always run via `composer exec phpunit` which reads `phpunit.xml.dist`, or pass `--bootstrap tests/bootstrap.php`.

### `SoapFault: SOAP-ERROR: Parsing WSDL` or connection timeouts
You are instantiating `GlobalSign` directly — this triggers SOAP connections. Unit tests must NEVER instantiate `GlobalSign`. Use `ReflectionClass` or `file_get_contents()` instead.

### `This test did not perform any assertions` (risky test warning)
The `phpunit.xml.dist` has `failOnRisky="true"`. Every test method must contain at least one assertion. Empty test methods or tests that only call code without asserting will fail.

### Test not discovered by PHPUnit
1. File must be in `tests/unit/` directory.
2. Filename must end with `Test.php`.
3. Class must extend `PHPUnit\Framework\TestCase`.
4. Method must be `public` and start with `test`.
5. Namespace must be `Detain\MyAdminGlobalSign\Tests\Unit`.

### `Undefined function myadmin_log()` or other global function errors
A function used by source code isn't stubbed in `tests/bootstrap.php`. Add a no-op stub following the existing pattern:
```php
if (!function_exists('your_function')) {
    function your_function($param1, $param2)
    {
        // no-op in test environment
    }
}
```

### `Undefined constant` errors
A constant used by source code isn't defined in `tests/bootstrap.php`. Add it:
```php
if (!defined('YOUR_CONSTANT')) {
    define('YOUR_CONSTANT', 'default_value');
}
```
