---
name: globalsign-api
description: Adds new GlobalSign SOAP API methods to src/GlobalSign.php following the soapCall() pattern with AuthToken, StatisticClient tick/report, and SoapFault handling. Use when user says 'add API method', 'new SOAP call', 'add GlobalSign endpoint', or modifies src/GlobalSign.php. Do NOT use for Plugin hook changes.
---
# GlobalSign SOAP API Method

## Critical

- **Every SOAP request** must include `'AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]` in the request header. Never hardcode credentials.
- **Never call `$client->__soapCall()` directly** from a public method. Always go through one of the three dispatcher methods: `$this->queryCall()`, `$this->functionsCall()`, or `$this->accountCall()`. These delegate to `soapCall()` which handles `StatisticClient::tick()`/`report()` and `SoapFault` catching.
- **Choose the correct SOAP client** based on the GlobalSign endpoint:
  - `functionsCall()` → certificate orders, approvals, reissue (`ServerSSLService?wsdl`)
  - `queryCall()` → queries, validation, date-range searches (`GASService?wsdl`)
  - `accountCall()` → account/billing operations (`AccountService?wsdl`)
- **Store params in `$this->extra`** before the SOAP call: `$this->extra['{MethodName}_params'] = $params;`
- **Product codes** are string constants: `DV_LOW_SHA2`, `DV_SHA2`, `DV_SKIP_SHA2`, `OV_SHA2`, `OV_SKIP_SHA2`, `EV_SHA2`. For wildcard certs, add `'BaseOption' => 'wildcard'` to `OrderRequestParameter`.

## Instructions

### 1. Determine the SOAP operation and client

Identify which GlobalSign WSDL the operation belongs to by checking the GlobalSign API documentation or existing methods in `src/GlobalSign.php`:

| Client | Dispatcher | Operations |
|--------|-----------|------------|
| `functionsClient` | `$this->functionsCall()` | `DVOrder`, `OVOrder`, `EVOrder`, `DVOrderWithoutCSR`, `OVOrderWithoutCSR`, `GetDVApproverList`, `ResendEmail`, `ChangeApproverEmail` |
| `queryClient` | `$this->queryCall()` | `GetOrderByOrderID`, `GetOrderByDateRange`, `GetCertificateOrders`, `ValidateOrderParameters`, `ReIssue` |
| `accountClient` | `$this->accountCall()` | Account/billing operations (`AccountSnapshot`, `QueryInvoices`, etc.) |

**Verify:** Confirm the operation name matches the WSDL before proceeding.

### 2. Determine the request header type

GlobalSign uses two header key names depending on the endpoint:

- **Query/validation operations** use `QueryRequestHeader`:
  ```php
  'QueryRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]]
  ```
- **Order/mutation operations** use `OrderRequestHeader`:
  ```php
  'OrderRequestHeader' => ['AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]]
  ```

Check existing methods that use the same client to confirm which header key applies.

**Verify:** The header key matches what other methods using the same dispatcher use.

### 3. Write the method with PHPDoc

Add the method to `src/GlobalSign.php` inside the `GlobalSign` class. Follow this exact structure:

```php
/**
 * Brief description of what the API call does
 *
 * @param string $paramName description
 * @return mixed
 */
public function MethodName($param1, $param2, $optionalParam = '')
{
    $params = [
        'MethodName' => [
            'Request' => [
                'QueryRequestHeader' => [
                    'AuthToken' => [
                        'UserName' => $this->username,
                        'Password' => $this->password
                    ]
                ],
                'SomeField' => $param1,
                'AnotherField' => $param2
    ]]];
    if ($optionalParam != '') {
        $params['MethodName']['Request']['OptionalField'] = $optionalParam;
    }
    $this->extra['MethodName_params'] = $params;
    return $this->queryCall('MethodName', $params);
}
```

Key details:
- The outer array key **must match** the SOAP operation name exactly (case-sensitive)
- Nest everything under `'Request'`
- Use `obj2array()` on the return value only for query methods that return complex nested objects (see `GetOrderByOrderID` as reference)
- For order methods that need extended timeouts, add before the call:
  ```php
  ini_set('max_execution_time', 1000);
  ini_set('default_socket_timeout', 1000);
  ```
- Optional: add `myadmin_log('ssl', 'info', ...)` calls before the SOAP call for debugging

**Verify:** The `$params` array structure has the operation name as outer key, `'Request'` as second key, and the correct header type as third key.

### 4. Handle wildcard support (if applicable)

If the method deals with certificate orders and should support wildcards:

```php
public function NewMethod($product, $fqdn, $csr, $wildcard = false)
{
    // ... build $params ...
    if ($wildcard === true) {
        $params['NewMethod']['Request']['OrderRequestParameter']['BaseOption'] = 'wildcard';
    }
    // ...
}
```

Always use strict `=== true` comparison for the `$wildcard` parameter. Make `$wildcard` the **last** parameter with a default of `false`.

**Verify:** Wildcard parameter is last, defaults to `false`, and uses `=== true`.

### 5. Write a high-level workflow method (if applicable)

If the new API call is part of a multi-step workflow (validate → approve → order), write a `create_*` or `renew*` method following the existing pattern in `create_alphassl()` / `create_domainssl()`:

```php
public function create_newtype($fqdn, $csr, $firstname, $lastname, $phone, $email, $approverEmail, $wildcard = false)
{
    $product = 'PRODUCT_CODE';
    $res = $this->ValidateOrderParameters($product, $fqdn, $csr, $wildcard);
    $this->extra = [];
    $this->extra['laststep'] = 'ValidateOrderParameters';
    $this->extra['ValidateOrderParameters'] = obj2array($res);
    if ($res->Response->OrderResponseHeader->SuccessCode != 0) {
        $this->extra['error'] = 'Error In order';
    }
    $this->__construct($this->username, $this->password);  // reinitialize SOAP clients
    // ... next step ...
    return $this->extra;
}
```

Critical pattern: call `$this->__construct($this->username, $this->password)` between multi-step SOAP calls to reinitialize the SOAP clients. Check `SuccessCode != 0` for errors.

**Verify:** Each step stores results in `$this->extra`, checks `SuccessCode`, and reinitializes before the next SOAP call.

### 6. Add test coverage

Add a reflection-based test to `tests/unit/GlobalSignClassTest.php` verifying the method exists and has the correct signature:

```php
public function testNewMethodSignature(): void
{
    $method = $this->reflection->getMethod('NewMethod');
    $params = $method->getParameters();
    $this->assertCount(3, $params);  // adjust count
    $this->assertSame('param1', $params[0]->getName());
    // verify optional params have defaults
    $this->assertTrue($params[2]->isDefaultValueAvailable());
}
```

Also update `testPublicApiMethodsExist()` to include the new method name in `$expectedMethods`.

**Verify:** Run `vendor/bin/phpunit tests/unit/GlobalSignClassTest.php` — all tests pass.

### 7. Update public method count test

In `tests/unit/GlobalSignClassTest.php`, check `testPublicMethodCount()`. If the `assertGreaterThanOrEqual` threshold needs updating after adding methods, increment it.

**Verify:** Run `vendor/bin/phpunit tests/unit` — full unit suite passes.

## Examples

### User says: "Add a method to decode a CSR via the GlobalSign API"

**Actions:**

1. Identify `DecodeCSR` is a query operation → uses `queryCall()` with `QueryRequestHeader`
2. Add to `src/GlobalSign.php`:

```php
/**
 * Decode a CSR and return its details
 *
 * @param string $csr the CSR to decode
 * @param string $productType optional product type
 * @return mixed
 */
public function DecodeCSR($csr, $productType = '')
{
    $params = [
        'DecodeCSR' => [
            'Request' => [
                'QueryRequestHeader' => [
                    'AuthToken' => [
                        'UserName' => $this->username,
                        'Password' => $this->password
                    ]
                ],
                'CSR' => $csr
    ]]];
    if ($productType != '') {
        $params['DecodeCSR']['Request']['ProductType'] = $productType;
    }
    $this->extra['DecodeCSR_params'] = $params;
    return $this->queryCall('DecodeCSR', $params);
}
```

3. Add `'DecodeCSR'` to `$expectedMethods` in `testPublicApiMethodsExist()`
4. Add signature test:

```php
public function testDecodeCSRSignature(): void
{
    $method = $this->reflection->getMethod('DecodeCSR');
    $params = $method->getParameters();
    $this->assertCount(2, $params);
    $this->assertSame('csr', $params[0]->getName());
    $this->assertTrue($params[1]->isDefaultValueAvailable());
}
```

5. Run `vendor/bin/phpunit tests/unit` — passes.

**Result:** New `DecodeCSR()` method in `src/GlobalSign.php` following the exact `soapCall()` pattern, with test coverage.

## Common Issues

**If you see `SoapFault: Function ("MethodName") is not a valid method for this service`:**
1. The operation name doesn't match the WSDL. Check the exact operation name in the WSDL: `curl -s 'https://system.globalsign.com/kb/ws/v1/GASService?wsdl' | grep 'operation name'`
2. You may be calling the wrong client — verify `functionsCall` vs `queryCall` vs `accountCall`

**If you see `SoapFault: looks like we got no XML document`:**
1. The SOAP client connection timed out. Increase `$this->connectionTimeout` or add `ini_set('default_socket_timeout', 1000)` before the call
2. For testing mode, verify the test WSDL URL is reachable: `https://test-gcc.globalsign.com/kb/ws/v1/`

**If `SuccessCode` is `-1` with `AuthToken` errors:**
1. Verify credentials constants are defined: `GLOBALSIGN_USERNAME`, `GLOBALSIGN_PASSWORD`
2. For test mode: `GLOBALSIGN_TEST_USERNAME`, `GLOBALSIGN_TEST_PASSWORD`
3. Check that `$this->testing` is set correctly — constructor uses it to pick prod vs test WSDLs

**If tests fail with `Class 'StatisticClient' not found`:**
1. Ensure `tests/bootstrap.php` is loaded (configured in `phpunit.xml.dist`)
2. The bootstrap defines a stub `StatisticClient` class when the real one isn't available

**If `$this->extra` is missing data after a multi-step workflow:**
1. Check that you're not resetting `$this->extra = []` between steps unless intentional
2. The `__construct()` re-call between steps does NOT reset `$this->extra` — it only reinitializes SOAP clients

**If the `$params` array outer key doesn't match the SOAP function name:**
1. The key in `$params` (e.g., `'DVOrder'`) MUST exactly match the string passed to `functionsCall('DVOrder', $params)`. A mismatch causes silent failures or wrong responses.