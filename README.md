# MyAdmin GlobalSign SSL Plugin

[![Tests](https://github.com/detain/myadmin-globalsign-ssl/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-globalsign-ssl/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-globalsign-ssl/version)](https://packagist.org/packages/detain/myadmin-globalsign-ssl)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-globalsign-ssl/downloads)](https://packagist.org/packages/detain/myadmin-globalsign-ssl)
[![License](https://poser.pugx.org/detain/myadmin-globalsign-ssl/license)](https://packagist.org/packages/detain/myadmin-globalsign-ssl)

A MyAdmin plugin that integrates with the GlobalSign SOAP API to provide automated SSL certificate ordering, renewal, and lifecycle management. Supports AlphaSSL, DomainSSL, OrganizationSSL, and ExtendedSSL certificate types with both standard and wildcard options.

## Features

- Automated SSL certificate provisioning via GlobalSign SOAP API
- Support for DV (Domain Validated), OV (Organization Validated), and EV (Extended Validation) certificates
- Wildcard certificate support across all validation levels
- Certificate renewal with automatic order validation
- CSR decoding and approver email management
- Configurable test/production mode switching
- Event-driven architecture using Symfony EventDispatcher

## Installation

Install with Composer:

```sh
composer require detain/myadmin-globalsign-ssl
```

## Configuration

The plugin exposes the following settings through the MyAdmin settings interface:

| Setting | Description |
|---------|-------------|
| `globalsign_username` | API username for production |
| `globalsign_password` | API password for production |
| `globalsign_test_username` | API username for test environment |
| `globalsign_test_password` | API password for test environment |
| `globalsign_testing` | Enable/disable test mode |
| `outofstock_globalsign_ssl` | Enable/disable sales |

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](https://opensource.org/licenses/LGPL-2.1) license.
