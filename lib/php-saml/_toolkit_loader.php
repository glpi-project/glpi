<?php

// Create an __autoload function 
// (can conflicts other autoloaders)
// http://php.net/manual/en/language.oop5.autoload.php

$libDir = dirname(__FILE__) . '/lib/Saml2/';
$extlibDir = dirname(__FILE__) . '/extlib/';

// Load composer
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
}

// Load now external libs
require_once $extlibDir . 'xmlseclibs/xmlseclibs.php';

$folderInfo = scandir($libDir);

foreach ($folderInfo as $element) {
    if (is_file($libDir.$element) && (substr($element, -4) === '.php')) {
        include_once $libDir.$element;
    }
}
    
