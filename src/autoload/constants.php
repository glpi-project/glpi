<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use function Safe\define;
use function Safe\preg_replace;
use function Safe\sha1_file;

define('GLPI_ROOT', dirname(__DIR__, 2));

// Current version of GLPI
define('GLPI_VERSION', '11.0.1');

$schema_file = sprintf('%s/install/mysql/glpi-empty.sql', GLPI_ROOT);
define(
    "GLPI_SCHEMA_VERSION",
    GLPI_VERSION . (is_readable($schema_file) ? '@' . sha1_file($schema_file) : '')
);

$version_file = sprintf('%s/version/%s', GLPI_ROOT, preg_replace('/^(\d+\.\d+\.\d+)(-.*)?$/', '$1', GLPI_VERSION));
define(
    "GLPI_FILES_VERSION",
    GLPI_VERSION . (is_readable($version_file) ? '-' . hash_file('CRC32c', $version_file) : '')
);

define('GLPI_MIN_PHP', '8.2'); // Must also be changed in top of public/index.php
define('GLPI_MAX_PHP', '8.5'); // Must also be changed in top of public/index.php
define('GLPI_YEAR', '2025');

// namespaces
define('NS_GLPI', 'Glpi\\');
define('NS_PLUG', 'GlpiPlugin\\');

// rights
define("READ", 1);
define("UPDATE", 2);
define("CREATE", 4);
define("DELETE", 8);
define("PURGE", 16);
define("ALLSTANDARDRIGHT", 31);
define("READNOTE", 32);
define("UPDATENOTE", 64);
define("UNLOCK", 128);
define("READ_ASSIGNED", 256);
define("UPDATE_ASSIGNED", 512);
define("READ_OWNED", 1024);
define("UPDATE_OWNED", 2048);

define("NOT_AVAILABLE", 'N/A');

// key used to crypt passwords in DB for external access : proxy / smtp / ldap /  mailcollectors
// This key is not used to crypt user's passwords
// If you hav to define passwords again
define("GLPIKEY", "GLPI£i'snarss'ç");

// TIMES
define("MINUTE_TIMESTAMP", 60);
define("HOUR_TIMESTAMP", 3600);
define("DAY_TIMESTAMP", 86400);
define("WEEK_TIMESTAMP", 604800);
define("MONTH_TIMESTAMP", 2592000);

//Management modes
define("MANAGEMENT_UNITARY", 0);
define("MANAGEMENT_GLOBAL", 1);

//Mail send methods
define("MAIL_MAIL", 0);
define("MAIL_SMTP", 1);
define("MAIL_SMTPSSL", 2);
define("MAIL_SMTPTLS", 3);
define("MAIL_SMTPOAUTH", 4);

// MESSAGE TYPE
define("INFO", 0);
define("ERROR", 1);
define("WARNING", 2);

// ACTIONS_ERROR
define("ERROR_NOT_FOUND", 1);
define("ERROR_RIGHT", 2);
define("ERROR_COMPAT", 3);
define("ERROR_ON_ACTION", 4);
define("ERROR_ALREADY_DEFINED", 5);

// Directory constants
define('GLPI_I18N_DIR', GLPI_ROOT . "/locales");
