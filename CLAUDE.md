# MyAdmin GlobalSign SSL Plugin

Integrates GlobalSign SOAP API for SSL certificate ordering, renewal, and lifecycle management within the MyAdmin billing system.

## Commands

```bash
composer install                          # install dependencies
composer exec phpunit                     # run unit tests (config: phpunit.xml.dist)
composer exec phpunit tests/unit          # run unit suite only
composer exec phpunit -- --coverage-text  # run with coverage report
```

```bash
# Run a specific test file
composer exec phpunit tests/unit/GlobalSignClassTest.php
composer exec phpunit tests/unit/PluginClassTest.php
```

```bash
# Verify SOAP extension is loaded (required by this plugin)
php -m | grep soap
# Check composer autoload is up to date
composer dump-autoload
```

## Architecture

**Namespace**: `Detain\MyAdminGlobalSign\` → `src/` (PSR-4 via `composer.json`)

**Core classes**:
- `src/GlobalSign.php` — SOAP API client. Wraps three SOAP clients (`functionsClient`, `queryClient`, `accountClient`) for GlobalSign endpoints. Handles order creation, renewal, CSR validation, approver lists, and certificate queries.
- `src/Plugin.php` — MyAdmin plugin. Registers event hooks via `getHooks()`, handles SSL activation in `getActivate()`, settings in `getSettings()`, menu in `getMenu()`.

**SOAP endpoints** (prod `system.globalsign.com` / test `test-gcc.globalsign.com`):
- `ServerSSLService?wsdl` → `functionsClient` — certificate orders (`DVOrder`, `OVOrder`, `EVOrder`, renewals)
- `GASService?wsdl` → `queryClient` — queries (`GetOrderByOrderID`, `GetCertificateOrders`, `GetDVApproverList`)
- `AccountService?wsdl` → `accountClient` — account operations

**CLI scripts** (`bin/`):
- `bin/GetDVApproverList.php` — fetch DV approver emails for a domain
- `bin/list_certs.php` — list all certificate orders
- `bin/make_cert.php` — interactive CSR generation
- `bin/parse_ssl_extra.php` — parse `ssl_extra` field from DB

**Tests** (`tests/`):
- `tests/bootstrap.php` — autoloader + stub functions (`myadmin_log`, `obj2array`, `dialog`, `make_csr`, etc.) + `StatisticClient` stub
- `tests/unit/` — unit tests (configured in `phpunit.xml.dist` as `unit` suite)
  - `tests/unit/FileExistenceTest.php` — validates source files and `composer.json` structure
  - `tests/unit/GlobalSignClassTest.php` — reflection-based API method/property verification
  - `tests/unit/GlobalSignStaticAnalysisTest.php` — source content assertions (WSDL URLs, product codes, auth tokens)
  - `tests/unit/GlobalSignWsdlTest.php` — WSDL default property values
  - `tests/unit/PluginClassTest.php` — plugin hook wiring and method signatures
- `tests/GlobalSignTest.php` — integration tests (requires live API credentials via `tests/.env`)

## Key Patterns

**SOAP calls**: All API calls go through `soapCall(&$client, $function, $params)` which handles `\StatisticClient::tick()`/`report()`, `SoapFault` catching, and `myadmin_log()`.

**Auth token**: Every SOAP request includes `'AuthToken' => ['UserName' => $this->username, 'Password' => $this->password]`.

**Product codes**: `DV_LOW_SHA2`, `DV_SHA2`, `DV_SKIP_SHA2`, `OV_SHA2`, `OV_SKIP_SHA2`, `EV_SHA2` — with `'BaseOption' => 'wildcard'` for wildcard certs.

**Plugin hooks** registered in `Plugin::getHooks()`:
- `ssl.activate` / `ssl.reactivate` → `getActivate()` — creates or renews certs based on `$event['field1']` (`DV_LOW`, `DV_SKIP`, `OV_SKIP`, `EV`)
- `ssl.settings` → `getSettings()` — registers admin settings (`globalsign_username`, `globalsign_password`, etc.)
- `function.requirements` → `getRequirements()`

**SSL type mapping** in `Plugin::getActivate()`: `['AlphaSSL' => 1, 'DomainSSL' => 2, 'OrganizationSSL' => 3, 'ExtendedSSL' => 4, ...]`

**Order flow**: Check `GetOrderByOrderID()` → if order status 4 and cert not expired → renew (`renewAlphaDomain`/`renewOrganizationSSL`/`renewExtendedSSL`), else new order (`create_alphassl`/`create_domainssl`/`create_organizationssl`/`create_extendedssl`).

## Conventions

- PHP `ext-soap` required — all API communication via `\SoapClient`
- Logging: `myadmin_log($module, $level, $message, __LINE__, __FILE__)`
- Statistics: `\StatisticClient::tick()` before SOAP call, `\StatisticClient::report()` after
- Test stubs defined in `tests/bootstrap.php` — mock `myadmin_log`, `obj2array`, `dialog`, `get_service_define`, `run_event`, `get_module_settings`, `ensure_csr`, `make_csr`
- Credentials via constants: `GLOBALSIGN_USERNAME`, `GLOBALSIGN_PASSWORD`, `GLOBALSIGN_TESTING`
- `composer.json` type is `myadmin-plugin` with `detain/myadmin-plugin-installer`
- CI: `.travis.yml` (PHP 5.4–7.1), `.scrutinizer.yml`, `.codeclimate.yml`, `.bettercodehub.yml`
- License: LGPL-2.1-only

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->

<!-- caliber:managed:sync -->
## Context Sync

This project uses [Caliber](https://github.com/caliber-ai-org/ai-setup) to keep AI agent configs in sync across Claude Code, Cursor, Copilot, and Codex.
Configs update automatically before each commit via `caliber refresh`.
If the pre-commit hook is not set up, run `/setup-caliber` to configure everything automatically.
<!-- /caliber:managed:sync -->
