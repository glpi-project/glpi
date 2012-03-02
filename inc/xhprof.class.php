<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * class XHProf
 *
 * Il you need to "profile" some part of code
 *
 * Install the pecl/xhprof extension
 *
 * Before the code
 *    $prof = new XHProf();
 *
 * If the code contains an exit() or a redirect() you must also call (before)
 *    $prof->stop();
 *
 * php-errors.log will give you the URL of the result.
 */
class XHProf {
   const XHPROF_PATH = '/usr/share';
   static private $run = false;

   function __construct($msg='') {
      $this->start($msg);
   }

   function __destruct() {
      $this->stop();
   }

   function start($msg='') {

      if (!self::$run && function_exists('xhprof_enable')) {
         xhprof_enable();
         Toolbox::logDebug("Start profiling with XHProf", $msg);
         self::$run = true;
      }
   }

   function stop() {

      if (self::$run) {
         $data = xhprof_disable();
         include_once self::XHPROF_PATH.'/xhprof_lib/utils/xhprof_lib.php';
         include_once self::XHPROF_PATH.'/xhprof_lib/utils/xhprof_runs.php';

         $runs = new XHProfRuns_Default();
         $id = $runs->save_run($data, 'glpi');

         $link = "http://".$_SERVER['HTTP_HOST']."/xhprof/index.php?run=$id&source=glpi";
         Toolbox::logDebug("Stop profiling with XHProf, result URL", $link);

         self::$run = false;
      }
   }
}
