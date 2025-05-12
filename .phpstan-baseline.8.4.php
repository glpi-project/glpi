<?php declare(strict_types = 1);

if (preg_match('/^8\.4\./', PHP_VERSION) !== 1) {
    return ['parameters' => ['ignoreErrors' => []]];
}

/*
 * The following error will occur only on PHP 8.4.
 * Unfortunately, it is not possible to ignore it using a @phpstan-ignore, so we have to handle it here.
 */

$ignoreErrors = [];

$ignoreErrors[] = [
	'message' => '#^Function openssl_pkey_get_details is unsafe to use\\. It can return FALSE instead of throwing an exception\\. Please add \'use function Safe\\\\openssl_pkey_get_details;\' at the beginning of the file to use the variant provided by the \'thecodingmachine/safe\' library\\.$#',
	'identifier' => 'theCodingMachineSafe.function',
	'count' => 1,
	'path' => __DIR__ . '/src/Glpi/OAuth/Server.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
