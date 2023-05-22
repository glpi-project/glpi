<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
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

namespace Glpi\System;

use Glpi\System\Requirement\DataDirectoriesProtectedPath;
use Glpi\System\Requirement\DbEngine;
use Glpi\System\Requirement\DbTimezones;
use Glpi\System\Requirement\DirectoriesWriteAccess;
use Glpi\System\Requirement\DirectoryWriteAccess;
use Glpi\System\Requirement\Extension;
use Glpi\System\Requirement\ExtensionConstant;
use Glpi\System\Requirement\ExtensionGroup;
use Glpi\System\Requirement\InstallationNotOverriden;
use Glpi\System\Requirement\IntegerSize;
use Glpi\System\Requirement\LogsWriteAccess;
use Glpi\System\Requirement\MemoryLimit;
use Glpi\System\Requirement\MysqliMysqlnd;
use Glpi\System\Requirement\PhpSupportedVersion;
use Glpi\System\Requirement\PhpVersion;
use Glpi\System\Requirement\SafeDocumentRoot;
use Glpi\System\Requirement\SeLinux;
use Glpi\System\Requirement\SessionsConfiguration;
use Glpi\System\Requirement\SessionsSecurityConfiguration;

/**
 * @since 9.5.0
 */
class RequirementsManager
{
    /**
     * Returns core requirement list.
     *
     * @param \DBmysql $db  DB instance (if null BD requirements will not be returned).
     *
     * @return RequirementsList
     */
    public function getCoreRequirementList(\DBmysql $db = null): RequirementsList
    {
        $requirements = [];

        $requirements[] = new PhpVersion(GLPI_MIN_PHP, GLPI_MAX_PHP);

        $requirements[] = new SessionsConfiguration();

        $requirements[] = new MemoryLimit(64 * 1024 * 1024);

        $requirements[] = new MysqliMysqlnd();

        // Mandatory PHP extensions that are defaultly enabled but can be disabled
        $requirements[] = new ExtensionGroup(
            __('PHP core extensions'),
            [
                'dom',
                'fileinfo',
                'filter',
                'libxml',
                'json',
                'simplexml',
                'xmlreader', // required/used by simplepie/simplepie and sabre/xml
                'xmlwriter', // required/used by sabre/xml
            ]
        );

        // Mandatory PHP extensions that are NOT defaultly enabled
        $requirements[] = new Extension(
            'curl',
            false,
            __('Required for remote access to resources (inventory agent requests, marketplace, RSS feeds, ...).')
        );
        $requirements[] = new Extension(
            'gd',
            false,
            __('Required for images handling.')
        );
        $requirements[] = new Extension(
            'intl',
            false,
            __('Required for internationalization.')
        );
        $requirements[] = new Extension(
            'zlib',
            false,
            __('Required for handling of compressed communication with inventory agents, installation of gzip packages from marketplace and PDF generation.')
        );
        $requirements[] = new ExtensionConstant(
            __('Sodium ChaCha20-Poly1305 size constant'),
            'SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES',
            false,
            __('Enable usage of ChaCha20-Poly1305 encryption required by GLPI. This is provided by libsodium 1.0.12 and newer.')
        );

        if ($db instanceof \DBmysql) {
            $requirements[] = new DbEngine($db);
        }

        $requirements[] = new InstallationNotOverriden($db);

        global $PHPLOGGER;
        $requirements[] = new LogsWriteAccess($PHPLOGGER);

        $requirements[] = new DirectoriesWriteAccess(
            __('Permissions for GLPI data directories'),
            array_filter(
                Variables::getDataDirectories(),
                function ($directory) {
                    return $directory !== GLPI_LOG_DIR; // Specifically checked by LogsWriteAccess requirement
                }
            )
        );

        $requirements[] = new SeLinux();

        // Below requirements are optionals

        $requirements[] = new PhpSupportedVersion();

        $safe_doc_root_requirement = new SafeDocumentRoot();
        $requirements[] = $safe_doc_root_requirement;
        if (!$safe_doc_root_requirement->isValidated()) {
            $requirements[] = new DataDirectoriesProtectedPath(Variables::getDataDirectoriesConstants());
        }
        $requirements[] = new SessionsSecurityConfiguration();
        $requirements[] = new IntegerSize();
        $requirements[] = new Extension(
            'exif',
            true,
            __('Enhance security on images validation.')
        );
        $requirements[] = new Extension(
            'ldap',
            true,
            __('Enable usage of authentication through remote LDAP server.')
        );
        $requirements[] = new Extension(
            'openssl',
            true,
            __('Enable email sending using SSL/TLS.')
        );
        $requirements[] = new ExtensionGroup(
            __('PHP extensions for marketplace'),
            ['bz2', 'Phar', 'zip'],
            true,
            __('Enable support of most common packages formats in marketplace.')
        );
        $requirements[] = new Extension(
            'Zend OPcache',
            true,
            __('Enhance PHP engine performances.')
        );
        $requirements[] = new ExtensionGroup(
            __('PHP emulated extensions'),
            ['ctype', 'iconv', 'mbstring', 'sodium'],
            true,
            __('Slightly enhance performances.')
        );

        $requirements[] = new DirectoryWriteAccess(
            GLPI_MARKETPLACE_DIR,
            true,
            __('Enable installation of plugins from marketplace.')
        );

        if ($db instanceof \DBmysql) {
            $requirements[] = new DbTimezones($db);
        }

        return new RequirementsList($requirements);
    }
}
