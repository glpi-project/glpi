<?php

/**
 * Autoloader Class
 *
 *  PHP Version 5
 *
 * @file      CAS/Autoload.php
 * @category  Authentication
 * @package   SimpleCAS
 * @author    Brett Bieber <brett.bieber@gmail.com>
 * @copyright 2008 Regents of the University of Nebraska
 * @license   http://www1.unl.edu/wdn/wiki/Software_License BSD License
 * @link      http://code.google.com/p/simplecas/
 **/

/**
 * Autoload a class
 *
 * @param string $class Classname to load
 *
 * @return bool
 */
function CAS_autoload($class)
{
    if (substr($class, 0, 4) !== 'CAS_') {
        return false;
    }
    $fp = @fopen(str_replace('_', '/', $class) . '.php', 'r', true);
    if ($fp) {
        fclose($fp);
        include str_replace('_', '/', $class) . '.php';
        if (!class_exists($class, false) && !interface_exists($class, false)) {
            die(
                new Exception(
                    'Class ' . $class . ' was not present in ' .
                    str_replace('_', '/', $class) . '.php (include_path="' .
                    get_include_path() .'") [CAS_autoload]'
                )
            );
        }
        return true;
    }
    $e = new Exception(
        'Class ' . $class . ' could not be loaded from ' .
        str_replace('_', '/', $class) . '.php, file does not exist (include_path="'
        . get_include_path() .'") [CAS_autoload]'
    );
    $trace = $e->getTrace();
    if (isset($trace[2]) && isset($trace[2]['function'])
        && in_array($trace[2]['function'], array('class_exists', 'interface_exists'))
    ) {
        return false;
    }
    if (isset($trace[1]) && isset($trace[1]['function'])
        && in_array($trace[1]['function'], array('class_exists', 'interface_exists'))
    ) {
        return false;
    }
    die ((string) $e);
}

// set up __autoload
if (function_exists('spl_autoload_register')) {
    if (!($_____t = spl_autoload_functions()) || !in_array('CAS_autoload', spl_autoload_functions())) {
        spl_autoload_register('CAS_autoload');
        if (function_exists('__autoload') && ($_____t === false)) {
            // __autoload() was being used, but now would be ignored, add
            // it to the autoload stack
            spl_autoload_register('__autoload');
        }
    }
    unset($_____t);
} elseif (!function_exists('__autoload')) {

    /**
     * Autoload a class
     *
     * @param string $class Class name
     *
     * @return bool
     */
    function __autoload($class)
    {
        return CAS_autoload($class);
    }
}

// set up include_path if it doesn't register our current location
$____paths = explode(PATH_SEPARATOR, get_include_path());
$____found = false;
foreach ($____paths as $____path) {
    if ($____path == dirname(dirname(__FILE__))) {
        $____found = true;
        break;
    }
}
if (!$____found) {
    set_include_path(dirname(dirname(__FILE__)) . PATH_SEPARATOR . get_include_path());
}
unset($____paths);
unset($____path);
unset($____found);

?>