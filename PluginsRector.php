<?php

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Configuration\RectorConfigBuilder;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

/**
 * Shared rector baseline for GLPI plugins.
 *
 * Plugins usage:
 *
 *     $baseline = require __DIR__ . '/../../PluginsRector.php';
 *     return $baseline([__DIR__ . '/src', __DIR__ . '/tests']);
 *
 * Do not call `->withPaths()` again on the returned builder.
 * `withPaths()` overwrites what `withRootFiles()` append and so would drop the root files.
 *
 * @param string[] $paths
 */
return static fn (array $paths): RectorConfigBuilder => RectorConfig::configure()
    ->withPaths($paths)
    ->withRootFiles()
    ->withCache(
        cacheDirectory: 'var/rector',
        cacheClass: FileCacheStorage::class,
    )
    ->withParallel(timeoutSeconds: 300)
    ->withImportNames(removeUnusedImports: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
    )
    // withPhpVersion() intentionally not called
    // both (withPhpSets and withPhpVersion) will resolve the PHP version from plugin own composer.json
    ->withPhpSets()
    ->withSkip([
        // GLPI plugins receive request data as strings ($_POST, $_GET, CommonDBTM::$input).
        // `strict_types=1` turns scalar coercion that return runtime TypeErrors.
        SafeDeclareStrictTypesRector::class,
    ])
;
