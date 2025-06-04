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

// Reported in PHP 7.4 but not in PHP 8.4
$ignoreErrors[] = [
    'message' => '#^Argument of an invalid type null supplied for foreach, only iterables are supported\\.$#',
    'identifier' => 'foreach.nonIterable',
    'count' => 1,
    'path' => __DIR__ . '/src/Inventory/Asset/NetworkPort.php',
];

// Reported in PHP 8.4 but not in PHP 7.4
$ignoreErrors[] = [
    'message' => '#^Call to function is_array\\(\\) with list\\<string\\> will always evaluate to true\\.$#',
    'identifier' => 'function.alreadyNarrowedType',
    'count' => 1,
    'path' => __DIR__ . '/src/Rack.php',
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

$ignoreErrors = array_map(
    function (array $specs): array {
        $specs['reportUnmatched'] = false;
        return $specs;
    },
    $ignoreErrors
);

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
