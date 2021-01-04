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

namespace Glpi\System\Requirement;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0
 */
class DirectoryWriteAccess extends AbstractRequirement {

   /**
    * Directory path.
    *
    * @var string
    */
   private $path;

   /**
    * @param string $path    Directory path.
    * @param bool $optional  Indicated if write access is optional.
    */
   public function __construct(string $path, bool $optional = false) {
      $this->path = $path;
      $this->optional = $optional;

      switch (realpath($this->path)) {
         case realpath(GLPI_CACHE_DIR):
            $this->title = __('Checking write permissions for cache files');
            break;
         case realpath(GLPI_CONFIG_DIR):
            $this->title = __('Checking write permissions for setting files');
            break;
         case realpath(GLPI_CRON_DIR):
            $this->title = __('Checking write permissions for automatic actions files');
            break;
         case realpath(GLPI_DOC_DIR):
            $this->title = __('Checking write permissions for document files');
            break;
         case realpath(GLPI_DUMP_DIR):
            $this->title = __('Checking write permissions for dump files');
            break;
         case realpath(GLPI_GRAPH_DIR):
            $this->title = __('Checking write permissions for graphic files');
            break;
         case realpath(GLPI_LOCK_DIR):
            $this->title = __('Checking write permissions for lock files');
            break;
         case realpath(GLPI_MARKETPLACE_DIR):
            $this->title = __('Checking write permissions for marketplace plugins directory');
            break;
         case realpath(GLPI_PLUGIN_DOC_DIR):
            $this->title = __('Checking write permissions for plugins document files');
            break;
         case realpath(GLPI_PICTURE_DIR):
            $this->title = __('Checking write permissions for pictures files');
            break;
         case realpath(GLPI_RSS_DIR):
            $this->title = __('Checking write permissions for rss files');
            break;
         case realpath(GLPI_SESSION_DIR):
            $this->title = __('Checking write permissions for session files');
            break;
         case realpath(GLPI_TMP_DIR):
            $this->title = __('Checking write permissions for temporary files');
            break;
         case realpath(GLPI_UPLOAD_DIR):
            $this->title = __('Checking write permissions for upload files');
            break;
         default:
            $this->title = sprintf(__('Checking write permissions for directory %s'), $this->path);
            break;
      }
   }

   protected function check() {

      $result = \Toolbox::testWriteAccessToDirectory($this->path);

      $this->validated = $result === 0;

      if (0 === $result) {
         $this->validated = true;
         $this->validation_messages[] = sprintf(__('Write access to %s has been validated.'), $this->path);
      } else {
         switch ($result) {
            case 1:
               $this->validation_messages[] = sprintf(__("The file was created in %s but can't be deleted."), $this->path);
               break;
            case 2:
               $this->validation_messages[] = sprintf(__('The file could not be created in %s.'), $this->path);
               break;
            case 3:
               $this->validation_messages[] = sprintf(__('The directory was created in %s but could not be removed.'), $this->path);
               break;
            case 4:
               $this->validation_messages[] = sprintf(__('The directory could not be created in %s.'), $this->path);
               break;
         }
      }
   }

}
