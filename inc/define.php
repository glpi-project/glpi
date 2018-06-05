<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
*/

// Current version of GLPI
define('GLPI_VERSION', '9.4-dev');
if (substr(GLPI_VERSION, -4) === '-dev') {
   //for dev version
   define('GLPI_PREVER', str_replace('-dev', '', GLPI_VERSION));
   define(
      'GLPI_SCHEMA_VERSION',
      GLPI_PREVER . '@' . sha1_file(GLPI_ROOT . '/install/mysql/glpi-empty.sql')
   );
} else {
   //for stable version
   define("GLPI_SCHEMA_VERSION", '9.4');
}
define('GLPI_MIN_PHP', '5.6.0'); // Must also be changed in top of index.php
define('GLPI_YEAR', '2018');
if (!defined('GLPI_DEMO_MODE')) {
   define('GLPI_DEMO_MODE', '0');
}
if (!defined('GLPI_USE_CSRF_CHECK')) {
   define('GLPI_USE_CSRF_CHECK', '1');
}
define("GLPI_CSRF_EXPIRES", "7200");
define("GLPI_CSRF_MAX_TOKENS", "100");

//Define a global recipient address for email notifications
//define('GLPI_FORCE_MAIL', 'me@localhost');

// for compatibility with mysql 5.7
// TODO: this var need to be set to 0 after review of all sql queries)
define("GLPI_FORCE_EMPTY_SQL_MODE", "1");

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

$DEFAULT_PLURAL_NUMBER = 2;

define("NOT_AVAILABLE", 'N/A');

// key used to crypt passwords in DB for external access : proxy / smtp / ldap /  mailcollectors
// This key is not used to crypt user's passwords
// If you hav to define passwords again
define("GLPIKEY", "GLPI£i'snarss'ç");

//Telemetry
if (!defined('GLPI_TELEMETRY_URI')) {
   define('GLPI_TELEMETRY_URI', 'http://glpi-project.org/telemetry');
}

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


// For plugins
$PLUGIN_HOOKS     = [];
$CFG_GLPI_PLUGINS = [];
$LANG             = [];

require_once __DIR__ . '/glpi_config.class.php';
$CFG_GLPI = new GlpiConfig();
