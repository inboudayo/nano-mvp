<?php

##################
# CONFIGURATION  #
##################

// display all page variables, warnings & errors
define('DEVELOPMENT', false);

// default timezone
// date_default_timezone_set("America/Chicago");

// database connection (optional)
define('DB_HOST', null);
define('DB_NAME', null);
define('DB_USER', null);
define('DB_PASS', null);

// only allow cookie based sessions
ini_set('session.use_only_cookies', true);
ini_set('session.cookie_lifetime', 0); // 0 = expire when browser is closed

// require cookies to use a secure connection
// ini_set('session.cookie_secure', true);

##################
# GLOBALS/HOOKS  #
##################

// any code that needs to be executed before routing, such as instantiating a user object
// this is called in Router()->route()
trait GlobalRepository {
    private function checkRepository()
    {
    }
}

##################
# INITIALIZATION #
##################

// website title - appended to page title
define('SITE_TITLE', $_SERVER['SERVER_NAME']);

// absolute public path
define('PUBLIC_PATH', dirname($_SERVER['SCRIPT_FILENAME']));

// absolute private path
define('PRIVATE_PATH', dirname(__FILE__));

// relative base path (should be reflected in .htaccess)
if ($_SERVER['DOCUMENT_ROOT'] == PUBLIC_PATH) {
    define('BASE_PATH', '/');
} else {
    define('BASE_PATH', dirname($_SERVER['SCRIPT_NAME']) . '/');
}

// base URL
$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
define('BASE_URL', $protocol . $_SERVER['SERVER_NAME'] . BASE_PATH);

if (DEVELOPMENT) {
    // report all errors
    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', 1);
}

// start script execution time (ends in View)
define('EXE_TIME_START', microtime(true));

// autoload classes
spl_autoload_register(function($class) {
    // convert namespaces to directory paths
    $file = __DIR__ . DIRECTORY_SEPARATOR;
    $file .= str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (is_readable($file)) {
        require($file);
    }
});

// start session
session_start();

// route request
(new framework\Router)->route();
