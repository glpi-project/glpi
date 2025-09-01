<?php declare(strict_types = 1);

$ignoreErrors = [];

/*
 * The following error will occur only on specific PHP versions.
 * Unfortunately, it is not possible to ignore it using a @phpstan-ignore,
 * so we handle it here with a `reportUnmatched' => false` flag to ease the
 * results handling between different PHP versions.
 */

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$newvalue of function ini_set expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/dashboard.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of function ini_set expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/ajax/dashboard.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$newvalue of function ini_set expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/central.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of function ini_set expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/central.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$newvalue of function ini_set expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/planning.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of function ini_set expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/planning.php',
];


// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$mon of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/report.infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/report.infocom.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/report.infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/report.infocom.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$mon of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.item.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.item.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.item.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$mon of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.location.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.location.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.location.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.location.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$mon of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.tracking.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.tracking.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.tracking.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/front/stat.tracking.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$port of class mysqli constructor expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/install/install.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$port of class mysqli constructor expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/install/install.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$str of function addslashes expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.x_to_0.85.0.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function addslashes expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/install/migrations/update_0.84.x_to_0.85.0.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$str of function strtolower expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent/Communication/AbstractRequest.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strtolower expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent/Communication/AbstractRequest.php',
];

// Only reported in PHP < 8.1
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$result of static method AuthLDAP\\:\\:get_entries_clean\\(\\) expects array, resource given\\.$#',
	'identifier' => 'argument.type',
	'count' => 5,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$hour of function gmmktime expects int, \\(string\\|false\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$min of function gmmktime expects int, \\(string\\|false\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$sec of function gmmktime expects int, \\(string\\|false\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$mon of function gmmktime expects int, \\(string\\|false\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function gmmktime expects int, \\(string\\|false\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function gmmktime expects int, \\(string\\|false\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$result_identifier of function ldap_get_entries expects resource, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$hour of function gmmktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$minute of function gmmktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$second of function gmmktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function gmmktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function gmmktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function gmmktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$result of function ldap_get_entries expects resource, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];
// alternative result reported in PHP 8.1+
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$result of function ldap_get_entries expects LDAP\\\\Result, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/AuthLDAP.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$mon of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 8,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 8,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 8,
	'path' => __DIR__ . '/src/Cartridge.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 8,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 8,
	'path' => __DIR__ . '/src/Cartridge.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 8,
	'path' => __DIR__ . '/src/Cartridge.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array_arg of function sort contains unresolvable type\\.$#',
	'identifier' => 'argument.unresolvableType',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function sort contains unresolvable type\\.$#',
	'identifier' => 'argument.unresolvableType',
	'count' => 1,
	'path' => __DIR__ . '/src/Config.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$file of function file_put_contents expects string, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Build/CompileScssCommand.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$filename of function file_put_contents expects string, array given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Console/Build/CompileScssCommand.php',
];

// Reported in PHP 8.x but not in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$value of method mysqli\\:\\:options\\(\\) expects int\\|string, true given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysql.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array_arg of function current expects array\\|object, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array_arg of function key expects array\\|object, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function current expects array\\|object, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function key expects array\\|object, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/DBmysqlIterator.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$mon of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dashboard/Provider.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dashboard/Provider.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Dashboard/Provider.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
    'message' => '#^Strict comparison using \\=\\=\\= between \\(float\\|false\\) and \'0\' will always evaluate to false\\.$#',
    'identifier' => 'identical.alwaysFalse',
    'count' => 1,
    'path' => __DIR__ . '/src/Dropdown.php',
];
// alternative result reported in PHP 8.4
$ignoreErrors[] = [
    'message' => '#^Strict comparison using \\=\\=\\= between float and \'0\' will always evaluate to false\\.$#',
    'identifier' => 'identical.alwaysFalse',
    'count' => 1,
    'path' => __DIR__ . '/src/Dropdown.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of function str_pad expects string, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$mon of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function str_pad expects string, float\\|int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Html.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Html.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$mon of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Infocom.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Infocom.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#6 \\$year of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/Infocom.php',
];

// Reported in PHP 7.4 but not in PHP 8.4
$ignoreErrors[] = [
    'message' => '#^Argument of an invalid type null supplied for foreach, only iterables are supported\\.$#',
    'identifier' => 'foreach.nonIterable',
    'count' => 1,
    'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array_arg of function current expects array\\|object, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$input of function array_keys expects array, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$var of function count expects array\\|Countable, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function current expects array\\|object, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_keys expects array, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$var of function count expects array\\|Countable, Iterator given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Rack.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, Iterator given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Item_Rack.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$slm of static method LevelAgreement\\:\\:showForSLM\\(\\) expects SLM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$start of method Calendar\\:\\:getActiveTimeBetween\\(\\) expects string, DateTime given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$time of function strtotime expects string, DateTime given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$slm of static method LevelAgreement\\:\\:showForSLM\\(\\) expects SLM, CommonGLPI given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$start of method Calendar\\:\\:getActiveTimeBetween\\(\\) expects string, DateTime given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$datetime of function strtotime expects string, DateTime given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/LevelAgreement.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$var of function count expects array\\|Countable, Iterator given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU_Rack.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, Iterator given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/PDU_Rack.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$timestamp of function date expects int, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$timestamp of function date expects int\\|null, float given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Planning.php',
];

// Reported in PHP 8.4 but not in PHP 7.4
$ignoreErrors[] = [
    'message' => '#^Call to function is_array\\(\\) with list\\<string\\> will always evaluate to true\\.$#',
    'identifier' => 'function.alreadyNarrowedType',
    'count' => 1,
    'path' => __DIR__ . '/src/Rack.php',
];

// Only reported in PHP < 8.1
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$result of static method AuthLDAP\\:\\:get_entries_clean\\(\\) expects array, resource given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/RuleRightCollection.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array_arg of function usort contains unresolvable type\\.$#',
	'identifier' => 'argument.unresolvableType',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function usort contains unresolvable type\\.$#',
	'identifier' => 'argument.unresolvableType',
	'count' => 1,
	'path' => __DIR__ . '/src/RSSFeed.php',
];

// Reported in PHP 8.x but not in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of function call_user_func expects callable\\(\\)\\: mixed, array\\{class\\-string, \'getDefaultSearchReq…\'\\} given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
    'message' => '#^Strict comparison using \\!\\=\\= between 100\\|float\\|string\\|false and null will always evaluate to true\\.$#',
    'identifier' => 'notIdentical.alwaysTrue',
    'count' => 1,
    'path' => __DIR__ . '/src/Search.php',
];
// alternative result reported in PHP 8.4
$ignoreErrors[] = [
    'message' => '#^Strict comparison using \\!\\=\\= between 100\\|float\\|string and null will always evaluate to true\\.$#',
    'identifier' => 'notIdentical.alwaysTrue',
    'count' => 1,
    'path' => __DIR__ . '/src/Search.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$character_mask of function ltrim expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$characters of function ltrim expects string, int given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Search.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$mon of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#4 \\$month of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$day of function mktime expects int\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Stat.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\<" between \d{5} and 80000 is always false\\.$#',
	'identifier' => 'smaller.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];
// Reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Comparison operation "\\<" between int\\<\d{5}, \d{5}\\> and 80000 is always false\\.$#',
	'identifier' => 'smaller.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/src/Toolbox.php',
];

// Reported in PHP 7.4
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$x_size of function imagecreatetruecolor expects int\\<1, max\\>, \\(float\\|int\\<min, 0\\>\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$y_size of function imagecreatetruecolor expects int\\<1, max\\>, \\(float\\|int\\<min, 0\\>\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
// alternative result reported in PHP 8.x
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$width of function imagecreatetruecolor expects int\\<1, max\\>, \\(float\\|int\\<min, 0\\>\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$height of function imagecreatetruecolor expects int\\<1, max\\>, \\(float\\|int\\<min, 0\\>\\) given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
// reported in PHP 8.5
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];
$ignoreErrors[] = [
	'message' => '#^Right side of && is always true\\.$#',
	'identifier' => 'booleanAnd.rightAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/UploadHandler.php',
];

// Only reported in PHP < 8.1
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$result of static method AuthLDAP\\:\\:get_entries_clean\\(\\) expects array, resource given\\.$#',
	'identifier' => 'argument.type',
	'count' => 3,
	'path' => __DIR__ . '/src/User.php',
];

$ignoreErrors = array_map(
    function (array $specs): array {
        $specs['reportUnmatched'] = false;
        return $specs;
    },
    $ignoreErrors
);

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
