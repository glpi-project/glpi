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
 *  Timer class for debug and some other cases
 */
class Timer {

   //! Timer value
   public $timer=0;


   /**
    * Start the Timer
    *
    * @return true
    */
   function start () {

      $this->timer = microtime(true);
      return true;
   }


   /**
    * Get the current time of the timer
    *
    * @param integer $decimals Number of decimal of the result (default 3)
    * @param boolean $raw      Get raw time
    *
    * @return time past from start
   **/
   function getTime ($decimals = 3, $raw = false) {
      $elapsed = microtime(true) - $this->timer;
      if ($raw === true) {
         return $elapsed * 1000;
      } else {
         // $decimals will set the number of decimals you want for your milliseconds.
         return number_format($elapsed, $decimals, '.', ' ');
      }
   }
}
