<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Method NetworkPort\\:\\:post_clone\\(\\) with return type void returns int\\|false but should not return anything\\.$#',
	'identifier' => 'return.void',
	'count' => 1,
	'path' => __DIR__ . '/src/NetworkPort.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
