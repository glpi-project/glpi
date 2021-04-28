<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

namespace Glpi\System;

use Glpi\System\Requirement\DirectoryWriteAccess;
use Glpi\System\Requirement\Extension;
use Glpi\System\Requirement\ExtensionClass;
use Glpi\System\Requirement\ExtensionFunction;
use Glpi\System\Requirement\LogsWriteAccess;
use Glpi\System\Requirement\MemoryLimit;
use Glpi\System\Requirement\MysqliMysqlnd;
use Glpi\System\Requirement\PhpVersion;
use Glpi\System\Requirement\ProtectedWebAccess;
use Glpi\System\Requirement\SeLinux;
use Glpi\System\Requirement\SessionsConfiguration;
use Glpi\System\Requirement\DbEngine;
use Glpi\System\Requirement\DbTimezones;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0
 */
class RequirementsManager {

   /**
    * Returns core requirement list.
    *
    * @param \DBmysql $db  DB instance (if null BD requirements will not be returned).
    *
    * @return RequirementsList
    */
   public function getCoreRequirementList(\DBmysql $db = null): RequirementsList {
      $requirements = [];

      $requirements[] = new PhpVersion(GLPI_MIN_PHP);

      $requirements[] = new SessionsConfiguration();

      $requirements[] = new MemoryLimit(64 * 1024 *1024);

      $requirements[] = new MysqliMysqlnd();
      $requirements[] = new Extension('ctype');
      $requirements[] = new Extension('fileinfo');
      $requirements[] = new Extension('json');
      $requirements[] = new Extension('mbstring');
      $requirements[] = new Extension('iconv');
      $requirements[] = new Extension('zlib');
      $requirements[] = new Extension('curl');
      $requirements[] = new Extension('gd');
      $requirements[] = new Extension('simplexml');
      $requirements[] = new Extension('intl');
      $requirements[] = new Extension('ldap', true); // to sync/connect from LDAP
      $requirements[] = new Extension('apcu', true); // to enhance perfs
      $requirements[] = new Extension('Zend OPcache', true); // to enhance perfs
      $requirements[] = new Extension('xmlrpc', true); // for XMLRPC API
      $requirements[] = new Extension('exif', true); // for security reasons (images checks)
      $requirements[] = new Extension('zip', true); // to handle zip packages on marketplace
      $requirements[] = new Extension('bz2', true); // to handle bz2 packages on marketplace
      $requirements[] = new Extension('sodium', true); // to enhance performances on encrypt/decrypt (fallback to polyfill)

      if ($db instanceof \DBmysql) {
         $requirements[] = new DbEngine($db);
         $requirements[] = new DbTimezones($db);
      }

      global $PHPLOGGER;
      $requirements[] = new LogsWriteAccess($PHPLOGGER);

      foreach (Variables::getDataDirectories() as $directory) {
         if ($directory === GLPI_LOG_DIR) {
            continue; // Specifically checked by LogsWriteAccess requirement
         }
         $requirements[] = new DirectoryWriteAccess($directory);
      }

      $requirements[] = new DirectoryWriteAccess(GLPI_MARKETPLACE_DIR, true);

      $requirements[] = new ProtectedWebAccess(Variables::getDataDirectories());

      $requirements[] = new SeLinux();

      return new RequirementsList($requirements);
   }
}
