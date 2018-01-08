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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * class XHProf
 *
 * @since 0.84
 *
 * Il you need to "profile" some part of code
 *
 * Install the pecl/xhprof extension
 *
 * Add XHPROF_PATH and XHPROF_URL in config/config_path.php (if needed)
 *
 * Before the code
 *    $prof = new XHProf("something useful");
 *
 * If the code contains an exit() or a redirect() you must also call (before)
 *    unset($prof);
 *
 * php-errors.log will give you the URL of the result.
 */
class XHProf {

   // this can be overloaded in config/config_path.php
   const XHPROF_PATH = '/usr/share/xhprof/xhprof_lib';
   const XHPROF_URL  = '/xhprof';


   static private $run = false;


   /**
    * @param $msg (default '')
   **/
   function __construct($msg = '') {
      $this->start($msg);
   }


   function __destruct() {
      $this->stop();
   }


   /**
    * @param $msg (default '')
   **/
   function start($msg = '') {

      if (!self::$run
          && function_exists('xhprof_enable')) {
         xhprof_enable();
         if (class_exists('Toolbox')) {
            Toolbox::logDebug("Start profiling with XHProf", $msg);
         }
         self::$run = true;
      }
   }


   function stop() {

      if (self::$run) {
         $data = xhprof_disable();

         $incl = (defined('XHPROF_PATH') ? XHPROF_PATH : self::XHPROF_PATH);
         include_once $incl.'/utils/xhprof_lib.php';
         include_once $incl.'/utils/xhprof_runs.php';

         $runs = new XHProfRuns_Default();
         $id   = $runs->save_run($data, 'glpi');

         $url  = (defined('XHPROF_URL') ? XHPROF_URL : self::XHPROF_URL);
         $host = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
         $link = "http://".$host."$url/index.php?run=$id&source=glpi";
         Toolbox::logDebug("Stop profiling with XHProf, result URL", $link);

         self::$run = false;
      }
   }
}
