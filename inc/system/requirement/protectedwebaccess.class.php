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
 *
 * @TODO Check access to each directory, not only to log file.
 */
class ProtectedWebAccess extends AbstractRequirement {

   /**
    * Paths of directories to check.
    *
    * @var string[]
    */
   private $directories;

   /**
    * @param array $directories  Paths of directories to check.
    */
   public function __construct(array $directories) {
      $this->title = __('Web access to files directory is protected');
      $this->optional = true;

      $this->directories = $directories;
   }

   protected function check() {
      global $CFG_GLPI;

      if (isCommandLine()) {
         $this->out_of_context = true;
         $this->validated = false;
         $this->validation_messages[] = __('Checking that web access to files directory is protected cannot be done on CLI context.');
         return;
      }

      $check_access = false;
      foreach ($this->directories as $dir) {
         if (\Toolbox::startsWith($dir, GLPI_ROOT)) {
            // Only check access if one of the data directories is under GLPI document root.
            $check_access = true;
            break;
         }
      }

      if (isset($_REQUEST['skipCheckWriteAccessToDirs']) || !$check_access) {
         $this->out_of_context = true;
         return;
      }

      $oldhand = set_error_handler(function($errno, $errmsg, $filename, $linenum){return true;});
      $oldlevel = error_reporting(0);

      //create a context to set timeout
      $context = stream_context_create([
         'http' => [
            'timeout' => 2.0
         ]
      ]);

      $protocol = 'http';
      if (isset($_SERVER['HTTPS'])) {
         $protocol = 'https';
      }
      $uri = $protocol . '://' . $_SERVER['SERVER_NAME'] . $CFG_GLPI['root_doc'];

      if ($fic = fopen($uri.'/index.php?skipCheckWriteAccessToDirs=1', 'r', false, $context)) {
         fclose($fic);
         if ($fic = fopen($uri.'/files/_log/php-errors.log', 'r', false, $context)) {
            fclose($fic);

            $this->validated = false;
            $this->validation_messages[] = __('Web access to the files directory should not be allowed');
            $this->validation_messages[] = __('Check the .htaccess file and the web server configuration.');
         } else {
            $this->validated = true;
            $this->validation_messages[] = __('Web access to files directory is protected');
         }
      } else {
         $this->validated = false;
         $this->validation_messages[] = __('Web access to the files directory should not be allowed but this cannot be checked automatically on this instance.');
         $this->validation_messages[] = sprintf(
            __('Make sure access to %s (%s) is forbidden; otherwise review .htaccess file and web server configuration.'),
            __('error log file'),
            $CFG_GLPI['root_doc'] . '/files/_log/php-errors.log'
         );
      }

      error_reporting($oldlevel);
      set_error_handler($oldhand);
   }
}
