<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

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
return static fn(array $paths): RectorConfigBuilder => RectorConfig::configure()
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
