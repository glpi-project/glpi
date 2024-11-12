<?php declare(strict_types = 1);

$ignoreErrors = [];

if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    /*
     * The following error will occur only on PHP >= 8.2.
     * Unfortunately, it is not possible to ignore it using a @phpstan-ignore, so we have to handle it here.
     */
    $ignoreErrors[] = [
        'message' => '/If condition is always false./',
        'path' => __DIR__ . '/src/CommonITILObject.php',
        'reportUnmatched' => false,
    ];
}

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
