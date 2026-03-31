---
name: plugin-hooks
description: Wires new MyAdmin plugin event hooks in src/Plugin.php using Symfony GenericEvent, getHooks() registration, and static handler methods. Use when user says 'add hook', 'new event handler', 'register plugin event', or modifies src/Plugin.php. Do NOT use for SOAP API methods in GlobalSign.php or CLI scripts in bin/.
---
# Plugin Hooks

Add or modify event hooks in `src/Plugin.php` for this MyAdmin plugin package. Hooks connect the plugin to the MyAdmin event system via Symfony's `GenericEvent`.

## Critical

- Every handler method **must** be `public static` and accept exactly one parameter: `GenericEvent $event`.
- Hook keys use dot-notation: `self::$module.'.eventname'` for module-scoped hooks, or a global key like `'function.requirements'` for cross-module hooks.
- Callbacks in `getHooks()` **must** use `[__CLASS__, 'methodName']` — never closures, never `$this`.
- Activate/reactivate handlers **must** guard with `if ($event['category'] == get_service_define('GLOBALSIGN'))` and call `$event->stopPropagation()` inside the guard. This prevents other SSL provider plugins from also processing the same event.
- Do NOT modify `src/GlobalSign.php` SOAP methods through this skill. This skill is only for `src/Plugin.php` hook wiring.
- After adding a hook, update `tests/unit/PluginClassTest.php` to cover the new hook key and handler method.

## Instructions

1. **Open `src/Plugin.php`** and identify the existing structure.
   - Namespace: `Detain\MyAdminGlobalSign`
   - Import: `use Symfony\Component\EventDispatcher\GenericEvent;`
   - Static properties: `$name`, `$description`, `$help`, `$module` (`'ssl'`), `$type` (`'service'`)
   - Verify `getHooks()` exists and returns an array before proceeding.

2. **Add the hook registration** in `getHooks()`. Insert a new entry in the returned array.
   - Module-scoped hook format: `self::$module.'.hookname' => [__CLASS__, 'getHandlerName']`
   - Global hook format: `'hook.name' => [__CLASS__, 'getHandlerName']`
   - Common module-scoped hooks: `activate`, `reactivate`, `settings`, `deactivate`, `change_ip`, `menu`
   - Common global hooks: `function.requirements`, `get_service_types`
   - Verify the handler method name you reference actually exists (or will exist after Step 3).

3. **Create the handler method** on the `Plugin` class.
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function getHandlerName(GenericEvent $event)
   {
       // Access the primary object passed to the event:
       $subject = $event->getSubject();

       // Access event data fields via array syntax:
       // $event['category'], $event['field1'], $event['field2']

       // Write back to event data:
       // $event['success'] = false;

       // For activate-type hooks, guard and stop propagation:
       if ($event['category'] == get_service_define('GLOBALSIGN')) {
           // ... handler logic ...
           $event->stopPropagation();
       }
   }
   ```
   - Method must be `public static`.
   - Parameter must be typed as `GenericEvent $event` (not just `$event`).
   - Use `myadmin_log(self::$module, $level, $message, __LINE__, __FILE__)` for logging.
   - Verify the method is placed inside the `Plugin` class and follows the PHPDoc format of existing methods.

4. **Update `tests/unit/PluginClassTest.php`** to cover the new hook.
   - Add the new hook key to `testGetHooksReturnsCorrectKeys()`:
     ```php
     $this->assertArrayHasKey('ssl.newhook', $hooks);
     ```
   - Add a mapping test verifying the callback target:
     ```php
     public function testGetHooksNewHookMapping(): void
     {
         $hooks = \Detain\MyAdminGlobalSign\Plugin::getHooks();
         $this->assertSame(
             [\Detain\MyAdminGlobalSign\Plugin::class, 'getHandlerName'],
             $hooks['ssl.newhook']
         );
     }
     ```
   - Add a signature test for the new handler method:
     ```php
     public function testGetHandlerNameSignature(): void
     {
         $method = $this->reflection->getMethod('getHandlerName');
         $this->assertTrue($method->isPublic());
         $this->assertTrue($method->isStatic());
         $params = $method->getParameters();
         $this->assertCount(1, $params);
         $this->assertSame('event', $params[0]->getName());
     }
     ```
   - Add the new method name to `testAllEventHandlerMethodsExist()`.
   - Verify tests pass:
     ```bash
     composer exec phpunit tests/unit/PluginClassTest.php
     ```

5. **Run the full test suite** to confirm nothing is broken.
   ```bash
   composer exec phpunit
   ```
   - Verify all tests pass before considering the task complete.

## Examples

### Adding an `ssl.deactivate` hook

**User says:** "Add a deactivate hook to the plugin"

**Actions taken:**

1. In `src/Plugin.php`, add to `getHooks()` return array:
   ```php
   self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
   ```

2. Add handler method to `Plugin` class:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function getDeactivate(GenericEvent $event)
   {
       if ($event['category'] == get_service_define('GLOBALSIGN')) {
           $serviceClass = $event->getSubject();
           myadmin_log(self::$module, 'info', 'GlobalSign Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
           // deactivation logic here
           $event->stopPropagation();
       }
   }
   ```

3. Update `tests/unit/PluginClassTest.php`:
   - Add `$this->assertArrayHasKey('ssl.deactivate', $hooks);` to `testGetHooksReturnsCorrectKeys`
   - Add mapping test and signature test for `getDeactivate`
   - Add `'getDeactivate'` to `testAllEventHandlerMethodsExist` array

4. Run tests:
   ```bash
   composer exec phpunit tests/unit/PluginClassTest.php
   ```

**Result:** New `ssl.deactivate` event is handled by `Plugin::getDeactivate()`, guarded by the GLOBALSIGN category check, with propagation stopped after handling.

### Adding a menu hook (already has method, just needs registration)

**User says:** "Register the getMenu method as a hook"

**Actions taken:**

1. Note that `getMenu()` already exists on the class but is NOT in `getHooks()`. Add:
   ```php
   self::$module.'.menu' => [__CLASS__, 'getMenu'],
   ```

2. Update tests to expect the new `ssl.menu` key.

3. Verify:
   ```bash
   composer exec phpunit
   ```

**Result:** `getMenu` is now dispatched when `ssl.menu` fires.

## Common Issues

**Hook handler not firing:**
1. Verify the hook key in `getHooks()` matches exactly what `run_event()` dispatches (e.g., `ssl.activate` not `ssl_activate`).
2. Check the category guard — `get_service_define('GLOBALSIGN')` must match `$event['category']`. If the constant is undefined, the guard silently fails.
3. Ensure another plugin hasn't already called `$event->stopPropagation()` on the same event.

**Test failure `Missing event handler: newMethod`:**
You added the method name to `testAllEventHandlerMethodsExist` but the method doesn't exist on the class yet. Add the method to `src/Plugin.php` first.

**Test failure `Failed asserting that an array has the key 'ssl.newhook'`:**
The hook key was added to the test but not to the `getHooks()` return array. Add the entry to `getHooks()` in `src/Plugin.php`.

**`Argument 1 passed to ... must be an instance of GenericEvent, array given`:**
The event system is dispatching a raw array instead of `GenericEvent`. This means `run_event()` was called without wrapping in `GenericEvent`. This is a caller-side issue, not a plugin issue — check how the event is dispatched.

**Static property count test fails after adding properties:**
If you add new static properties to `Plugin`, update `testStaticPropertyCount` in `tests/unit/PluginClassTest.php` to reflect the new count (currently expects 5).
