<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.4
 */
class Variables {

   /**
    * Returns list of directories that contains custom data.
    *
    * @return string[]
    */
   public static function getDataDirectories() {
      return [
         GLPI_CACHE_DIR,
         GLPI_CONFIG_DIR,
         GLPI_CRON_DIR,
         GLPI_DOC_DIR,
         GLPI_DUMP_DIR,
         GLPI_GRAPH_DIR,
         GLPI_LOCK_DIR,
         GLPI_LOG_DIR,
         GLPI_PICTURE_DIR,
         GLPI_PLUGIN_DOC_DIR,
         GLPI_RSS_DIR,
         GLPI_SESSION_DIR,
         GLPI_TMP_DIR,
         GLPI_UPLOAD_DIR,
      ];
   }
}
