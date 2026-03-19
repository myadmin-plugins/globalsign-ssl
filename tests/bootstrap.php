<?php
/**
 * PHPUnit bootstrap file for myadmin-globalsign-ssl tests.
 *
 * Provides autoloading and stubs for global functions used by the
 * source code that are not available in an isolated test environment.
 */

// Autoload via Composer if available
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];

foreach ($autoloadPaths as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
        break;
    }
}

// Define constants used by the source code if not already defined
if (!defined('WSDL_CACHE_BOTH')) {
    define('WSDL_CACHE_BOTH', 3);
}
if (!defined('STATISTICS_SERVER')) {
    define('STATISTICS_SERVER', '');
}
if (!defined('GLOBALSIGN_USERNAME')) {
    define('GLOBALSIGN_USERNAME', 'test_user');
}
if (!defined('GLOBALSIGN_PASSWORD')) {
    define('GLOBALSIGN_PASSWORD', 'test_pass');
}
if (!defined('GLOBALSIGN_TESTING')) {
    define('GLOBALSIGN_TESTING', 'true');
}
if (!defined('GLOBALSIGN_TEST_USERNAME')) {
    define('GLOBALSIGN_TEST_USERNAME', 'test_user');
}
if (!defined('GLOBALSIGN_TEST_PASSWORD')) {
    define('GLOBALSIGN_TEST_PASSWORD', 'test_pass');
}
if (!defined('OUTOFSTOCK_GLOBALSIGN_SSL')) {
    define('OUTOFSTOCK_GLOBALSIGN_SSL', '0');
}

// Stub global functions used by the source code
if (!function_exists('myadmin_log')) {
    function myadmin_log($module, $level, $message, $line = 0, $file = '', $module2 = '', $id = 0)
    {
        // no-op in test environment
    }
}

if (!function_exists('obj2array')) {
    function obj2array($obj)
    {
        $out = [];
        foreach ($obj as $key => $val) {
            switch (true) {
                case is_object($val):
                    $out[$key] = obj2array($val);
                    break;
                case is_array($val):
                    $out[$key] = obj2array($val);
                    break;
                default:
                    $out[$key] = $val;
                    break;
            }
        }
        return $out;
    }
}

if (!function_exists('dialog')) {
    function dialog($title, $message)
    {
        // no-op in test environment
    }
}

if (!function_exists('myadmin_stringify')) {
    function myadmin_stringify($data)
    {
        return json_encode($data);
    }
}

if (!function_exists('get_service_define')) {
    function get_service_define($name)
    {
        return $name;
    }
}

if (!function_exists('run_event')) {
    function run_event($event, $default = false, $module = '')
    {
        return $default;
    }
}

if (!function_exists('get_module_settings')) {
    function get_module_settings($module)
    {
        return [];
    }
}

if (!function_exists('ensure_csr')) {
    function ensure_csr($id)
    {
        return ['csr' => 'test-csr'];
    }
}

if (!function_exists('_')) {
    function _($text)
    {
        return $text;
    }
}

if (!function_exists('make_csr')) {
    function make_csr($fqdn, $email, $city, $state, $country, $company, $department)
    {
        return ['csr-data', 'cert-data', 'key-data'];
    }
}

// Pre-load StatisticClient from vendor if available to prevent
// the require_once in GlobalSign.php from conflicting
$statisticClientPath = __DIR__ . '/../../../workerman/statistics/Applications/Statistics/Clients/StatisticClient.php';
if (!class_exists('StatisticClient', false)) {
    if (file_exists($statisticClientPath)) {
        require_once $statisticClientPath;
    } else {
        class StatisticClient
        {
            public static function tick($module, $function)
            {
            }

            public static function report($module, $function, $success, $code = 0, $msg = '', $server = '')
            {
            }
        }
    }
}
