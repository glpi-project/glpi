<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config
    ->addPathsToScan([
        'ajax',
        'front',
        'inc',
        'install',
        'stubs',
        // 'src' Loaded from the autoloader
    ], false)

    ->ignoreUnknownClasses(['DB', 'DbTestCase'])

    // Ignore errors on extensions that are suggested but not required
    ->ignoreErrorsOnExtensionAndPaths('ext-exif', [
        'src/Document.php',
        'src/UploadHandler.php',
    ], [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnExtensionAndPaths('ext-pcntl', ['src/CronTask.php'], [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnExtensionAndPaths('ext-posix', ['front/cron.php', 'src/Glpi/Console/Application.php'], [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnExtension('ext-ldap', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnExtension('ext-sodium', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnExtension('ext-zend-opcache', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('paragonie/sodium_compat', [ErrorType::UNUSED_DEPENDENCY])

    // Only loaded in a conditional block that checks if the environment is dev
    ->ignoreErrorsOnPackages([
        'symfony/debug-bundle',
        'symfony/twig-bundle',
        'symfony/web-profiler-bundle',
    ], [ErrorType::DEV_DEPENDENCY_IN_PROD])

    ->ignoreErrorsOnExtension('ext-bcmath', [ErrorType::UNUSED_DEPENDENCY]) // Required by tc-lib-barcode
    ->ignoreErrorsOnExtension('ext-tokenizer', [ErrorType::UNUSED_DEPENDENCY]) // Required by symfony/routing
    ->ignoreErrorsOnPackages([
        'apereo/phpcas', // Not detected because the library doesn't have an autoloader
        'bacon/bacon-qr-code', // Used by TwoFactorAuth as suggested dependency
        'laminas/laminas-mime', // Required by laminas-mail
        'league/html-to-markdown', // Required by twig/markdown-extra
        'phpdocumentor/reflection-docblock', // Required by phpdocumentor/type-resolver
        'symfony/css-selector', // Required by web tests based on the `FrontBaseClass` class
        'symfony/polyfill-ctype',
        'symfony/polyfill-iconv',
        'symfony/polyfill-php83',
        'symfony/polyfill-php84',
        'symfony/property-access',
        'symfony/polyfill-mbstring',
    ], [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnExtension('ext-iconv', [ErrorType::UNUSED_DEPENDENCY]) // Required by Safe/iconv()
    ->ignoreErrorsOnExtension('ext-zlib', [ErrorType::UNUSED_DEPENDENCY]) // Required by Safe/gzcompress() Safe::gzuncompress()

    ->disableReportingUnmatchedIgnores()
;
